@extends('layouts.admin')
@section('title','New Product')
@section('content')
<h1 class="text-xl font-semibold mb-4">New Product</h1>
<form action="{{ route('admin.products.store') }}" method="POST" class="grid gap-4 max-w-3xl">
  @csrf
  @include('admin.products._form', ['product'=>$product, 'categories'=>$categories])
  <div class="flex gap-2">
    <button class="px-3 py-2 bg-indigo-600 text-white rounded-md">Save</button>
    <a href="{{ route('admin.products.index') }}" class="px-3 py-2 border rounded-md">Cancel</a>
  </div>
</form>
@endsection
