<?php

namespace Tests\Browser;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ClickableElementsAuditTest extends DuskTestCase
{
    private const MAX_SAFE_CLICKS_PER_PAGE = 2;

    public function test_guest_clickable_elements_do_not_crash(): void
    {
        $this->browse(function (Browser $browser) {
            $this->auditPages($browser, [
                'login' => '/login',
                'register' => '/register',
                'forgot password' => '/forgot-password',
                'about' => '/about/page/view',
                'policy' => '/policy/page/view',
                'terms' => '/term/condition/view',
            ]);
        });
    }

    public function test_user_clickable_elements_do_not_crash(): void
    {
        $user = $this->activeUser('dusk-user@example.test', UserRole::General);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user);

            $this->auditPages($browser, [
                'timeline' => '/',
                'pages' => '/pages',
                'groups' => '/groups',
                'events' => '/events',
                'blogs' => '/blogs',
                'videos' => '/videos',
                'marketplace' => '/products',
                'jobs' => '/jobs',
                'notifications' => '/all/notification',
                'user settings' => '/user/settings',
            ]);
        });
    }

    public function test_admin_clickable_elements_do_not_crash(): void
    {
        $admin = $this->activeUser('dusk-admin@example.test', UserRole::Admin);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin);

            $this->auditPages($browser, [
                'admin dashboard' => '/admin/dashboard',
                'admin pages' => '/admin/page',
                'admin groups' => '/admin/group',
                'admin blogs' => '/admin/blog',
                'admin about settings' => '/admin/settings/about',
            ]);
        });
    }

    /**
     * @param  array<string, string>  $pages
     */
    private function auditPages(Browser $browser, array $pages): void
    {
        foreach ($pages as $name => $path) {
            $this->auditPage($browser, $path, $name);
        }
    }

    private function auditPage(Browser $browser, string $path, string $name): void
    {
        $browser->visit($path)->pause(250);
        $this->assertPageDidNotServerError($browser, $name);

        $controls = $this->clickableElements($browser);

        $this->assertNotEmpty($controls, "No clickable controls were found on [{$name}] at [{$path}].");

        foreach ($controls as $control) {
            $this->assertNotSame('', $control['label'], "A clickable control on [{$name}] is missing text, aria-label, title, href, or class fallback.");
        }

        $safeControls = array_slice(
            array_values(array_filter($controls, static fn (array $control): bool => $control['safeToClick'])),
            0,
            self::MAX_SAFE_CLICKS_PER_PAGE
        );

        foreach ($safeControls as $control) {
            $browser->visit($path)->pause(250);
            $this->clickableElements($browser);
            $browser->script($this->clickElementScript((int) $control['index']));
            $browser->pause(250);

            $this->assertPageDidNotServerError(
                $browser,
                "{$name} -> {$control['label']}"
            );
        }
    }

    /**
     * @return list<array{index: int, label: string, safeToClick: bool}>
     */
    private function clickableElements(Browser $browser): array
    {
        $result = $browser->script(<<<'JS'
            return JSON.stringify((() => {
                const candidates = Array.from(document.querySelectorAll([
                    'a[href]',
                    'button',
                    'input[type="button"]',
                    'input[type="submit"]',
                    'input[type="reset"]',
                    '[role="button"]',
                    '[onclick]',
                    '[data-bs-toggle]'
                ].join(',')));

                const visible = (element) => {
                    const rect = element.getBoundingClientRect();
                    const style = window.getComputedStyle(element);

                    return rect.width > 0
                        && rect.height > 0
                        && style.display !== 'none'
                        && style.visibility !== 'hidden'
                        && !element.disabled;
                };

                return candidates.filter(visible).map((element, index) => {
                    element.setAttribute('data-dusk-audit-id', `audit-${index}`);

                    const href = element.getAttribute('href') || '';
                    const type = (element.getAttribute('type') || '').toLowerCase();
                    const tag = element.tagName.toLowerCase();
                    const form = element.closest('form');
                    const formAction = form ? (form.getAttribute('action') || '') : '';
                    const formMethod = form ? (form.getAttribute('method') || '') : '';
                    const className = typeof element.className === 'string' ? element.className : '';
                    const label = [
                        element.innerText,
                        element.value,
                        element.getAttribute('aria-label'),
                        element.getAttribute('title'),
                        href,
                        className,
                        tag
                    ].find((value) => value && String(value).trim().length > 0);
                    const fullUrl = href ? new URL(href, window.location.href) : null;
                    const external = Boolean(fullUrl && fullUrl.origin !== window.location.origin);
                    const anchorWithoutNavigation = tag === 'a' && (!href || href.startsWith('#') || href.startsWith('javascript:'));
                    const submitControl = type === 'submit' || type === 'file';
                    const rawIntent = [href, formAction, formMethod, label, type].join(' ');
                    const destructive = /delete|remove|logout|disable|status|accept|decline|approve|reject|unfriend|unfollow|clear-cache|payment|pay|purchase|install|update|save|create|post|submit|upload/i.test(rawIntent);

                    return {
                        index,
                        label: String(label || '').replace(/\s+/g, ' ').trim().slice(0, 120),
                        safeToClick: !external && !anchorWithoutNavigation && !submitControl && !destructive
                    };
                });
            })());
        JS)[0] ?? '[]';

        $controls = json_decode((string) $result, true, flags: JSON_THROW_ON_ERROR);

        return array_map(
            static fn (array $control): array => [
                'index' => (int) $control['index'],
                'label' => (string) $control['label'],
                'safeToClick' => (bool) $control['safeToClick'],
            ],
            $controls
        );
    }

    private function clickElementScript(int $index): string
    {
        return <<<JS
            const element = document.querySelector('[data-dusk-audit-id="audit-{$index}"]');

            if (element) {
                element.click();
            }
        JS;
    }

    private function assertPageDidNotServerError(Browser $browser, string $context): void
    {
        foreach ([
            'SQLSTATE[',
            'no such table',
            'Illuminate\\Database\\QueryException',
            'Server Error',
            'Internal Server Error',
        ] as $needle) {
            $browser->assertDontSee($needle);
        }

        $this->assertTrue(true, "No server error was rendered for [{$context}].");
    }

    private function activeUser(string $email, UserRole $role): User
    {
        return User::query()->where('email', $email)->first()
            ?? User::factory()->create([
                'name' => $role === UserRole::Admin ? 'Dusk Admin' : 'Dusk User',
                'email' => $email,
                'username' => str_replace(['@', '.'], '-', $email),
                'user_role' => $role->value,
                'status' => UserAccountStatus::Active->value,
                'timezone' => 'UTC',
                'lastActive' => now(),
            ]);
    }
}
