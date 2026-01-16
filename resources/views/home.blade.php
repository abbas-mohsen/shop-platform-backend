@extends('layouts.app')

@section('title', 'Home')

@section('content')
    {{-- HERO SECTION --}}
    <div class="mb-5">
        <div class="p-4 p-md-5 rounded-4 position-relative overflow-hidden"
             style="background: linear-gradient(135deg, #0d6efd, #6610f2);">
            <div class="row align-items-center">
                <div class="col-md-7 text-white">
                    <h1 class="display-5 fw-bold mb-3">Welcome to Shop Platform</h1>
                    <p class="lead mb-4">
                        Simple and clean online shopping for electronics, home appliances, and accessories.
                    </p>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('products.index') }}" class="btn btn-light btn-lg fw-semibold px-4">
                            Start Shopping
                        </a>
                    </div>
                </div>

                <div class="col-md-5 d-none d-md-block">
                    <div class="bg-white bg-opacity-10 border border-light border-opacity-25 rounded-4 p-3">
                        <div class="d-flex flex-column align-items-center justify-content-center" style="min-height:220px;">
                            <div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center mb-3"
                                 style="width:96px;height:96px;">
                                <span class="fs-1">🛒</span>
                            </div>
                            <p class="text-white-50 mb-1">Browse products in a few clicks</p>
                            <p class="text-white-50 mb-1">Fast and easy checkout</p>
                            <p class="text-white-50 mb-0">Track your orders from one place</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- subtle gradient overlay --}}
            <div class="position-absolute top-0 end-0 opacity-25 d-none d-md-block"
                 style="width:260px;height:260px;
                        background: radial-gradient(circle at center, #ffffff 0, transparent 70%);">
            </div>
        </div>
    </div>

    {{-- LATEST PRODUCTS --}}
    @if($products->count())
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Latest Products</h3>
            <a href="{{ route('products.index') }}" class="small text-decoration-none">
                View all →
            </a>
        </div>

        <div class="row g-3 g-md-4">
            @foreach($products as $product)
                <div class="col-6 col-md-3">
                    <div class="card h-100 border-0 shadow-sm rounded-4">

                        {{-- IMAGE + CATEGORY BADGE --}}
                        <div class="position-relative">
                            @if($product->image)
                                <img src="{{ asset('storage/' . $product->image) }}"
                                     class="card-img-top rounded-top-4"
                                     alt="{{ $product->name }}"
                                     style="height:190px;object-fit:cover;">
                            @else
                                <div class="card-img-top rounded-top-4 d-flex align-items-center justify-content-center bg-light"
                                     style="height:190px;">
                                    <span class="text-muted">No image</span>
                                </div>
                            @endif

                            @if(optional($product->category)->name)
                                <span class="badge bg-dark bg-opacity-75 position-absolute top-0 start-0 m-2">
                                    {{ optional($product->category)->name }}
                                </span>
                            @endif
                        </div>

                        {{-- BODY --}}
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title mb-1 text-truncate" title="{{ $product->name }}">
                                {{ $product->name }}
                            </h6>

                            @if($product->description)
                                <p class="small text-muted mb-2"
                                   style="max-height:3rem;overflow:hidden;">
                                    {{ $product->description }}
                                </p>
                            @endif

                            <p class="fw-bold mb-3 fs-6">
                                ${{ number_format($product->price, 2) }}
                            </p>

                            <a href="{{ route('products.show', $product) }}"
                               class="btn btn-sm btn-primary w-100 mt-auto">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center text-muted py-5">
            <p class="mb-1 fs-5">No products available yet.</p>
            <p class="mb-0">Check back soon for new items!</p>
        </div>
    @endif
@endsection
