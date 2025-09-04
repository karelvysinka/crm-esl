<?php
return [
    'db_dump_stale_minutes' => (int) env('OPS_DB_DUMP_STALE_MIN', 24 * 60),
    'db_dump_fail_minutes' => (int) env('OPS_DB_DUMP_FAIL_MIN', 26 * 60),
    'snapshot_stale_minutes' => (int) env('OPS_SNAPSHOT_STALE_MIN', 25 * 60),
    'verify_overdue_hours' => (int) env('OPS_VERIFY_OVERDUE_HOURS', 7 * 24 + 12),
    'alerts_enabled' => (bool) env('OPS_ALERTS_ENABLED', true),
];
