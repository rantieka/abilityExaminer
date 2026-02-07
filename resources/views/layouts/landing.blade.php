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
        // Success Message
        @if(session('success'))
            Toastify({
                text: '<i class="fa-solid fa-circle-check text-success fs-5 me-2"></i> <span class="fw-semibold">{{ session('success') }}</span>',
                duration: 5000,
                close: true,
                gravity: "top", 
                position: "right", 
                stopOnFocus: true, 
                escapeMarkup: false,
                className: "toast-custom",
                style: {
                    background: "#ffffff",
                    color: "#1f2937",
                    boxShadow: "0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)",
                    border: "1px solid #f3f4f6",
                    borderRadius: "12px",
                    display: "flex",
                    alignItems: "center",
                    gap: "10px",
                    zIndex: "9999",
                },
                offset: {
                    x: "1.5rem", 
                    y: "1.5rem" 
                },
            }).showToast();
        @endif

        // Error Message (Manual)
        // Error Message (Manual)
        @if(session('error'))
             Toastify({
                text: '<i class="fa-solid fa-circle-xmark text-danger fs-5 me-2"></i> <span class="fw-semibold">{{ session('error') }}</span>',
                duration: 5000,
                close: true,
                gravity: "top", 
                position: "right", 
                stopOnFocus: true, 
                escapeMarkup: false,
                className: "toast-custom",
                style: {
                    background: "#ffffff",
                    color: "#1f2937",
                    boxShadow: "0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)",
                    border: "1px solid #f3f4f6",
                    borderRadius: "12px",
                    display: "flex",
                    alignItems: "center",
                    gap: "10px",
                    zIndex: "9999",
                },
                offset: {
                    x: "1.5rem", 
                    y: "1.5rem" 
                },
            }).showToast();
        @endif

        // Validation Errors
        @if($errors->any())
            @foreach($errors->all() as $error)
                Toastify({
                    text: '<i class="fa-solid fa-circle-exclamation text-danger fs-5 me-2"></i> <span class="fw-semibold">{{ $error }}</span>',
                    duration: 5000,
                    close: true,
                    gravity: "top", 
                    position: "right", 
                    stopOnFocus: true, 
                    escapeMarkup: false,
                    className: "toast-custom",
                    style: {
                        background: "#ffffff",
                        color: "#1f2937",
                        boxShadow: "0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)",
                        border: "1px solid #f3f4f6",
                        borderRadius: "12px",
                        display: "flex",
                        alignItems: "center",
                        gap: "10px",
                        zIndex: "9999",
                    },
                    offset: {
                        x: "1.5rem", 
                        y: "1.5rem" 
                    },
                }).showToast();
            @endforeach
        @endif
    });
  </script>
</body>
</html>
