<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Online Test - Ability Examiner</title>
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
  
  <!-- Scripts & Styles -->
  @vite(['resources/sass/app.scss', 'resources/js/app.js'])

  <style>
    body {
      background-color: #f8f9fa;
    }
    .test-header {
      background: white;
      padding: 1rem 0;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      margin-bottom: 2rem;
    }
    .logo-text {
      font-weight: 600;
      font-size: 1.25rem;
      color: #333;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <header class="test-header">
    <div class="container d-flex justify-content-between align-items-center">
      <a href="#" class="logo-text">
        Ability Examiner
      </a>
      <div>
        <span class="text-muted small">Applicant Test Session</span>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  @yield('content')
</body>
</html>
