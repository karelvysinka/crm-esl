<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Contact;
use App\Models\ContactIdentity;

class MergeDuplicateContacts extends Command
{
    protected $signature = 'contacts:merge-duplicates {--apply} {--by=auto} {--limit=500}';
    protected $description = 'Sloučí duplicitní kontakty (auto: ac_id -> normalized_email -> normalized_phone). Použij --apply pro skutečné provedení.';

    public function handle(): int
    {
        $apply = (bool)$this->option('apply');
        $by = (string)$this->option('by');
        $limit = (int)$this->option('limit');

        $groups = $this->findGroups($by, $limit);
        if ($groups->isEmpty()) {
            $this->info('Nenalezeny žádné duplicity.');
            return 0;
        }
        foreach ($groups as $group) {
            $key = $group['key'];
            $ids = $group['ids'];
            $this->line("Skupina '{$group['type']}'='{$key}' -> ".implode(',', $ids));
            if ($apply) {
                $this->mergeGroup($ids);
            }
        }
        $this->info($apply ? 'Sloučení dokončeno.' : 'Dry-run hotov. Přidej --apply pro provedení.');
        return 0;
    }

    private function findGroups(string $by, int $limit)
    {
        $out = collect();
        if ($by === 'ac_id' || $by === 'auto') {
            $dupes = Contact::query()->select('ac_id', DB::raw('GROUP_CONCAT(id) as ids'), DB::raw('COUNT(*) as cnt'))
                ->whereNotNull('ac_id')
                ->groupBy('ac_id')->having('cnt', '>', 1)->limit($limit)->get();
            foreach ($dupes as $d) { $out->push(['type'=>'ac_id','key'=>$d->ac_id,'ids'=>array_map('intval', explode(',', $d->ids))]); }
        }
        if ($by === 'email' || $by === 'auto') {
            $dupes = Contact::query()
                ->select('company_id','normalized_email', DB::raw('GROUP_CONCAT(id) as ids'), DB::raw('COUNT(*) as cnt'))
                ->whereNotNull('normalized_email')
                ->groupBy('company_id','normalized_email')
                ->having('cnt', '>', 1)->limit($limit)->get();
            foreach ($dupes as $d) { $out->push(['type'=>'email','key'=>$d->company_id.':'.$d->normalized_email,'ids'=>array_map('intval', explode(',', $d->ids))]); }
        }
        if ($by === 'email_global') {
            $dupes = Contact::query()
                ->select('normalized_email', DB::raw('GROUP_CONCAT(id) as ids'), DB::raw('COUNT(*) as cnt'))
                ->whereNotNull('normalized_email')
                ->groupBy('normalized_email')
                ->having('cnt', '>', 1)->limit($limit)->get();
            foreach ($dupes as $d) { $out->push(['type'=>'email_global','key'=>$d->normalized_email,'ids'=>array_map('intval', explode(',', $d->ids))]); }
        }
    if ($by === 'phone' || $by === 'auto') {
            $dupes = Contact::query()
                ->select('company_id','normalized_phone', DB::raw('GROUP_CONCAT(id) as ids'), DB::raw('COUNT(*) as cnt'))
        ->whereNotNull('normalized_phone')
        ->whereRaw('CHAR_LENGTH(normalized_phone) >= 9')
                ->groupBy('company_id','normalized_phone')
                ->having('cnt', '>', 1)->limit($limit)->get();
            foreach ($dupes as $d) { $out->push(['type'=>'phone','key'=>$d->company_id.':'.$d->normalized_phone,'ids'=>array_map('intval', explode(',', $d->ids))]); }
        }
        return $out;
    }

    private function mergeGroup(array $ids): void
    {
        // Keep only existing contacts; require at least 2
        $existing = Contact::query()->whereIn('id', $ids)->pluck('id')->all();
        if (count($existing) < 2) { return; }
        sort($existing);
        $primaryId = array_shift($existing);
        $others = $existing;
        DB::transaction(function () use ($primaryId, $others) {
            $primary = Contact::find($primaryId);
            if (!$primary) { return; }
            foreach ($others as $oid) {
                $dup = Contact::find($oid);
                if (!$dup) continue;
                // Fill gaps on primary
                foreach (['first_name','last_name','email','phone','mobile','position','department','address','city','country','notes'] as $col) {
                    if (empty($primary->{$col}) && !empty($dup->{$col})) { $primary->{$col} = $dup->{$col}; }
                }
                foreach (['normalized_email','normalized_phone','ac_id','ac_hash','ac_updated_at','marketing_status'] as $col) {
                    if (empty($primary->{$col}) && !empty($dup->{$col})) { $primary->{$col} = $dup->{$col}; }
                }
                $primary->save();
                // Reattach identities
                ContactIdentity::where('contact_id', $dup->id)->update(['contact_id' => $primary->id]);
                // Merge tags
                $tagIds = $dup->tags()->pluck('tags.id')->all();
                if ($tagIds) { $primary->tags()->syncWithoutDetaching($tagIds); }
                // Merge custom fields (upsert by key)
                foreach ($dup->customFields as $cf) {
                    \App\Models\ContactCustomField::updateOrCreate(
                        ['contact_id' => $primary->id, 'key' => $cf->key],
                        ['value' => $cf->value, 'type' => $cf->type]
                    );
                }
                // Repoint relations
                \App\Models\SalesOrder::where('contact_id', $dup->id)->update(['contact_id' => $primary->id]);
                \App\Models\Task::where('taskable_type', Contact::class)->where('taskable_id', $dup->id)->update(['taskable_id' => $primary->id]);
                // Delete duplicate
                $dup->delete();
            }
        });
    }
}
