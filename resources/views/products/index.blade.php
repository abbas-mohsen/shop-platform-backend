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
    @if(!empty($product->sizes))
        {{ implode(', ', $product->sizes) }}
    @else
        N/A
    @endif
</p>


                    {{-- ADD TO CART FORM GOES HERE --}}
                    <form action="{{ route('cart.add', $product->id) }}" method="POST" class="mb-2">
                        @csrf

                        <div class="mb-2">
                            <label class="form-label">Size (optional)</label>
                            <select name="size" class="form-select form-select-sm">
                                <option value="">Select size</option>
                                <option value="S">S</option>
                                <option value="M">M</option>
                                <option value="L">L</option>
                                <option value="XL">XL</option>
                                <option value="XXL">XXL</option>
                                <option value="One Size">One Size</option>
                            </select>
                        </div>

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
