<?php
namespace App\Services\Ops;

use Illuminate\Support\Facades\Log;

class AlertService
{
    protected ?string $webhook;

    public function __construct(?string $webhook = null)
    {
        $this->webhook = $webhook ?? env('ALERT_SLACK_WEBHOOK');
    }

    public function enabled(): bool
    {
        return (bool) $this->webhook;
    }

    public function send(string $message, array $context = []): void
    {
        if (!$this->enabled()) return;
        $payload = [
            'text' => $message.(empty($context)?'':' ```'.substr(json_encode($context, JSON_UNESCAPED_UNICODE),0,1800).'```')
        ];
        try {
            $ch = curl_init($this->webhook);
            curl_setopt_array($ch,[
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
            ]);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Throwable $e) {
            Log::channel('ops')->warning('Alert send failed',['err'=>$e->getMessage()]);
        }
    }

    public function maybeStaleDbDumpAlert(callable $statusResolver): void
    {
        if (!config('ops.alerts_enabled')) return;
        try {
            $status = $statusResolver();
            if (($status['status'] ?? null) === 'OK') return;
            if (in_array($status['status'] ?? '', ['STALE','FAIL'])) {
                $this->send('DB dump '.$status['status'], [
                    'age_minutes'=>$status['age_minutes'] ?? null,
                    'file'=>$status['file'] ?? null,
                ]);
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
