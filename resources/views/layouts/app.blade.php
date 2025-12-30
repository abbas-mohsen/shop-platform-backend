<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Shop Platform')</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Bootstrap 5 CSS from CDN -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
        crossorigin="anonymous"
    >

    <!-- Optional: your own CSS -->
    <style>
        body {
            background-color: #f4f5f7;
        }
        .navbar-brand {
            font-weight: 700;
        }
        .main-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        main {
            flex: 1;
        }
        footer {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<div class="main-wrapper">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">Shop Platform</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <!-- Left -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}"
                           href="{{ route('home') }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}"
                           href="{{ route('products.index') }}">Shop</a>
                    </li>
                    @auth
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('cart.*') ? 'active' : '' }}"
                               href="{{ route('cart.index') }}">Cart</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}"
                               href="{{ route('orders.index') }}">My Orders</a>
                        </li>
                    @endauth

                    @auth
                        @if (auth()->user()->is_admin)
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ request()->is('admin*') ? 'active' : '' }}"
                                   href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown"
                                   aria-expanded="false">
                                    Admin
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.products.index') }}">
                                            Manage Products
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.orders.index') }}">
                                            Manage Orders
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    @endauth
                </ul>

                <!-- Right -->
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-sm btn-outline-light ms-2" href="{{ route('register') }}">Register</a>
                        </li>
                    @else
                        <li class="nav-item">
                            <span class="navbar-text me-2">
                                {{ Auth::user()->name }}
                                @if(Auth::user()->is_admin)
                                    <span class="badge bg-warning text-dark ms-1">Admin</span>
                                @endif
                            </span>
                        </li>
                        <li class="nav-item">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button class="btn btn-sm btn-outline-light" type="submit">Logout</button>
                            </form>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <main class="py-4">
        <div class="container">
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <footer class="bg-dark text-light text-center py-3 mt-4">
        <div class="container">
            <small>&copy; {{ date('Y') }} Shop Platform – CSC 400 Web Programming Project</small>
        </div>
    </footer>
</div>

<!-- Bootstrap JS (Popper + JS bundle) -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
    crossorigin="anonymous"
></script>

@stack('scripts')
</body>
</html>
