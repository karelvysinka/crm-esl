<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contact;
use App\Models\ContactIdentity;

class BackfillContactIdentities extends Command
{
    protected $signature = 'contacts:backfill-identities {--chunk=1000} {--dry-run}';
    protected $description = 'Doplní contact_identities z existujícího sloupce contacts.ac_id (source=ac).';

    public function handle(): int
    {
        $chunk = (int)$this->option('chunk');
        $dry = (bool)$this->option('dry-run');
        $created = 0; $scanned = 0;
        Contact::query()
            ->whereNotNull('ac_id')
            ->orderBy('id')
            ->chunk($chunk, function($contacts) use (&$created, &$scanned, $dry) {
                foreach ($contacts as $c) {
                    $scanned++;
                    $exists = ContactIdentity::where(['source' => 'ac', 'external_id' => (string)$c->ac_id])->exists();
                    if ($exists) continue;
                    if ($dry) {
                        $this->line("Chybí identity pro contact #{$c->id} ac_id={$c->ac_id}");
                    } else {
                        ContactIdentity::create([
                            'contact_id' => $c->id,
                            'source' => 'ac',
                            'external_id' => (string)$c->ac_id,
                            'external_hash' => $c->ac_hash,
                        ]);
                    }
                    $created++;
                }
            });
        $this->info("Zkontrolováno: {$scanned}, vytvořeno identit: {$created}");
        return 0;
    }
}
