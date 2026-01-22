<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ability Examiner</title>
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
  
  <!-- Scripts & Styles -->
  @vite(['resources/sass/app.scss','resources/sass/custom/landing.scss', 'resources/js/app.js'])
</head>
<body class="d-flex flex-column min-vh-100">
  <!-- Navbar -->
  @include('components.navbar_landing')

  <!-- Main Content -->
  <main class="flex-grow-1">
    @yield('content')
  </main>

  <!-- Footer -->
  @include('components.footer_landing')
</body>
</html>
