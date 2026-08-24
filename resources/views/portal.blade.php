<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Akses Portal - Ability Examiner</title>
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
  
  <!-- Scripts & Styles -->
  @vite(['resources/sass/app.scss', 'resources/js/app.js'])
  
  <style>
    body {
      background: radial-gradient(circle at 50% 50%, #f9fafb 0%, #f3f4f6 100%);
      font-family: 'Instrument Sans', sans-serif;
      min-height: 100vh;
      color: #111827;
      overflow-x: hidden;
    }
    
    .portal-container {
      position: relative;
      z-index: 10;
    }
    
    .admin-card {
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 20px;
      transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 10px 15px -3px rgba(0, 0, 0, 0.08);
    }
    
    .admin-card:hover {
      transform: translateY(-6px);
      border-color: #cbd5e1;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    .icon-box {
      width: 60px;
      height: 60px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 20px;
      font-size: 1.6rem;
      transition: all 0.3s ease;
    }
    
    .icon-staff {
      background: #fef3c7;
      color: #d97706;
      border: 1px solid #fde68a;
    }
    
    .admin-card:hover .icon-staff {
      background: #fde68a;
      transform: scale(1.05);
      box-shadow: 0 0 15px rgba(245, 158, 11, 0.2);
    }
    
    .btn-portal {
      border: none;
      padding: 8px 18px;
      border-radius: 8px;
      font-size: 0.875rem;
      font-weight: 600;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      width: auto;
      justify-content: center;
      text-decoration: none !important;
    }
    
    .btn-staff {
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
      color: #ffffff !important;
    }
    
    .btn-staff:hover {
      background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
      box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3) !important;
      transform: translateY(-2px) !important;
      color: #ffffff !important;
    }
    
    .back-link {
      color: #4b5563;
      text-decoration: none;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-weight: 500;
    }
    
    .back-link:hover {
      color: #111827;
    }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center py-5">
  <div class="container portal-container">
    <div class="text-center mb-5">
      <h2 class="fw-bold mb-2" style="letter-spacing: -1px; color: #111827;">Portal Internal Perusahaan</h2>
    </div>
    
    <!-- Centered Card -->
    <div class="d-flex justify-content-center" style="max-width: 400px; margin: 0 auto;">
      <!-- Staff & HRD Card -->
      <div class="admin-card w-100 p-4 d-flex flex-column justify-content-between text-center">
        <div>
          <div class="icon-box icon-staff mx-auto">
            <i class="fas fa-user-shield"></i>
          </div>
          <h5 class="fw-semibold mb-2" style="color: #111827;">Masuk Dashboard</h5>
          <p class="mb-3" style="font-size: 0.9rem; line-height: 1.5; color: #4b5563 !important;">
           Halaman ini menyediakan akses login bagi Supervisor, Manager HRD, dan Administrator untuk memasuki dashboard sistem.
          </p>
        </div>
        <div>
          <a href="{{ url('/admin/login') }}" class="btn-portal btn-staff">
            <span>Lanjut ke Login</span>
            <i class="fas fa-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>
    
    <!-- Footer Back Link -->
    <div class="text-center mt-5">
      <a href="{{ url('/') }}" class="back-link">
        <i class="fas fa-chevron-left"></i>
        <span>Kembali ke Beranda Utama</span>
      </a>
    </div>
  </div>
</body>
</html>
