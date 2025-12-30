@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <div class="p-4 p-md-5 bg-white rounded-3 shadow-sm">
            <h1 class="display-6 fw-bold mb-3">Welcome to Shop Platform</h1>
            <p class="lead mb-3">
                Browse electronics, home appliances, and accessories easily.
                Simple online shopping experience for local customers.
            </p>
            <a href="{{ route('products.index') }}" class="btn btn-primary btn-lg">
                Start Shopping
            </a>
        </div>
    </div>
    <div class="col-md-4 mt-4 mt-md-0">
        <div class="bg-white rounded-3 shadow-sm p-3 h-100">
            <h5 class="mb-3">Why use this website?</h5>
            <ul class="list-unstyled mb-0">
                <li class="mb-2">✔ Easy product browsing</li>
                <li class="mb-2">✔ Track your orders</li>
                <li class="mb-2">✔ Admin dashboard for store owner</li>
            </ul>
        </div>
    </div>
</div>

@if($products->count())
    <h3 class="mb-3">Latest Products</h3>
    <div class="row g-3">
        @foreach($products as $product)
            <div class="col-6 col-md-3">
                <div class="card h-100 shadow-sm">
                    @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}"
                             class="card-img-top" alt="{{ $product->name }}">
                    @else
                        <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                             style="height: 160px;">
                            <span class="text-muted">No image</span>
                        </div>
                    @endif
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title">{{ $product->name }}</h6>
                        <p class="mb-1">
    <strong>Size:</strong> {{ $product->size ?? 'N/A' }}
</p>
                        <p class="fw-bold mb-2">${{ number_format($product->price, 2) }}</p>
                        <a href="{{ route('products.show', $product) }}"
                           class="btn btn-sm btn-outline-primary mt-auto">View</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
