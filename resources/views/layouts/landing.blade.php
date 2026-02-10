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

  <!-- Toastify CSS & JS -->
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.12.0/toastify.min.css">
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.12.0/toastify.min.js"></script>
  <style>
      .toast-custom .toast-close {
          color: #9ca3af !important; /* Tailwind gray-400 */
          opacity: 1 !important;
          padding-left: 10px;
      }
      .toast-custom .toast-close:hover {
          color: #4b5563 !important; /* Tailwind gray-600 */
      }
  </style>
  
  <script>
    // Toast Notification Logic
    document.addEventListener('DOMContentLoaded', function() {
        // ... (toast logic remains same) ...
    });
  </script>
  @stack('scripts')
</body>
</html>
