<?php

return [
    // Placeholder mapping – will refine once full semantics of each code confirmed.
    // key => ['label' => 'Human readable', 'final' => bool]
    'S' => ['label' => 'Stav S (ověřit)', 'final' => false],
    'P' => ['label' => 'Přijato', 'final' => false],
    'V' => ['label' => 'Vyskladněno', 'final' => false],
    'O' => ['label' => 'Odesláno', 'final' => false],
    'C' => ['label' => 'Čeká se na platbu', 'final' => false],
    'Č' => ['label' => 'Částečný / Č (ověřit)', 'final' => false],
    'Z' => ['label' => 'Zaplaceno', 'final' => false],
    'FINAL_VYRIZENO' => ['label' => 'Vyřízeno', 'final' => true],
];
