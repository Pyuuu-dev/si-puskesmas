<?php

namespace App\Services\Arsip;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LinkMetadataService
{
    private const HTTP_TIMEOUT      = 5;     // detik
    private const MAX_HTML_BYTES    = 512000; // 500KB cukup untuk parse meta
    private const MAX_IMAGE_BYTES   = 2097152; // 2MB cap untuk cache lokal

    /**
     * Fetch metadata sinkron dari URL.
     *
     * @return array{title:?string, favicon:?string, thumbnail:?string, description:?string, status:string}
     */
    public function fetch(string $url): array
    {
        $result = [
            'title'       => null,
            'favicon'     => null,
            'thumbnail'   => null,
            'description' => null,
            'status'      => 'failed',
        ];

        try {
            $response = Http::withHeaders([
                    'User-Agent'      => 'Mozilla/5.0 (compatible; ArsipBot/1.0)',
                    'Accept'          => 'text/html,application/xhtml+xml,*/*;q=0.8',
                    'Accept-Language' => 'id,en;q=0.8',
                ])
                ->timeout(self::HTTP_TIMEOUT)
                ->withOptions([
                    'allow_redirects' => true,
                    'verify'          => true,
                ])
                ->get($url);

            if (!$response->ok()) {
                return $result;
            }

            $html = substr($response->body(), 0, self::MAX_HTML_BYTES);
            $host = parse_url($url, PHP_URL_HOST) ?: '';

            $result['title']       = $this->extractTitle($html);
            $result['description'] = $this->extractMeta($html, [
                'og:description', 'twitter:description', 'description',
            ]);

            $ogImage = $this->extractMeta($html, [
                'og:image', 'og:image:url', 'twitter:image', 'twitter:image:src',
            ]);
            if ($ogImage) {
                $cached = $this->cacheImage($this->absolutize($ogImage, $url), 'thumbnails');
                $result['thumbnail'] = $cached ?: $this->absolutize($ogImage, $url);
            }

            $faviconHref = $this->extractFavicon($html);
            $faviconAbs  = $faviconHref
                ? $this->absolutize($faviconHref, $url)
                : ($host ? "https://www.google.com/s2/favicons?domain={$host}&sz=64" : null);

            if ($faviconAbs) {
                $cached = $this->cacheImage($faviconAbs, 'favicons');
                $result['favicon'] = $cached ?: $faviconAbs;
            }

            if ($result['title'] || $result['favicon'] || $result['thumbnail']) {
                $result['status'] = ($result['title'] && $result['favicon']) ? 'ok' : 'partial';
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $result;
    }

    private function extractTitle(string $html): ?string
    {
        $og = $this->extractMeta($html, ['og:title', 'twitter:title']);
        if ($og) {
            return Str::limit(trim($og), 250, '');
        }
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
            $clean = html_entity_decode(trim(strip_tags($m[1])), ENT_QUOTES | ENT_HTML5);
            return Str::limit($clean, 250, '');
        }
        return null;
    }

    /**
     * Cari <meta name|property=$name content=...>; juga handle urutan terbalik.
     *
     * @param  array<int,string>  $names
     */
    private function extractMeta(string $html, array $names): ?string
    {
        foreach ($names as $n) {
            $q = preg_quote($n, '/');

            $p1 = '/<meta[^>]+(?:name|property|itemprop)\s*=\s*["\']' . $q
                . '["\'][^>]*content\s*=\s*["\']([^"\']*)["\'][^>]*>/i';
            if (preg_match($p1, $html, $m) && $m[1] !== '') {
                return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
            }

            $p2 = '/<meta[^>]+content\s*=\s*["\']([^"\']*)["\'][^>]*(?:name|property|itemprop)\s*=\s*["\']'
                . $q . '["\'][^>]*>/i';
            if (preg_match($p2, $html, $m) && $m[1] !== '') {
                return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
            }
        }
        return null;
    }

    private function extractFavicon(string $html): ?string
    {
        if (preg_match(
            '/<link[^>]+rel\s*=\s*["\'](?:shortcut\s+)?icon["\'][^>]*href\s*=\s*["\']([^"\']+)["\']/i',
            $html, $m
        )) {
            return $m[1];
        }
        if (preg_match(
            '/<link[^>]+href\s*=\s*["\']([^"\']+)["\'][^>]*rel\s*=\s*["\'](?:shortcut\s+)?icon["\']/i',
            $html, $m
        )) {
            return $m[1];
        }
        if (preg_match(
            '/<link[^>]+rel\s*=\s*["\']apple-touch-icon[^"\']*["\'][^>]*href\s*=\s*["\']([^"\']+)["\']/i',
            $html, $m
        )) {
            return $m[1];
        }
        return null;
    }

    private function absolutize(string $maybeRelative, string $base): string
    {
        if (Str::startsWith($maybeRelative, ['http://', 'https://', 'data:'])) {
            return $maybeRelative;
        }
        $b = parse_url($base);
        $scheme = $b['scheme'] ?? 'https';
        $host   = $b['host']   ?? '';

        if (Str::startsWith($maybeRelative, '//')) {
            return $scheme . ':' . $maybeRelative;
        }
        if (Str::startsWith($maybeRelative, '/')) {
            return "{$scheme}://{$host}" . $maybeRelative;
        }
        $path = $b['path'] ?? '/';
        $dir = rtrim(dirname($path), '/');
        return "{$scheme}://{$host}{$dir}/" . $maybeRelative;
    }

    /**
     * Download remote image dan simpan ke storage/public/arsip/{type}/.
     * Return path relatif (untuk Storage::url()) atau null kalau gagal.
     */
    private function cacheImage(string $url, string $type): ?string
    {
        if (Str::startsWith($url, 'data:')) return null;

        try {
            $resp = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; ArsipBot/1.0)',
                ])
                ->timeout(self::HTTP_TIMEOUT)
                ->get($url);

            if (!$resp->ok()) return null;

            $body = $resp->body();
            if (strlen($body) === 0 || strlen($body) > self::MAX_IMAGE_BYTES) {
                return null;
            }

            $mime = strtolower((string) $resp->header('Content-Type'));
            $ext  = match (true) {
                str_contains($mime, 'jpeg')   => 'jpg',
                str_contains($mime, 'jpg')    => 'jpg',
                str_contains($mime, 'png')    => 'png',
                str_contains($mime, 'webp')   => 'webp',
                str_contains($mime, 'svg')    => 'svg',
                str_contains($mime, 'gif')    => 'gif',
                str_contains($mime, 'icon'),
                str_contains($mime, 'x-icon') => 'ico',
                default                       => null,
            };
            if (!$ext) {
                // Fallback: coba detect via path URL
                $pathExt = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                $ext = in_array($pathExt, ['jpg','jpeg','png','webp','svg','gif','ico'], true)
                    ? ($pathExt === 'jpeg' ? 'jpg' : $pathExt)
                    : 'img';
            }

            $name = sprintf('arsip/%s/%s.%s', $type, sha1($url), $ext);
            Storage::disk('public')->put($name, $body);
            return $name;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
