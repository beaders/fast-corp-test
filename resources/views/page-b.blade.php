@extends('layouts.analytics')

@section('title', 'Page B')

@section('content')
    <h1>Page B</h1>
    <section class="card" aria-label="Page B actions">
        <form class="card-body" action="{{ route('actions.download') }}" method="post">
            @csrf
            <button class="primary" type="submit">Download</button>
        </form>
    </section>
@endsection
