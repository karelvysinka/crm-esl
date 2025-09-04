<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactCustomField;
use App\Models\Tag;
use Illuminate\Support\Carbon;
use Psr\Log\LoggerInterface;

class ActiveCampaignImporter
{
    private array $tagCacheById = [];
    private array $fieldCacheById = [];
    private int $perCallSleepMs = 150; // small backoff between enrichment calls

    public function __construct(private ActiveCampaignClient $ac, private LoggerInterface $logger)
    {
    }

    /**
     * Import a batch of contacts from payload returned by AC API.
     * Returns an array with counters and up to 3 error samples.
     *
     * @param array $contactsData The 'contacts' array from AC response
     */
    public function importContacts(array $contactsData): array
    {
        $created = 0; $updated = 0; $skipped = 0; $skippedUnchanged = 0; $errors = 0; $errorSamples = [];
        $sampleCreatedIds = []; $sampleUpdatedIds = [];

        // Determine creator id (current user or first user)
        $creatorId = auth()->id();
        if (!$creatorId) {
            try { $creatorId = \App\Models\User::query()->min('id'); } catch (\Throwable $e) { $creatorId = null; }
        }

    foreach ($contactsData as $c) {
            $acId = (string)($c['id'] ?? '');
            $email = trim((string)($c['email'] ?? ''));
            if (!$acId && !$email) { $skipped++; continue; }

            $first = trim((string)($c['firstName'] ?? ''));
            $last = trim((string)($c['lastName'] ?? ''));
            $phone = trim((string)($c['phone'] ?? ''));
            $updatedAt = $c['updated_at'] ?? ($c['updatedAt'] ?? null);
            $incomingAt = $updatedAt ? Carbon::parse($updatedAt) : null;
            $status = (string)($c['status'] ?? 'active');

            // Fallbacks for required columns
            if (!$email) { $skipped++; continue; }
            if (!$first) { $first = explode('@', $email)[0] ?: 'AC'; }
            if (!$last) { $last = '-'; }

            // Normalization helpers
            $normEmail = null;
            if ($email) {
                $normEmail = mb_strtolower(trim($email));
                if (str_contains($normEmail, '@placeholder.local') || str_starts_with($normEmail, 'noemail-')) {
                    // treat placeholder as non-matchable for existing contact lookup
                    $normEmail = null;
                }
            }
            $normPhone = null;
            $rawPhone = $phone ?: null;
            if ($rawPhone) {
                $normPhone = preg_replace('/[^0-9+]/', '', $rawPhone);
                // normalize Czech numbers without +420
                if ($normPhone && !str_starts_with($normPhone, '+') && preg_match('/^420?\d{9}$/', $normPhone)) {
                    $digits = preg_replace('/\D/', '', $normPhone);
                    if (strlen($digits) === 12 && str_starts_with($digits, '420')) { $normPhone = '+'.$digits; }
                    elseif (strlen($digits) === 9) { $normPhone = '+420'.$digits; }
                }
            }

            $attrs = [
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'normalized_email' => $normEmail,
                'phone' => $phone ?: null,
                'normalized_phone' => $normPhone,
                'status' => in_array($status, ['active','inactive']) ? $status : 'active',
                'ac_hash' => $c['hash'] ?? null,
                'ac_updated_at' => $incomingAt,
            ];

            // Upsert by ac_id/email
            $contactModel = null;
            // 1) identity table
            if ($acId) {
                $contactModel = \App\Models\ContactIdentity::where(['source' => 'ac', 'external_id' => $acId])
                    ->with('contact')
                    ->first()?->contact;
            }
            // 2) direct column ac_id (backward compatibility)
            if (!$contactModel && $acId) { $contactModel = Contact::firstWhere('ac_id', $acId); }
            // 3) normalized email (if not placeholder)
            if (!$contactModel && $normEmail) { $contactModel = Contact::firstWhere('normalized_email', $normEmail); }
            // 4) raw email (fallback)
            if (!$contactModel && $email) { $contactModel = Contact::firstWhere('email', $email); }
            // 5) normalized phone as last resort
            if (!$contactModel && $normPhone) { $contactModel = Contact::firstWhere('normalized_phone', $normPhone); }

            try {
                if ($contactModel) {
                    if ($incomingAt && $contactModel->ac_updated_at && $incomingAt->lessThanOrEqualTo($contactModel->ac_updated_at)) {
                        $skippedUnchanged++;
                        // still ensure ac_id present
                        if ($acId && !$contactModel->ac_id) { $contactModel->ac_id = $acId; $contactModel->save(); }
                        // Enrich even when unchanged to ensure tags/fields? We'll enrich lazily only if missing
                    } else {
                        $contactModel->fill($attrs);
                        if ($acId) { $contactModel->ac_id = $acId; }
                        $contactModel->save();
                        // Ensure identity row exists
                        if ($acId) {
                            \App\Models\ContactIdentity::firstOrCreate(
                                ['source' => 'ac', 'external_id' => (string)$acId],
                                ['contact_id' => $contactModel->id, 'external_hash' => $c['hash'] ?? null]
                            );
                        }
                        $updated++;
                        if (count($sampleUpdatedIds) < 20) { $sampleUpdatedIds[] = $contactModel->id; }
                    }
                } else {
                    $attrs['ac_id'] = $acId ?: null;
                    if ($creatorId) { $attrs['created_by'] = $creatorId; }
                    $contactModel = Contact::create($attrs);
                    // Create identity row if ac id present
                    if ($acId) {
                        \App\Models\ContactIdentity::firstOrCreate(
                            ['source' => 'ac', 'external_id' => (string)$acId],
                            ['contact_id' => $contactModel->id, 'external_hash' => $c['hash'] ?? null]
                        );
                    }
                    $created++;
                    if (count($sampleCreatedIds) < 20) { $sampleCreatedIds[] = $contactModel->id; }
                }
            } catch (\Throwable $ex) {
                $errors++;
                if (count($errorSamples) < 3) { $errorSamples[] = $ex->getMessage(); }
                $this->logger->error('AC import: failed to upsert contact', ['ac_id' => $acId, 'email' => $email, 'error' => $ex->getMessage()]);
                continue;
            }

            // Tags from inline data if present
            $tagNames = [];
            if (!empty($c['tags']) && is_array($c['tags'])) {
                foreach ($c['tags'] as $t) {
                    $tagNames[] = is_array($t) ? ($t['tag'] ?? $t['name'] ?? null) : $t;
                }
            }
            $tagNames = array_values(array_filter(array_unique(array_map('trim', $tagNames))));
            if (!$tagNames && $acId) {
                // Enrich via contactTags endpoint
                try {
                    $tagNames = $this->fetchTagNamesForContact($acId);
                } catch (\Throwable $e) {
                    $this->logger->warning('AC import: tag enrichment failed', ['ac_id' => $acId, 'error' => $e->getMessage()]);
                }
            }
            if ($tagNames) {
                $tagIds = [];
                foreach ($tagNames as $name) {
                    if (!$name) continue;
                    $tag = Tag::firstOrCreate(['name' => $name, 'source' => 'ac']);
                    $tagIds[] = $tag->id;
                }
                if ($tagIds) { $contactModel->tags()->syncWithoutDetaching($tagIds); }
            }

            // Custom fields from inline, else enrichment
            $fvInline = $c['fieldValues'] ?? $c['fields'] ?? [];
            $fieldValues = [];
            if (is_array($fvInline) && !empty($fvInline)) {
                foreach ($fvInline as $fv) {
                    $key = $fv['field'] ?? ($fv['key'] ?? null);
                    $val = $fv['value'] ?? ($fv['val'] ?? null);
                    if ($key) { $fieldValues[(string)$key] = $val; }
                }
            }
            if (!$fieldValues && $acId) {
                try {
                    $fieldValues = $this->fetchFieldValuesForContact($acId);
                } catch (\Throwable $e) {
                    $this->logger->warning('AC import: custom field enrichment failed', ['ac_id' => $acId, 'error' => $e->getMessage()]);
                }
            }
            if ($fieldValues) {
                foreach ($fieldValues as $key => $val) {
                    ContactCustomField::updateOrCreate(
                        ['contact_id' => $contactModel->id, 'key' => (string)$key],
                        ['value' => is_scalar($val) ? (string)$val : json_encode($val), 'type' => is_scalar($val) ? 'string' : 'json']
                    );
                }
            }
            // small delay to be polite with API
            usleep($this->perCallSleepMs * 1000);
        }

    return compact('created','updated','skipped','skippedUnchanged','errors','errorSamples','sampleCreatedIds','sampleUpdatedIds');
    }

    /**
     * Fetch tag names for a contact via /contactTags and /tags/{id}
     * Caches tag ids to names in-memory for the life of this service instance.
     */
    private function fetchTagNamesForContact(string $acId): array
    {
        $out = [];
        $resp = $this->ac->get('contactTags', ['contact' => $acId, 'limit' => 100]);
        $pairs = $resp['contactTags'] ?? [];
        foreach ($pairs as $p) {
            $tagId = (string)($p['tag'] ?? '');
            if (!$tagId) continue;
            $name = $this->tagCacheById[$tagId] ?? null;
            if ($name === null) {
                // fetch and cache
                $tagResp = $this->ac->get('tags/' . $tagId);
                $name = (string)($tagResp['tag']['tag'] ?? $tagResp['tag']['name'] ?? null);
                if ($name) { $this->tagCacheById[$tagId] = $name; }
                usleep($this->perCallSleepMs * 1000);
            }
            if ($name) { $out[] = $name; }
        }
        return array_values(array_unique(array_filter($out)));
    }

    /**
     * Fetch custom field values for a contact via /contacts/{id}/fieldValues and map field ids to field titles via /fields/{id}
     */
    private function fetchFieldValuesForContact(string $acId): array
    {
        $out = [];
        $resp = $this->ac->get('contacts/' . $acId . '/fieldValues', ['limit' => 100]);
        $items = $resp['fieldValues'] ?? [];
        foreach ($items as $fv) {
            $fieldId = (string)($fv['field'] ?? '');
            if (!$fieldId) continue;
            $title = $this->fieldCacheById[$fieldId] ?? null;
            if ($title === null) {
                $fResp = $this->ac->get('fields/' . $fieldId);
                $title = (string)($fResp['field']['title'] ?? $fResp['field']['perstag'] ?? $fieldId);
                $this->fieldCacheById[$fieldId] = $title;
                usleep($this->perCallSleepMs * 1000);
            }
            $val = $fv['value'] ?? null;
            if ($title) { $out[$title] = $val; }
        }
        return $out;
    }
}
