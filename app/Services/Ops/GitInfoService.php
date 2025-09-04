<?php

namespace App\Services\Ops;

class GitInfoService
{
    public function currentRevision(): array
    {
        $revisionFile = base_path('REVISION');
        $hash = null; $tag = null;
        if (is_file($revisionFile)) {
            $data = trim(file_get_contents($revisionFile));
            [$hash,$tag] = array_pad(explode(' ', $data, 2), 2, null);
        }
        return [
            'hash' => $hash,
            'tag' => $tag,
            'deployed_at' => $this->guessDeployedAt(),
        ];
    }

    private function guessDeployedAt(): ?string
    {
        $path = base_path('REVISION');
        return is_file($path) ? date('c', filemtime($path)) : null;
    }
}
