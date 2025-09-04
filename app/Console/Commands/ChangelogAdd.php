<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ChangelogAdd extends Command
{
    protected $signature = 'changelog:add {type : ADDED|CHANGED|FIXED|REMOVED|SECURITY|PERF|DOCS} {summary : Krátké shrnutí} {--version=} {--details=} {--impact=} {--no-edit : Nepokoušet se o otevření editoru}';
    protected $description = 'Přidá položku do docs/01-intro/changelog.md (na začátek sekce Poslední změny)';

    public function handle(): int
    {
        $type = strtoupper($this->argument('type'));
        if (!in_array($type, ['ADDED','CHANGED','FIXED','REMOVED','SECURITY','PERF','DOCS'])) {
            $this->error('Neplatný type');
            return self::INVALID;
        }
        $summary = trim($this->argument('summary'));
        $version = $this->option('version') ?: $this->autoVersion();
        $details = $this->option('details');
        $impact = $this->option('impact');
        $date = now()->format('Y-m-d');

        $entry = [];
        $entry[] = "### [$date] v$version ($type)";
        $entry[] = '#### Shrnutí';
        $entry[] = $summary; if ($details) { $entry[] = ''; $entry[] = '#### Detaily'; $entry[] = $details; }
        if ($impact) { $entry[] = ''; $entry[] = '#### Dopady'; $entry[] = $impact; }
        $entry[] = ""; // blank end
        $entryText = implode("\n", $entry) . "\n";

        $path = base_path('docs/01-intro/changelog.md');
        if (!file_exists($path)) { $this->error('Changelog neexistuje: ' . $path); return self::FAILURE; }
        $content = file_get_contents($path);

        // Najít kotvu '## Poslední změny' a vložit hned za ni (po prvním newline)
        $needle = '## Poslední změny';
        $pos = strpos($content, $needle);
        if ($pos === false) { $this->error('Nenalezena sekce "Poslední změny"'); return self::FAILURE; }

        // Najít konec řádku s jehlou
        $afterHeading = strpos($content, "\n", $pos + strlen($needle));
        if ($afterHeading === false) { $afterHeading = strlen($content); }
        $insertionPoint = $afterHeading + 1; // Za koncem řádku nadpisu

        $newContent = substr($content, 0, $insertionPoint) . $entryText . substr($content, $insertionPoint);
        file_put_contents($path, $newContent);

        $this->info('Položka přidána do changelogu: ' . $version);
        return self::SUCCESS;
    }

    private function autoVersion(): string
    {
        // Heuristika: vezme poslední verzi v souboru a bumpne patch
        $path = base_path('docs/01-intro/changelog.md');
        $content = @file_get_contents($path) ?: '';
        if (preg_match('/v(\d+)\.(\d+)\.(\d+)/', $content, $m)) {
            return $m[1] . '.' . $m[2] . '.' . ((int)$m[3] + 1);
        }
        return '0.1.0';
    }
}
