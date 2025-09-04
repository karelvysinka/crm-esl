<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    private function ensureAdmin(Request $request): void
    {
        if (function_exists('auth') && auth()->check() && method_exists(auth()->user(), 'getAttribute') && (bool) auth()->user()->getAttribute('is_admin')) {
            return;
        }
        abort(403, 'Pouze pro administrátory');
    }

    public function index(Request $request)
    {
        $this->ensureAdmin($request);
        // List backup zip files from storage disk
    $disk = Storage::disk(config('backup.backup.destination.disks.0', 'local'));
    // Use recursive listing to find backup ZIPs even in nested folders (e.g., laravel-backup/)
        $files = collect($disk->allFiles())
            ->filter(fn($f) => str_ends_with($f, '.zip'))
            ->map(function($f) use ($disk) {
                return [
                    'path' => $f,
                    'size' => $disk->size($f),
                    'lastModified' => $disk->lastModified($f),
                ];
            })
            ->sortByDesc('lastModified')
            ->values();

        $scheduleEnabled = (bool) config('backup.schedule.enabled', false);
        $scheduleCron = (string) config('backup.schedule.cron', '0 3 * * *');

        // Stats & health
        $lastBackupAt = $files->first()['lastModified'] ?? null;
        $backupCount = $files->count();
        $totalSize = $files->sum('size');
        $maxAgeDays = 0;
        // Take the first monitor config, if present
        $monitor = config('backup.monitor_backups.0');
        if (is_array($monitor) && isset($monitor['health_checks'])) {
            foreach ($monitor['health_checks'] as $class => $value) {
                // The MaximumAgeInDays health check uses integer days
                if (is_int($value) && str_contains($class, 'MaximumAgeInDays')) {
                    $maxAgeDays = $value;
                }
            }
        }
        $healthy = true;
        if ($lastBackupAt) {
            $healthy = (time() - $lastBackupAt) <= max(1, $maxAgeDays) * 86400;
        } else {
            $healthy = false;
        }

        return view('system.backup.index', compact('files', 'scheduleEnabled', 'scheduleCron', 'lastBackupAt', 'backupCount', 'totalSize', 'healthy', 'maxAgeDays'));
    }

    public function run(Request $request)
    {
        $this->ensureAdmin($request);
        Artisan::call('backup:run');
        $output = Artisan::output();
        return back()->with('success', 'Záloha byla spuštěna.')->with('backup_output', $output);
    }

    public function download(Request $request, string $path)
    {
        $this->ensureAdmin($request);
        $diskName = config('backup.backup.destination.disks.0', 'local');
        $disk = Storage::disk($diskName);
        if (! $disk->exists($path)) {
            abort(404);
        }
        $filename = basename($path);
        $stream = $disk->readStream($path);
        return new StreamedResponse(function() use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"'
        ]);
    }

    public function clean(Request $request)
    {
        $this->ensureAdmin($request);
        Artisan::call('backup:clean');
        $output = Artisan::output();
        return back()->with('success', 'Čištění starých záloh bylo spuštěno.')->with('backup_output', $output);
    }
}
