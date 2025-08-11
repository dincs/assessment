<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-50">
  <form method="POST" action="/login" class="w-full max-w-sm bg-white p-6 rounded-xl shadow">
    @csrf
    <h1 class="text-xl font-semibold mb-4">Admin Login</h1>

    @if ($errors->any())
      <div class="mb-3 text-sm text-red-600">{{ $errors->first() }}</div>
    @endif

    <label class="block text-sm mb-1">Email</label>
    <input name="email" type="email" value="{{ old('email') }}" class="w-full border rounded px-3 py-2 mb-3" required>

    <label class="block text-sm mb-1">Password</label>
    <input name="password" type="password" class="w-full border rounded px-3 py-2 mb-3" required>

    <label class="inline-flex items-center gap-2 mb-4">
      <input type="checkbox" name="remember">
      <span class="text-sm">Remember me</span>
    </label>

    <button class="w-full bg-indigo-600 text-white py-2 rounded">Sign in</button>
  </form>
</body>
</html>
