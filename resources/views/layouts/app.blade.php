<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Quay' }}</title>
    <link rel="stylesheet" href="{{ asset('css/quay.css') }}">
</head>
<body>
    <header class="topbar">
        <a class="brand" href="{{ route('warehouse.stock') }}">Quay</a>
        @auth
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <span>{{ auth()->user()->name }} · {{ auth()->user()->warehouse_code }}</span>
                <button class="secondary" type="submit">Sign out</button>
            </form>
        @endauth
    </header>
    @auth
        <nav class="nav">
            <a href="{{ route('warehouse.stock') }}" @if (request()->routeIs('warehouse.stock')) aria-current="page" @endif>Stock</a>
            <a href="{{ route('warehouse.deliveries') }}" @if (request()->routeIs('warehouse.deliveries')) aria-current="page" @endif>Deliveries</a>
            <a href="{{ route('warehouse.scans') }}" @if (request()->routeIs('warehouse.scans')) aria-current="page" @endif>Scans</a>
        </nav>
    @endauth
    <main>
        @if (session('status'))
            <div class="flash" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="error" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
