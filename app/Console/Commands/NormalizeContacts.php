<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contact;

class NormalizeContacts extends Command
{
    protected $signature = 'contacts:normalize {--chunk=500} {--dry-run}';
    protected $description = 'Naplní normalized_email/normalized_phone pro stávající kontakty';

    public function handle(): int
    {
        $chunk = (int) $this->option('chunk');
        $dry = (bool) $this->option('dry-run');
        $total = 0; $updated = 0;

        Contact::query()->orderBy('id')->chunk($chunk, function($contacts) use (&$total, &$updated, $dry) {
            foreach ($contacts as $c) {
                $total++;
                [$normEmail, $normPhone] = [$this->normalizeEmail($c->email), $this->normalizePhone($c->phone ?: $c->mobile)];
                $changes = [];
                if ($c->normalized_email !== $normEmail) { $changes['normalized_email'] = $normEmail; }
                if ($c->normalized_phone !== $normPhone) { $changes['normalized_phone'] = $normPhone; }
                if ($changes) {
                    if ($dry) {
                        $this->line("#{$c->id} {$c->email} => {$normEmail} | {$c->phone} => {$normPhone}");
                    } else {
                        $c->fill($changes);
                        $c->save();
                    }
                    $updated++;
                }
            }
        });

        $this->info("Zpracováno: {$total}, aktualizováno: {$updated}");
        return 0;
    }

    private function normalizeEmail(?string $email): ?string
    {
        if (!$email) return null;
        $e = mb_strtolower(trim($email));
        if (str_contains($e, '@placeholder.local') || str_starts_with($e, 'noemail-')) return null;
        return $e;
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (!$phone) return null;
        $p = preg_replace('/[^0-9+]/', '', $phone);
        if (!$p) return null;
        // If has + already, leave as-is cleaned
        if (str_starts_with($p, '+')) return $p;
        // Heuristics for CZ numbers
        $digits = preg_replace('/\D/', '', $p);
        if (strlen($digits) === 12 && str_starts_with($digits, '420')) return '+'.$digits;
        if (strlen($digits) === 9) return '+420'.$digits;
        return $p;
    }
}
