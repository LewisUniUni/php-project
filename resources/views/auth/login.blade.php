@extends('layouts.app')

@section('content')
<section class="panel login">
    <h1>Sign in to the floor</h1>
    <p class="muted">Warehouse operators receive stock and hold reservations. Planners can look, and cannot change quantities.</p>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label>Email
            <input name="email" type="email" value="{{ old('email') }}" autocomplete="username" required>
        </label>
        <label>Password
            <input name="password" type="password" autocomplete="current-password" required>
        </label>
        <button type="submit">Enter the warehouse</button>
    </form>
</section>
@endsection
