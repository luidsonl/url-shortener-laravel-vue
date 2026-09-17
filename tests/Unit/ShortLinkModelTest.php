<?php

namespace Tests\Unit\Models;

use App\Models\ShortLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Hashids\Hashids;
use Illuminate\Support\Facades\DB;

class ShortLinkModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('DELETE FROM sqlite_sequence WHERE name="short_links"');
        }
    }
    
    public function test_that_it_creates_short_link_with_factory()
    {
        $shortLink = ShortLink::factory()->create();

        $this->assertInstanceOf(ShortLink::class, $shortLink);
        $this->assertNotNull($shortLink->user_id);
        $this->assertNotNull($shortLink->original_url);
        $this->assertNotNull($shortLink->code);
        $this->assertIsString($shortLink->code);
        $this->assertLessThanOrEqual(8, strlen($shortLink->code));
    }

    public function test_that_it_stores_fields_correctly()
    {
        $url = 'https://example.com/test';
        
        $shortLink = ShortLink::factory()->create([
            'original_url' => $url,
            'clicks' => 42,
        ]);

        $this->assertEquals($url, $shortLink->original_url);
        $this->assertEquals(42, $shortLink->clicks);
    }

    public function test_code_is_generated_automatically_when_empty()
    {
        $shortLink = ShortLink::factory()->create([
            'code' => null,
        ]);

        $this->assertNotNull($shortLink->code);
        $this->assertNotEmpty($shortLink->code);
        
        $hashids = new Hashids(
            config('hashids.connections.short_links.salt', config('hashids.salt')),
            config('hashids.connections.short_links.length', 6)
        );
        $decoded = $hashids->decode($shortLink->code);
        
        $this->assertNotEmpty($decoded);
        $this->assertEquals($shortLink->id, $decoded[0]);
    }

    public function test_code_is_not_overwritten_when_provided()
    {
        $customCode = 'custom123';
        
        $shortLink = ShortLink::factory()->create([
            'code' => $customCode,
        ]);

        $this->assertEquals($customCode, $shortLink->code);
    }

    public function test_short_url_attribute()
    {
        $shortLink = ShortLink::factory()->create();

        $expectedUrl = url('/' . $shortLink->code);
        $this->assertEquals($expectedUrl, $shortLink->short_url);
    }

    public function test_that_it_belongs_to_user()
    {
        $user = User::factory()->create();
        $shortLink = ShortLink::factory()->forUser($user)->create();

        $this->assertInstanceOf(User::class, $shortLink->user);
        $this->assertEquals($user->id, $shortLink->user->id);
    }

    public function test_code_is_unique()
    {
        $code = 'abc123';
        
        ShortLink::factory()->create(['code' => $code]);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        ShortLink::factory()->create(['code' => $code]);
    }

    public function test_that_it_creates_popular_short_link()
    {
        $shortLink = ShortLink::factory()->popular()->create();

        $this->assertGreaterThanOrEqual(1000, $shortLink->clicks);
        $this->assertLessThanOrEqual(10000, $shortLink->clicks);
    }

    public function test_that_it_creates_expired_short_link()
    {
        $shortLink = ShortLink::factory()->expired()->create();

        $this->assertNotNull($shortLink->expires_at);
        $this->assertTrue($shortLink->expires_at->isPast());
    }

    public function test_that_it_creates_permanent_short_link()
    {
        $shortLink = ShortLink::factory()->permanent()->create();

        $this->assertNull($shortLink->expires_at);
    }

    public function test_that_it_creates_short_link_with_specific_code()
    {
        $specificCode = 'mycode123';
        
        $shortLink = ShortLink::factory()
            ->withCode($specificCode)
            ->create();

        $this->assertEquals($specificCode, $shortLink->code);
    }

    public function test_that_it_increments_clicks()
    {
        $shortLink = ShortLink::factory()->create(['clicks' => 5]);
        
        $shortLink->increment('clicks');
        
        $this->assertEquals(6, $shortLink->fresh()->clicks);
    }

    public function test_find_by_code_method()
    {
        $shortLink = ShortLink::factory()->create();
        
        $found = ShortLink::findByCode($shortLink->code);
        
        $this->assertInstanceOf(ShortLink::class, $found);
        $this->assertEquals($shortLink->id, $found->id);
        $this->assertEquals($shortLink->code, $found->code);
    }

    public function test_find_by_code_returns_null_for_invalid_code()
    {
        $found = ShortLink::findByCode('nonexistent123');
        
        $this->assertNull($found);
    }

    public function test_is_expired_method()
    {
        $expiredLink = ShortLink::factory()->expired()->create();
        $this->assertTrue($expiredLink->isExpired());
        
        $permanentLink = ShortLink::factory()->permanent()->create();
        $this->assertFalse($permanentLink->isExpired());
        
        $futureLink = ShortLink::factory()->create([
            'expires_at' => now()->addDays(7),
        ]);
        $this->assertFalse($futureLink->isExpired());
    }

    public function test_is_valid_method()
    {
        $expiredLink = ShortLink::factory()->expired()->create();
        $this->assertFalse($expiredLink->isValid());
        
        $permanentLink = ShortLink::factory()->permanent()->create();
        $this->assertTrue($permanentLink->isValid());
        
        $futureLink = ShortLink::factory()->create([
            'expires_at' => now()->addDays(7),
        ]);
        $this->assertTrue($futureLink->isValid());
    }

    public function test_fillable_attributes()
    {
        $shortLink = new ShortLink();

        $this->assertEquals([
            'user_id',
            'original_url',
            'code',
            'clicks',
            'expires_at',
        ], $shortLink->getFillable());
    }

    public function test_casts_attributes()
    {
        $shortLink = new ShortLink();
        $casts = $shortLink->getCasts();

        $this->assertArrayHasKey('expires_at', $casts);
        $this->assertEquals('datetime', $casts['expires_at']);
    }

    public function test_code_collation_is_case_sensitive()
    {
        $lowerCode = 'abc123';
        $upperCode = 'ABC123';
        
        ShortLink::factory()->create(['code' => $lowerCode]);
        
        $upperLink = ShortLink::factory()->create(['code' => $upperCode]);
        
        $this->assertNotEquals($lowerCode, $upperLink->code);
        $this->assertEquals($upperCode, $upperLink->code);
    }

    public function test_generate_code_from_id_method()
    {
        $shortLink = ShortLink::factory()->create();
        $originalCode = $shortLink->code;
        
        $newId = 999;
        $shortLink->id = $newId;
        $result = $shortLink->generateCodeFromId();
        
        $this->assertInstanceOf(ShortLink::class, $result);
        $this->assertNotEquals($originalCode, $shortLink->code);
        
        $hashids = new Hashids(
            config('hashids.connections.short_links.salt', config('hashids.salt')),
            config('hashids.connections.short_links.length', 6)
        );
        $decoded = $hashids->decode($shortLink->code);
        
        $this->assertEquals($newId, $decoded[0]);
    }
}