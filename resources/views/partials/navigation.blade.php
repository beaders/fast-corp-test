<nav aria-label="Pages">
    <a href="{{ route('page-a') }}" @if (request()->routeIs('page-a')) aria-current="page" @endif>Page A</a>
    <a href="{{ route('page-b') }}" @if (request()->routeIs('page-b')) aria-current="page" @endif>Page B</a>
    @if (auth('web')->user()?->role === 'admin')
        <a href="{{ route('page-stats') }}" @if (request()->routeIs('page-stats')) aria-current="page" @endif>Statistics</a>
    @endif
    <a href="{{ route('page-reports') }}" @if (request()->routeIs('page-reports')) aria-current="page" @endif>Reports</a>
    <form action="{{ route('logout') }}" method="post" style="display: inline">
        @csrf
        <button type="submit">Log out</button>
    </form>
</nav>
