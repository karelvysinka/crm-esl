<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OpsMigrateLegacyBackups extends Command
{
    protected $signature = 'ops:backups-migrate {--dry-run : Only print actions}';
    protected $description = 'Migrate legacy /srv/backups/crm/<timestamp>/mysql.sql structure into OPS_BACKUP_BASE db/ + sha256 sums';

    public function handle(): int
    {
        $base = rtrim(env('OPS_BACKUP_BASE','/srv/backups/crm'),'/');
        $legacyDirs = glob($base.'/*', GLOB_ONLYDIR);
        if (!$legacyDirs) {
            $this->info('No legacy directories found.');
            return self::SUCCESS;
        }
        $dbTarget = $base.'/db';
        if (!is_dir($dbTarget)) @mkdir($dbTarget,0775,true);
        $migrated = 0; $skipped=0;
        foreach ($legacyDirs as $dir) {
            $sql = $dir.'/mysql.sql';
            if (!is_file($sql)) { $skipped++; continue; }
            // Derive timestamp from folder name if matches pattern YYYYmmdd-HHMMSS else use filemtime
            $folder = basename($dir);
            $ts = $folder;
            if (!preg_match('/^\d{8}-\d{6}$/',$folder)) {
                $ts = date('Ymd-His', filemtime($sql));
            }
            $target = $dbTarget.'/full-'.$ts.'.sql.gz';
            if (is_file($target)) { $this->line("Exists, skipping $target"); continue; }
            if ($this->option('dry-run')) {
                $this->line("Would compress & move $sql -> $target");
                continue;
            }
            // Compress source (it may be plain sql)
            $compressor = trim(shell_exec('command -v pigz')) ? 'pigz -9' : 'gzip -9';
            $cmd = $compressor.' < '.escapeshellarg($sql).' > '.escapeshellarg($target).' 2>&1';
            $ret = 0; exec($cmd, $out, $ret);
            if ($ret!==0 || !is_file($target)) { $this->error("Compression failed for $sql"); continue; }
            // Hash
            $hashOut=[]; $hashRet=0; exec('sha256sum '.escapeshellarg($target), $hashOut, $hashRet);
            if ($hashRet===0 && isset($hashOut[0])) file_put_contents($target.'.sha256', $hashOut[0]."\n");
            $migrated++;
        }
        $this->info("Migrated: $migrated, skipped(no sql): $skipped");
        return self::SUCCESS;
    }
}
