<?php

namespace Modules\SkyOps\Tests;

use App\Models\User;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\SkyOps\Helpers\FinanceLinkHelper;
use PHPUnit\Framework\TestCase;

/**
 * Finance Hub tab: appears automatically when DynamicFares is active and the user may open its finance page.
 * Needs DynamicFares enabled in the test install (route dynamicfares.frontend.finance).
 */
class FinanceLinkTest extends TestCase
{
    private User $pilot;

    protected function setUp(): void
    {
        parent::setUp();
        $name = (string) DB::connection()->getDatabaseName();
        if (!($name === 'phpvms7' || str_contains($name, 'test') || str_contains($name, 'sandbox'))) {
            $this->fail("Database '$name' is not a test database.");
        }
        if (!Route::has(FinanceLinkHelper::ROUTE)) {
            $this->markTestSkipped('DynamicFares is not enabled in this install.');
        }
        DB::beginTransaction();
        view()->getFinder()->prependNamespace('skyops', dirname(__DIR__) . '/Resources/views');
        config()->set('skyops.features.show_finance_link', false);
        config()->set('skyops.features.finance_link_auto', true);

        $pilot = User::query()->whereDoesntHave('roles')->first();
        if (!$pilot) {
            $this->markTestSkipped('No user without roles in the test database.');
        }
        $this->pilot = $pilot;
    }

    protected function tearDown(): void
    {
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        auth()->forgetGuards();
        parent::tearDown();
    }

    private function setFinanceForAll(bool $on): void
    {
        if (!Schema::hasColumn('dynamic_fares_settings', 'frontend_finance_enabled')) {
            $this->markTestSkipped('DynamicFares settings column missing.');
        }
        $q = DB::table('dynamic_fares_settings')->whereNull('airline_id');
        if ($q->exists()) {
            $q->update(['frontend_finance_enabled' => $on]);
        } else {
            DB::table('dynamic_fares_settings')->insert(['airline_id' => null, 'frontend_finance_enabled' => $on]);
        }
    }

    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first();
        if (!$admin) {
            $this->markTestSkipped('No admin user in the test database.');
        }

        return $admin;
    }

    public function test_shown_automatically_for_pilots_when_finance_page_is_open_to_all(): void
    {
        $this->setFinanceForAll(true);
        $this->assertTrue(FinanceLinkHelper::visible($this->pilot));
    }

    public function test_missing_settings_row_counts_as_open_like_dynamicfares(): void
    {
        $this->setFinanceForAll(false);
        DB::table('dynamic_fares_settings')->whereNull('airline_id')->delete();
        $this->assertTrue(FinanceLinkHelper::visible($this->pilot));
    }

    public function test_hidden_for_pilots_but_shown_for_admins_when_finance_page_is_staff_only(): void
    {
        $this->setFinanceForAll(false);
        $this->assertFalse(FinanceLinkHelper::visible($this->pilot));
        $this->assertTrue(FinanceLinkHelper::visible($this->admin()));
    }

    public function test_hidden_for_guests(): void
    {
        $this->setFinanceForAll(true);
        $this->assertFalse(FinanceLinkHelper::visible(null));
    }

    public function test_auto_off_hides_and_manual_switch_forces(): void
    {
        $this->setFinanceForAll(true);
        config()->set('skyops.features.finance_link_auto', false);
        $this->assertFalse(FinanceLinkHelper::visible($this->pilot));

        config()->set('skyops.features.show_finance_link', true);
        $this->assertTrue(FinanceLinkHelper::visible($this->pilot));
    }

    public function test_env_strings_are_understood(): void
    {
        $this->setFinanceForAll(false);
        config()->set('skyops.features.finance_link_auto', 'false');
        config()->set('skyops.features.show_finance_link', '1');
        $this->assertTrue(FinanceLinkHelper::visible($this->pilot));
        config()->set('skyops.features.show_finance_link', 'false');
        $this->assertFalse(FinanceLinkHelper::visible($this->pilot));
    }

    public function test_empty_auto_value_keeps_detection_on(): void
    {
        $this->setFinanceForAll(true);
        config()->set('skyops.features.finance_link_auto', '');
        $this->assertTrue(FinanceLinkHelper::visible($this->pilot));
        config()->set('skyops.features.finance_link_auto', null);
        $this->assertTrue(FinanceLinkHelper::visible($this->pilot));
    }

    public function test_old_published_config_without_auto_key_still_detects(): void
    {
        $this->setFinanceForAll(true);
        $features = config('skyops.features');
        unset($features['finance_link_auto']);
        config()->set('skyops.features', $features);
        $this->assertTrue(FinanceLinkHelper::visible($this->pilot));
    }

    public function test_hidden_when_dynamicfares_is_not_installed(): void
    {
        $this->setFinanceForAll(true);
        $router = app('router');
        $original = $router->getRoutes();
        try {
            $router->setRoutes(new RouteCollection());
            $this->assertFalse(FinanceLinkHelper::visible($this->pilot));
        } finally {
            $router->setRoutes($original);
        }
    }

    public function test_skyops_page_shows_tab_through_the_router(): void
    {
        $this->setFinanceForAll(true);
        $html = $this->getPage($this->pilot);
        $this->assertStringContainsString('href="' . route(FinanceLinkHelper::ROUTE) . '"', $html);
        $this->assertStringContainsString(__('skyops::skyops.finance'), $html);
    }

    public function test_skyops_page_hides_tab_for_pilots_when_staff_only(): void
    {
        $this->setFinanceForAll(false);
        $html = $this->getPage($this->pilot);
        $this->assertStringContainsString(route('skyops.fleet'), $html, 'page did not render the SkyOps navigation');
        $this->assertStringNotContainsString('href="' . route(FinanceLinkHelper::ROUTE) . '"', $html);
    }

    private function getPage(User $user): string
    {
        auth()->guard('web')->setUser($user);
        $response = app(HttpKernel::class)->handle(Request::create(route('skyops.airlines'), 'GET'));
        $this->assertSame(200, $response->getStatusCode(), 'SkyOps page did not return 200');

        return (string) $response->getContent();
    }
}
