@extends('layouts.app')

@section('title', 'Add Product')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="bg-white rounded-3 shadow-sm p-4">
            <h2 class="mb-4">Add Product</h2>

            <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- name --}}
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="form-control @error('name') is-invalid @enderror">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- category --}}
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select name="category_id"
                            id="categorySelect"
                            class="form-select @error('category_id') is-invalid @enderror">
                        <option value="">-- Choose category --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}"
                                    data-name="{{ strtolower($category->name) }}"
                                    {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- description --}}
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3"
                              class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- price & stock --}}
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Price ($)</label>
                        <input type="number" step="0.01" name="price" value="{{ old('price') }}"
                               class="form-control @error('price') is-invalid @enderror">
                        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Stock</label>
                        <input type="number" name="stock" value="{{ old('stock', 0) }}"
                               class="form-control @error('stock') is-invalid @enderror">
                        @error('stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Sizes --}}
<div class="mb-3">
    <label class="form-label">Available sizes (tops/pants)</label>
    <div class="d-flex flex-wrap gap-2">
        @foreach($clothingSizes as $size)
            <div class="form-check form-check-inline">
                <input class="form-check-input"
                       type="checkbox"
                       name="sizes[]"
                       value="{{ $size }}"
                       {{ in_array($size, old('sizes', [])) ? 'checked' : '' }}>
                <label class="form-check-label">{{ $size }}</label>
            </div>
        @endforeach
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Available sizes (shoes)</label>
    <div class="d-flex flex-wrap gap-2">
        @foreach($shoeSizes as $size)
            <div class="form-check form-check-inline">
                <input class="form-check-input"
                       type="checkbox"
                       name="sizes[]"
                       value="{{ $size }}"
                       {{ in_array((string)$size, old('sizes', [])) ? 'checked' : '' }}>
                <label class="form-check-label">{{ $size }}</label>
            </div>
        @endforeach
    </div>
</div>

                {{-- Image --}}
<div class="mb-3">
    <label class="form-label">Image</label>
    <input type="file" name="image"
           class="form-control @error('image') is-invalid @enderror">
    @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const categorySelect = document.getElementById('categorySelect');
    const clothingDiv = document.getElementById('clothingSizes');
    const shoeDiv = document.getElementById('shoeSizes');

    function updateSizeUI() {
        const option = categorySelect.options[categorySelect.selectedIndex];
        const name = option ? (option.getAttribute('data-name') || '') : '';

        // If category name contains 'shoe', treat as shoes, else clothing
        if (name.includes('shoe')) {
            clothingDiv.style.display = 'none';
            shoeDiv.style.display = '';
        } else {
            clothingDiv.style.display = '';
            shoeDiv.style.display = 'none';
        }
    }

    categorySelect.addEventListener('change', updateSizeUI);
    updateSizeUI();
});
</script>
@endsection
