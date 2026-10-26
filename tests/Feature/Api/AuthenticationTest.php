<?php

namespace Tests\Feature\Api;

use App\Models\ActivityEvent;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;

class AuthenticationTest extends ApiTestCase
{
    private function registration(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Alice Example',
            'email' => 'alice@example.com',
            'password' => 'correct-password',
            'password_confirmation' => 'correct-password',
        ], $overrides);
    }

    public function test_registration_normalizes_email_ignores_role_escalation_and_starts_a_session(): void
    {
        $response = $this->postJson('/api/register', $this->registration([
            'email' => '  ALICE@EXAMPLE.COM  ',
            'role' => 'admin',
            'user_id' => 999,
            'occurred_at' => '2000-01-01 00:00:00',
        ]));

        $user = User::query()->sole();
        $expected = ['user' => [
            'id' => $user->id, 'name' => 'Alice Example', 'email' => 'alice@example.com', 'role' => 'user',
        ]];

        $response->assertCreated()->assertExactJson($expected);
        $this->assertNotSame('correct-password', $user->password);
        $this->assertTrue(Hash::check('correct-password', $user->password));
        $this->assertAuthenticatedAs($user, 'web');
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/me')->assertOk()->assertExactJson($expected);
        $this->assertDatabaseCount('activity_events', 1);
        $this->assertLatestEvent($user, 'authentication', 'registration');
    }

    #[DataProvider('invalidRegistrations')]
    public function test_invalid_registration_has_no_user_or_activity_side_effects(array $input, string $field): void
    {
        $this->postJson('/api/register', $this->registration($input))
            ->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertGuest('web');
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('activity_events', 0);
    }

    public static function invalidRegistrations(): array
    {
        return [
            'missing name' => [['name' => null], 'name'],
            'blank name' => [['name' => '   '], 'name'],
            'non-string name' => [['name' => ['Alice']], 'name'],
            'long name' => [['name' => str_repeat('a', 256)], 'name'],
            'missing email' => [['email' => null], 'email'],
            'invalid email' => [['email' => 'invalid'], 'email'],
            'non-string email' => [['email' => ['alice@example.com']], 'email'],
            'long email' => [['email' => str_repeat('a', 245).'@example.com'], 'email'],
            'missing password' => [['password' => null], 'password'],
            'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
            'non-string password' => [['password' => ['password']], 'password'],
            'missing confirmation' => [['password_confirmation' => null], 'password'],
            'mismatched confirmation' => [['password_confirmation' => 'different-password'], 'password'],
            'over bcrypt byte limit' => [['password' => str_repeat('a', 73), 'password_confirmation' => str_repeat('a', 73)], 'password'],
            'multibyte over bcrypt limit' => [['password' => str_repeat('ž', 37), 'password_confirmation' => str_repeat('ž', 37)], 'password'],
            'null byte' => [['password' => "password\0value", 'password_confirmation' => "password\0value"], 'password'],
        ];
    }

    #[DataProvider('validPasswords')]
    public function test_registration_accepts_password_length_boundaries(string $password): void
    {
        $this->postJson('/api/register', $this->registration([
            'password' => $password, 'password_confirmation' => $password,
        ]))->assertCreated();

        $this->assertTrue(Hash::check($password, User::query()->sole()->password));
    }

    public static function validPasswords(): array
    {
        return ['minimum' => ['12345678'], 'maximum bytes' => [str_repeat('a', 72)], 'multibyte maximum' => [str_repeat('ž', 36)]];
    }

    public function test_registration_rejects_a_normalized_duplicate_email(): void
    {
        $existing = $this->createUser(attributes: ['email' => 'alice@example.com']);

        $this->postJson('/api/register', $this->registration(['email' => ' ALICE@EXAMPLE.COM ']))
            ->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($existing->name, $existing->fresh()->name);
        $this->assertDatabaseCount('activity_events', 0);
        $this->assertGuest('web');
    }

    #[DataProvider('roles')]
    public function test_login_authenticates_each_role_and_records_only_the_authenticated_actor(string $role): void
    {
        $user = $this->createUser($role, ['email' => 'alice@example.com']);
        $other = $this->createUser();

        $response = $this->postJson('/api/login', [
            'email' => ' ALICE@EXAMPLE.COM ', 'password' => 'password',
            'user_id' => $other->id, 'role' => 'admin', 'action' => 'registration',
        ]);

        $response->assertOk()->assertExactJson(['user' => $user->only(['id', 'name', 'email', 'role'])]);
        $this->assertAuthenticatedAs($user, 'web');
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/me')->assertOk()->assertExactJson($response->json());
        $this->assertDatabaseCount('activity_events', 1);
        $this->assertLatestEvent($user, 'authentication', 'login');
    }

    public static function roles(): array
    {
        return ['user' => ['user'], 'admin' => ['admin']];
    }

    #[DataProvider('invalidLogins')]
    public function test_invalid_login_does_not_authenticate_or_record_an_event(array $credentials, string $field): void
    {
        $this->createUser(attributes: ['email' => 'alice@example.com']);

        $this->postJson('/api/login', $credentials)->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertGuest('web');
        $this->assertDatabaseCount('activity_events', 0);
    }

    public static function invalidLogins(): array
    {
        return [
            'empty request' => [[], 'email'],
            'missing password' => [['email' => 'alice@example.com'], 'password'],
            'invalid email' => [['email' => 'invalid', 'password' => 'password'], 'email'],
            'non-string email' => [['email' => [], 'password' => 'password'], 'email'],
            'non-string password' => [['email' => 'alice@example.com', 'password' => []], 'password'],
            'wrong password' => [['email' => 'alice@example.com', 'password' => 'wrong-password'], 'email'],
            'unknown account' => [['email' => 'missing@example.com', 'password' => 'password'], 'email'],
        ];
    }

    public function test_registration_logout_login_logout_journey_revokes_access_and_tracks_every_transition(): void
    {
        $this->postJson('/api/register', $this->registration())->assertCreated();
        $user = User::query()->sole();
        $this->getJson('/api/me')->assertOk()->assertJsonPath('user.id', $user->id);
        $this->withSession(['private-marker' => 'secret']);
        $token = session()->token();

        $this->postJson('/api/logout')->assertNoContent()->assertSessionMissing('private-marker');
        $this->assertGuest('web');
        $this->assertNotSame($token, session()->token());
        $this->getJson('/api/me')->assertUnauthorized();
        $this->postJson('/api/logout')->assertUnauthorized();
        $this->postJson('/api/buy-cow')->assertUnauthorized();
        $this->get('/page-a')->assertRedirect('/login');

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'correct-password'])->assertOk();
        $this->getJson('/api/me')->assertOk()->assertJsonPath('user.id', $user->id);
        $this->postJson('/api/logout')->assertNoContent();

        $this->assertSame(['registration', 'logout', 'login', 'logout'], ActivityEvent::query()->orderBy('id')->toBase()->pluck('action')->all());
        $this->assertSame([$user->id], ActivityEvent::query()->distinct()->pluck('user_id')->all());
        $this->assertSame(['authentication'], ActivityEvent::query()->distinct()->toBase()->pluck('type')->all());
        $this->assertLatestEvent($user, 'authentication', 'logout');
    }

    #[DataProvider('csrfEndpoints')]
    public function test_mutating_endpoints_reject_missing_and_invalid_csrf_tokens(string $uri): void
    {
        $user = $this->createUser();
        $this->actingAs($user, 'web');
        $this->enableCsrfVerification();
        $this->withSession(['_token' => 'valid-token']);

        $this->postJson($uri, $this->registration())->assertStatus(419);
        $this->postJson($uri, $this->registration(), ['X-CSRF-TOKEN' => 'wrong-token'])->assertStatus(419);

        $this->assertDatabaseCount('activity_events', 0);
        $this->assertDatabaseCount('users', 1);
        $this->assertAuthenticatedAs($user, 'web');
    }

    #[DataProvider('csrfEndpoints')]
    public function test_mutating_endpoints_accept_a_matching_csrf_token(string $uri): void
    {
        $user = $this->createUser(attributes: ['email' => 'existing@example.com']);
        if (in_array($uri, ['/api/logout', '/api/buy-cow', '/api/download'], true)) {
            $this->actingAs($user, 'web');
        }
        $this->enableCsrfVerification();
        $this->withSession(['_token' => 'valid-token']);
        $payload = $uri === '/api/login'
            ? ['email' => $user->email, 'password' => 'password']
            : $this->registration();

        $response = $this->postJson($uri, $payload, ['X-CSRF-TOKEN' => 'valid-token']);
        $response->assertStatus(match ($uri) {
            '/api/register' => 201,
            '/api/logout', '/api/buy-cow' => 204,
            default => 200,
        });
        $this->assertDatabaseCount('activity_events', 1);
    }

    private function enableCsrfVerification(): void
    {
        // Keep the real middleware; override only Laravel's test-environment bypass.
        $this->app->instance(PreventRequestForgery::class, new class($this->app, $this->app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
    }

    public static function csrfEndpoints(): array
    {
        return array_combine(
            ['register', 'login', 'logout', 'buy-cow', 'download'],
            array_map(fn ($path) => ['/api/'.$path], ['register', 'login', 'logout', 'buy-cow', 'download']),
        );
    }

    #[DataProvider('rateLimitedEndpoints')]
    public function test_authentication_endpoints_limit_requests_and_recover_after_a_minute(string $uri): void
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->postJson($uri, [])->assertUnprocessable();
        }

        $this->postJson($uri, [])->assertStatus(429)->assertHeader('Retry-After');
        $this->travel(61)->seconds();
        $this->postJson($uri, [])->assertUnprocessable();
        $this->assertDatabaseCount('activity_events', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public static function rateLimitedEndpoints(): array
    {
        return ['login' => ['/api/login'], 'register' => ['/api/register']];
    }
}
