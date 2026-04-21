<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Laravel</title>
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
  
  <!-- Scripts & Styles -->
  @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid justify-content-end">
      @if (Route::has('login'))
        <div class="d-flex">
          @auth
            <a href="{{ url('/dashboard') }}" class="btn btn-outline-dark me-2">Dashboard</a>
          @else
            <a href="{{ route('login') }}" class="btn btn-outline-dark me-2">Log in</a>
            @if (Route::has('register'))
              <a href="{{ route('register') }}" class="btn btn-dark">Register</a>
            @endif
          @endauth
        </div>
      @endif
    </div>
  </nav>

  <div class="container my-5">
    <div class="row justify-content-center">
      <div class="col-md-8 text-center">
        <h1 class="display-4 fw-bold">Let's get started</h1>
        <p class="lead text-muted mb-5">Laravel has an incredibly rich ecosystem. We suggest starting with the following:</p>

        <div class="row g-4 text-start">
          <!-- Card 1 -->
          <div class="col-md-6">
            <div class="card h-100 shadow-sm border-0">
              <div class="card-body d-flex align-items-center">
                <div class="bg-light rounded-circle p-3 me-3 text-danger">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-book" viewBox="0 0 16 16">
                   <path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811V2.828zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492V2.687zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783z"/>
                  </svg>
                </div>
                <div>
                  <h5 class="card-title fw-bold">Documentation</h5>
                  <p class="card-text text-muted small">Read the <a href="https://laravel.com/docs" class="text-danger text-decoration-none">Documentation</a></p>
                </div>
              </div>
            </div>
          </div>

          <!-- Card 2 -->
          <div class="col-md-6">
            <div class="card h-100 shadow-sm border-0">
              <div class="card-body d-flex align-items-center">
                <div class="bg-light rounded-circle p-3 me-3 text-danger">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-camera-video" viewBox="0 0 16 16">
                   <path fill-rule="evenodd" d="M0 5a2 2 0 0 1 2-2h7.5a2 2 0 0 1 1.983 1.738l3.11-1.382A1 1 0 0 1 16 4.269v7.461a1 1 0 0 1-1.406.913l-3.111-1.382A2 2 0 0 1 9.5 13H2a2 2 0 0 1-2-2V5zm11.5 5.175 3.5 1.556V4.269l-3.5 1.556v4.35zM2 4a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h7.5a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1H2z"/>
                  </svg>
                </div>
                <div>
                  <h5 class="card-title fw-bold">Laracasts</h5>
                  <p class="card-text text-muted small">Watch tutorials at <a href="https://laracasts.com" class="text-danger text-decoration-none">Laracasts</a></p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-5">
          <a href="https://cloud.laravel.com" class="btn btn-dark px-4 py-2 fw-semibold">Deploy now</a>
        </div>

      </div>
    </div>
    
    <footer class="mt-5 text-center text-muted small">
      Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})
    </footer>
  </div>
</body>
</html>
