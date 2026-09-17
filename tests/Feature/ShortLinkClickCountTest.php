<?php

namespace Tests\Feature;

use App\Models\ShortLink;
use App\Models\User;
use App\Jobs\ProcessRedirectCount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class ShortLinkClickCountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.cache_ttl.short_links' => 10]);
        config(['cache.cache_ttl.click_counts' => 60]);

        Cache::flush();
    }

    public function test_first_click_creates_cache_and_dispatches_job()
    {
        Bus::fake();
        
        $shortLink = ShortLink::factory()->create([
            'code' => 'test123',
            'original_url' => 'https://example.com',
            'clicks' => 0
        ]);

        // Primeiro clique VIA ROTA
        $response = $this->get("/{$shortLink->code}");
        
        // Verifica redirect
        $response->assertRedirect($shortLink->original_url);
        
        // Verifica se a URL foi salva no cache
        $this->assertEquals($shortLink->original_url, Cache::get("code:{$shortLink->code}"));

        // Verifica se o contador de cliques foi criado no cache
        $this->assertEquals(1, Cache::get("code:{$shortLink->code}:clicks"));

        // Verifica se job foi despachado
        Bus::assertDispatched(ProcessRedirectCount::class, function ($job) use ($shortLink) {
            return $job->code === $shortLink->code;
        });
    }

    public function test_multiple_clicks_increment_cache_but_not_database_immediately()
    {
        Bus::fake();
        
        $shortLink = ShortLink::factory()->create([
            'code' => 'test123',
            'original_url' => 'https://example.com',
            'clicks' => 0
        ]);

        // Três cliques
        $this->get("/{$shortLink->code}");
        $this->get("/{$shortLink->code}");
        $this->get("/{$shortLink->code}");
        
        // Job foi despachado apenas uma vez (no primeiro clique)
        Bus::assertDispatchedTimes(ProcessRedirectCount::class, 1);
        
        // Cache de cliques deve ser 3
        $this->assertEquals(3, Cache::get("code:{$shortLink->code}:clicks"));

        // Banco ainda tem 0 (não foi processado ainda)
        $this->assertEquals(0, $shortLink->fresh()->clicks);
    }

    public function test_job_execution_updates_database_accurately()
    {
        // Não usamos Bus::fake() aqui porque queremos instanciar e rodar o job manualmente
        
        $shortLink = ShortLink::factory()->create([
            'code' => 'testjob',
            'original_url' => 'https://example.com',
            'clicks' => 5
        ]);

        // Simula 3 cliques novos no cache
        Cache::put("code:{$shortLink->code}:clicks", 3);
        
        // Executa o job manualmente
        $job = new ProcessRedirectCount($shortLink->code);
        $job->handle();
        
        // Verifica se o banco foi atualizado (5 + 3 = 8)
        $this->assertEquals(8, $shortLink->fresh()->clicks);
        
        // Verifica se o cache foi limpo após o processamento
        $this->assertNull(Cache::get("code:{$shortLink->code}:clicks"));
    }

    public function test_redirect_works_with_expired_link_and_caches_status()
    {
        Bus::fake();
        
        $shortLink = ShortLink::factory()->create([
            'code' => 'expired',
            'original_url' => 'https://example.com',
            'expires_at' => now()->subDay(),
            'clicks' => 0
        ]);

        // Primeiro hit: deve buscar no banco, cachear 'link_expired'
        $response = $this->get("/{$shortLink->code}");
        
        $response->assertStatus(410);
        $this->assertEquals('link_expired', Cache::get("code:{$shortLink->code}"));
        
        // NÃO deve despachar job para links expirados
        Bus::assertNotDispatched(ProcessRedirectCount::class);

        // Segundo hit: deve usar cache 'link_expired'
        $this->get("/{$shortLink->code}");
        
        // NÃO deve ter criado contador de cliques no cache
        $this->assertNull(Cache::get("code:{$shortLink->code}:clicks"));
        
        // Total de jobs disparados deve ser 0
        Bus::assertNotDispatched(ProcessRedirectCount::class);
    }

    public function test_redirect_returns_404_and_caches_not_found()
    {
        Bus::fake();
        $code = 'invalid123';
        
        // Primeiro hit: deve buscar no banco (falhar), cachear 'not_found'
        $response = $this->get("/{$code}");
        
        $response->assertStatus(404);
        $this->assertEquals('not_found', Cache::get("code:{$code}"));
        
        // NÃO deve despachar job para links inexistentes
        Bus::assertNotDispatched(ProcessRedirectCount::class);
        
        // NÃO deve ter criado contador de cliques no cache
        $this->assertNull(Cache::get("code:$code:clicks"));
    }

    public function test_cache_is_separate_per_shortlink()
    {
        Bus::fake();
        
        $shortLink1 = ShortLink::factory()->create(['code' => 'link1']);
        $shortLink2 = ShortLink::factory()->create(['code' => 'link2']);
        
        $this->get("/{$shortLink1->code}");
        $this->get("/{$shortLink1->code}");
        $this->get("/{$shortLink2->code}");
        
        $this->assertEquals(2, Cache::get("code:{$shortLink1->code}:clicks"));
        $this->assertEquals(1, Cache::get("code:{$shortLink2->code}:clicks"));
        
        Bus::assertDispatchedTimes(ProcessRedirectCount::class, 2);
    }

    public function test_job_handles_nonexistent_link_gracefully()
    {
        $job = new ProcessRedirectCount('nonexistent');
        
        // O job não deve lançar exceção se o link não existir
        $job->handle();
        
        $this->assertTrue(true);
    }
}