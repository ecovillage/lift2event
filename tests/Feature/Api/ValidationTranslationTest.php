<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Confirms validation messages honor the request locale, not that every
 * language reads correctly — one override locale is enough to prove the
 * lang/{locale}/validation.php files are actually being picked up.
 */
class ValidationTranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_field_message_stays_german_by_default(): void
    {
        $this->getJson('/api/geocode/search')
            ->assertUnprocessable()
            ->assertJsonPath('errors.q.0', 'Das Feld Suchbegriff muss ausgefüllt werden.');
    }

    public function test_required_field_message_is_translated_when_locale_is_switched(): void
    {
        $this->getJson('/api/geocode/search', ['X-Locale' => 'en'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.q.0', 'The search term field is required.');
    }

    public function test_custom_attribute_name_is_translated_when_locale_is_switched(): void
    {
        $this->postJson('/api/register', [
            'email'                 => 'not-an-email',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ], ['X-Locale' => 'en'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'The name field is required.')
            ->assertJsonPath('errors.email.0', 'The email address must be a valid email address.');
    }
}
