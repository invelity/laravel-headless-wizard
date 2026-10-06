<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Integration;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Invelity\WizardPackage\Contracts\WizardManagerInterface;
use Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep;
use Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep;
use Invelity\WizardPackage\Tests\TestCase;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use PHPUnit\Framework\Attributes\Test;

final class RouteRegistrationTest extends TestCase
{
    #[Test]
    public function it_registers_the_routes_by_default(): void
    {
        $this->assertTrue(Route::has('wizard.show'));
        $this->assertSame(
            url('wizard/test-wizard/personal-info'),
            route('wizard.show', ['wizard' => 'test-wizard', 'step' => 'personal-info']),
        );
    }

    #[Test]
    #[DefineEnvironment('disableRoutes')]
    public function it_does_not_register_the_routes_when_they_are_disabled(): void
    {
        $this->assertFalse(Route::has('wizard.show'));
        $this->assertFalse(Route::has('wizard.store'));
        $this->assertFalse(Route::has('wizard.completed'));
    }

    #[Test]
    #[DefineEnvironment('disableRoutes')]
    public function navigation_items_have_no_url_when_the_routes_are_disabled(): void
    {
        $manager = $this->app->make(WizardManagerInterface::class);
        $manager->initialize('test-wizard', [
            'steps' => [PersonalInfoStep::class, ContactDetailsStep::class],
        ]);

        $items = $manager->getNavigation()->getItems();

        $this->assertNotEmpty($items);

        foreach ($items as $item) {
            $this->assertNull($item->url);
        }
    }

    #[Test]
    #[DefineEnvironment('useDocumentedRouteKeys')]
    public function it_reads_the_documented_routes_keys(): void
    {
        $this->assertSame(
            url('steps/test-wizard/personal-info'),
            route('wizard.show', ['wizard' => 'test-wizard', 'step' => 'personal-info']),
        );
    }

    #[Test]
    #[DefineEnvironment('useLegacyRouteKeys')]
    public function the_legacy_route_keys_take_precedence(): void
    {
        $this->assertSame(
            url('legacy/test-wizard/personal-info'),
            route('wizard.show', ['wizard' => 'test-wizard', 'step' => 'personal-info']),
        );
        $this->assertSame(['web'], Route::getRoutes()->getByName('wizard.show')?->middleware());
    }

    /**
     * @param  Application  $app
     */
    protected function disableRoutes($app): void
    {
        $app['config']->set('wizard.routes.enabled', false);
    }

    /**
     * @param  Application  $app
     */
    protected function useDocumentedRouteKeys($app): void
    {
        $app['config']->set('wizard.routes.prefix', 'steps');
    }

    /**
     * @param  Application  $app
     */
    protected function useLegacyRouteKeys($app): void
    {
        $app['config']->set('wizard.routes.prefix', 'steps');
        $app['config']->set('wizard.route.prefix', 'legacy');
        $app['config']->set('wizard.route.middleware', ['web']);
    }
}
