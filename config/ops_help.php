<?php
return [
    'git.strategy' => 'Strategie větví: main + krátké feature větve. Viz plán sekce 2–4.',
    'backup.rpo' => 'RPO definuje maximální ztrátu dat. Cíl DB 15 min (po aktivaci binlogů).',
    'backup.verify' => 'Verify restore: týdně automatizovaný test obnovy, ověření klíčových tabulek (sekce 19).',
    'release.process' => 'Release: Merge -> CI -> Tag -> Deploy -> Health check -> Audit (sekce 9,21).',
    'ops.limits' => 'Limity: rate limit akcí, jednorázové tokeny, alerty při stárnutí (sekce 20,33).'
];
