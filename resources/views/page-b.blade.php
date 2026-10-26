<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Page B</title>
    </head>
    <body>
        <nav aria-label="Pages">
            <a href="{{ route('page-a') }}">Page A</a>
            <a href="{{ route('page-b') }}" aria-current="page">Page B</a>
        </nav>

        <main>
            <h1>Page B</h1>
            <form action="{{ route('download') }}" method="get">
                <button type="submit">Download</button>
            </form>
        </main>
    </body>
</html>
