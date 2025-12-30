@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="py-4">

    {{-- HERO SECTION --}}
    <div class="p-4 p-md-5 mb-4 rounded-3 shadow-sm text-white"
         style="background: linear-gradient(135deg, #0d6efd, #6610f2);">
        <div class="row align-items-center">
            <div class="col-md-7">
                <h1 class="display-5 fw-bold mb-3">Welcome to Shop Platform</h1>
                <p class="lead mb-4">
                    Simple online store for electronics, home appliances, and accessories.
                    Browse products, add to cart, and place your order easily.
                </p>

                <a href="{{ route('products.index') }}" class="btn btn-light btn-lg me-2">
                    Start Shopping
                </a>

                @guest
                    <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg">
                        Create an Account
                    </a>
                @endguest
            </div>

            <div class="col-md-5 d-none d-md-block">
                <div class="bg-white bg-opacity-10 rounded-3 p-3">
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="small">Fast</div>
                            <div class="fw-bold">Checkout</div>
                        </div>
                        <div class="col-4">
                            <div class="small">Local</div>
                            <div class="fw-bold">Store</div>
                        </div>
                        <div class="col-4">
                            <div class="small">Secure</div>
                            <div class="fw-bold">Login</div>
                        </div>
                    </div>
                    <p class="mt-3 mb-0 small">
                        Manage your orders and products easily through your account.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- LATEST PRODUCTS --}}
    @if($products->count())
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Latest Products</h3>
            <a href="{{ route('products.index') }}" class="text-decoration-none small">
                View all →
            </a>
        </div>

        <div class="row g-3">
            @foreach($products as $product)
                <div class="col-6 col-md-3">
                    <div class="card h-100 shadow-sm border-0">

                        {{-- Product image --}}
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}"
                                 class="card-img-top"
                                 alt="{{ $product->name }}"
                                 style="height: 180px; object-fit: cover;">
                        @else
                            <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                                 style="height: 180px;">
                                <span class="text-muted">No image</span>
                            </div>
                        @endif

                        <div class="card-body d-flex flex-column">
                            {{-- Category badge (if any) --}}
                            @if(optional($product->category)->name)
                                <span class="badge bg-secondary mb-1">
                                    {{ $product->category->name }}
                                </span>
                            @endif

                            <h6 class="card-title mb-1">{{ $product->name }}</h6>

                            {{-- Sizes (from saved sizes) --}}
                            @php
                                // uses accessor getSizesArrayAttribute()
                                $sizes = $product->sizes ?? [];
                            @endphp

                            <p class="mb-1 small text-muted">
                                <strong>Sizes:</strong>
                                @if(!empty($sizes))
                                    {{ implode(', ', $sizes) }}
                                @else
                                    N/A
                                @endif
                            </p>

                            <p class="fw-bold mb-2">
                                ${{ number_format($product->price, 2) }}
                            </p>

                            <a href="{{ route('products.show', $product) }}"
                               class="btn btn-sm btn-outline-primary mt-auto">
                                View details
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-muted">No products added yet. Ask the admin to add some items.</p>
    @endif
</div>
@endsection
