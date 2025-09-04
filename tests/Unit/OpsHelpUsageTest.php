<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifies that every @help('key') used in Blade views references an existing key
 * in config/ops_help.php AND that every defined help key is used at least once
 * (prevents dead or missing help entries). Simplistic regex scan.
 */
class OpsHelpUsageTest extends TestCase
{
    public function test_help_directives_match_registry(): void
    {
        $config = require __DIR__ . '/../../config/ops_help.php';
        $defined = array_keys($config);

        // Scan resources/views/ops for @help('...') usage
        $base = realpath(__DIR__ . '/../../resources/views/ops');
        $this->assertNotFalse($base, 'Ops views directory missing');
        $used = [];
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));
        foreach ($rii as $file) {
            if ($file->isDir()) continue;
            $content = file_get_contents($file->getPathname());
            if ($content === false) continue;
            if (preg_match_all("/@help\((['\"])(.+?)\\1\)/", $content, $m)) {
                foreach ($m[2] as $k) {
                    $used[] = $k;
                    $this->assertArrayHasKey($k, $config, "Help key '{$k}' used in view but not defined in config/ops_help.php");
                }
            }
        }
        $used = array_unique($used);
        // Every defined key should be used at least once (avoid dead entries)
        $unused = array_diff($defined, $used);
        $this->assertEmpty($unused, 'Unused help keys in config: '.implode(', ', $unused));
    }
}
