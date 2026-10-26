<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Page A</title>
    </head>
    <body>
        @include('partials.navigation')

        <main>
            <h1>Page A</h1>
            <div aria-live="polite">
                <button type="button" id="buy-cow">Buy a cow</button>
                <p id="buy-cow-error" hidden></p>
            </div>
        </main>

        <script>
            document.getElementById('buy-cow').addEventListener('click', async function () {
                if (this.disabled) return;

                const error = document.getElementById('buy-cow-error');
                this.disabled = true;
                error.hidden = true;

                try {
                    const response = await fetch(@json(route('actions.buy-cow')), {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });

                    if (!response.ok) throw new Error('Unable to save the action.');

                    this.replaceWith(document.createTextNode('thankYou'));
                } catch {
                    error.textContent = 'Something went wrong. Please try again.';
                    error.hidden = false;
                    this.disabled = false;
                }
            });
        </script>
    </body>
</html>
