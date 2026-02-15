<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ability Examiner</title>
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
  
  <!-- Scripts & Styles -->
  @vite(['resources/sass/app.scss', 'resources/sass/test-layout.scss', 'resources/js/app.js', 'resources/js/test-background.js'])

  <style>
    body {
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      font-family: 'Instrument Sans', sans-serif;
      min-height: 100vh;
      position: relative;
      overflow-x: hidden;
    }
    #bg-canvas {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -1;
      pointer-events: none;
    }
    .test-header {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      padding: 1rem 0;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
      border-bottom: 1px solid rgba(0,0,0,0.05);
      margin-bottom: 2rem;
      position: relative;
      z-index: 1030;
    }
    .logo-text {
      font-weight: 700;
      font-size: 1.5rem;
      color: #333;
      text-decoration: none;
      letter-spacing: -0.5px;
    }
    /* Enhance Card readability over animated bg */
    .card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(5px);
    }
  </style>
</head>
<body>
  <canvas id="bg-canvas" class="fullscreen-bg"></canvas>

  <header class="test-header">
    <div class="container d-flex justify-content-between align-items-center">
      <a href="#" class="logo-text">
        Ability Examiner
      </a>
    </div>
  </header>

  <!-- Main Content -->
  @yield('content')

  <footer class="text-center py-4 text-muted" style="position: relative; z-index: 10;">
    <small>&copy; 2026 </small>
  </footer>
</body>
</html>
