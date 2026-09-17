<?php

namespace App\Jobs;

use App\Models\ShortLink;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class ProcessRedirectCount implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $code,
    )
    {
        $this->delay(now()->addSeconds(config('cache.cache_ttl.short_links')));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $clicksCacheKey = "code:$this->code:clicks";
        $cachedCount = Cache::get($clicksCacheKey);
        if ($cachedCount !== null) {
            $shortLink = ShortLink::findByCode($this->code);
            if (!$shortLink) {
                return;
            }
            $shortLink->increment('clicks', $cachedCount);
            Cache::forget($clicksCacheKey);
        }
    }
}
