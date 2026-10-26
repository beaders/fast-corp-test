<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title') | Fast Corporation</title>
        <style>
            * { box-sizing: border-box; }
            body { margin: 0; padding: 2rem 1rem; min-height: 100vh; display: grid; place-items: center; background: #f5f5f4; color: #1c1917; font-family: system-ui, sans-serif; line-height: 1.5; }
            main { width: 100%; max-width: 26rem; padding: 2rem; border: 1px solid #e7e5e4; border-radius: 0.75rem; background: white; }
            h1 { margin: 0 0 1.5rem; font-size: 1.75rem; }
            form { display: grid; gap: 1rem; }
            label { display: block; margin-bottom: 0.35rem; font-weight: 600; }
            input, button { width: 100%; padding: 0.7rem 0.8rem; border: 1px solid #a8a29e; border-radius: 0.35rem; font: inherit; }
            input:focus-visible, button:focus-visible, a:focus-visible { outline: 3px solid #2563eb; outline-offset: 3px; }
            input[aria-invalid="true"] { border-color: #b91c1c; }
            button { margin-top: 0.5rem; border-color: #1c1917; background: #1c1917; color: white; font-weight: 600; cursor: pointer; }
            button:hover { background: #44403c; }
            a { color: #1d4ed8; }
            .error { margin: 0.35rem 0 0; color: #b91c1c; font-size: 0.875rem; }
            .hint, .switch { color: #57534e; font-size: 0.875rem; }
            .hint { margin: 0.35rem 0 0; }
            .switch { margin: 1.5rem 0 0; }
        </style>
    </head>
    <body>
        <main>
            <h1>@yield('title')</h1>
            @yield('content')
        </main>
    </body>
</html>
