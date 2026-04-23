@extends('admin.layout')

@section('content')
    <section class="hero">
        <span class="status">Frontend shell online</span>
        <h2>{{ $pageTitle }}</h2>
        <p>
            This page is the initial admin shell for the AI Office OS. The layout, navigation, and bootstrap state are in place so later slices can attach live data from the existing admin API without reworking the frontend foundation.
        </p>
    </section>

    <section class="grid">
        <article class="card">
            <h3>Route</h3>
            <p>The current frontend route is ready for page-specific rendering and later data loading.</p>
            <code>{{ request()->path() }}</code>
        </article>

        <article class="card">
            <h3>Bootstrap State</h3>
            <p>A minimal global state object is exposed on <code>window.OfficeAdmin</code> for progressive enhancement.</p>
            <code>{{ '$window.OfficeAdmin.page = '.$page }}</code>
        </article>

        <article class="card">
            <h3>API Readiness</h3>
            <p>Admin API endpoints are predeclared in the shell bootstrap payload for summary and list views.</p>
            <code>{{ $bootstrap['api']['summary'] }}</code>
        </article>

        <article class="card">
            <h3>Next Screens</h3>
            <p>Dashboard, agent registry, task operations, execution visibility, and audit review are scaffolded as separate routes.</p>
        </article>
    </section>
@endsection
