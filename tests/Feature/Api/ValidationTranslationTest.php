<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationTranslationTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Standard validation rule messages (validation.required)
    // -------------------------------------------------------------------------

    public function test_required_field_message_stays_german_by_default(): void
    {
        $this->getJson('/api/geocode/search')
            ->assertUnprocessable()
            ->assertJsonPath('errors.q.0', 'Das Feld Suchbegriff muss ausgefüllt werden.');
    }

    public function test_required_field_message_is_translated_to_english(): void
    {
        $this->getJson('/api/geocode/search', ['X-Locale' => 'en'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.q.0', 'The search term field is required.');
    }

    public function test_required_field_message_is_translated_to_french(): void
    {
        $this->getJson('/api/geocode/search', ['X-Locale' => 'fr'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.q.0', 'Le champ terme de recherche est obligatoire.');
    }

    public function test_required_field_message_is_translated_to_chinese(): void
    {
        $this->getJson('/api/geocode/search', ['X-Locale' => 'zh'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.q.0', '搜索词 不能为空。');
    }

    // -------------------------------------------------------------------------
    // Custom attribute names (validation.attributes) combined with a rule
    // -------------------------------------------------------------------------

    public function test_custom_attribute_names_are_translated_to_english(): void
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

    public function test_custom_attribute_names_are_translated_to_french(): void
    {
        $this->postJson('/api/register', [
            'email'                 => 'not-an-email',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ], ['X-Locale' => 'fr'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'Le champ nom est obligatoire.')
            ->assertJsonPath('errors.email.0', 'Le champ adresse e-mail doit être une adresse e-mail valide.');
    }

    public function test_custom_attribute_names_are_translated_to_chinese(): void
    {
        $this->postJson('/api/register', [
            'email'                 => 'not-an-email',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ], ['X-Locale' => 'zh'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', '姓名 不能为空。')
            ->assertJsonPath('errors.email.0', '邮箱地址 必须是一个有效的邮箱地址。');
    }

    public function test_custom_attribute_names_stay_german_by_default(): void
    {
        $this->postJson('/api/register', [
            'email'                 => 'not-an-email',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'Das Feld Name muss ausgefüllt werden.')
            ->assertJsonPath('errors.email.0', 'Das Feld E-Mail-Adresse muss eine gültige E-Mail-Adresse sein.');
    }
}
