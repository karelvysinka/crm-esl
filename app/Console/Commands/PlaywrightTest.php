<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Tools\PlaywrightTool;
use App\Models\SystemSetting;

class PlaywrightTest extends Command
{
    protected $signature = 'tools:playwright:test {url?} {--timeout=20000} {--screenshot=} {--no-robots}';
    protected $description = 'Otestuje Playwright runner fetch volání';

    public function handle(PlaywrightTool $tool): int
    {
        $url = $this->argument('url') ?: 'https://www.esl.cz/';
        $timeout = (int) $this->option('timeout');
        $allowedCsv = (string) SystemSetting::get('tools.playwright.allowed_domains', 'esl.cz');
        $allowed = array_values(array_filter(array_map('trim', explode(',', $allowedCsv))));
        $this->info("Testing fetch: $url (timeout {$timeout} ms)");
        $shotOpt = $this->option('screenshot');
        $opts = [ 'respect_robots' => !$this->option('no-robots') ];
        if ($shotOpt) {
            // allow: true or json string
            if ($shotOpt === 'true' || $shotOpt === '1') { $opts['screenshot'] = ['enabled' => true]; }
            else {
                try { $opts['screenshot'] = ['enabled' => true] + (array) json_decode($shotOpt, true, 512, JSON_THROW_ON_ERROR); }
                catch (\Throwable $e) { $opts['screenshot'] = ['enabled' => true]; }
            }
        }
        $out = $tool->fetch($url, [], $allowed, $timeout, null, null, $opts);
        $this->line(json_encode($out, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        return ($out['ok'] ?? false) ? self::SUCCESS : self::FAILURE;
    }
}
