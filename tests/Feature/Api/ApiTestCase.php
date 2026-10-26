<?php

namespace Tests\Feature\Api;

use App\Models\ActivityEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-26 12:00:00', 'UTC'));
    }

    protected function createUser(string $role = 'user', array $attributes = []): User
    {
        return User::factory()->create([...$attributes, 'role' => $role]);
    }

    protected function event(?User $user, string $type, string $action, string $at = '2026-10-24 12:00:00'): ActivityEvent
    {
        return ActivityEvent::create([
            'user_id' => $user?->id,
            'type' => $type,
            'action' => $action,
            'occurred_at' => $at,
        ]);
    }

    protected function assertLatestEvent(?User $user, string $type, string $action): void
    {
        $event = ActivityEvent::query()->latest('id')->firstOrFail();

        $this->assertSame($user?->id, $event->user_id);
        $this->assertSame($type, $event->type->value);
        $this->assertSame($action, $event->action->value);
        $this->assertTrue($event->occurred_at->equalTo(now()));
    }
}
