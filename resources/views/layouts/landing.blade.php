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
  <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
  <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
  
  <script>
    // Toast Notification Logic
    document.addEventListener('DOMContentLoaded', function() {
        @if(session('success'))
            Toastify({
                text: "{{ session('success') }}",
                duration: 5000,
                close: true,
                gravity: "top", // `top` or `bottom`
                position: "right", // `left`, `center` or `right`
                stopOnFocus: true, 
                style: {
                    background: "linear-gradient(to right, #10b981, #059669)",
                    boxShadow: "0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)",
                    borderRadius: "8px",
                    fontWeight: "500",
                },
            }).showToast();
        @endif

        @if(session('error'))
             Toastify({
                text: "{{ session('error') }}",
                duration: 5000,
                close: true,
                gravity: "top", 
                position: "right", 
                stopOnFocus: true, 
                style: {
                    background: "linear-gradient(to right, #ef4444, #dc2626)",
                    boxShadow: "0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)",
                    borderRadius: "8px",
                    fontWeight: "500",
                },
            }).showToast();
        @endif
    });
  </script>
</body>
</html>
