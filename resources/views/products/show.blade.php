@extends('layouts.app')

@section('title', $product->name)

@section('content')
<div class="row">
    <div class="col-md-5">
        <div class="bg-white rounded-3 shadow-sm p-3">
            @if($product->image)
                <img src="{{ asset('storage/' . $product->image) }}"
                     alt="{{ $product->name }}" class="img-fluid rounded">
            @else
                <div class="d-flex align-items-center justify-content-center bg-light rounded"
                     style="height: 300px;">
                    <span class="text-muted">No image</span>
                </div>
            @endif
        </div>
    </div>

    <div class="col-md-7 mt-4 mt-md-0">
        <div class="bg-white rounded-3 shadow-sm p-4 h-100">
            <span class="badge bg-secondary mb-2">
                {{ optional($product->category)->name ?? 'Other' }}
            </span>

            <h2>{{ $product->name }}</h2>

            <p class="lead fw-bold mb-3">${{ number_format($product->price, 2) }}</p>
            <p class="mb-4">{{ $product->description }}</p>

            <p class="mb-2">
                <strong>In stock:</strong> {{ $product->stock }}
            </p>

            {{-- Available sizes display --}}
            @php
                $availableSizes = $product->sizes ?? [];
            @endphp

            @if(!empty($availableSizes))
                <p class="mb-3">
                    <strong>Available sizes:</strong>
                    {{ implode(', ', $availableSizes) }}
                </p>
            @else
                <p class="mb-3">
                    <strong>Available sizes:</strong> N/A
                </p>
            @endif

            @auth
                <form action="{{ route('cart.add', $product) }}" method="POST" class="mt-3">
                    @csrf

                    {{-- Size selector ONLY from saved sizes --}}
                    @if(!empty($availableSizes))
                        <div class="mb-3">
                            <label class="form-label">Size</label>
                            <select name="size"
                                    class="form-select @error('size') is-invalid @enderror"
                                    required>
                                <option value="">-- Choose size --</option>
                                @foreach($availableSizes as $size)
                                    <option value="{{ $size }}"
                                        {{ old('size') === $size ? 'selected' : '' }}>
                                        {{ $size }}
                                    </option>
                                @endforeach
                            </select>
                            @error('size') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @else
                        {{-- No sizes defined -> send empty size --}}
                        <input type="hidden" name="size" value="">
                    @endif

                    <div class="mb-3 d-flex align-items-center">
                        <label class="me-2 mb-0">Qty</label>
                        <input type="number"
                               name="quantity"
                               value="{{ old('quantity', 1) }}"
                               min="1"
                               max="{{ $product->stock }}"
                               class="form-control"
                               style="width: 100px;">
                    </div>

                    <button class="btn btn-primary" type="submit">
                        Add to Cart
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-primary mt-3">
                    Login to buy
                </a>
            @endauth
        </div>
    </div>
</div>
@endsection
