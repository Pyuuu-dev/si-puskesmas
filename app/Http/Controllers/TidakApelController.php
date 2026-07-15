<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TidakApelController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        $namaBulan = Carbon::createFromDate($tahun, $bulan, 1)
            ->locale('id')
            ->isoFormat('MMMM');

        // Ambil semua record TA bulan/tahun dipilih, dengan detail tanggal
        $taRaw = Absensi::select('user_id', 'slot', 'tanggal')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->where('status', 'hadir')
            ->where('keterangan', 'tidak_apel')
            ->whereHas('user', fn($q) => $q->where('role', '!=', 'super_admin'))
            ->with('user:id,name,jabatan,nip')
            ->orderBy('tanggal')
            ->get();

        // Kelompokkan per user, hitung pagi/siang, kumpulkan daftar tanggal
        $taPerUser = [];
        foreach ($taRaw as $row) {
            $uid = $row->user_id;
            if (!isset($taPerUser[$uid])) {
                $taPerUser[$uid] = [
                    'nama'    => $row->user->name ?? '-',
                    'jabatan' => $row->user->jabatan ?? '-',
                    'nip'     => $row->user->nip ?? '-',
                    'pagi'    => 0,
                    'siang'   => 0,
                    'total'   => 0,
                    'detail'  => [],
                ];
            }

            if ($row->slot === 'pagi') {
                $taPerUser[$uid]['pagi']++;
            } else {
                $taPerUser[$uid]['siang']++;
            }
            $taPerUser[$uid]['total']++;

            $taPerUser[$uid]['detail'][] = [
                'tanggal'      => $row->tanggal,
                'tanggal_fmt'  => Carbon::parse($row->tanggal)->locale('id')->isoFormat('dddd, D MMMM YYYY'),
                'slot'         => $row->slot,
            ];
        }

        // Sort by total desc
        usort($taPerUser, fn($a, $b) => $b['total'] <=> $a['total']);
        $taPerUser = array_values($taPerUser);

        // Ringkasan
        $totalPegawai = count($taPerUser);
        $totalPagi    = array_sum(array_column($taPerUser, 'pagi'));
        $totalSiang   = array_sum(array_column($taPerUser, 'siang'));

        return view('tidak-apel.index', compact(
            'taPerUser',
            'bulan',
            'tahun',
            'namaBulan',
            'totalPegawai',
            'totalPagi',
            'totalSiang'
        ));
    }
}
