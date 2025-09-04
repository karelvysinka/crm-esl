<?php

namespace App\Services\AI;

/**
 * Minimal heuristic planner for M3.
 * - Extracts basic entities (emails, phones) from user text
 * - Detects potential mutation intent (requires confirmation)
 * - Returns a simple plan structure for the agent to act on
 */
class Planner
{
    /**
     * Plan structure:
     * [
     *   'entities' => [ 'emails' => string[], 'phones' => string[] ],
     *   'actions' => [ 'lookup_contact_by_email' | 'lookup_contact_by_phone' ... ],
     *   'mutations' => string[],
     *   'requires_confirmation' => bool,
     *   'notes' => string[],
     * ]
     */
    public function plan(string $text): array
    {
        $emails = $this->extractEmails($text);
        $phones = $this->extractPhones($text);

        $actions = [];
        if (!empty($emails)) {
            $actions[] = 'lookup_contact_by_email';
        }
        if (!empty($phones)) {
            $actions[] = 'lookup_contact_by_phone';
        }

        // Lightweight partial search intent: catch more Czech phrasings and extract a likely keyword
        $partialQuery = null;
        if (empty($emails) && empty($phones)) {
            $lower = mb_strtolower($text);
            $verbHints = ['najdi','najit','najít','našel','nasel','najdete','najděte','hledej','hledat','hledam','hledám','vyhledej','vyhledat','vyhledejte','vyhledani','vyhledání','dohledej','dohledat'];
            $nounHints = ['kontakt','kontaktu','kontaktem','osobu','osoba','osoby','cloveka','člověka','zakaznika','zákazníka','člověk','clovek','zákazník','zakaznik'];
            $hasIntent = false;
            foreach ($verbHints as $vh) { if (mb_stripos($lower, $vh) !== false) { $hasIntent = true; break; } }
            if (!$hasIntent) { foreach ($nounHints as $nh) { if (mb_stripos($lower, $nh) !== false) { $hasIntent = true; break; } } }

            if ($hasIntent) {
                // 1) Try to extract a term after common verbs/nouns: e.g., "najdi kontakt Novosad"
                if (preg_match('/(?:najdi|najit|najít|našel|nasel|najdete|najděte|hledej|hledat|vyhledej|vyhledat|vyhledani|vyhledání|dohledej|dohledat)(?:\s+(?:kontakt\w*|osob\w*))?\s+([\p{L}0-9._+-]{3,}(?:\s+[\p{L}0-9._+-]{3,})?)/ui', $text, $m)) {
                    $partialQuery = trim($m[1]);
                }
                // 2) Or after noun alone (incl. declensions): "kontakt[u] Novosad"
                if (!$partialQuery && preg_match('/(?:kontakt\w*|osob\w*)\s+([\p{L}0-9._+-]{3,}(?:\s+[\p{L}0-9._+-]{3,})?)/ui', $text, $m2)) {
                    $partialQuery = trim($m2[1]);
                }
                // 3) Or NAME before noun (incl. declensions): "Novosad kontakt[u]"
                if (!$partialQuery && preg_match('/([\p{L}0-9._+-]{3,}(?:\s+[\p{L}0-9._+-]{3,})?)\s+(?:kontakt\w*|osob\w*)/ui', $text, $m3)) {
                    $partialQuery = trim($m3[1]);
                }

                // Cleanup: strip noun keywords at edges and remove stopwords within tokens
                if ($partialQuery) {
                    $pq = trim($partialQuery);
                    $pq = preg_replace('/^(?:kontakt\w*|osob\w*)\s+/ui', '', $pq);
                    $pq = preg_replace('/\s+(?:kontakt\w*|osob\w*)$/ui', '', $pq);
                    $stop = array_merge($verbHints, $nounHints, ['chci','aby','jsi','prosím','prosim','na','pro','se','a','najdes','najdeš']);
                    $parts = preg_split('/\s+/u', $pq);
                    $parts = array_values(array_filter(array_map(function($t){
                        return trim($t, ",.;:!?()[]{}\"'\n\r\t");
                    }, $parts), function($t) use ($stop){
                        return $t !== '' && mb_strlen($t) >= 2 && !in_array(mb_strtolower($t), $stop, true);
                    }));
                    if (!empty($parts)) {
                        // Keep up to two last tokens to allow "Jan Novak"
                        $partialQuery = implode(' ', array_slice($parts, -2));
                    } else {
                        $partialQuery = null;
                    }
                }

                // 4) Fallback: pick last meaningful token
                if (!$partialQuery) {
                    $stop = array_merge($verbHints, $nounHints, ['chci','aby','jsi','prosím','prosim','na','pro','se','a']);
                    $tokens = preg_split('/\s+/u', trim($lower));
                    $cand = null;
                    foreach (array_reverse($tokens) as $t) {
                        $t = trim($t, ",.;:!?()[]{}\"'\n\r\t");
                        if ($t === '' || mb_strlen($t) < 3) continue;
                        if (in_array($t, $stop, true)) continue;
                        $cand = $t; break;
                    }
                    if ($cand) { $partialQuery = $cand; }
                }
            }
        }
        if ($partialQuery) {
            $actions[] = 'search_contact_by_text';
        }

        // Very simple Czech mutation intent detection
        $mutationKeywords = [
            'smaž', 'smazat', 'smaz', 'vymaž', 'vymazat',
            'aktualizuj', 'aktualizovat', 'uprav', 'upravit', 'změň', 'zmenit', 'změnit',
            'nastav', 'nastavit', 'přidej', 'pridat', 'přidat', 'vytvoř', 'vytvorit', 'vytvořit',
        ];
        $requiresConfirmation = false;
        $mutations = [];
        foreach ($mutationKeywords as $kw) {
            if (mb_stripos($text, $kw) !== false) {
                $requiresConfirmation = true;
                $mutations[] = $kw;
            }
        }

        return [
            'entities' => [ 'emails' => $emails, 'phones' => $phones, 'partial' => $partialQuery ? [$partialQuery] : [] ],
            'actions' => $actions,
            'mutations' => $mutations,
            'requires_confirmation' => $requiresConfirmation,
            'notes' => [],
        ];
    }

    protected function extractEmails(string $text): array
    {
        $matches = [];
        preg_match_all('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text, $matches);
        $emails = array_values(array_unique($matches[0] ?? []));
        return $emails;
    }

    protected function extractPhones(string $text): array
    {
        // Very simple phone extraction: matches +420 123 456 789 or 123 456 789, tolerates dashes/spaces
        $matches = [];
        preg_match_all('/(?:(?:\+|00)\d{1,3})?[\s-]?\d{3}[\s-]?\d{3}[\s-]?\d{3,4}/', $text, $matches);
        $phones = array_values(array_unique(array_map(function ($p) {
            return preg_replace('/\s|-/', '', $p);
        }, $matches[0] ?? [])));
        return $phones;
    }
}
