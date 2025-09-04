<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\ContactCustomField;
use App\Models\Tag;
use App\Services\ActiveCampaignClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AcEnrichContactJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $acId)
    {
        $this->onQueue('imports');
    }

    public function handle(ActiveCampaignClient $ac): void
    {
        $contact = Contact::firstWhere('ac_id', $this->acId);
        if (!$contact) {
            Log::warning('AC enrich skipped, contact not found', ['ac_id' => $this->acId]);
            return;
        }

        $data = $ac->get('contacts/'.urlencode($this->acId), [
            'include' => 'contactTags,fieldValues,tags',
        ]);

        $c = $data['contact'] ?? null;
        if (!$c) {
            Log::warning('AC enrich: no contact data returned', ['ac_id' => $this->acId]);
            return;
        }

        // Map tags from top-level collections
        $tagsById = [];
        foreach (($data['tags'] ?? []) as $t) {
            $tid = (string)($t['id'] ?? '');
            if ($tid !== '') { $tagsById[$tid] = $t['tag'] ?? $t['name'] ?? null; }
        }
        $tagNames = [];
        foreach (($data['contactTags'] ?? []) as $ct) {
            $tid = (string)($ct['tag'] ?? '');
            if ($tid === '') continue;
            $name = $tagsById[$tid] ?? null;
            if ($name) { $tagNames[] = $name; }
        }
        if (!empty($c['tags']) && is_array($c['tags'])) {
            foreach ($c['tags'] as $t) {
                $tagNames[] = is_array($t) ? ($t['tag'] ?? $t['name'] ?? null) : $t;
            }
        }
        $tagNames = array_values(array_filter(array_unique(array_map('trim', $tagNames))));
        if ($tagNames) {
            $tagIds = [];
            foreach ($tagNames as $name) {
                $tag = Tag::firstOrCreate(['name' => $name, 'source' => 'ac']);
                $tagIds[] = $tag->id;
            }
            $contact->tags()->syncWithoutDetaching($tagIds);
        }

        // Custom fields
        $fieldValues = $c['fieldValues'] ?? [];
        if (!$fieldValues && !empty($data['fieldValues']) && is_array($data['fieldValues'])) {
            $fieldValues = $data['fieldValues'];
        }
        if (is_array($fieldValues)) {
            foreach ($fieldValues as $fv) {
                $key = $fv['field'] ?? ($fv['key'] ?? null);
                $val = $fv['value'] ?? ($fv['val'] ?? null);
                if (!$key) continue;
                ContactCustomField::updateOrCreate(
                    ['contact_id' => $contact->id, 'key' => (string)$key],
                    ['value' => is_scalar($val) ? (string)$val : json_encode($val), 'type' => is_scalar($val) ? 'string' : 'json']
                );
            }
        }
    }
}
