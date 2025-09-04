#!/usr/bin/env php
<?php
// Basic validation of changelog format: newest first, required headings, allowed TYPE tokens
$file = __DIR__ . '/../docs/01-intro/changelog.md';
if (!file_exists($file)) { fwrite(STDERR, "Changelog nenalezen\n"); exit(1); }
$c = file_get_contents($file);
$required = ['## Formát položky','## Poslední změny'];
foreach ($required as $r) { if (strpos($c,$r)===false){ fwrite(STDERR, "Chybí sekce: $r\n"); exit(1);} }
// Extract entries
preg_match_all('/^### \[(\d{4}-\d{2}-\d{2})\] v(\d+\.\d+\.\d+) \(([^)]+)\)$/m',$c,$m, PREG_SET_ORDER);
if (!$m){ fwrite(STDERR, "Žádné položky changelogu\n"); exit(1);} 
$lastDate = null; $types=['ADDED','CHANGED','FIXED','REMOVED','SECURITY','PERF','DOCS'];
foreach ($m as $entry){ [$full,$date,$ver,$type]=$entry; if(!in_array($type,$types)){ fwrite(STDERR,"Neplatný TYPE: $type\n"); exit(1);} if($lastDate && $date > $lastDate){ fwrite(STDERR,"Chybný pořadí dat (musí být nejnovější nahoře) u $date\n"); exit(1);} $lastDate=$date; }
echo "Changelog OK (".count($m)." položek).\n"; exit(0);
