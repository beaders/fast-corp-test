<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title') · Fast Corporation</title>
        <style>
            :root { color-scheme: light; font-family: system-ui, sans-serif; color: #172033; background: #f6f7fb; }
            * { box-sizing: border-box; }
            body { margin: 0; }
            a { color: #2459c4; text-underline-offset: 3px; }
            nav[aria-label="Pages"] { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 24px; padding: 16px max(24px, calc((100vw - 1200px) / 2)); background: white; border-bottom: 1px solid #e1e5ed; }
            nav[aria-label="Pages"] > a { padding: 8px 0; color: #536079; text-decoration: none; }
            nav[aria-label="Pages"] > a[aria-current] { color: #2459c4; font-weight: 650; text-decoration: underline; }
            nav[aria-label="Pages"] form { margin-left: auto; }
            main { max-width: 1248px; margin: 0 auto; padding: 36px 24px 56px; }
            h1 { margin: 0 0 8px; font-size: 30px; letter-spacing: -1px; }
            h2 { margin: 0; font-size: 18px; }
            p { line-height: 1.6; }
            .subtitle { margin: 0 0 28px; color: #59657a; }
            .card { margin: 20px 0; background: white; border: 1px solid #dfe4ed; border-radius: 12px; overflow: hidden; }
            .card-body { padding: 24px; }
            .card-header { padding: 20px 24px; border-bottom: 1px solid #e8ebf1; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }
            .filters { display: flex; flex-wrap: wrap; align-items: end; gap: 16px; padding: 24px; }
            .field { display: flex; flex-direction: column; gap: 8px; min-width: 170px; flex: 1; }
            label { font-size: 14px; font-weight: 600; }
            input, select, button, .button { font: inherit; }
            input, select { width: 100%; min-height: 42px; padding: 8px 12px; border: 1px solid #bcc5d4; border-radius: 6px; background: white; color: inherit; }
            button, .button { display: inline-flex; justify-content: center; align-items: center; min-height: 42px; padding: 8px 16px; border: 1px solid #bcc5d4; border-radius: 6px; background: white; color: #172033; cursor: pointer; text-decoration: none; }
            .primary { background: #2459c4; color: white; border-color: #2459c4; }
            button:hover, .button:hover { filter: brightness(.96); }
            :focus-visible { outline: 3px solid #8db4ff; outline-offset: 3px; }
            .filter-actions { display: flex; align-items: center; gap: 12px; }
            .muted, small { color: #59657a; }
            small { display: block; margin-top: 4px; }
            .table-scroll { overflow-x: auto; }
            table { border-collapse: collapse; width: 100%; text-align: left; font-size: 14px; }
            caption { text-align: left; padding: 16px 24px; color: #59657a; }
            th, td { padding: 16px 24px; border-bottom: 1px solid #e8ebf1; }
            th { background: #f9fafc; font-size: 12px; font-weight: 650; color: #536079; white-space: nowrap; }
            tbody tr:last-child td { border-bottom: none; }
            .numeric { text-align: right; font-variant-numeric: tabular-nums; }
            time { white-space: nowrap; font-variant-numeric: tabular-nums; }
            .badge { display: inline-block; padding: 4px 9px; border-radius: 5px; background: #edf2fc; color: #31528d; font-size: 12px; white-space: nowrap; }
            .empty { text-align: center; padding: 48px 24px; color: #59657a; }
            .pagination { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; padding: 18px 24px; border-top: 1px solid #e8ebf1; font-size: 14px; }
            .pagination-links { display: flex; gap: 10px; }
            .pagination .disabled { color: #8b94a5; background: #f6f7fb; }
            .errors { padding: 16px 24px; border: 1px solid #edbdc2; border-radius: 8px; color: #9b2030; background: #fff5f5; }
            .errors ul { margin: 8px 0 0; padding-left: 20px; }
            .summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
            .summary-item { padding: 20px; border: 1px solid #dfe4ed; border-radius: 10px; background: white; }
            .summary-item p { margin: 0; font-size: 14px; color: #59657a; }
            .summary-item strong { display: block; margin-top: 8px; font-size: 28px; font-variant-numeric: tabular-nums; }
            .chart { padding: 24px; }
            .chart svg { display: block; width: 100%; height: auto; }
            .chart-legend { display: flex; flex-wrap: wrap; gap: 12px 24px; margin: 0 0 20px; padding: 0; list-style: none; font-size: 13px; }
            .swatch { display: inline-block; width: 10px; height: 10px; margin-right: 7px; border-radius: 50%; }
            @media (max-width: 700px) {
                main { padding: 24px 16px; }
                nav[aria-label="Pages"] { padding: 12px 16px; gap: 8px 16px; }
                .summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .filters, .card-header, .card-body, .chart { padding: 16px; }
                th, td { padding: 14px 16px; }
                .filter-actions { width: 100%; }
                .chart-plot { overflow-x: auto; }
                .chart svg { min-width: 900px; }
            }
        </style>
    </head>
    <body>
        @include('partials.navigation')

        <main>
            @yield('content')
        </main>

        @stack('scripts')
    </body>
</html>
