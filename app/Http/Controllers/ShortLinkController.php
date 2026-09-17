<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShortLinkResource;
use App\Jobs\ProcessRedirectCount;
use App\Models\ShortLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ShortLinkController extends Controller
{
    /**
     * Get all short links for current user
     */
    public function index(Request $request)
    {
        $user = auth()->guard()->user();
        
        $shortLinks = ShortLink::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);
        
        return ShortLinkResource::collection($shortLinks);
    }

    /**
     * Create a new short link
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'original_url' => 'required|url|max:2048',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $shortLink = ShortLink::create([
            'user_id' => auth()->guard()->id(),
            'original_url' => $request->original_url,
            'expires_at' => $request->expires_at,
            'clicks' => 0,
        ]);

        return response()->json([
            'message' => 'Short link created',
            ...(new ShortLinkResource($shortLink))->resolve()
        ], 201);
    }

    /**
     * Get a specific short link
     */
    public function show(ShortLink $shortLink)
    {
        $user = auth()->guard()->user();
        
        if ($shortLink->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new ShortLinkResource($shortLink);
    }

    /**
     * Update a short link
     */
    public function update(Request $request, ShortLink $shortLink)
    {
        $user = auth()->guard()->user();
        
        if ($shortLink->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'original_url' => 'sometimes|url|max:2048',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $shortLink->update($request->only(['original_url', 'expires_at']));

        return response()->json([
            'message' => 'Short link updated',
            ...(new ShortLinkResource($shortLink))->resolve()
        ]);
    }

    /**
     * Delete a short link
     */
    public function destroy(ShortLink $shortLink)
    {
        $user = auth()->guard()->user();
        
        if ($shortLink->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $shortLink->delete();

        return response()->json(['message' => 'Short link deleted'], 200);
    }

    /**
     * Bulk delete links
     */
    public function bulkDestroy(Request $request)
    {
        $user = auth()->guard()->user();
        
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:short_links,id,user_id,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $deleted = ShortLink::where('user_id', $user->id)
            ->whereIn('id', $request->ids)
            ->delete();

        return response()->json([
            'message' => "{$deleted} links deleted"
        ]);
    }

    /**
     * Redirect to original URL
     */
    public function redirect($code)
    {
        $cachedResult = Cache::get("code:$code");
        
        if ($cachedResult !== null) {
            if ($cachedResult !== 'not_found' && $cachedResult !== 'link_expired') {
                $this->incrementClickCount($code);
            }
            return $this->handleCachedResult($cachedResult);
        }
        
        try {
            $shortLink = ShortLink::findByCode($code);
            
            if (!$shortLink) {
                return $this->cacheAndReturnNotFound($code);
            }
            
            if ($shortLink->isExpired()) {
                return $this->cacheAndReturnExpired($code);
            }

            $this->incrementClickCount($shortLink->code);
            return $this->cacheAndRedirect($code, $shortLink->original_url);
            
        } catch (\Exception $e) {
            Log::error('Error processing redirect', [
                'code' => $code,
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['message' => 'Internal error'], 500);
        }
    }

    /**
     * Process cached result
     */
    private function handleCachedResult($cachedResult)
    {
        if ($cachedResult === 'not_found') {
            return response()->json(['message' => 'Link not found'], 404);
        }
        
        if ($cachedResult === 'link_expired') {
            return response()->json(['message' => 'Link expired'], 410);
        }
        

        return redirect()->away($cachedResult);
    }

    /**
     * Cache and return 404 error
     */
    private function cacheAndReturnNotFound($code)
    {
        Cache::put("code:$code", 'not_found', now()->addSeconds(config('cache.cache_ttl.short_links')));
        return response()->json(['message' => 'Link not found'], 404);
    }

    /**
     * Cache and return 410 error
     */
    private function cacheAndReturnExpired($code)
    {
        Cache::put("code:$code", 'link_expired', now()->addSeconds(config('cache.cache_ttl.short_links')));
        return response()->json(['message' => 'Link expired'], 410);
    }

    /**
     * Cache and redirect
     */
    private function cacheAndRedirect($code, $url)
    {

        Cache::put("code:$code", $url, now()->addSeconds(config('cache.cache_ttl.short_links')));
        return redirect()->away($url);
    }

    private function incrementClickCount(string $code)
    {
        $clicksCacheKey = "code:$code:clicks";

        if (Cache::has($clicksCacheKey)) {
            Cache::increment($clicksCacheKey);
            return;
        }
        Cache::put($clicksCacheKey, 1, now()->addSeconds(config('cache.cache_ttl.click_counts')));
        ProcessRedirectCount::dispatch($code);
    }

}