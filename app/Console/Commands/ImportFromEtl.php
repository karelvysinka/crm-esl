<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Contact;
use App\Models\ContactCustomField;
use App\Models\ProductGroup;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportFromEtl extends Command
{
    protected $signature = 'import:etl {--dir=} {--limit=0} {--dry-run}';
    protected $description = 'Load parsed CSVs from tools/import/etl/out into CRM tables idempotently.';

    private function withRetry(callable $fn, int $attempts = 5, int $sleepMs = 200): void
    {
        $try = 0;
        beginning:
        try {
            $fn();
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $retryable = str_contains($msg, 'Lock wait timeout') || str_contains($msg, 'Deadlock found') || str_contains($msg, 'SQLSTATE[40001]') || str_contains($msg, 'SQLSTATE[HY000]: General error: 1205') || str_contains($msg, 'SQLSTATE[40001]: Serialization failure') || str_contains($msg, 'SQLSTATE[1213]');
            if ($retryable && ++$try < $attempts) {
                usleep($sleepMs * 1000);
                $sleepMs = min($sleepMs * 2, 2000);
                goto beginning;
            }
            throw $e;
        }
    }

    public function handle(): int
    {
        $dir = $this->option('dir') ?: base_path('tools/import/etl/out');
        $limit = (int)($this->option('limit') ?: 0);
        $dry = (bool)$this->option('dry-run');

        $this->info('ETL dir: '.$dir);
        if (!is_dir($dir)) { $this->error('Dir not found'); return self::INVALID; }

        // Tuning to reduce timeouts and memory pressure
        DB::disableQueryLog();
        if (!$dry) {
            try {
                DB::statement('SET SESSION innodb_lock_wait_timeout = 50');
                DB::statement('SET SESSION wait_timeout = 28800');
            } catch (Throwable $e) {}
        }

    $summary = [ 'companies'=>0, 'contacts'=>0, 'custom_fields'=>0, 'tags'=>0, 'orders'=>0, 'orders_with_contact'=>0, 'items'=>0 ];

        $tx = function() use ($dir, $limit, $dry, &$summary) {
            // Companies
            $companiesPath = $dir.'/companies.csv';
            if (is_file($companiesPath)) {
                $handle = fopen($companiesPath, 'r');
                $header = fgetcsv($handle);
                $count = 0;
                while (($row = fgetcsv($handle)) !== false) {
                    if ($limit && $count >= $limit) break;
                    $data = array_combine($header, $row);
                    $name = trim($data['name'] ?? '');
                    if ($name === '') continue;
                    if (!$dry) {
                        $this->withRetry(function() use ($name) {
                            Company::firstOrCreate(['name' => $name], ['created_by' => 1]);
                        });
                    }
                    $count++;
                }
                fclose($handle);
                $summary['companies'] = $count;
                $this->info("Companies done: {$count}");
            }

            // Contacts
            $contactsPath = $dir.'/contacts.csv';
            if (is_file($contactsPath)) {
                $handle = fopen($contactsPath, 'r');
                $header = fgetcsv($handle);
                $count = 0;
                while (($row = fgetcsv($handle)) !== false) {
                    if ($limit && $count >= $limit) break;
                    $data = array_combine($header, $row);
                    $legacy = trim($data['legacy_external_id'] ?? '');
                    $email = strtolower(trim($data['email'] ?? ''));
                    $companyName = trim($data['company_name'] ?? '');
                    $company = null;
                    if ($companyName) {
                        $this->withRetry(function() use (&$company, $companyName) {
                            $company = Company::firstOrCreate(['name'=>$companyName], ['created_by'=>1]);
                        });
                    }
                    if ($email === '') {
                        if ($legacy !== '') {
                            $email = 'legacy-'.preg_replace('/[^a-z0-9_-]+/i','', $legacy).'@placeholder.local';
                        } else {
                            $seed = strtolower(($data['first_name'] ?? '').'|'.($data['last_name'] ?? '').'|'.$companyName);
                            $email = 'noemail-'.substr(sha1($seed), 0, 16).'@placeholder.local';
                        }
                    }
                    $attrs = [
                        'first_name' => $data['first_name'] ?? '',
                        'last_name' => $data['last_name'] ?? '',
                        'email' => $email,
                        'phone' => $data['phone'] ?? null,
                        'company_id' => $company?->id,
                        'created_by' => 1,
                    ];
                    if (!$dry) {
                        $this->withRetry(function() use ($legacy, $email, $attrs, $company) {
                            $model = null;
                            if ($legacy) { $model = Contact::firstWhere('legacy_external_id', $legacy); }
                            // Prefer (company_id + email) match when company exists
                            if (!$model && $email && $company) {
                                $model = Contact::where('email', $email)->where('company_id', $company->id)->first();
                            }
                            // Fallback to global email match (may belong to different company)
                            if (!$model && $email) { $model = Contact::firstWhere('email', $email); }
                            if ($model) {
                                // If the found model belongs to another company and we have target $company, don't cross-assign; create a new contact below.
                                if ($company && $model->company_id && $model->company_id !== $company->id) {
                                    $model = null;
                                }
                            }
                            if ($model) {
                                // Avoid unique violation: if another contact in same company has this email, skip updating email
                                $upd = $attrs;
                                if ($email && $company) {
                                    $existsConflict = Contact::where('company_id', $company->id)
                                        ->where('email', $email)
                                        ->where('id', '!=', $model->id)
                                        ->exists();
                                    if ($existsConflict) {
                                        unset($upd['email']);
                                    }
                                }
                                $model->fill($upd);
                                if ($legacy) $model->legacy_external_id = $legacy;
                                $model->save();
                            } else {
                                // If a same (company,email) record exists by the time we create, reuse it
                                if ($email && $company) {
                                    $existing = Contact::where('company_id', $company->id)->where('email', $email)->first();
                                    if ($existing) {
                                        $existing->fill($attrs);
                                        if ($legacy) $existing->legacy_external_id = $legacy;
                                        $existing->save();
                                        return;
                                    }
                                }
                                Contact::create(array_merge($attrs, ['legacy_external_id' => $legacy ?: null]));
                            }
                        });
                    }
                    $count++;
                    if ($count % 5000 === 0) { $this->line("  contacts processed: {$count}"); }
                }
                fclose($handle);
                $summary['contacts'] = $count;
                $this->info("Contacts done: {$count}");
            }

            // Custom fields
            $cfPath = $dir.'/contact_custom_fields.csv';
            if (is_file($cfPath)) {
                $handle = fopen($cfPath, 'r');
                $header = fgetcsv($handle);
                $count = 0;
                while (($row = fgetcsv($handle)) !== false) {
                    if ($limit && $count >= $limit) break;
                    $data = array_combine($header, $row);
                    $legacy = trim($data['legacy_external_id'] ?? '');
                    if ($legacy === '') continue;
                    if (!$dry) {
                        $this->withRetry(function() use ($legacy, $data) {
                            $contact = Contact::firstWhere('legacy_external_id', $legacy);
                            if ($contact) {
                                ContactCustomField::updateOrCreate(
                                    ['contact_id'=>$contact->id, 'key'=>$data['key']],
                                    ['value'=>$data['value']]
                                );
                            }
                        });
                    }
                    $count++;
                }
                fclose($handle);
                $summary['custom_fields'] = $count;
                $this->info("Custom fields done: {$count}");
            }

            // Tags
            $tagsPath = $dir.'/contact_tags.csv';
            if (is_file($tagsPath)) {
                $handle = fopen($tagsPath, 'r');
                $header = fgetcsv($handle);
                $count = 0;
                while (($row = fgetcsv($handle)) !== false) {
                    if ($limit && $count >= $limit) break;
                    $data = array_combine($header, $row);
                    $legacy = trim($data['legacy_external_id'] ?? '');
                    $tagName = trim($data['tag'] ?? '');
                    if ($legacy === '' || $tagName === '') continue;
                    if (!$dry) {
                        $this->withRetry(function() use ($legacy, $tagName) {
                            $contact = Contact::firstWhere('legacy_external_id', $legacy);
                            if ($contact) {
                                $tag = Tag::firstOrCreate(['name'=>$tagName, 'source'=>'crm']);
                                $contact->tags()->syncWithoutDetaching([$tag->id]);
                            }
                        });
                    }
                    $count++;
                }
                fclose($handle);
                $summary['tags'] = $count;
                $this->info("Tags done: {$count}");
            }

            // Product groups
            $pgPath = $dir.'/product_groups.csv';
            if (is_file($pgPath)) {
                $handle = fopen($pgPath, 'r');
                $header = fgetcsv($handle);
                $count = 0;
                while (($row = fgetcsv($handle)) !== false) {
                    $data = array_combine($header, $row);
                    if ($dry) { $count++; continue; }
                    $this->withRetry(function() use ($data) {
                        ProductGroup::updateOrCreate(
                            ['name' => $data['name']],
                            ['code' => $data['code'] ?: null, 'eshop_url' => $data['eshop_url'] ?: null]
                        );
                    });
                    $count++;
                }
                fclose($handle);
                $this->info("Product groups done: {$count}");
            }

            // Orders and items
            $ordersPath = $dir.'/orders.csv';
            if (is_file($ordersPath)) {
                $map = [];
                $handle = fopen($ordersPath, 'r');
                $header = fgetcsv($handle);
                $count = 0;
                while (($row = fgetcsv($handle)) !== false) {
                    if ($limit && $count >= $limit) break;
                    $data = array_combine($header, $row);
                    $ext = trim($data['external_order_no'] ?? '');
                    if ($ext === '') continue;
                    $company = null;
                    if (!empty($data['company_name'])) {
                        $this->withRetry(function() use (&$company, $data) {
                            $company = Company::firstOrCreate(['name'=>$data['company_name']], ['created_by'=>1]);
                        });
                    }
                    // Resolve contact: email + company, or legacy ID, else try by name; create minimal when email present
                    $contactId = null;
                    $rawContact = trim((string)($data['contact_name'] ?? ''));
                    $contactEmail = strtolower(trim((string)($data['contact_email'] ?? '')));
                    $contactLegacy = trim((string)($data['contact_legacy_id'] ?? ''));
                    if ($company) {
                        if ($contactEmail) {
                            $existing = Contact::where('email', $contactEmail)
                                ->where('company_id', $company->id)
                                ->first();
                            if ($existing) {
                                $contactId = $existing->id;
                            } else {
                                // create minimal contact to ensure future linking
                                $created = Contact::create([
                                    'first_name' => $rawContact ?: 'Kontakt',
                                    'last_name' => '',
                                    'email' => $contactEmail,
                                    'phone' => null,
                                    'company_id' => $company->id,
                                    'created_by' => 1,
                                    'legacy_external_id' => $contactLegacy ?: null,
                                ]);
                                $contactId = $created->id;
                            }
                        } elseif ($contactLegacy) {
                            $byLegacy = Contact::firstWhere('legacy_external_id', $contactLegacy);
                            if ($byLegacy) { $contactId = $byLegacy->id; }
                        } elseif ($rawContact !== '') {
                            // normalize by removing common Czech/academic titles and excess spaces
                            $norm = mb_strtolower(trim(preg_replace('/\s+/', ' ', $rawContact)));
                            $norm = preg_replace('/\b(ing\.|bc\.|mgr\.|mga\.|ph\.d\.|phd\.|judr\.|rndr\.|mudr\.|mddr\.|bca\.|dis\.|mba|ll\.m\.|doc\.|prof\.|phdr\.|thdr\.|thlic\.|dr\.|drsc\.)\b/iu', '', $norm);
                            $norm = trim(preg_replace('/\s+/', ' ', $norm));
                            $candidates = Contact::query()
                                ->where('company_id', $company->id)
                                ->where(function($q) use ($norm) {
                                    $q->whereRaw("LOWER(CONCAT(TRIM(first_name),' ',TRIM(last_name))) = ?", [$norm])
                                      ->orWhereRaw("LOWER(CONCAT(TRIM(last_name),' ',TRIM(first_name))) = ?", [$norm]);
                                })
                                ->limit(2)
                                ->get();
                            if ($candidates->count() === 1) {
                                $contactId = $candidates->first()->id;
                            }
                        }
                    }
                    if (!$dry) {
                        $this->withRetry(function() use (&$map, $ext, $company, $data, $contactId, &$summary) {
                            $order = SalesOrder::updateOrCreate(
                                ['external_order_no'=>$ext],
                                [
                                    'company_id' => $company?->id,
                                    'contact_id' => $contactId,
                                    'order_date' => $data['order_date'] ?: null,
                                    'author' => $data['author'] ?: null,
                                    'total_amount' => $data['total_amount'] ?: 0,
                                    'source' => $data['source'] ?: 'helios',
                                    'notes' => $data['notes'] ?: null,
                                ]
                            );
                            $map[$ext] = $order->id;
                            if ($contactId) { $summary['orders_with_contact']++; }
                        });
                    }
                    $count++;
                    if ($count % 1000 === 0) { $this->line("  orders processed: {$count}"); }
                }
                fclose($handle);
                $summary['orders'] = $count;
                $this->info("Orders done: {$count}");

                // Items (clear per order to stay idempotent)
                $itemsPath = $dir.'/order_items.csv';
                if (is_file($itemsPath)) {
                    $cleared = [];
                    $handle = fopen($itemsPath, 'r');
                    $header = fgetcsv($handle);
                    $count = 0;
                    while (($row = fgetcsv($handle)) !== false) {
                        if ($limit && $count >= $limit) break;
                        $data = array_combine($header, $row);
                        $ext = trim($data['external_order_no'] ?? '');
                        if ($ext === '' || !isset($map[$ext])) { $count++; continue; }
                        $orderId = $map[$ext];
                        if (!$dry) {
                            if (!isset($cleared[$orderId])) {
                                $this->withRetry(function() use ($orderId) {
                                    SalesOrderItem::where('sales_order_id', $orderId)->delete();
                                });
                                $cleared[$orderId] = true;
                            }
                            $this->withRetry(function() use ($orderId, $data) {
                                SalesOrderItem::create([
                                    'sales_order_id' => $orderId,
                                    'sku' => $data['sku'] ?: null,
                                    'alt_code' => $data['alt_code'] ?: null,
                                    'name' => $data['name'] ?: '',
                                    'qty' => $data['qty'] ?: 0,
                                    'unit_price' => $data['unit_price'] ?: null,
                                    'discounts_card' => $data['discount_percent'] ?? null,
                                    'product_group' => $data['product_group_code'] ?: null,
                                    'eshop_category_url' => $data['eshop_category_url'] ?: null,
                                    'tax_code' => $data['vat_percent'] ?: null,
                                    'currency' => 'CZK',
                                ]);
                            });
                        }
                        $count++;
                        if ($count % 2000 === 0) { $this->line("  items processed: {$count}"); }
                    }
                    fclose($handle);
                    $summary['items'] = $count;
                    $this->info("Items done: {$count}");
                }
            }
        };

        try {
            if ($dry) {
                DB::beginTransaction();
                $tx();
                DB::rollBack();
            } else {
                $tx();
            }
        } catch (Throwable $e) {
            Log::error('import:etl failed', ['error'=>$e->getMessage()]);
            $this->error('Import failed: '.$e->getMessage());
            return self::FAILURE;
        }

        // write a summary log file
        try {
            $logDir = storage_path('app/import_logs');
            if (!is_dir($logDir)) { @mkdir($logDir, 0777, true); }
            $ts = date('Ymd_His');
            $payload = [
                'timestamp' => $ts,
                'dir' => $dir,
                'dry_run' => $dry,
                'limit' => $limit,
                'summary' => $summary,
            ];
            file_put_contents($logDir."/import_${ts}.json", json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            // ignore file logging errors
        }

        $this->line('Summary: '.json_encode($summary));
        return self::SUCCESS;
    }
}
