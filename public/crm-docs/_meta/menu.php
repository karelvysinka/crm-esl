<?php
return [
    'dashboard' => [
        'title' => 'Dashboard',
        'description' => 'Výchozí přehled (widgety, stav synchronizací, klíčové KPI).',
        'order' => 1,
        'routes' => [
            'crm.dashboard' => 'Hlavní přehled a rychlé akce.'
        ]
    ],
    'companies' => [
        'title' => 'Firmy',
        'description' => 'Evidence firem (profil, revenue, průmysl) – vstup do detailu vztahů.',
        'order' => 10,
        'routes' => []
    ],
    'contacts' => [
        'title' => 'Kontakty',
        'description' => 'Evidence osob navázaných na firmy, segmentace a marketing status.',
        'order' => 11,
        'routes' => []
    ],
    'leads' => [
        'title' => 'Leady',
        'description' => 'Potenciální příležitosti před kvalifikací (kanban/pipeline varianty).',
        'order' => 12,
        'routes' => []
    ],
    'opportunities' => [
        'title' => 'Příležitosti',
        'description' => 'Obchodní příležitosti s fázemi, pravděpodobností a predikcí closingu.',
        'order' => 13,
        'routes' => []
    ],
    'tasks' => [
        'title' => 'Úkoly',
        'description' => 'Operativní práce navázaná na entity (polymorfní taskable).',
        'order' => 20,
        'routes' => []
    ],
    'projects' => [
        'title' => 'Projekty',
        'description' => 'Dlouhodobější iniciativy agregující úkoly a obchodní kontext.',
        'order' => 21,
        'routes' => []
    ],
    'salesorder' => [
        'title' => 'Objednávky',
        'description' => 'Uzavřené zakázky / objednávky včetně položek a částek.',
        'order' => 30,
        'routes' => []
    ],
    'knowledge' => [
        'title' => 'Knowledge Base',
        'description' => 'Znalostní báze (dokumenty, chunking, vektorové vyhledávání).',
        'order' => 40,
        'routes' => []
    ],
    'system' => [
        'title' => 'System',
        'description' => 'Admin konfigurace (ActiveCampaign, Backup, Qdrant, Tools, Chat, integrace).',
        'order' => 90,
        'routes' => []
    ],
    'marketing' => [
        'title' => 'Marketing',
        'description' => 'Strategie, exekuce, analytika, nastavení marketingu.',
        'order' => 50,
        'routes' => []
    ],
    'chat' => [
        'title' => 'Chat',
        'description' => 'Konverzační AI / uživatelské relace.',
        'order' => 60,
        'routes' => []
    ],
];
