<div>
  <label class="block text-sm mb-1">Name</label>
  <input name="name" value="{{ old('name', $product->name) }}" class="w-full border rounded-md px-3 py-2">
  @error('name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
  <label class="block text-sm mb-1">Category</label>
  <select name="category_id" class="w-full border rounded-md px-3 py-2">
    <option value="">-- choose --</option>
    @foreach($categories as $c)
      <option value="{{ $c->id }}" @selected(old('category_id', $product->category_id)==$c->id)>{{ $c->name }}</option>
    @endforeach
  </select>
  @error('category_id') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
  <label class="block text-sm mb-1">Description</label>
  <textarea name="description" rows="3" class="w-full border rounded-md px-3 py-2">{{ old('description', $product->description) }}</textarea>
  @error('description') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
  <div>
    <label class="block text-sm mb-1">Price</label>
    <input type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}" class="w-full border rounded-md px-3 py-2">
    @error('price') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
  </div>
  <div>
    <label class="block text-sm mb-1">Stock</label>
    <input type="number" name="stock" value="{{ old('stock', $product->stock) }}" class="w-full border rounded-md px-3 py-2">
    @error('stock') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
  </div>
  <div class="flex items-center gap-2 pt-6">
    <input type="hidden" name="enabled" value="0">
    <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $product->enabled))>
    <span class="text-sm">Enabled</span>
  </div>
</div>
