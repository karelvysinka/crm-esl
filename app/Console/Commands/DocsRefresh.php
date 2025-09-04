<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use ReflectionClass;

class DocsRefresh extends Command
{
    protected $signature = 'docs:refresh {--no-routes} {--no-jobs} {--no-env} {--no-schedule} {--no-permissions} {--no-erd} {--no-model-fields} {--no-db-schema} {--no-menu}';
    protected $description = 'Generuje / aktualizuje referenční dokumenty v docs/16-generated';

    public function handle(): int
    {
        $base = base_path('docs/16-generated');
        if (!is_dir($base)) {
            mkdir($base, 0775, true);
        }

    if (!$this->option('no-routes')) { $this->generateRoutes($base); }
    if (!$this->option('no-jobs')) { $this->generateJobsCatalog($base); }
    if (!$this->option('no-env')) { $this->generateEnvMatrix($base); }
    if (!$this->option('no-schedule')) { $this->generateSchedule($base); }
    if (!$this->option('no-permissions')) { $this->generatePermissions($base); }
    $this->generateBackupReportSummary($base); // always attempt (non-fatal)
    if (!$this->option('no-erd')) { $this->generateErd($base); }
    if (!$this->option('no-model-fields')) { $this->generateModelFields($base); }
    if (!$this->option('no-db-schema')) { $this->generateDbSchemaDetails($base); }
    if (!$this->option('no-menu')) { $this->generateNavigationDoc($base); }

        $this->info('Dokumentace (část) regenerována.');
        return self::SUCCESS;
    }

    protected function generateRoutes(string $base): void
    {
        $this->info('> Routy');
        try {
            $routes = \Route::getRoutes();
        } catch (\Throwable $e) {
            $this->warn('Nelze načíst router: '.$e->getMessage());
            return;
        }
        $rows = [];
        foreach ($routes as $route) {
            /** @var \Illuminate\Routing\Route $route */
            $methods = array_diff($route->methods(), ['HEAD']);
            $uri = $route->uri();
            $name = $route->getName();
            $action = $route->getActionName();
            $middleware = method_exists($route, 'gatherMiddleware') ? $route->gatherMiddleware() : ($route->middleware() ?? []);
            $rows[] = [implode(',', $methods), $uri, $name, implode(',', $middleware), Str::afterLast($action, '\\')];
        }
        // Sort by URI then method
        usort($rows, fn($a,$b) => [$a[1], $a[0]] <=> [$b[1], $b[0]]);
        $lines = ["# Routy (generováno)", '', '| Metoda | URI | Název | Middleware | Akce |', '|--------|-----|-------|------------|-------|'];
        foreach ($rows as $r) {
            $lines[] = sprintf('| %s | %s | %s | %s | %s |', $r[0], $r[1], $r[2] ?? '', Str::limit($r[3], 80), $r[4]);
        }
        file_put_contents($base.'/routes.md', implode("\n", $lines)."\n");
    }

    protected function generateJobsCatalog(string $base): void
    {
        $this->info('> Jobs katalog');
        $jobsDir = app_path('Jobs');
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($jobsDir));
        $rows = [];
        foreach ($rii as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') continue;
            $rel = Str::after($file->getPathname(), app_path().DIRECTORY_SEPARATOR);
            $class = 'App\\'.str_replace(['/', '.php'], ['\\', ''], Str::after($rel, ''));
            try {
                if (!class_exists($class)) continue;
                $ref = new ReflectionClass($class);
                if (!$ref->isInstantiable()) continue;
                $interfaces = collect($ref->getInterfaceNames());
                $isQueue = $interfaces->contains('Illuminate\\Contracts\\Queue\\ShouldQueue');
                if (!$isQueue) continue;
                $queue = 'default';
                // Attempt to detect queue via onQueue call pattern in constructor (best-effort)
                $source = file_get_contents($file->getPathname());
                if (preg_match('/->onQueue\(["\']([^"\']+)["\']\)/', $source, $m)) {
                    $queue = $m[1];
                }
                $rows[] = [$class, $queue];
            } catch (\Throwable $e) {
                $this->warn('Skip '.$class.': '.$e->getMessage());
            }
        }
        sort($rows);
        $lines = ["# Queue Job Katalog (generováno)", '', '| Třída | Queue | Popis |', '|-------|-------|-------|'];
        foreach ($rows as [$c, $q]) {
            // Attempt to extract first docblock line
            $desc = '';
            try {
                $ref = new ReflectionClass($c);
                $doc = $ref->getDocComment();
                if ($doc && preg_match('/\*\s([^@\n][^\n]+)/', $doc, $m)) { $desc = trim($m[1]); }
            } catch (\Throwable $e) {}
            $lines[] = sprintf('| %s | %s | %s |', Str::after($c, 'App\\'), $q, Str::limit(str_replace('|','/', $desc), 80));
        }
        file_put_contents($base.'/jobs.md', implode("\n", $lines)."\n");
    }

    protected function generateEnvMatrix(string $base): void
    {
        $this->info('> ENV matrix');
        $example = @file_get_contents(base_path('.env.example')) ?: '';
        $exampleVars = [];
        foreach (preg_split('/\r?\n/', $example) as $l) {
            if (preg_match('/^([A-Z0-9_]+)=/', $l, $m)) { $exampleVars[$m[1]] = true; }
        }
        // Scan config/ for env(
        $configVars = [];
        foreach (glob(config_path('*.php')) as $cfg) {
            $src = file_get_contents($cfg);
            if (preg_match_all('/env\(["\']([A-Z0-9_]+)["\']/', $src, $mm)) {
                foreach ($mm[1] as $v) { $configVars[$v] = true; }
            }
        }
        ksort($exampleVars); ksort($configVars);
        $lines = ["# ENV Matrix (generováno)", '', '| Var | V .env.example | Použito v config |', '|-----|----------------|-----------------|'];
        $all = array_unique(array_merge(array_keys($exampleVars), array_keys($configVars)));
        sort($all);
        foreach ($all as $v) {
            $lines[] = sprintf('| %s | %s | %s |', $v, isset($exampleVars[$v]) ? 'Ano' : '', isset($configVars[$v]) ? 'Ano' : '');
        }
        file_put_contents($base.'/env-matrix.md', implode("\n", $lines)."\n");
    }

    protected function generateSchedule(string $base): void
    {
        $this->info('> Schedule');
        $file = app_path('Console/Kernel.php');
        if (!file_exists($file)) { $this->warn('Kernel.php nenalezen'); return; }
        $content = file_get_contents($file);
        $linesRaw = preg_split('/\r?\n/', $content);
        $entries = [];
        $buffer = '';
        foreach ($linesRaw as $line) {
            $trim = trim($line);
            if (str_contains($trim, '$schedule->')) {
                $buffer = $trim;
                if (str_contains($trim, ';')) { $entries[] = $buffer; $buffer=''; }
                continue;
            }
            if ($buffer !== '') {
                $buffer .= ' '. $trim;
                if (str_contains($trim, ';')) { $entries[] = $buffer; $buffer=''; }
            }
        }
        $rows = [];
        foreach ($entries as $e) {
            $target = '';$type='';$freq='';$queue='';$notes='';
            if (preg_match('/\$schedule->command\(["\']([^"\']+)["\']/', $e, $m)) { $type='command'; $target=$m[1]; }
            elseif (preg_match('/\$schedule->job\(new\\s+([^\(]+)\(/', $e, $m)) { $type='job'; $target=$m[1]; }
            elseif (preg_match('/\$schedule->call\(/', $e)) { $type='call'; $target='closure'; }
            // Frequency heuristics
            if (preg_match('/->cron\(["\']([^"\']+)["\']\)/', $e, $m)) { $freq='cron: '.$m[1]; }
            elseif (preg_match('/->everyTenMinutes\(/', $e)) { $freq='everyTenMinutes'; }
            elseif (preg_match('/->everyMinute\(/', $e)) { $freq='everyMinute'; }
            elseif (preg_match('/->weeklyOn\(([^\)]+)\)/', $e, $m)) { $freq='weeklyOn('.$m[1].')'; }
            elseif (preg_match('/->weekly\(/', $e)) { $freq='weekly'; }
            elseif (preg_match('/->dailyOn\(([^\)]+)\)/', $e,$m)) { $freq='dailyOn('.$m[1].')'; }
            if (preg_match('/->onQueue\(["\']([^"\']+)["\']\)/', $e, $m)) { $queue=$m[1]; }
            $rows[] = [$type,$target,$freq,$queue,$notes];
        }
        $out = ["# Schedule (generováno)", '', '| Typ | Cíl | Frekvence | Queue | Poznámka |', '|-----|-----|-----------|-------|----------|'];
        foreach ($rows as $r) {
            $out[] = sprintf('| %s | %s | %s | %s | %s |', $r[0], $r[1], $r[2], $r[3], $r[4]);
        }
        file_put_contents($base.'/schedule.md', implode("\n", $out)."\n");
    }

    protected function generatePermissions(string $base): void
    {
        $this->info('> Permissions');
        $perms = collect(); $roles = collect(); $error = null;
        if (!class_exists(\Spatie\Permission\Models\Permission::class)) {
            $error = 'Balík nenainstalován';
        } else {
            try {
                $perms = \Spatie\Permission\Models\Permission::query()->orderBy('name')->get();
                $roles = class_exists(\Spatie\Permission\Models\Role::class) ? \Spatie\Permission\Models\Role::with('permissions')->get() : collect();
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }
        $lines = ["# Permissions (generováno)", '', '## Seznam oprávnění', '', '| Permission | Guard |', '|------------|-------|'];
        if ($perms->count()) {
            foreach ($perms as $p) { $lines[] = '| '.$p->name.' | '.$p->guard_name.' |'; }
        } else {
            $lines[] = '| _žádná data_ | |';
        }
        if ($roles->count()) {
            $lines[]='';
            $lines[]='## Role a jejich oprávnění';
            $lines[]='';
            foreach ($roles as $r) {
                $lines[]='### '.$r->name;
                $names = $r->permissions->pluck('name')->sort()->values()->all();
                $lines[]='';
                $lines[]= $names ? implode(', ', $names) : '_bez oprávnění_';
                $lines[]='';
            }
        }
        if ($error) {
            $lines[]='';
            $lines[]='> Poznámka: Nepodařilo se získat runtime data (`'.$error.'`). Spusťte v běžícím aplikačním kontejneru.';
        }
        file_put_contents($base.'/permissions.md', implode("\n", $lines)."\n");
    }

    protected function generateErd(string $base): void
    {
        $this->info('> ERD (mermaid)');
        $modelsDir = app_path('Models');
        if (!is_dir($modelsDir)) { return; }
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($modelsDir));
        $relations = [];// [A][B] = ['hasMany'|'belongsTo']
        $entities = [];// name => ['fields'=>[], 'casts'=>[]]
        foreach ($rii as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') continue;
            $src = file_get_contents($file->getPathname());
            if (!preg_match('/class\s+(\w+)/', $src, $cm)) continue;
            $class = $cm[1];
            $entities[$class] = ['fields'=>[], 'casts'=>[]];
            // fillable
            if (preg_match('/protected\s+\$fillable\s*=\s*\[(.*?)\];/s', $src, $fm)) {
                if (preg_match_all('/["\']([a-zA-Z0-9_]+)["\']/', $fm[1], $fld)) {
                    $entities[$class]['fields'] = $fld[1];
                }
            }
            // casts
            if (preg_match('/protected\s+\$casts\s*=\s*\[(.*?)\];/s', $src, $castm)) {
                if (preg_match_all('/["\']([a-zA-Z0-9_]+)["\']\s*=>\s*["\']([^"\']+)["\']/', $castm[1], $cst)) {
                    foreach ($cst[1] as $i=>$field) { $entities[$class]['casts'][$field] = $cst[2][$i]; }
                }
            }
            // belongsTo
            if (preg_match_all('/belongsTo\(([^\)]+)\)/', $src, $bm)) {
                foreach ($bm[1] as $raw) {
                    if (preg_match('/([A-Z][A-Za-z0-9_]+)::class/', $raw, $rm)) {
                        $target = $rm[1];
                        $relations[$class][$target]['belongsTo'] = true;
                    }
                }
            }
            // hasMany
            if (preg_match_all('/hasMany\(([^\)]+)\)/', $src, $hm)) {
                foreach ($hm[1] as $raw) {
                    if (preg_match('/([A-Z][A-Za-z0-9_]+)::class/', $raw, $rm)) {
                        $target = $rm[1];
                        $relations[$class][$target]['hasMany'] = true;
                    }
                }
            }
            // morphMany
            if (preg_match_all('/morphMany\(([^\)]+)\)/', $src, $mm)) {
                foreach ($mm[1] as $raw) {
                    if (preg_match('/([A-Z][A-Za-z0-9_]+)::class/', $raw, $rm)) {
                        $target = $rm[1];
                        $relations[$class][$target]['morphMany'] = true;
                    }
                }
            }
            // morphTo (cannot know targets statically, annotate)
            if (preg_match_all('/morphTo\(/', $src, $mt)) {
                $relations[$class]['_POLYMORPHIC_']['morphTo'] = true;
            }
        }
        // Build mermaid ER diagram
        $lines = ['# ERD (generováno, heuristika ze source kódu)', '', '```mermaid', 'erDiagram'];
        foreach ($relations as $a=>$targets) {
            foreach ($targets as $b=>$kinds) {
                if ($b === '_POLYMORPHIC_' && isset($kinds['morphTo'])) {
                    $lines[] = sprintf('  %s }o..o{ POLYMORPHIC : morph_to', strtoupper($a));
                    continue;
                }
                $card = '||--o{'; // default hasMany direction A -> B
                if (isset($kinds['belongsTo']) && !isset($kinds['hasMany'])) {
                    // A belongsTo B means A }o--|| B, invert arrow printing from A to B but cardinalities flipped
                    $lines[] = sprintf('  %s }o--|| %s : belongs_to', strtoupper($a), strtoupper($b));
                    continue;
                }
                if (isset($kinds['hasMany'])) {
                    $lines[] = sprintf('  %s ||--o{ %s : has_many', strtoupper($a), strtoupper($b));
                } elseif (isset($kinds['morphMany'])) {
                    $lines[] = sprintf('  %s ||--o{ %s : morph_many', strtoupper($a), strtoupper($b));
                }
            }
        }
        // Entities block with fields
        foreach ($entities as $name=>$data) {
            $lines[] = sprintf('  %s {', strtoupper($name));
            $fields = array_slice($data['fields'], 0, 25); // limit to avoid huge block
            foreach ($fields as $f) {
                $type = $data['casts'][$f] ?? 'string';
                $lines[] = sprintf('    %s %s', $type, $f);
            }
            $lines[] = '  }';
        }
        $lines[] = '```';
        file_put_contents($base.'/erd.md', implode("\n", $lines)."\n");
    }

    protected function generateModelFields(string $base): void
    {
        $this->info('> Model Fields');
        $modelsDir = app_path('Models');
        if (!is_dir($modelsDir)) { return; }
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($modelsDir));
        $out = ['# Model Fields (generováno)', '', '_Heuristický výpis na základě $fillable a $casts. Skutečné DB typy ověřte migracemi._', ''];
        foreach ($rii as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') continue;
            $src = file_get_contents($file->getPathname());
            if (!preg_match('/class\s+(\w+)/', $src, $cm)) continue; $class=$cm[1];
            $fillable=[]; $casts=[];
            if (preg_match('/protected\s+\$fillable\s*=\s*\[(.*?)\];/s', $src, $fm)) {
                if (preg_match_all('/["\']([a-zA-Z0-9_]+)["\']/', $fm[1], $fld)) { $fillable=$fld[1]; }
            }
            if (preg_match('/protected\s+\$casts\s*=\s*\[(.*?)\];/s', $src, $castm)) {
                if (preg_match_all('/["\']([a-zA-Z0-9_]+)["\']\s*=>\s*["\']([^"\']+)["\']/', $castm[1], $cst)) {
                    foreach ($cst[1] as $i=>$field) { $casts[$field]=$cst[2][$i]; }
                }
            }
            $out[] = '## '.$class; $out[]='';
            if (!$fillable) { $out[] = '_Žádné $fillable pole nenalezeno_'; $out[]=''; continue; }
            $out[]='| Pole | Typ (cast) |';
            $out[]='|------|-----------|';
            foreach ($fillable as $f) { $out[] = '| '.$f.' | '.($casts[$f] ?? '').' |'; }
            $out[]='';
        }
        file_put_contents($base.'/model-fields.md', implode("\n", $out)."\n");
    }

    protected function generateBackupReportSummary(string $base): void
    {
        $this->info('> Backup Report Summary');
        $repDir = '/srv/backups/crm/reports';
        $outFile = $base.'/backup-report-latest.md';
        if (!is_dir($repDir)) {
            file_put_contents($outFile, "# Backup Report (latest)\n\nAdresář reportů nenalezen. Spusť `GenerateBackupReportJob`.\n");
            return;
        }
        $files = glob($repDir.'/backup-report-*.md');
        if (!$files) {
            file_put_contents($outFile, "# Backup Report (latest)\n\nŽádný report ještě neexistuje.\n");
            return;        }
        usort($files, fn($a,$b)=>filemtime($b)<=>filemtime($a));
        $latest = $files[0];
        $raw = file_get_contents($latest) ?: '';
        // Zkrácený výpis (prvních 1200 znaků) + cesta
        $short = substr($raw,0,1200);
        $content = "# Backup Report (latest)\n\nZdroj: `".basename($latest)."` (".date('Y-m-d H:i:s', filemtime($latest)).")\n\n".$short;
        if (strlen($raw) > 1200) { $content .= "\n\n… (zkráceno)"; }
        file_put_contents($outFile, $content."\n");
    }

    protected function generateDbSchemaDetails(string $base): void
    {
        $this->info('> DB Schema (information_schema)');
        try { $conn = \DB::connection(); $pdo = $conn->getPdo(); }
        catch (\Throwable $e) {
            file_put_contents($base.'/db-schema.md', "# DB Schema (generováno)\n\n_Nepodařilo se připojit k DB: `".str_replace('`','\\`',$e->getMessage())."`_\n");
            return;
        }
        try {
            $dbName = $conn->getDatabaseName();
            $tables = $conn->select("SELECT table_name FROM information_schema.tables WHERE table_schema = ?", [$dbName]);
        } catch (\Throwable $e) {
            file_put_contents($base.'/db-schema.md', "# DB Schema (generováno)\n\n_Chyba při čtení information_schema: `".str_replace('`','\\`',$e->getMessage())."`_\n");
            return;
        }
        $names = collect($tables)->pluck('table_name')->filter(fn($t)=>!preg_match('/^(migrations|cache|jobs|failed_jobs)$/',$t))->sort()->values();
        $out = ["# DB Schema (generováno)", '', '_Zdroj: INFORMATION_SCHEMA. Omezené k indexům & FK._', ''];
        foreach ($names as $t) {
            $cols = $conn->select("SELECT column_name, column_type, is_nullable, column_default FROM information_schema.columns WHERE table_schema = ? AND table_name = ? ORDER BY ordinal_position", [$dbName,$t]);
            $idxRows = $conn->select("SELECT index_name, non_unique, GROUP_CONCAT(column_name ORDER BY seq_in_index) cols FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? GROUP BY index_name, non_unique", [$dbName,$t]);
            $fkRows = $conn->select("SELECT constraint_name, group_concat(column_name ORDER BY ordinal_position) local_cols, referenced_table_name, group_concat(referenced_column_name ORDER BY position_in_unique_constraint) ref_cols FROM information_schema.key_column_usage WHERE table_schema=? AND table_name=? AND referenced_table_name IS NOT NULL GROUP BY constraint_name, referenced_table_name", [$dbName,$t]);
            $idxMap = [];
            foreach ($idxRows as $ir) { $idxMap[$ir->index_name] = ['unique'=>!$ir->non_unique,'cols'=>$ir->cols]; }
            $out[]='## `'.$t.'`';
            $out[]='';
            $out[]='| Sloupec | Typ | Nullable | Default | Indexy |';
            $out[]='|---------|-----|----------|---------|--------|';
            foreach ($cols as $c) {
                $indexes = [];
                foreach ($idxMap as $name=>$meta) {
                    $colsIn = explode(',', $meta['cols']);
                    if (in_array($c->column_name, $colsIn, true)) {
                        if ($name === 'PRIMARY') $indexes[]='PRIMARY';
                        elseif ($meta['unique']) $indexes[]='UNIQUE'; else $indexes[]='INDEX';
                    }
                }
                $out[]='| '.$c->column_name.' | '.$c->column_type.' | '.($c->is_nullable==='YES'?'ANO':'').' | '.str_replace('|','/',(string)($c->column_default ?? '')).' | '.implode(',',array_unique($indexes)).' |';
            }
            if ($fkRows) {
                $out[]='';
                $out[]='**FK**';
                foreach ($fkRows as $fk) {
                    $out[]='- '.$fk->constraint_name.': (`'.str_replace(',', '`,`', $fk->local_cols).'`) → `'.$fk->referenced_table_name.'` (`'.str_replace(',', '`,`', $fk->ref_cols).'`)';
                }
            }
            $out[]='';
        }
        file_put_contents($base.'/db-schema.md', implode("\n", $out)."\n");
    }

    protected function generateNavigationDoc(string $base): void
    {
        $this->info('> Navigace (CRM menu)');
        try { $routes = \Route::getRoutes(); } catch (\Throwable $e) {
            file_put_contents($base.'/navigation.md', "# Navigace (generováno)\n\n_Nelze načíst router: `".$e->getMessage()."`_\n");
            return;
        }
        // Load meta mapping if exists
        $metaFile = base_path('docs/_meta/menu.php');
        $meta = file_exists($metaFile) ? (include $metaFile) : [];
        $sections = [];// slug => ['title'=>..., 'description'=>..., 'order'=>..., 'routes'=>[]]
        foreach ($routes as $r) {
            /** @var \Illuminate\Routing\Route $r */
            $uri = $r->uri();
            $methods = array_diff($r->methods(), ['HEAD']);
            if (!in_array('GET',$methods)) continue; // menu = GET stránky
            if (!str_starts_with($uri, 'crm')) continue;
            $path = substr($uri, 3); // remove 'crm'
            $path = ltrim($path,'/');
            $name = $r->getName();
            // Determine section slug (first segment or dashboard)
            $slug = $path === '' ? 'dashboard' : explode('/', $path)[0];
            $m = $meta[$slug] ?? [];
            if (!isset($sections[$slug])) {
                $sections[$slug] = [
                    'slug'=>$slug,
                    'title'=>$m['title'] ?? ucfirst(str_replace(['-','_'],' ',$slug)),
                    'description'=>$m['description'] ?? '',
                    'order'=>$m['order'] ?? 100,
                    'routes'=>[]
                ];
            }
            // route description mapping
            $routeDesc = $m['routes'][$name] ?? '';
            $sections[$slug]['routes'][] = [
                'name'=>$name ?? '',
                'uri'=>$uri,
                'path'=>$path === '' ? '/' : '/'.$path,
                'desc'=>$routeDesc,
            ];
        }
        // sort sections by order then title
        usort($sections, fn($a,$b)=>[$a['order'],$a['title']] <=> [$b['order'],$b['title']]);
        foreach ($sections as &$sec) {
            usort($sec['routes'], fn($a,$b)=>$a['path'] <=> $b['path']);
        }
        $lines = ['# Navigace (generováno)', '', '_Automatický přehled položek CRM menu (GET routy s prefixem `/crm`). Popisy lze doplnit v `docs/_meta/menu.php`._', ''];
        foreach ($sections as $s) {
            $lines[] = '## '.$s['title'];
            if ($s['description']) { $lines[]=''; $lines[]=$s['description']; }
            $lines[]='';
            $lines[]='| Cesta | Route name | Popis |';
            $lines[]='|-------|------------|-------|';
            foreach ($s['routes'] as $r) {
                $desc = $r['desc'] ?: '_(popis chybí)_';
                $lines[] = sprintf('| `%s` | %s | %s |', $r['path'], $r['name'], str_replace('|','/',$desc));
            }
            $lines[]='';
        }
        file_put_contents($base.'/navigation.md', implode("\n", $lines)."\n");
    }
}
