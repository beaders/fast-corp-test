<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Page B</title>
    </head>
    <body>
        @include('partials.navigation')

        <main>
            <h1>Page B</h1>
            <form action="{{ route('actions.download') }}" method="post">
                @csrf
                <button type="submit">Download</button>
            </form>
        </main>
    </body>
</html>
