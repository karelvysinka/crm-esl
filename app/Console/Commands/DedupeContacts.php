<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\SalesOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DedupeContacts extends Command
{
    protected $signature = 'contacts:dedupe {--apply} {--company-id=} {--email=}';
    protected $description = 'Find and optionally merge duplicate contacts (same email) within the same company. Default is dry-run.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $companyId = $this->option('company-id');
        $filterEmail = $this->option('email');

        // Build duplicate groups: same company_id + lower(email), excluding placeholder emails
        $dupQuery = Contact::query()
            ->select('company_id', DB::raw('LOWER(email) as email_lc'), DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('email')
            ->where('email', 'NOT LIKE', '%@placeholder.local')
            ->when($companyId, fn($q) => $q->where('company_id', (int) $companyId))
            ->groupBy('company_id', 'email_lc')
            ->having('cnt', '>', 1);

        $groups = $dupQuery->get();

        if ($groups->isEmpty()) {
            $this->info('No duplicate contacts by (company_id, email) found.');
            return self::SUCCESS;
        }

        $totalGroups = 0;
        $totalMerged = 0;
        foreach ($groups as $g) {
            if ($filterEmail && strtolower($filterEmail) !== $g->email_lc) {
                continue;
            }
            $totalGroups++;
            $contacts = Contact::where('company_id', $g->company_id)
                ->whereRaw('LOWER(email) = ?', [$g->email_lc])
                ->get();
            if ($contacts->count() < 2) { continue; }

            // Choose canonical: max sales order count, then oldest created
            $canonical = $contacts->sortByDesc(fn($c) => $c->salesOrders()->count())
                                   ->sortBy('created_at')
                                   ->first();
            $dupes = $contacts->where('id', '!=', $canonical->id);

            $this->line("Duplicate group: company={$g->company_id}, email={$g->email_lc}, total={$contacts->count()}, canonical={$canonical->id}");

            if (!$apply) {
                foreach ($dupes as $d) {
                    $this->line("  would-merge contact {$d->id} into {$canonical->id} (orders=".$d->salesOrders()->count().")");
                }
                continue;
            }

            DB::transaction(function () use ($canonical, $dupes, &$totalMerged) {
                foreach ($dupes as $d) {
                    // Reassign orders
                    SalesOrder::where('contact_id', $d->id)->update(['contact_id' => $canonical->id]);

                    // Merge tags
                    $tagIds = $d->tags()->pluck('tags.id')->all();
                    if ($tagIds) {
                        $canonical->tags()->syncWithoutDetaching($tagIds);
                    }

                    // Merge custom fields (do not overwrite existing keys)
                    foreach ($d->customFields as $cf) {
                        $exists = $canonical->customFields()->where('key', $cf->key)->exists();
                        if (!$exists) {
                            $canonical->customFields()->create(['key' => $cf->key, 'value' => $cf->value]);
                        }
                    }

                    $d->delete();
                    $totalMerged++;
                }
            });
        }

        $this->info("Groups scanned: {$totalGroups}");
        if ($apply) {
            $this->info("Duplicates merged: {$totalMerged}");
        } else {
            $this->info('Dry-run complete. Re-run with --apply to perform merges.');
        }

        return self::SUCCESS;
    }
}
