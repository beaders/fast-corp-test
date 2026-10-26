<?php

namespace Tests\Feature\Api;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;

class ReportsTest extends ApiTestCase
{
    private const METRICS = ['page_a', 'page_b', 'buy_cow', 'download'];

    private function row(string $date, int $pageA = 0, int $pageB = 0, int $buyCow = 0, int $download = 0): array
    {
        return ['date' => $date, 'page_a' => $pageA, 'page_b' => $pageB, 'buy_cow' => $buyCow, 'download' => $download];
    }

    private function dailyActivity(User $user, string $date, int $pageA, int $pageB, int $buyCow, int $download): void
    {
        foreach ([['view-page', 'page-a', $pageA], ['view-page', 'page-b', $pageB], ['button-click', 'buy-cow', $buyCow], ['button-click', 'download', $download]] as [$type, $action, $count]) {
            for ($index = 0; $index < $count; $index++) {
                $this->event($user, $type, $action, $date.' 12:00:00');
            }
        }
    }

    public function test_reports_aggregate_all_users_into_four_daily_metrics_with_zero_days(): void
    {
        $user = $this->createUser();
        $other = $this->createUser();
        $this->dailyActivity($user, '2026-10-23', 2, 1, 3, 4);
        $this->dailyActivity($other, '2026-10-23', 1, 4, 1, 3);
        $this->dailyActivity($other, '2026-10-25', 4, 3, 2, 1);
        foreach (['login', 'logout', 'registration'] as $action) {
            $this->event($user, 'authentication', $action, '2020-01-01 12:00:00');
        }
        foreach (['login', 'registration', 'page-stats', 'page-reports'] as $action) {
            $this->event(null, 'view-page', $action, '2030-01-01 12:00:00');
        }
        // Neither an action name nor a type alone is sufficient to count a metric.
        $this->event($user, 'view-page', 'download');
        $this->event($user, 'button-click', 'page-a');
        $this->event(null, 'view-page', 'page-b', '2026-10-25 18:00:00');

        $response = $this->actingAs($user, 'web')->get('/page-reports')->assertOk()->assertViewIs('page-reports');
        $expected = [
            $this->row('2026-10-23', 3, 5, 4, 7),
            $this->row('2026-10-24'),
            $this->row('2026-10-25', 4, 4, 2, 1),
        ];

        $this->assertSame($expected, $response->viewData('days')->all());
        $this->assertSame($expected, $response->viewData('rows')->items());
        $this->assertSame(['page_a' => 7, 'page_b' => 9, 'buy_cow' => 6, 'download' => 8], $response->viewData('totals')->all());
        $this->assertSame(self::METRICS, array_keys($response->viewData('metrics')));
        $this->assertSame(['from' => '2026-10-23', 'to' => '2026-10-25'], $response->viewData('filters'));
        $this->assertLatestEvent($user, 'view-page', 'page-reports');

        $xpath = $this->xpath($response->getContent());
        $headers = $xpath->query('//table/thead/tr/th');
        $this->assertSame(['Date', 'Page view A', 'Page view B', 'Click “Buy a cow”', 'Click “Download”'], array_map(fn ($node) => trim($node->textContent), iterator_to_array($headers)));
        $tableRows = $xpath->query('//table/tbody/tr');
        $this->assertSame(count($expected), $tableRows->length);
        foreach ($tableRows as $index => $tableRow) {
            $cells = array_map(fn ($node) => trim($node->textContent), iterator_to_array($xpath->query('./td', $tableRow)));
            $this->assertSame(array_map('strval', array_values($expected[$index])), $cells);
        }
        $this->assertSame(4, $xpath->query('//svg/polyline')->length);
        // For these counts the displayed axis is 0..10, at y=260..40.
        // Fixed expected coordinates catch series swaps and flat/incorrect plots.
        $chartPoints = [
            'page_a' => '70,194 500,260 930,172',
            'page_b' => '70,150 500,260 930,172',
            'buy_cow' => '70,172 500,260 930,216',
            'download' => '70,106 500,260 930,238',
        ];
        foreach (self::METRICS as $metric) {
            $series = $xpath->query('//svg/polyline[@data-series="'.$metric.'"]');
            $this->assertSame(1, $series->length);
            $this->assertSame($chartPoints[$metric], $series->item(0)->getAttribute('points'));
            $label = $response->viewData('metrics')[$metric]['label'];
            foreach ($expected as $index => $day) {
                $title = $day['date'].' · '.$label.': '.$day[$metric];
                $circle = $xpath->query('//svg/circle[title="'.$title.'"]');
                $this->assertSame(1, $circle->length);
                [$x, $y] = explode(',', explode(' ', $chartPoints[$metric])[$index]);
                $this->assertSame($x, $circle->item(0)->getAttribute('cx'));
                $this->assertSame($y, $circle->item(0)->getAttribute('cy'));
            }
        }
        $response->assertSee('Dates on the horizontal axis and event counts on the vertical axis.');
    }

    public function test_real_http_journey_is_reflected_in_reports_and_admin_statistics(): void
    {
        $admin = $this->createUser('admin');
        $this->postJson('/api/register', [
            'name' => 'Alice', 'email' => 'alice@example.com',
            'password' => 'correct-password', 'password_confirmation' => 'correct-password',
        ])->assertCreated();
        $user = User::query()->where('email', 'alice@example.com')->sole();
        $this->get('/page-a')->assertOk();
        $this->get('/page-a')->assertOk();
        $this->postJson('/api/buy-cow')->assertNoContent();
        $this->get('/page-b')->assertOk();
        $this->post('/api/download')->assertDownload('sample.exe');
        $this->postJson('/api/logout')->assertNoContent();
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'correct-password'])->assertOk();
        $this->get('/page-reports')->assertOk()->assertViewHas('days', fn ($days) => $days->all() === [$this->row('2026-10-26', 2, 1, 1, 1)]);
        $this->postJson('/api/logout')->assertNoContent();

        $this->postJson('/api/login', ['email' => $admin->email, 'password' => 'password'])->assertOk();
        $response = $this->get('/page-stats?user='.$user->id)->assertOk();
        $this->assertSame(
            ['logout', 'page-reports', 'login', 'logout', 'download', 'page-b', 'buy-cow', 'page-a', 'page-a', 'registration'],
            $response->viewData('events')->pluck('action')->map(fn ($action) => $action->value)->all(),
        );
    }

    public function test_report_date_range_is_inclusive_and_excludes_activity_outside_it(): void
    {
        $user = $this->createUser();
        $this->event($user, 'view-page', 'page-a', '2026-10-23 23:59:59.999999');
        $this->event($user, 'view-page', 'page-a', '2026-10-24 00:00:00');
        $this->event($user, 'button-click', 'download', '2026-10-25 23:59:59.999999');
        $this->event($user, 'button-click', 'download', '2026-10-26 00:00:00');

        $response = $this->actingAs($user, 'web')->get('/page-reports?from=2026-10-24&to=2026-10-25')->assertOk();

        $this->assertSame([$this->row('2026-10-24', 1), $this->row('2026-10-25', download: 1)], $response->viewData('days')->all());
        $this->assertSame(['page_a' => 1, 'page_b' => 0, 'buy_cow' => 0, 'download' => 1], $response->viewData('totals')->all());
    }

    public function test_an_empty_database_has_zero_totals_and_an_empty_report(): void
    {
        $response = $this->actingAs($this->createUser(), 'web')->get('/page-reports')->assertOk();

        $this->assertSame([], $response->viewData('days')->all());
        $this->assertSame(0, $response->viewData('rows')->total());
        $this->assertSame(array_fill_keys(self::METRICS, 0), $response->viewData('totals')->all());
        $response->assertSee('No page views or button clicks recorded')->assertSee('No activity to report yet.')->assertDontSee('<svg', false);
        $this->assertDatabaseCount('activity_events', 1);
    }

    public function test_an_explicit_empty_range_renders_zero_rows_and_four_flat_chart_series(): void
    {
        $response = $this->actingAs($this->createUser(), 'web')
            ->get('/page-reports?from=2026-10-24&to=2026-10-25')->assertOk();

        $this->assertSame([$this->row('2026-10-24'), $this->row('2026-10-25')], $response->viewData('days')->all());
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(4, $xpath->query('//svg/polyline')->length);
        foreach ($xpath->query('//svg/polyline') as $series) {
            $this->assertSame('70,260 930,260', $series->getAttribute('points'));
        }
    }

    public function test_one_day_range_has_one_row_and_a_chart_point_for_each_metric(): void
    {
        $user = $this->createUser();
        $this->dailyActivity($user, '2026-10-24', 1, 2, 3, 4);
        $response = $this->actingAs($user, 'web')->get('/page-reports?from=2026-10-24&to=2026-10-24')->assertOk();

        $this->assertSame([$this->row('2026-10-24', 1, 2, 3, 4)], $response->viewData('days')->all());
        $this->assertSame(4, $this->xpath($response->getContent())->query('//svg/circle[@cx="500"]')->length);
    }

    public function test_report_pagination_preserves_dates_and_keeps_full_chart_and_totals_on_every_page(): void
    {
        $user = $this->createUser();
        $this->dailyActivity($user, '2026-09-01', 1, 2, 3, 4);
        $this->dailyActivity($user, '2026-10-10', 4, 3, 2, 1);
        $response = $this->actingAs($user, 'web')->get('/page-reports?from=2026-09-01&to=2026-10-10')->assertOk();
        $rows = $response->viewData('rows');

        $this->assertSame(40, $rows->total());
        $this->assertSame(31, $rows->count());
        parse_str(parse_url($rows->nextPageUrl(), PHP_URL_QUERY), $query);
        $this->assertSame(['from' => '2026-09-01', 'to' => '2026-10-10', 'page' => '2'], $query);
        $next = $this->get($rows->nextPageUrl())->assertOk();
        $this->assertSame(9, $next->viewData('rows')->count());
        $this->assertSame('2026-10-02', $next->viewData('rows')->items()[0]['date']);
        $this->assertSame('2026-10-10', $next->viewData('rows')->items()[8]['date']);
        $this->assertSame($response->viewData('days')->all(), $next->viewData('days')->all());
        $this->assertSame(array_fill_keys(self::METRICS, 5), $next->viewData('totals')->all());
        $this->assertSame(160, $this->xpath($next->getContent())->query('//svg/circle')->length);
    }

    #[DataProvider('implicitRanges')]
    public function test_implicit_ranges_are_limited_to_366_days(array $filters, string $from, string $to, int $pageA, int $downloads): void
    {
        $user = $this->createUser();
        $this->event($user, 'view-page', 'page-a', '2024-01-01 12:00:00');
        $this->event($user, 'button-click', 'download', '2026-10-25 12:00:00');
        $response = $this->actingAs($user, 'web')->get('/page-reports?'.http_build_query($filters))->assertOk();

        $this->assertSame(['from' => $from, 'to' => $to], $response->viewData('filters'));
        $this->assertSame(366, $response->viewData('days')->count());
        $this->assertSame($from, $response->viewData('days')->first()['date']);
        $this->assertSame($to, $response->viewData('days')->last()['date']);
        $this->assertSame(['page_a' => $pageA, 'page_b' => 0, 'buy_cow' => 0, 'download' => $downloads], $response->viewData('totals')->all());
    }

    public static function implicitRanges(): array
    {
        return [
            'default latest year' => [[], '2025-10-25', '2026-10-25', 0, 1],
            'from only starts there' => [['from' => '2024-01-01'], '2024-01-01', '2024-12-31', 1, 0],
            'to only ends there' => [['to' => '2026-10-25'], '2025-10-25', '2026-10-25', 0, 1],
        ];
    }

    public function test_the_maximum_explicit_range_is_accepted_including_leap_day(): void
    {
        $response = $this->actingAs($this->createUser(), 'web')->get('/page-reports?from=2024-01-01&to=2024-12-31')->assertOk();

        $this->assertSame(366, $response->viewData('days')->count());
        $this->assertContains('2024-02-29', $response->viewData('days')->pluck('date')->all());
    }

    #[DataProvider('invalidRanges')]
    public function test_invalid_report_ranges_fail_validation_without_recording_a_page_view(array $filters, string $field): void
    {
        $this->actingAs($this->createUser(), 'web')->getJson('/page-reports?'.http_build_query($filters))
            ->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('activity_events', 0);
    }

    public static function invalidRanges(): array
    {
        return [
            'invalid from' => [['from' => 'invalid'], 'from'],
            'invalid to' => [['to' => '24-10-2026'], 'to'],
            'invalid calendar day' => [['from' => '2026-02-30'], 'from'],
            'array date' => [['from' => ['2026-10-24']], 'from'],
            'reversed range' => [['from' => '2026-10-25', 'to' => '2026-10-24'], 'to'],
            '367 days' => [['from' => '2024-01-01', 'to' => '2025-01-01'], 'to'],
        ];
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return new DOMXPath($document);
    }
}
