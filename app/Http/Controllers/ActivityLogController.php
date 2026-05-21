<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Halaman daftar log aktivitas dengan filter.
     */
    public function index(Request $request)
    {
        $userId = $request->query('user_id');
        $module = $request->query('module');
        $event  = $request->query('event');
        $from   = $request->query('from');
        $to     = $request->query('to');
        $search = $request->query('q');
        $perPage = (int) $request->query('per_page', 25);
        if (!in_array($perPage, [25, 50, 100], true)) {
            $perPage = 25;
        }

        $logs = ActivityLog::query()
            ->forUser($userId ? (int) $userId : null)
            ->forModule($module)
            ->forEvent($event)
            ->between($from, $to)
            ->search($search)
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        // Pegawai untuk dropdown filter
        $pegawai = User::orderBy('name')->get(['id', 'name', 'role']);

        // Statistik ringkas (tanpa filter, total tabel)
        $totalAll = ActivityLog::count();
        $totalToday = ActivityLog::whereDate('created_at', today())->count();
        $retentionDays = (int) (\App\Models\Setting::get('activity_log_retention_days', 180) ?: 180);

        return view('log-aktivitas.index', [
            'logs'          => $logs,
            'pegawai'       => $pegawai,
            'events'        => ActivityLog::EVENTS,
            'modules'       => ActivityLog::MODULES,
            'userIdFilter'  => $userId ? (int) $userId : null,
            'moduleFilter'  => $module,
            'eventFilter'   => $event,
            'fromFilter'    => $from,
            'toFilter'      => $to,
            'search'        => $search,
            'perPage'       => $perPage,
            'totalAll'      => $totalAll,
            'totalToday'    => $totalToday,
            'retentionDays' => $retentionDays,
        ]);
    }

    /**
     * Detail satu log (untuk modal).
     */
    public function show($id)
    {
        $log = ActivityLog::with('user')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id'           => $log->id,
                'created_at'   => $log->created_at?->format('d/m/Y H:i:s'),
                'user_id'      => $log->user_id,
                'user_name'    => $log->user_name,
                'user_role'    => $log->user_role,
                'event'        => $log->event,
                'event_label'  => $log->event_label,
                'module'       => $log->module,
                'module_label' => $log->module_label,
                'description'  => $log->description,
                'subject_type' => $log->subject_type,
                'subject_id'   => $log->subject_id,
                'properties'   => $log->properties,
                'ip_address'   => $log->ip_address,
                'user_agent'   => $log->user_agent,
                'url'          => $log->url,
                'method'       => $log->method,
            ],
        ]);
    }

    /**
     * Bersihkan log lama secara manual.
     */
    public function prune(Request $request)
    {
        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:3650',
        ]);

        $days = (int) $validated['days'];
        $cutoff = now()->subDays($days);

        $deleted = ActivityLog::where('created_at', '<', $cutoff)->delete();

        ActivityLogger::log(
            event: 'delete',
            module: 'settings',
            description: "Membersihkan log aktivitas lebih dari {$days} hari ({$deleted} record dihapus)",
            properties: [
                'days_threshold' => $days,
                'cutoff'         => $cutoff->format('Y-m-d H:i:s'),
                'deleted'        => $deleted,
            ],
        );

        return redirect()->route('log-aktivitas.index')
            ->with('success', "Berhasil menghapus {$deleted} log lebih lama dari {$days} hari.");
    }
}
