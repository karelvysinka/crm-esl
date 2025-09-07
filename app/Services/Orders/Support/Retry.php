<?php
namespace App\Services\Orders\Support;

use Closure;

class Retry
{
    /**
     * @template T
     * @param Closure(int $attempt):T $callback
     * @param int $attempts
     * @param int $baseDelayMs
     * @param callable(\Throwable,int):bool|null $shouldRetry returns true to retry
     * @return mixed
     */
    public static function run(Closure $callback, int $attempts = 3, int $baseDelayMs = 150, ?callable $shouldRetry = null)
    {
        $shouldRetry = $shouldRetry ?? function(\Throwable $e, int $attempt){ return $attempt < ($attempts-1); };
        $last = null;
        for ($i=0; $i<$attempts; $i++) {
            try { return $callback($i); } catch (\Throwable $e) {
                $last = $e;
                if (!$shouldRetry($e,$i)) { throw $e; }
                usleep(($baseDelayMs * (2 ** $i) + random_int(10,60)) * 1000);
            }
        }
        throw $last ?: new \RuntimeException('Retry failed');
    }
}
