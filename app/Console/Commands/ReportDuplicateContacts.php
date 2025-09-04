<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Contact;

class ReportDuplicateContacts extends Command
{
    protected $signature = 'contacts:report-duplicates {--limit=1000} {--company_id=}';
    protected $description = 'Vypíše přehled duplicitních kontaktů podle ac_id, normalized_email a normalized_phone';

    public function handle(): int
    {
        $limit = (int)$this->option('limit');

        $this->info('Duplicitní podle ac_id:');
        Contact::query()
            ->select('ac_id')
            ->whereNotNull('ac_id')
            ->groupBy('ac_id')
            ->havingRaw('COUNT(*) > 1')
            ->limit($limit)
            ->pluck('ac_id')
            ->each(function($ac){ $this->line(" - ac_id: {$ac}"); });

        $companyId = $this->option('company_id');
        if ($companyId) {
            $this->info('Duplicitní podle normalized_email (v rámci zadané společnosti):');
            $q = Contact::query()
                ->select('company_id','normalized_email', DB::raw('COUNT(*) as cnt'))
                ->where('company_id', $companyId)
                ->whereNotNull('normalized_email')
                ->groupBy('company_id','normalized_email')
                ->having('cnt', '>', 1)
                ->limit($limit)
                ->get();
            $q->each(function($row){
                $this->line(" - company={$row->company_id} email={$row->normalized_email} (cnt={$row->cnt})");
            });
        } else {
            $this->info('Duplicitní podle normalized_email (globálně):');
            Contact::query()
                ->select('normalized_email', DB::raw('COUNT(*) as cnt'))
                ->whereNotNull('normalized_email')
                ->groupBy('normalized_email')
                ->having('cnt', '>', 1)
                ->limit($limit)
                ->get()
                ->each(function($row){
                    $this->line(" - email={$row->normalized_email} (cnt={$row->cnt})");
                });
        }

        $this->info('Duplicitní podle normalized_phone (min. délka 9 znaků):');
        $q2 = Contact::query()
            ->select('company_id','normalized_phone', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('normalized_phone')
            ->whereRaw('CHAR_LENGTH(normalized_phone) >= 9');
        if ($companyId) { $q2->where('company_id', $companyId); }
        $q2->groupBy('company_id','normalized_phone')->having('cnt', '>', 1)->limit($limit)->get()
            ->each(function($row){ $this->line(" - company={$row->company_id} phone={$row->normalized_phone} (cnt={$row->cnt})"); });

        return 0;
    }
}
