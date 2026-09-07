<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** La page de login est toujours en arabe RTL (layout guest duré en dur). */
    public function test_login_page_always_arabic_rtl(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('تسجيل الدخول', false);
    }

    /** Le dashboard du directeur affiche les libellés arabes dans la navigation. */
    public function test_directeur_dashboard_renders_arabic_navigation(): void
    {
        $directeur = \App\Models\User::where('email', 'directeur@madrasa.ma')->first();

        $this->actingAs($directeur);
        $this->get(route('dashboard'))
            ->assertOk()
            // Navigation en arabe
            ->assertSee('الطلبة', false)
            ->assertSee('التقارير اليومية', false);
    }
}
