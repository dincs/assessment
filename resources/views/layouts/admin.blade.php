<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>@yield('title','Admin')</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  {{-- Tailwind CDN for speed. If you’re using Vite, replace with @vite. --}}
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">
  <nav class="bg-white shadow">
    <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
      <a href="{{ route('admin.products.index') }}" class="font-semibold">Admin</a>
      <div class="space-x-3">
        <a href="{{ route('admin.products.index') }}" class="text-sm text-gray-700">Products</a>
        <form action="/logout" method="POST" class="inline">
          @csrf
          <button class="text-sm text-red-600">Logout</button>
        </form>
      </div>
    </div>
  </nav>

  <main class="max-w-6xl mx-auto p-4">
    @if(session('success'))
      <div class="mb-4 rounded-lg bg-green-100 text-green-800 px-4 py-3">
        {{ session('success') }}
      </div>
    @endif
    @yield('content')
  </main>
</body>
</html>
