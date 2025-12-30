@extends('layouts.app')

@section('title', 'Products')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">All Products</h2>

    <form action="{{ route('products.index') }}" method="GET" class="d-flex" style="max-width: 300px;">
        <input type="text" name="q" class="form-control form-control-sm"
               placeholder="Search..." value="{{ request('q') }}">
        <button class="btn btn-sm btn-outline-secondary ms-2" type="submit">Search</button>
    </form>
</div>

<div class="row g-3">
    @forelse($products as $product)
        @php
            // Normalize sizes to a clean array
            $sizes = [];

            if (is_array($product->sizes)) {
                $sizes = $product->sizes;
            } elseif (!empty($product->sizes)) {
                // if stored as comma-separated string
                $sizes = explode(',', $product->sizes);
            } elseif (!empty($product->size)) {
                // fallback to single size column if you still use it
                $sizes = [$product->size];
            }

            $sizes = array_values(array_unique(array_filter(array_map('trim', $sizes))));
        @endphp

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
                    <span class="badge bg-secondary mb-1">
                        {{ optional($product->category)->name ?? 'Other' }}
                    </span>

                    <h6 class="card-title">{{ $product->name }}</h6>

                    <p class="fw-bold mb-2">
                        ${{ number_format($product->price, 2) }}
                    </p>

                    <p class="mb-1">
                        <strong>Sizes:</strong>
                        @if(count($sizes))
                            {{ implode(', ', $sizes) }}
                        @else
                            N/A
                        @endif
                    </p>

                    {{-- ADD TO CART FORM – uses ONLY available sizes --}}
                    <form action="{{ route('cart.add', $product->id) }}" method="POST" class="mb-2">
                        @csrf

                        @if(count($sizes))
                            <div class="mb-2">
                                <label class="form-label">Size</label>
                                <select name="size" class="form-select form-select-sm" required>
                                    <option value="">Select size</option>
                                    @foreach($sizes as $size)
                                        <option value="{{ $size }}">{{ $size }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="mb-2">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" value="1" min="1"
                                   class="form-control form-control-sm">
                        </div>

                        <button class="btn btn-sm btn-primary w-100">
                            Add to cart
                        </button>
                    </form>

                    <a href="{{ route('products.show', $product) }}"
                       class="btn btn-sm btn-outline-primary mt-auto">
                        Details
                    </a>
                </div>
            </div>
        </div>
    @empty
        <p>No products found.</p>
    @endforelse
</div>
@endsection
