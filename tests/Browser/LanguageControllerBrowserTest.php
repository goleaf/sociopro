<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Language;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LanguageControllerBrowserTest extends DuskTestCase
{
    private const ADMIN_EMAIL = 'dusk-language-admin@example.test';

    private const ORIGINAL_LANGUAGE = 'Dusk Browser Language';

    private const CREATED_LANGUAGE = 'Dusk Browser Created';

    private const RENAMED_LANGUAGE = 'dusk browser renamed';

    private const PLAIN_PHRASE = 'Dusk Browser Plain Phrase';

    private const PLACEHOLDER_PHRASE = '____ Dusk Browser Placeholder Phrase';

    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteFixtures();
    }

    protected function tearDown(): void
    {
        $this->deleteFixtures();

        parent::tearDown();
    }

    public function test_admin_language_buttons_and_phrase_updates_work_in_browser(): void
    {
        $admin = $this->activeAdmin();
        $plain = $this->language(self::ORIGINAL_LANGUAGE, self::PLAIN_PHRASE, 'Old browser plain translation');
        $placeholder = $this->language(self::ORIGINAL_LANGUAGE, self::PLACEHOLDER_PHRASE, 'Old ____ browser placeholder translation');

        $this->browse(function (Browser $browser) use ($admin, $plain, $placeholder) {
            $browser->loginAs($admin)
                ->visitRoute('admin.language.settings')
                ->assertSee('Languages')
                ->assertSee(self::ORIGINAL_LANGUAGE)
                ->press('Add new language')
                ->waitFor('#createModal.show', 5);

            $browser->within('#createModal', function (Browser $modal) {
                $modal->type('language', self::CREATED_LANGUAGE)
                    ->press('Add');
            });

            $browser->waitForText(self::CREATED_LANGUAGE, 5);

            $this->postForm(
                $browser,
                route('admin.languages.update', self::ORIGINAL_LANGUAGE, false),
                ['language' => self::RENAMED_LANGUAGE],
                'languageRenameResponse',
                self::RENAMED_LANGUAGE
            );

            $browser->visitRoute('admin.languages.edit.phrase', self::RENAMED_LANGUAGE)
                ->assertSee(self::PLAIN_PHRASE)
                ->assertSee(self::PLACEHOLDER_PHRASE);

            $this->clickPhraseUpdateButton($browser, $plain->id, 'Browser plain translation updated');
            $this->clickPhraseUpdateButton($browser, $placeholder->id, '____ Browser placeholder translation updated');

            $this->postForm(
                $browser,
                route('admin.languages.update.phrase', $placeholder->id, false),
                ['translated' => 'Browser placeholder removed'],
                'languageInvalidPlaceholderResponse',
                null
            );
        });

        $this->assertDatabaseHas('languages', [
            'name' => self::CREATED_LANGUAGE,
            'phrase' => self::CREATED_LANGUAGE,
            'translated' => self::CREATED_LANGUAGE,
        ]);
        $this->assertDatabaseHas('languages', [
            'name' => self::RENAMED_LANGUAGE,
            'phrase' => self::PLAIN_PHRASE,
            'translated' => 'Browser plain translation updated',
        ]);
        $this->assertDatabaseHas('languages', [
            'name' => self::RENAMED_LANGUAGE,
            'phrase' => self::PLACEHOLDER_PHRASE,
            'translated' => '____ Browser placeholder translation updated',
        ]);
        $this->assertDatabaseMissing('languages', [
            'phrase' => self::PLACEHOLDER_PHRASE,
            'translated' => 'Browser placeholder removed',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postForm(Browser $browser, string $url, array $payload, string $windowKey, ?string $expectedText): void
    {
        $encodedUrl = json_encode($url, JSON_THROW_ON_ERROR);
        $encodedPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $encodedWindowKey = json_encode($windowKey, JSON_THROW_ON_ERROR);

        $browser->script(<<<JS
            window[{$encodedWindowKey}] = null;
            const payload = {$encodedPayload};
            const params = new URLSearchParams();

            Object.entries(payload).forEach(([key, value]) => {
                params.append(key, value ?? '');
            });

            const token = document.querySelector('meta[name="csrf_token"], meta[name="csrf-token"]')?.content;

            fetch({$encodedUrl}, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'text/html,application/json',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-CSRF-TOKEN': token,
                },
                body: params,
            }).then(async (response) => {
                window[{$encodedWindowKey}] = {
                    status: response.status,
                    text: await response.text(),
                };
            }).catch((error) => {
                window[{$encodedWindowKey}] = {
                    status: -1,
                    text: String(error),
                };
            });
        JS);

        $browser->waitUntil("window[{$encodedWindowKey}] !== null && window[{$encodedWindowKey}].status === 200", 5);

        if ($expectedText !== null) {
            $encodedExpectedText = json_encode($expectedText, JSON_THROW_ON_ERROR);

            $browser->waitUntil("window[{$encodedWindowKey}].text.includes({$encodedExpectedText})", 5);
        }
    }

    private function clickPhraseUpdateButton(Browser $browser, int $phraseId, string $translated): void
    {
        $encodedTranslated = json_encode($translated, JSON_THROW_ON_ERROR);

        $browser->script(<<<JS
            document.querySelector('#phrase{$phraseId}').value = {$encodedTranslated};
            document.querySelector('#btn{$phraseId}').click();
        JS);

        $browser->waitUntil("document.querySelector('#btn{$phraseId}').textContent.includes('Updated')", 5);
    }

    private function activeAdmin(): User
    {
        $user = User::query()->where('email', self::ADMIN_EMAIL)->first() ?? new User;
        $user->forceFill([
            'name' => 'Dusk Language Admin',
            'email' => self::ADMIN_EMAIL,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'email_verified_at' => now(),
            'username' => 'dusk-language-admin',
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'user_role' => UserRole::Admin->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
        ]);
        $user->save();

        return $user;
    }

    private function language(string $name, string $phrase, string $translated): Language
    {
        $language = Language::query()
            ->where('name', $name)
            ->where('phrase', $phrase)
            ->first() ?? new Language;
        $language->forceFill([
            'name' => $name,
            'phrase' => $phrase,
            'translated' => $translated,
        ]);
        $language->save();

        return $language;
    }

    private function deleteFixtures(): void
    {
        User::query()->where('email', self::ADMIN_EMAIL)->delete();

        Language::query()
            ->whereIn('name', [
                self::ORIGINAL_LANGUAGE,
                self::CREATED_LANGUAGE,
                self::RENAMED_LANGUAGE,
            ])
            ->orWhereIn('phrase', [
                self::CREATED_LANGUAGE,
                self::PLAIN_PHRASE,
                self::PLACEHOLDER_PHRASE,
            ])
            ->delete();
    }
}
