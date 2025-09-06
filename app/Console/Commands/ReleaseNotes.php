<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReleaseNotes extends Command
{
    protected $signature = 'release:notes {version? : Konkrétní verze (např. v0.1.1); default = poslední} {--output= : Cesta k souboru pro uložení} {--no-heading : Nezahrnovat první řádek nadpisu}';
    protected $description = 'Vytáhne z changelogu blok release poznámek pro danou nebo poslední verzi a vypíše jej.';

    public function handle(): int
    {
        $path = base_path('docs/01-intro/changelog.md');
        if (!file_exists($path)) {
            $this->error('Changelog nenalezen: ' . $path);
            return self::FAILURE;
        }
        $content = file_get_contents($path);
        $versionArg = $this->argument('version');

        // Najdi všechny entry
        $pattern = '/^### \[(\d{4}-\d{2}-\d{2})\] (v\d+\.\d+\.\d+) \(([^)]+)\)\n(?:(?!^### \[).|\n)*/m';
        preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);
        if (empty($matches[0])) {
            $this->error('Nebyl nalezen žádný release blok.');
            return self::FAILURE;
        }

        $entryBlock = null;
        if ($versionArg) {
            foreach ($matches[0] as $i => $full) {
                $ver = $matches[2][$i][0];
                if ($ver === $versionArg) { $entryBlock = $full[0]; break; }
            }
            if (!$entryBlock) {
                $this->error('Verze ' . $versionArg . ' nenalezena v changelogu.');
                return self::FAILURE;
            }
        } else {
            // První výskyt je nejnovější (dle konvence) – pattern čte sekvenčně; zajistíme že pořadí odpovídá uloženému
            $entryBlock = $matches[0][0][0];
        }

        // Ořízni případné trailing blank lines
        $entryBlock = rtrim($entryBlock) . "\n";

        if ($this->option('no-heading')) {
            $lines = explode("\n", $entryBlock);
            array_shift($lines); // zahodí nadpis ### [...]
            $entryBlock = ltrim(implode("\n", $lines));
        }

        if ($out = $this->option('output')) {
            file_put_contents($out, $entryBlock);
            $this->info('Release notes uloženy do: ' . $out);
        }

        $this->line($entryBlock);
        return self::SUCCESS;
    }
}
