<?php

namespace Tests\Feature\Api;

use App\Models\ActivityEvent;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\DataProvider;

class StatisticsTest extends ApiTestCase
{
    public function test_statistics_lists_event_details_newest_first_with_a_deterministic_tie_break(): void
    {
        $admin = $this->createUser('admin');
        $user = $this->createUser(attributes: ['name' => 'Alice', 'email' => 'alice@example.com']);
        $older = $this->event($user, 'authentication', 'registration', '2026-10-23 23:59:59');
        $sameTimeFirst = $this->event($user, 'view-page', 'page-a');
        $sameTimeLast = $this->event(null, 'view-page', 'login');

        $response = $this->actingAs($admin, 'web')->get('/page-stats')->assertOk()->assertViewIs('page-stats');
        $events = $response->viewData('events');

        $this->assertInstanceOf(LengthAwarePaginator::class, $events);
        $this->assertSame([ActivityEvent::max('id'), $sameTimeLast->id, $sameTimeFirst->id, $older->id], $events->pluck('id')->all());
        $this->assertSame('Alice', $events->firstWhere('id', $sameTimeFirst->id)->user->name);
        $response->assertSee('alice@example.com')->assertSee('Guest / deleted user')
            ->assertSee('registration')->assertSee('page-a')->assertSee('2026-10-23 23:59:59');
        $this->assertDatabaseCount('activity_events', 4);
        $this->assertLatestEvent($admin, 'view-page', 'page-stats');
    }

    public function test_date_filter_includes_the_whole_day_and_excludes_neighboring_days(): void
    {
        $user = $this->createUser();
        $this->event($user, 'view-page', 'page-a', '2026-10-23 23:59:59.999999');
        $start = $this->event($user, 'view-page', 'page-a', '2026-10-24 00:00:00');
        $end = $this->event($user, 'button-click', 'download', '2026-10-24 23:59:59.999999');
        $this->event($user, 'button-click', 'download', '2026-10-25 00:00:00');

        $response = $this->actingAs($this->createUser('admin'), 'web')->get('/page-stats?date=2026-10-24')->assertOk();

        $this->assertSame([$end->id, $start->id], $response->viewData('events')->pluck('id')->all());
        $this->assertSame(['date' => '2026-10-24'], $response->viewData('filters'));
    }

    #[DataProvider('actions')]
    public function test_each_action_filter_returns_only_matching_events(string $action, array $expectedIndexes): void
    {
        $user = $this->createUser();
        $events = [
            $this->event($user, 'authentication', 'login'),
            $this->event($user, 'authentication', 'logout'),
            $this->event($user, 'authentication', 'registration'),
            $this->event(null, 'view-page', 'login'),
            $this->event(null, 'view-page', 'registration'),
            $this->event($user, 'view-page', 'page-a'),
            $this->event($user, 'view-page', 'page-b'),
            $this->event($user, 'button-click', 'buy-cow'),
            $this->event($user, 'button-click', 'download'),
        ];

        // Constrain the date to keep this request's own statistics view outside the fixture.
        $response = $this->actingAs($this->createUser('admin'), 'web')
            ->get('/page-stats?date=2026-10-24&action='.$action)->assertOk();
        $expectedIds = array_map(fn ($index) => $events[$index]->id, $expectedIndexes);

        $this->assertSame(array_reverse($expectedIds), $response->viewData('events')->pluck('id')->all());
    }

    public static function actions(): array
    {
        return [
            'login excludes login form views' => ['login', [0]],
            'logout' => ['logout', [1]],
            'registration excludes form views' => ['registration', [2]],
            'page views include guest form views' => ['view-page', [3, 4, 5, 6]],
            'both buttons' => ['button-click', [7, 8]],
        ];
    }

    public function test_user_filter_isolates_one_users_events(): void
    {
        $selected = $this->createUser();
        $other = $this->createUser();
        $first = $this->event($selected, 'view-page', 'page-a');
        $second = $this->event($selected, 'button-click', 'buy-cow');
        $this->event($other, 'view-page', 'page-a');
        $this->event(null, 'view-page', 'login');

        $response = $this->actingAs($this->createUser('admin'), 'web')->get('/page-stats?user='.$selected->id)->assertOk();

        $this->assertSame([$second->id, $first->id], $response->viewData('events')->pluck('id')->all());
    }

    public function test_guest_filter_includes_deleted_users_without_losing_their_activity(): void
    {
        $user = $this->createUser();
        $deleted = $this->createUser();
        $guest = $this->event(null, 'view-page', 'login');
        $orphan = $this->event($deleted, 'button-click', 'download');
        $this->event($user, 'view-page', 'page-a');
        $deleted->delete();

        $response = $this->actingAs($this->createUser('admin'), 'web')->get('/page-stats?user=guest')->assertOk();

        $this->assertSame([$orphan->id, $guest->id], $response->viewData('events')->pluck('id')->all());
        $this->assertNull($orphan->fresh()->user_id);
        $response->assertSee('Guest / deleted user');
    }

    public function test_date_user_and_action_filters_are_combined_with_and(): void
    {
        $user = $this->createUser();
        $other = $this->createUser();
        $match = $this->event($user, 'button-click', 'buy-cow');
        $this->event($user, 'view-page', 'page-a');
        $this->event($other, 'button-click', 'buy-cow');
        $this->event($user, 'button-click', 'download', '2026-10-25 12:00:00');

        $response = $this->actingAs($this->createUser('admin'), 'web')
            ->get('/page-stats?'.http_build_query(['date' => '2026-10-24', 'user' => $user->id, 'action' => 'button-click']))->assertOk();

        $this->assertSame([$match->id], $response->viewData('events')->pluck('id')->all());
    }

    public function test_a_filter_with_no_matches_returns_an_empty_table(): void
    {
        $this->event($this->createUser(), 'authentication', 'login');

        $response = $this->actingAs($this->createUser('admin'), 'web')->get('/page-stats?date=2020-01-01')->assertOk();

        $this->assertSame(0, $response->viewData('events')->total());
        $response->assertSee('No events match these filters.');
    }

    public function test_pagination_preserves_all_filters_and_snapshot_when_new_events_arrive(): void
    {
        $admin = $this->createUser('admin');
        $user = $this->createUser();
        $ids = [];
        for ($index = 0; $index < 30; $index++) {
            $ids[] = $this->event($user, 'button-click', 'download')->id;
        }

        $filters = ['date' => '2026-10-24', 'user' => $user->id, 'action' => 'button-click'];
        $response = $this->actingAs($admin, 'web')->get('/page-stats?'.http_build_query($filters))->assertOk();
        $pageOne = $response->viewData('events');
        $this->assertSame(25, $pageOne->count());
        $this->assertSame(30, $pageOne->total());
        parse_str(parse_url($pageOne->nextPageUrl(), PHP_URL_QUERY), $nextQuery);
        foreach ($filters as $key => $value) {
            $this->assertSame((string) $value, $nextQuery[$key]);
        }
        $this->assertSame('2', $nextQuery['page']);
        $this->assertSame((string) ActivityEvent::max('id'), $nextQuery['snapshot']);

        $newEvent = $this->event($user, 'button-click', 'download', '2026-10-24 23:00:00');
        $pageTwo = $this->get($pageOne->nextPageUrl())->assertOk()->viewData('events');

        $this->assertSame(30, $pageTwo->total());
        $this->assertSame(5, $pageTwo->count());
        $combined = [...$pageOne->pluck('id')->all(), ...$pageTwo->pluck('id')->all()];
        $this->assertSame(array_reverse($ids), $combined);
        $this->assertNotContains($newEvent->id, $combined);
        $this->assertSame([], array_intersect($pageOne->pluck('id')->all(), $pageTwo->pluck('id')->all()));
    }

    #[DataProvider('invalidFilters')]
    public function test_invalid_statistics_filters_return_validation_errors_without_recording_a_view(array $filters, string $field): void
    {
        $this->actingAs($this->createUser('admin'), 'web')->getJson('/page-stats?'.http_build_query($filters))
            ->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('activity_events', 0);
    }

    public static function invalidFilters(): array
    {
        return [
            'invalid date format' => [['date' => '24-10-2026'], 'date'],
            'invalid calendar day' => [['date' => '2026-02-30'], 'date'],
            'date array' => [['date' => ['2026-10-24']], 'date'],
            'nonexistent user' => [['user' => 999999], 'user'],
            'noninteger user' => [['user' => 'admin'], 'user'],
            'unsupported action' => [['action' => 'buy-cow'], 'action'],
            'action array' => [['action' => ['login']], 'action'],
            'zero snapshot' => [['snapshot' => 0], 'snapshot'],
            'noninteger snapshot' => [['snapshot' => 'invalid'], 'snapshot'],
        ];
    }
}
