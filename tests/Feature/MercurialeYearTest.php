<?php

namespace Tests\Feature;

use App\Models\MercurialeArticle;
use App\Models\MercurialeFamille;
use App\Models\MercurialeRubrique;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Mercuriale\MercurialeYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MercurialeYearTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_activate_imported_year(): void
    {
        $this->seedArticle(2026);
        $this->seedArticle(2027, '01-001-999999');

        $admin = User::factory()->create([
            'is_platform_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.mercuriale.activate'), ['year' => 2027])
            ->assertRedirect();

        $this->assertSame(2027, app(MercurialeYearService::class)->active());
    }

    public function test_catalogue_filters_by_year(): void
    {
        $this->seedArticle(2026, '01-001-111111');
        $this->seedArticle(2027, '01-001-222222');
        app(MercurialeYearService::class)->setActive(2027);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant = Tenant::query()->create([
            'name' => 'Org',
            'plan' => 'free',
            'currency' => 'XAF',
            'is_active' => true,
            'onboarding_completed' => true,
        ]);
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $user->forceFill(['current_tenant_id' => $tenant->id])->save();

        $this->actingAs($user)
            ->get(route('app.catalogue.index', ['year' => 2027]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('App/Catalogue/Index')
                ->where('filters.year', 2027)
                ->where('stats.total', 1));
    }

    public function test_admin_can_import_year_from_upload(): void
    {
        Storage::fake('local');

        MercurialeRubrique::query()->create([
            'code' => '01',
            'name' => 'Test',
            'articles_count' => 0,
        ]);

        $admin = User::factory()->create([
            'is_platform_admin' => true,
            'email_verified_at' => now(),
        ]);

        $content = "01-001-123456 Adaptateur HDMI 1 UNITE 12 500\n";
        $file = UploadedFile::fake()->createWithContent('m2027.txt', $content);

        $this->actingAs($admin)
            ->post(route('admin.mercuriale.import'), [
                'year' => 2027,
                'fresh' => true,
                'activate' => true,
                'source' => $file,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('mercuriale_articles', [
            'reference' => '01-001-123456',
            'budget_year' => 2027,
        ]);
        $this->assertSame(2027, app(MercurialeYearService::class)->active());
    }

    private function seedArticle(int $year, string $reference = '01-001-000001'): void
    {
        $rubrique = MercurialeRubrique::query()->firstOrCreate(
            ['code' => '01'],
            ['name' => 'Test', 'articles_count' => 0],
        );
        $famille = MercurialeFamille::query()->firstOrCreate(
            ['rubrique_id' => $rubrique->id, 'code' => '001'],
            ['name' => 'Famille', 'articles_count' => 0],
        );

        MercurialeArticle::query()->create([
            'rubrique_id' => $rubrique->id,
            'famille_id' => $famille->id,
            'reference' => $reference,
            'article_code' => substr($reference, -6),
            'designation' => 'Article '.$reference,
            'unit' => 'UNITE',
            'quantity' => 1,
            'unit_price' => 1000,
            'budget_year' => $year,
            'source' => 'test',
        ]);
    }
}
