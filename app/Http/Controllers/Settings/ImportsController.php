<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;

class ImportsController extends Controller
{
    public function index()
    {
        $dir = storage_path('app/import_logs');
        $logs = [];
        if (is_dir($dir)) {
            $files = collect(File::files($dir))
                ->filter(fn($f) => strtolower($f->getExtension()) === 'json')
                ->sortByDesc(fn($f) => $f->getMTime())
                ->take(100);
            foreach ($files as $f) {
                $content = @file_get_contents($f->getPathname());
                $json = $content ? json_decode($content, true) : null;
                $logs[] = [
                    'file' => $f->getFilename(),
                    'mtime' => $f->getMTime(),
                    'size' => $f->getSize(),
                    'summary' => is_array($json) ? ($json['summary'] ?? $json['counts'] ?? $json) : null,
                    'path' => $f->getPathname(),
                ];
            }
        }
        return view('crm.settings.imports', compact('logs'));
    }
}
