@extends('layouts.admin')
@section('title','Products')

@section('content')
<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold">Products</h1>
  <a href="{{ route('admin.products.create') }}" class="px-3 py-2 bg-indigo-600 text-white rounded-md">New</a>
</div>

<form method="GET" class="mb-4 grid grid-cols-1 sm:grid-cols-4 gap-3">
  <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name..." class="border rounded-md px-3 py-2">
  <select name="category_id" class="border rounded-md px-3 py-2">
    <option value="">All categories</option>
    @foreach($categories as $c)
      <option value="{{ $c->id }}" @selected(request('category_id')==$c->id)>{{ $c->name }}</option>
    @endforeach
  </select>
  <select name="enabled" class="border rounded-md px-3 py-2">
    <option value="">All statuses</option>
    <option value="true"  @selected(request('enabled')==='true')>Enabled</option>
    <option value="false" @selected(request('enabled')==='false')>Disabled</option>
  </select>
  <div class="flex gap-2">
    <button class="px-3 py-2 bg-gray-800 text-white rounded-md">Filter</button>
    <a href="{{ route('admin.products.index') }}" class="px-3 py-2 border rounded-md">Reset</a>
    <a href="{{ route('admin.products.export', request()->query()) }}" class="px-3 py-2 bg-green-600 text-white rounded-md">Export</a>
  </div>
</form>

<div class="overflow-x-auto bg-white border rounded-lg">
  <table class="min-w-full text-sm">
    <thead class="bg-gray-100">
      <tr>
        <th class="p-3"><input type="checkbox" id="checkAll"></th>
        <th class="p-3 text-left">ID</th>
        <th class="p-3 text-left">Name</th>
        <th class="p-3 text-left">Category</th>
        <th class="p-3 text-right">Price</th>
        <th class="p-3 text-right">Stock</th>
        <th class="p-3 text-center">Enabled</th>
        <th class="p-3 text-right">Actions</th>
      </tr>
    </thead>
    <tbody>
    @forelse($products as $p)
      <tr class="border-t">
        <td class="p-3 align-middle">
          <input type="checkbox" class="rowCheck" value="{{ $p->id }}">
        </td>
        <td class="p-3">{{ $p->id }}</td>
        <td class="p-3">{{ $p->name }}</td>
        <td class="p-3">{{ optional($p->category)->name }}</td>
        <td class="p-3 text-right">{{ number_format($p->price, 2) }}</td>
        <td class="p-3 text-right">{{ $p->stock }}</td>
        <td class="p-3 text-center">
          @if($p->enabled)
            <span class="px-2 py-1 bg-green-100 text-green-800 rounded">Yes</span>
          @else
            <span class="px-2 py-1 bg-red-100 text-red-800 rounded">No</span>
          @endif
        </td>
        <td class="p-3 text-right space-x-2">
          <a href="{{ route('admin.products.edit', $p) }}" class="text-indigo-600">Edit</a>
          {{-- per-row delete is its own form (NOT nested) --}}
          <form action="{{ route('admin.products.destroy', $p) }}" method="POST" class="inline"
                onsubmit="return confirm('Delete this product?')">
            @csrf @method('DELETE')
            <button class="text-red-600">Delete</button>
          </form>
        </td>
      </tr>
    @empty
      <tr><td colspan="8" class="p-6 text-center text-gray-500">No products</td></tr>
    @endforelse
    </tbody>
  </table>
</div>

<div class="mt-3 flex items-center justify-between">
  <button type="button" class="px-3 py-2 bg-red-600 text-white rounded-md"
          onclick="submitBulkDelete()">Bulk delete</button>
  {{ $products->links() }}
</div>

{{-- hidden bulk delete form (separate, not wrapping table) --}}
<form id="bulk-delete-form" method="POST" action="{{ route('admin.products.bulkDelete') }}" class="hidden">
  @csrf
</form>

<script>
  const checkAll = document.getElementById('checkAll');
  checkAll?.addEventListener('change', e => {
    document.querySelectorAll('.rowCheck').forEach(cb => cb.checked = e.target.checked);
  });

  function submitBulkDelete() {
    const ids = Array.from(document.querySelectorAll('.rowCheck:checked')).map(cb => cb.value);
    if (ids.length === 0) {
      alert('No items selected');
      return;
    }
    if (!confirm('Delete selected products?')) return;

    const form = document.getElementById('bulk-delete-form');
    form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());

    ids.forEach(id => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'ids[]';
      input.value = id;
      form.appendChild(input);
    });
    form.submit();
  }
</script>
@endsection
