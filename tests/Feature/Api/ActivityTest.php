<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActivityTest extends ApiTestCase
{
    #[DataProvider('protectedEndpoints')]
    public function test_guests_receive_unauthorized_json_without_activity(string $method, string $uri): void
    {
        $this->json($method, $uri)->assertUnauthorized()->assertJsonStructure(['message']);
        $this->assertDatabaseCount('activity_events', 0);
    }

    public static function protectedEndpoints(): array
    {
        return [
            'me' => ['GET', '/api/me'], 'logout' => ['POST', '/api/logout'],
            'buy' => ['POST', '/api/buy-cow'], 'download' => ['POST', '/api/download'],
            'page A' => ['GET', '/page-a'], 'page B' => ['GET', '/page-b'],
            'statistics' => ['GET', '/page-stats'], 'reports' => ['GET', '/page-reports'],
        ];
    }

    #[DataProvider('apiEndpoints')]
    public function test_guest_api_requests_without_accept_headers_still_receive_json(string $method, string $uri): void
    {
        $this->call($method, $uri)->assertUnauthorized()->assertHeader('Content-Type', 'application/json');
        $this->assertDatabaseCount('activity_events', 0);
    }

    public static function apiEndpoints(): array
    {
        return array_slice(self::protectedEndpoints(), 0, 4, true);
    }

    #[DataProvider('protectedPages')]
    public function test_guest_page_requests_redirect_to_login_without_recording_a_view(string $uri): void
    {
        $this->get($uri)->assertRedirect('/login');
        $this->assertDatabaseCount('activity_events', 0);
    }

    public static function protectedPages(): array
    {
        return [['/'], ['/page-a'], ['/page-b'], ['/page-stats'], ['/page-reports']];
    }

    #[DataProvider('pageAccess')]
    public function test_role_page_access_and_exact_view_activity(string $role, string $uri, string $action, int $status): void
    {
        $user = $this->createUser($role);
        $response = $this->actingAs($user, 'web')->get($uri)->assertStatus($status);

        if ($status === 403) {
            $this->assertDatabaseCount('activity_events', 0);

            return;
        }

        $response->assertViewIs(ltrim($uri, '/'));
        $this->assertDatabaseCount('activity_events', 1);
        $this->assertLatestEvent($user, 'view-page', $action);
    }

    public static function pageAccess(): array
    {
        $cases = [];
        foreach (['user', 'admin'] as $role) {
            foreach (['page-a', 'page-b', 'page-reports', 'page-stats'] as $page) {
                $cases[$role.' '.$page] = [$role, '/'.$page, $page, $role === 'user' && $page === 'page-stats' ? 403 : 200];
            }
        }

        return $cases;
    }

    public function test_the_root_redirect_does_not_record_a_view_and_repeated_page_visits_each_record_one(): void
    {
        $user = $this->createUser();
        $this->actingAs($user, 'web')->get('/')->assertRedirect('/page-a');
        $this->assertDatabaseCount('activity_events', 0);

        $this->get('/page-a')->assertOk();
        $this->get('/page-a')->assertOk();
        $this->assertDatabaseCount('activity_events', 2);
        $this->assertLatestEvent($user, 'view-page', 'page-a');
    }

    #[DataProvider('publicForms')]
    public function test_authentication_form_views_are_guest_page_events(string $uri, string $action): void
    {
        $this->get($uri)->assertOk();
        $this->assertDatabaseCount('activity_events', 1);
        $this->assertLatestEvent(null, 'view-page', $action);
    }

    public static function publicForms(): array
    {
        return [['/login', 'login'], ['/register', 'registration']];
    }

    public function test_page_a_exposes_the_buy_button_and_page_b_posts_to_the_download_api(): void
    {
        $this->actingAs($this->createUser(), 'web')->get('/page-a')
            ->assertOk()->assertSee('id="buy-cow"', false)->assertSee('Buy a cow')
            ->assertSee(json_encode(url('/api/buy-cow')), false)->assertSee('thankYou');

        $this->get('/page-b')->assertOk()->assertSee('Download')
            ->assertSee('action="'.url('/api/download').'" method="post"', false)
            ->assertSee('name="_token"', false);
    }

    #[DataProvider('roles')]
    public function test_buying_a_cow_records_one_click_for_the_session_actor(string $role): void
    {
        $user = $this->createUser($role);
        $other = $this->createUser();

        $this->actingAs($user, 'web')->postJson('/api/buy-cow', [
            'user_id' => $other->id, 'type' => 'authentication', 'action' => 'download',
            'occurred_at' => '2000-01-01 00:00:00',
        ])->assertNoContent();

        $this->assertDatabaseCount('activity_events', 1);
        $this->assertLatestEvent($user, 'button-click', 'buy-cow');
    }

    #[DataProvider('roles')]
    public function test_download_returns_an_executable_attachment_and_records_one_click(string $role): void
    {
        $user = $this->createUser($role);
        $other = $this->createUser();
        $response = $this->actingAs($user, 'web')->post('/api/download', [
            'user_id' => $other->id, 'action' => 'buy-cow', 'occurred_at' => '2000-01-01 00:00:00',
        ]);

        $response->assertOk()->assertDownload('sample.exe')->assertHeader('Content-Type', 'application/octet-stream');
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $file = $response->baseResponse->getFile();
        $this->assertSame(realpath(resource_path('downloads/sample.exe')), $file->getRealPath());
        $this->assertGreaterThan(0, $file->getSize());
        $this->assertSame('MZ', file_get_contents($file->getPathname(), false, null, 0, 2));
        $this->assertDatabaseCount('activity_events', 1);
        $this->assertLatestEvent($user, 'button-click', 'download');
    }

    public static function roles(): array
    {
        return ['user' => ['user'], 'admin' => ['admin']];
    }

    public function test_a_page_visit_and_button_action_are_distinct_and_both_click_types_allow_subsequent_requests(): void
    {
        $user = $this->createUser();
        $this->actingAs($user, 'web')->get('/page-a')->assertOk();
        $this->postJson('/api/buy-cow')->assertNoContent();
        $this->get('/page-b')->assertOk();
        $this->post('/api/download')->assertDownload('sample.exe');
        $this->postJson('/api/buy-cow')->assertNoContent();
        $this->post('/api/download')->assertDownload('sample.exe');

        $this->assertDatabaseCount('activity_events', 6);
        $this->assertDatabaseCount('users', 1);
        $this->assertSame(['page-a', 'buy-cow', 'page-b', 'download', 'buy-cow', 'download'], $user->activityEvents()->orderBy('id')->toBase()->pluck('action')->all());
    }
}
