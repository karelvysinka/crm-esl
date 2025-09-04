<?php

namespace App\Services\Ops;

class BackupStatusService
{
    public function latestDbDumpStatus(): array
    {
    $base = rtrim(env('OPS_BACKUP_BASE', '/srv/backups/crm'), '/');
    $dir = $base . '/db';
        if (!is_dir($dir)) {
            // Fallback to legacy flat timestamp directories containing mysql.sql
            $legacy = glob($base.'/*/mysql.sql');
            if ($legacy) {
                usort($legacy, fn($a,$b)=> filemtime($b)<=>filemtime($a));
                $file = $legacy[0];
                $ageMinutes = (time()-filemtime($file))/60;
                $cfg = function($k,$d){ return function_exists('config') ? config($k,$d) : $d; };
                $staleThreshold = (int) $cfg('ops.db_dump_stale_minutes', 90);
                $failThreshold = (int) $cfg('ops.db_dump_fail_minutes', 240);
                $status = 'OK';
                if ($ageMinutes >= $failThreshold) {
                    $status = 'FAIL';
                } elseif ($ageMinutes >= $staleThreshold) {
                    $status = 'STALE';
                }
                return [
                    'status'=>$status,
                    'file'=>basename($file),
                    'age_minutes'=>round($ageMinutes,1),
                    'size_mb'=>round((filesize($file)?:0)/1024/1024,2),
                    'legacy'=>true
                ];
            }
            return ['status'=>'MISSING'];
        }
        $files = glob(rtrim($dir,'/').'/full-*.sql.gz');
        if (!$files) {
            // Legacy fallback if new naming not present
            $legacy = glob($base.'/*/mysql.sql');
            if ($legacy) {
                usort($legacy, fn($a,$b)=> filemtime($b)<=>filemtime($a));
                $file = $legacy[0];
                $ageMinutes = (time()-filemtime($file))/60;
                $cfg = function($k,$d){ return function_exists('config') ? config($k,$d) : $d; };
                $staleThreshold = (int) $cfg('ops.db_dump_stale_minutes', 90);
                $failThreshold = (int) $cfg('ops.db_dump_fail_minutes', 240);
                $status = 'OK';
                if ($ageMinutes >= $failThreshold) {
                    $status = 'FAIL';
                } elseif ($ageMinutes >= $staleThreshold) {
                    $status = 'STALE';
                }
                return [
                    'status'=>$status,
                    'file'=>basename($file),
                    'age_minutes'=>round($ageMinutes,1),
                    'size_mb'=>round((filesize($file)?:0)/1024/1024,2),
                    'legacy'=>true
                ];
            }
            return ['status'=>'MISSING'];
        }
        rsort($files);
        $file = $files[0];
        $ageMinutes = (time()-filemtime($file))/60;
    $cfg = function($k,$d){ return function_exists('config') ? config($k,$d) : $d; };
    $staleThreshold = (int) $cfg('ops.db_dump_stale_minutes', 90);
    $failThreshold = (int) $cfg('ops.db_dump_fail_minutes', 240);
        $status = 'OK';
        if ($ageMinutes >= $failThreshold) {
            $status = 'FAIL';
        } elseif ($ageMinutes >= $staleThreshold) {
            $status = 'STALE';
        }
        return [
            'status' => $status,
            'file' => basename($file),
            'age_minutes' => round($ageMinutes,1),
            'size_mb' => round((filesize($file)?:0)/1024/1024,2)
        ];
    }

    public function latestSnapshotStatus(): array
    {
        $base = rtrim(env('OPS_BACKUP_BASE', '/srv/backups/crm'), '/');
        $dir = $base . '/snapshots';
    if (!is_dir($dir)) return ['status'=>'MISSING'];
        $files = glob(rtrim($dir,'/').'/snapshot-*.txt'); // Placeholder marker files
        if (!$files) return ['status'=>'MISSING'];
        rsort($files);
        $file = $files[0];
        $ageMinutes = (time()-filemtime($file))/60;
    $cfg = function($k,$d){ return function_exists('config') ? config($k,$d) : $d; };
    $staleThreshold = (int) $cfg('ops.snapshot_stale_minutes', 180);
        $status = 'OK';
        if ($ageMinutes >= $staleThreshold) {
            $status = 'STALE';
        }
        return [
            'status'=>$status,
            'file'=>basename($file),
            'age_minutes'=>round($ageMinutes,1)
        ];
    }

    public function latestVerifyStatus(): array
    {
        $base = rtrim(env('OPS_BACKUP_BASE', '/srv/backups/crm'), '/');
        $dir = $base . '/verify';
    if (!is_dir($dir)) return ['status'=>'MISSING'];
    $files = glob(rtrim($dir,'/').'/verify-*.log');
    if (!$files) return ['status'=>'MISSING'];
        rsort($files);
        $file = $files[0];
        $ageHours = (time()-filemtime($file))/3600;
    $cfg = function($k,$d){ return function_exists('config') ? config($k,$d) : $d; };
    $overdueHours = (int) $cfg('ops.verify_overdue_hours', 24);
        $status = 'OK';
        if ($ageHours >= $overdueHours) $status='STALE';
        return [
            'status'=>$status,
            'file'=>basename($file),
            'age_hours'=>round($ageHours,1)
        ];
    }

    // Legacy helpers (can be deprecated after dashboard refactor)
    public function dbStatus(): array
    {
        $latest = $this->latestFile('/srv/backups/crm', 'mysql.sql');
        return $this->formatStatus($latest, 'db');
    }

    public function uploadsStatus(): array
    {
        $repo = env('RESTIC_REPO');
        $ok = $repo && is_dir($repo.'/.git') === false && is_dir($repo);
        return [
            'last_snapshot' => null,
            'ok' => $ok,
        ];
    }

    private function latestFile(string $dir, string $filename): ?array
    {
        if (!is_dir($dir)) return null;
        $candidates = glob(rtrim($dir,'/').'/*/'.$filename);
        if (!$candidates) return null;
        usort($candidates, fn($a,$b) => filemtime($b) <=> filemtime($a));
        $file = $candidates[0];
        return [
            'path' => $file,
            'mtime' => filemtime($file),
            'size' => filesize($file),
        ];
    }

    private function formatStatus(?array $file, string $type): array
    {
        if (!$file) return ['status'=>'FAIL','message'=>'Nenalezen žádný backup'];
        $ageMinutes = (time() - $file['mtime'])/60;
        $status = 'OK';
        if ($type==='db') {
            if ($ageMinutes > 26*60) $status='FAIL';
            elseif ($ageMinutes > 24*60) $status='STALE';
        }
        return [
            'status' => $status,
            'age_minutes' => (int)$ageMinutes,
            'size_bytes' => $file['size'] ?? null,
            'file' => $file['path'],
        ];
    }
}
