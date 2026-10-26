<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Page A</title>
    </head>
    <body>
        <nav aria-label="Pages">
            <a href="{{ route('page-a') }}" aria-current="page">Page A</a>
            <a href="{{ route('page-b') }}">Page B</a>
        </nav>

        <main>
            <h1>Page A</h1>
            <div aria-live="polite">
                <button type="button" id="buy-cow">Buy a cow</button>
            </div>
        </main>

        <script>
            document.getElementById('buy-cow').addEventListener('click', function () {
                this.replaceWith(document.createTextNode('thankYou'));
            }, { once: true });
        </script>
    </body>
</html>
