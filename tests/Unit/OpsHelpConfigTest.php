<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class OpsHelpConfigTest extends TestCase
{
    public function test_help_keys_present(): void
    {
        $cfg = require __DIR__ . '/../../config/ops_help.php';
        $expected = ['git.strategy','backup.rpo','backup.verify','release.process','ops.limits'];
        foreach ($expected as $k) {
            $this->assertArrayHasKey($k, $cfg);
            $this->assertNotEmpty($cfg[$k]);
        }
    }
}