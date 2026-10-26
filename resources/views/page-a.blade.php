@extends('layouts.analytics')

@section('title', 'Page A')

@section('content')
    <h1>Page A</h1>
    <section class="card" aria-label="Page A actions">
        <div class="card-body" aria-live="polite">
            <button class="primary" type="button" id="buy-cow">Buy a cow</button>
            <p class="errors" id="buy-cow-error" hidden></p>
        </div>
    </section>
@endsection

@push('scripts')
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
@endpush
