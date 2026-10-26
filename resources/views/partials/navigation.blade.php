<nav aria-label="Pages">
    <a href="{{ route('home') }}" @if (request()->routeIs('home')) aria-current="page" @endif>Home</a>
    <a href="{{ route('page-a') }}" @if (request()->routeIs('page-a')) aria-current="page" @endif>Page A</a>
    <a href="{{ route('page-b') }}" @if (request()->routeIs('page-b')) aria-current="page" @endif>Page B</a>
    <form action="{{ route('logout') }}" method="post" style="display: inline">
        @csrf
        <button type="submit">Log out</button>
    </form>
</nav>
