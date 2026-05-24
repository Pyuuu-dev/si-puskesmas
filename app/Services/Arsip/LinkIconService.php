<?php

namespace App\Services\Arsip;

use Illuminate\Support\Str;

/**
 * Preset icon registry untuk Arsip Link.
 *
 * Tiap preset punya:
 *  - label   : nama tampilan
 *  - bg      : warna background (Tailwind class)
 *  - domains : array domain yang otomatis di-detect ke preset ini
 *  - svg     : inline SVG monokrom putih (mengisi container 100%)
 *  - emoji   : fallback emoji jika SVG tidak dipakai
 */
class LinkIconService
{
    /**
     * Registry preset.
     *
     * @return array<string, array{label:string, bg:string, domains:array<int,string>, svg:string, emoji:string}>
     */
    public static function registry(): array
    {
        return [
            // ── Google Workspace ──────────────────────────────
            'gdrive' => [
                'label'   => 'Google Drive',
                'bg'      => 'bg-yellow-500',
                'domains' => ['drive.google.com'],
                'emoji'   => '📁',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1"><path d="M7.71 3.5L1.15 15l3.42 6 6.56-11.5L7.71 3.5zm8.71 0H8.85l6.56 11.5h7.43L16.42 3.5zM6.62 16l-3.43 6h13.13l3.43-6H6.62z"/></svg>',
            ],
            'gdocs' => [
                'label'   => 'Google Docs',
                'bg'      => 'bg-blue-600',
                'domains' => ['docs.google.com'],
                'emoji'   => '📄',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>',
            ],
            'gsheets' => [
                'label'   => 'Google Sheets',
                'bg'      => 'bg-green-600',
                'domains' => ['sheets.google.com'],
                'emoji'   => '📊',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-3 17H7v-2h4v2zm0-4H7v-2h4v2zm6 4h-4v-2h4v2zm0-4h-4v-2h4v2zm-4-5V3.5L17.5 9H13z"/></svg>',
            ],
            'gslides' => [
                'label'   => 'Google Slides',
                'bg'      => 'bg-amber-500',
                'domains' => ['slides.google.com'],
                'emoji'   => '📽️',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-6h8v6zm-3-9V3.5L18.5 9H13z"/></svg>',
            ],
            'gform' => [
                'label'   => 'Google Form',
                'bg'      => 'bg-violet-600',
                'domains' => ['forms.google.com', 'forms.gle', 'docs.google.com/forms'],
                'emoji'   => '📋',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M19 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm8 8h-6v-2h6v2zm0-4h-6v-2h6v2zm0-4h-6V7h6v2z"/></svg>',
            ],
            'gmail' => [
                'label'   => 'Gmail',
                'bg'      => 'bg-red-500',
                'domains' => ['mail.google.com'],
                'emoji'   => '✉️',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>',
            ],
            'gmeet' => [
                'label'   => 'Google Meet',
                'bg'      => 'bg-emerald-600',
                'domains' => ['meet.google.com'],
                'emoji'   => '🎥',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>',
            ],
            'gcal' => [
                'label'   => 'Google Calendar',
                'bg'      => 'bg-blue-500',
                'domains' => ['calendar.google.com'],
                'emoji'   => '📅',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19a2 2 0 002 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>',
            ],

            // ── Social Media ─────────────────────────────────
            'youtube' => [
                'label'   => 'YouTube',
                'bg'      => 'bg-red-600',
                'domains' => ['youtube.com', 'youtu.be', 'm.youtube.com'],
                'emoji'   => '▶️',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M21.58 7.19c-.23-.86-.91-1.54-1.77-1.77C18.25 5 12 5 12 5s-6.25 0-7.81.42c-.86.23-1.54.91-1.77 1.77C2 8.75 2 12 2 12s0 3.25.42 4.81c.23.86.91 1.54 1.77 1.77C5.75 19 12 19 12 19s6.25 0 7.81-.42c.86-.23 1.54-.91 1.77-1.77C22 15.25 22 12 22 12s0-3.25-.42-4.81zM10 15V9l5.2 3-5.2 3z"/></svg>',
            ],
            'instagram' => [
                'label'   => 'Instagram',
                'bg'      => 'bg-pink-600',
                'domains' => ['instagram.com'],
                'emoji'   => '📷',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 01-5.8 5.8H7.8C4.6 22 2 19.4 2 16.2V7.8A5.8 5.8 0 017.8 2zm-.2 2A3.6 3.6 0 004 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 003.6-3.6V7.6C20 5.61 18.39 4 16.4 4H7.6zm9.65 1.5a1.25 1.25 0 011.25 1.25A1.25 1.25 0 0117.25 8 1.25 1.25 0 0116 6.75a1.25 1.25 0 011.25-1.25zM12 7a5 5 0 015 5 5 5 0 01-5 5 5 5 0 01-5-5 5 5 0 015-5zm0 2a3 3 0 00-3 3 3 3 0 003 3 3 3 0 003-3 3 3 0 00-3-3z"/></svg>',
            ],
            'facebook' => [
                'label'   => 'Facebook',
                'bg'      => 'bg-blue-700',
                'domains' => ['facebook.com', 'fb.com', 'm.facebook.com'],
                'emoji'   => '📘',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M12 2.04c-5.5 0-10 4.49-10 10.02 0 5 3.66 9.15 8.44 9.9v-7H7.9v-2.9h2.54V9.85c0-2.51 1.49-3.89 3.78-3.89 1.09 0 2.23.19 2.23.19v2.47h-1.26c-1.24 0-1.63.77-1.63 1.56v1.88h2.78l-.45 2.9h-2.33v7a10 10 0 008.44-9.9c0-5.53-4.5-10.02-10-10.02z"/></svg>',
            ],
            'twitter' => [
                'label'   => 'X (Twitter)',
                'bg'      => 'bg-gray-900',
                'domains' => ['twitter.com', 'x.com'],
                'emoji'   => '🐦',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
            ],
            'tiktok' => [
                'label'   => 'TikTok',
                'bg'      => 'bg-gray-900',
                'domains' => ['tiktok.com'],
                'emoji'   => '🎵',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005.8 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1.84-.1z"/></svg>',
            ],
            'linkedin' => [
                'label'   => 'LinkedIn',
                'bg'      => 'bg-blue-700',
                'domains' => ['linkedin.com'],
                'emoji'   => '💼',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M19 3a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h14m-.5 15.5v-5.3a3.26 3.26 0 00-3.26-3.26c-.85 0-1.84.52-2.32 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 011.4 1.4v4.93h2.79M6.88 8.56a1.68 1.68 0 001.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 00-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37h2.77z"/></svg>',
            ],

            // ── Komunikasi ────────────────────────────────────
            'whatsapp' => [
                'label'   => 'WhatsApp',
                'bg'      => 'bg-green-500',
                'domains' => ['whatsapp.com', 'wa.me', 'chat.whatsapp.com'],
                'emoji'   => '💬',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M16.75 13.96c.25.13.41.2.46.3.06.11.04.61-.21 1.18-.2.56-1.24 1.1-1.7 1.12-.46.02-.47.36-2.96-.73-2.49-1.09-3.99-3.75-4.11-3.92-.12-.17-.96-1.38-.92-2.61.05-1.22.69-1.8.95-2.04.24-.26.51-.29.68-.31l.47-.01c.15 0 .36-.06.55.45l.69 1.87c.06.13.1.28.01.44l-.27.41-.39.42c-.12.12-.26.25-.12.5.12.26.62 1.09 1.32 1.78.91.88 1.71 1.17 1.95 1.3.24.14.39.12.54-.04l.81-.94c.19-.25.35-.19.58-.11l1.67.88M12 2a10 10 0 0110 10 10 10 0 01-10 10c-1.97 0-3.8-.57-5.35-1.55L2 22l1.55-4.65A9.969 9.969 0 012 12 10 10 0 0112 2m0 2a8 8 0 00-8 8c0 1.72.54 3.31 1.46 4.61L4.5 19.5l2.89-.96A7.95 7.95 0 0012 20a8 8 0 008-8 8 8 0 00-8-8z"/></svg>',
            ],
            'telegram' => [
                'label'   => 'Telegram',
                'bg'      => 'bg-sky-500',
                'domains' => ['t.me', 'telegram.org', 'telegram.me'],
                'emoji'   => '✈️',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/></svg>',
            ],

            // ── Dev / Tools ──────────────────────────────────
            'github' => [
                'label'   => 'GitHub',
                'bg'      => 'bg-gray-900',
                'domains' => ['github.com', 'gist.github.com'],
                'emoji'   => '🐙',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M12 .5C5.37.5 0 5.78 0 12.292c0 5.211 3.438 9.63 8.205 11.188.6.111.82-.254.82-.567 0-.28-.01-1.022-.015-2.005-3.338.711-4.042-1.582-4.042-1.582-.546-1.361-1.335-1.725-1.335-1.725-1.087-.731.084-.716.084-.716 1.205.082 1.838 1.215 1.838 1.215 1.07 1.803 2.809 1.282 3.495.981.108-.763.417-1.282.76-1.577-2.665-.295-5.466-1.309-5.466-5.827 0-1.287.465-2.339 1.235-3.164-.135-.298-.54-1.497.105-3.121 0 0 1.005-.316 3.3 1.209.96-.262 1.98-.392 3-.398 1.02.006 2.04.136 3 .398 2.28-1.525 3.285-1.209 3.285-1.209.645 1.624.24 2.823.12 3.121.765.825 1.23 1.877 1.23 3.164 0 4.53-2.805 5.527-5.475 5.817.42.354.81 1.077.81 2.182 0 1.578-.015 2.846-.015 3.229 0 .315.21.689.825.567C20.565 21.917 24 17.495 24 12.292 24 5.78 18.627.5 12 .5z"/></svg>',
            ],
            'gitlab' => [
                'label'   => 'GitLab',
                'bg'      => 'bg-orange-600',
                'domains' => ['gitlab.com'],
                'emoji'   => '🦊',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M22.65 14.39L12 22.13 1.35 14.39a.84.84 0 01-.3-.94l1.22-3.78 2.44-7.51A.42.42 0 015.5 2a.42.42 0 01.41.3l2.44 7.51h8.27l2.44-7.51A.42.42 0 0119.5 2a.42.42 0 01.41.3l2.44 7.51 1.22 3.78a.84.84 0 01-.3.94"/></svg>',
            ],
            'notion' => [
                'label'   => 'Notion',
                'bg'      => 'bg-gray-800',
                'domains' => ['notion.so', 'notion.site'],
                'emoji'   => '📝',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M4.46 1.41C5.89.31 7.62.5 9.61.69c2.5.24 4.99.49 7.5.7 1.32.13 2.66.16 3.95.46.74.18 1.47.61 1.83 1.31.32.65.27 1.39.26 2.1l-.01 13.84c.01.94-.34 1.95-1.13 2.5-1.18.85-2.66.99-4.07 1.13-3.85.39-7.71.74-11.55 1.07-.85.07-1.78-.01-2.5-.51-.66-.45-1.06-1.18-1.31-1.93C2.13 20.39 2 19.4 2 18.42V5.39c.02-.86.21-1.77.81-2.41.43-.5 1.04-.78 1.65-1.05M9.83 4.43c-.5.07-1 .14-1.5.21-.13.43.16.97.62.99 1.93.16 3.86.32 5.8.46-.06.02-.18.06-.24.08v6.51c0 1.46-.04 2.93-.05 4.4 1.06-.13 2.13-.13 3.18-.32-.01-3.71 0-7.43.01-11.14-.06-.22-.31-.27-.49-.31-2.45-.16-4.89-.49-7.33-.88M5.5 6c-.06 4.06-.02 8.13-.04 12.19.05.43.02.95.32 1.3.39.39.99.31 1.49.31 2.31-.06 4.62-.16 6.93-.31-.01-2.31-.01-4.62.01-6.93-.07-1.83.13-3.69-.18-5.5-.04-.39-.45-.59-.79-.59-2.58-.16-5.16-.27-7.74-.47z"/></svg>',
            ],
            'figma' => [
                'label'   => 'Figma',
                'bg'      => 'bg-purple-600',
                'domains' => ['figma.com'],
                'emoji'   => '🎨',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M15.5 2A3.5 3.5 0 0119 5.5 3.5 3.5 0 0115.5 9h-2v6.5A3.5 3.5 0 0110 19a3.5 3.5 0 01-3.5-3.5c0-1.32.73-2.5 1.83-3.09A3.49 3.49 0 016.5 9.5c0-1.32.73-2.5 1.83-3.09A3.49 3.49 0 016.5 3.5 3.5 3.5 0 0110 0h5.5v2zm-5.5 0v5h-2A2.5 2.5 0 0110 0v2zm0 7H8.5a2.5 2.5 0 100 5H10V9zm5.5-7v5h-1A2.5 2.5 0 0011 4.5v-1A2.5 2.5 0 0114.5 1h1z"/></svg>',
            ],

            // ── Generic ───────────────────────────────────────
            'pdf' => [
                'label'   => 'Dokumen PDF',
                'bg'      => 'bg-red-500',
                'domains' => [],
                'emoji'   => '📕',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M12 10.5h1v3h-1v-3m1.5-1H12v-1h1.5v1m1.5 0H17V11h-2v.5m0 1.5h2V12h-2v1m4.5-3.5H22V8h-2.5V11h.5V9.5M16 4l-4-4H4v24h17v-8h2V4h-7m4 16h-13V2h7v6h6v12z"/></svg>',
            ],
            'document' => [
                'label'   => 'Dokumen',
                'bg'      => 'bg-blue-500',
                'domains' => [],
                'emoji'   => '📄',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/></svg>',
            ],
            'image' => [
                'label'   => 'Gambar',
                'bg'      => 'bg-pink-500',
                'domains' => [],
                'emoji'   => '🖼️',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>',
            ],
            'video' => [
                'label'   => 'Video',
                'bg'      => 'bg-rose-500',
                'domains' => [],
                'emoji'   => '🎬',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M8 5v14l11-7z"/></svg>',
            ],
            'code' => [
                'label'   => 'Code/Repo',
                'bg'      => 'bg-slate-700',
                'domains' => [],
                'emoji'   => '💻',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/></svg>',
            ],
            'web' => [
                'label'   => 'Website',
                'bg'      => 'bg-indigo-500',
                'domains' => [],
                'emoji'   => '🌐',
                'svg'     => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full p-1.5"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm6.93 6h-2.95a15.65 15.65 0 00-1.38-3.56A8.03 8.03 0 0118.92 8zM12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96zM4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2 0 .68.06 1.34.14 2H4.26zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56A7.987 7.987 0 015.08 16zm2.95-8H5.08a7.987 7.987 0 014.33-3.56A15.65 15.65 0 008.03 8zM12 19.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM14.34 14H9.66c-.09-.66-.16-1.32-.16-2 0-.68.07-1.35.16-2h4.68c.09.65.16 1.32.16 2 0 .68-.07 1.34-.16 2zm.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95a8.03 8.03 0 01-4.33 3.56zM16.36 14c.08-.66.14-1.32.14-2 0-.68-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2h-3.38z"/></svg>',
            ],
        ];
    }

    /**
     * Detect preset slug dari URL berdasarkan match domain.
     * Return null kalau tidak ada match.
     */
    public static function detect(?string $url): ?string
    {
        if (!$url) return null;

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (!$host) return null;
        $host = preg_replace('/^www\./', '', $host);

        $path = (string) parse_url($url, PHP_URL_PATH);

        // Kasus khusus: forms.google.com sebenarnya `docs.google.com/forms/...`
        if ($host === 'docs.google.com' && Str::startsWith(ltrim($path, '/'), 'forms/')) {
            return 'gform';
        }

        foreach (self::registry() as $slug => $preset) {
            foreach ($preset['domains'] as $domain) {
                $domain = strtolower($domain);
                if ($host === $domain || Str::endsWith($host, '.' . $domain)) {
                    return $slug;
                }
            }
        }
        return null;
    }

    /**
     * Daftar preset untuk dipass ke frontend (tanpa SVG, supaya payload kecil).
     *
     * @return array<int,array{slug:string, label:string, bg:string, domains:array<int,string>, emoji:string}>
     */
    public static function frontendList(): array
    {
        return collect(self::registry())
            ->map(fn ($p, $slug) => [
                'slug'    => $slug,
                'label'   => $p['label'],
                'bg'      => $p['bg'],
                'domains' => $p['domains'],
                'emoji'   => $p['emoji'],
            ])
            ->values()
            ->all();
    }

    /**
     * Validasi slug — apakah preset terdaftar.
     */
    public static function isValid(?string $slug): bool
    {
        return $slug !== null && array_key_exists($slug, self::registry());
    }

    /**
     * Ambil 1 preset berdasarkan slug.
     *
     * @return array{label:string, bg:string, domains:array<int,string>, svg:string, emoji:string}|null
     */
    public static function get(?string $slug): ?array
    {
        return self::isValid($slug) ? self::registry()[$slug] : null;
    }
}
