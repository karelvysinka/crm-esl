<?php

namespace Tests\Unit;

use App\Services\Ops\BackupStatusService;
use Tests\TestCase;

class BackupStatusServiceTest extends TestCase
{
    public function test_latest_db_dump_status_handles_missing_dir(): void
    {
    // Use a non-existent temporary base to avoid host legacy backups influencing result
    putenv('OPS_BACKUP_BASE='.sys_get_temp_dir().'/ops-test-'.uniqid());
    $svc = new BackupStatusService();
    $res = $svc->latestDbDumpStatus();
    $this->assertEquals('MISSING', $res['status']);
    }
}
