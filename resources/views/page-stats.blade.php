@extends('layouts.analytics')

@section('title', 'Statistics')

@section('content')
    <h1>Statistics</h1>
    <p class="subtitle">Explore recorded events by date, user, and action. All timestamps are in {{ config('app.timezone') }}.</p>

    @if ($errors->any())
        <div class="errors" role="alert">
            Please check the selected filters.
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="card" aria-label="Filter events">
        <form class="filters" method="get" action="{{ route('page-stats') }}">
            <div class="field">
                <label for="date">Date</label>
                <input type="date" id="date" name="date" value="{{ old('date', $filters['date'] ?? '') }}">
            </div>
            <div class="field">
                <label for="user">User</label>
                <select id="user" name="user">
                    <option value="">All users</option>
                    <option value="guest" @selected(old('user', $filters['user'] ?? '') === 'guest')>Guest / deleted user</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) old('user', $filters['user'] ?? '') === (string) $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="action">Action</label>
                <select id="action" name="action">
                    <option value="">All actions</option>
                    @foreach (['login' => 'Login', 'logout' => 'Logout', 'registration' => 'Registration', 'view-page' => 'View page', 'button-click' => 'Button click'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('action', $filters['action'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-actions">
                <button class="primary" type="submit">Apply filters</button>
                <a class="button" href="{{ route('page-stats') }}">Reset</a>
            </div>
        </form>
    </section>

    <section class="card" aria-labelledby="events-heading">
        <div class="card-header">
            <h2 id="events-heading">Events</h2>
            <span class="muted">{{ number_format($events->total()) }} {{ Str::plural('event', $events->total()) }}</span>
        </div>
        <div class="table-scroll">
            <table>
                <caption>Most recent events first</caption>
                <thead>
                    <tr>
                        <th scope="col">Date and time ({{ config('app.timezone') }})</th>
                        <th scope="col">User</th>
                        <th scope="col">Action</th>
                        <th scope="col">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td><time datetime="{{ $event->occurred_at->toIso8601String() }}">{{ $event->occurred_at->format('Y-m-d H:i:s') }}</time></td>
                            <td>
                                {{ $event->user?->name ?? 'Guest / deleted user' }}
                                @if ($event->user)
                                    <small>{{ $event->user->email }}</small>
                                @endif
                            </td>
                            <td><span class="badge">{{ $event->type === \App\Enums\ActivityEventType::Authentication ? $event->action->value : $event->type->value }}</span></td>
                            <td>{{ $event->action->value }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">No events match these filters. Try another date, user, or action.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials.analytics-pagination', ['paginator' => $events, 'label' => 'events'])
    </section>
@endsection
