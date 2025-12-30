@extends('layouts.app')

@section('title', 'Admin - Products')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Manage Products</h2>
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
        + Add Product
    </a>
</div>

<div class="bg-white rounded-3 shadow-sm p-3">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
        <tr>
            <th>#</th>
            <th>Image</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th width="160">Actions</th>
        </tr>
        </thead>
        <tbody>
        @foreach($products as $product)
            <tr>
                <td>{{ $product->id }}</td>
                <td>
                    @if($product->image)
                        <img src="{{ asset('storage/'.$product->image) }}" alt=""
                             style="width: 48px; height: 48px; object-fit: cover;" class="rounded">
                    @else
                        <span class="text-muted">No image</span>
                    @endif
                </td>
                <td>{{ $product->name }}</td>
                <td>{{ optional($product->category)->name ?? '-' }}</td>
                <td>${{ number_format($product->price, 2) }}</td>
                <td>{{ $product->stock }}</td>
                <td>
                    <a href="{{ route('admin.products.edit', $product) }}"
                       class="btn btn-sm btn-outline-secondary">Edit</a>
                    <form action="{{ route('admin.products.destroy', $product) }}"
                          method="POST" class="d-inline"
                          onsubmit="return confirm('Delete this product?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" type="submit">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
