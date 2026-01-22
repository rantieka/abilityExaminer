<div class="backdrop-navbar-mobile vh-100 w-100 position-fixed"></div>
<nav class="navbar navbar-expand-lg shadow-sm fixed-top bg-white" id="">
  <div class="container">
    <div class="d-flex justify-content-between w-100 align-items-center">
      <a class="navbar-brand" href="/">
        <img src="{{ asset('images/nineod-logo.png') }}" alt="Logo" class="new-logo">
      </a>
      <ul class="navbar-nav d-none d-lg-flex">
        <li class="navbar-item px-4">
          <a href="#home" class="nav-link navbar-link px-0">Home</a>
        </li>
        <li class="navbar-item px-4">
          <a href="#about" class="nav-link navbar-link px-0">About</a>
        </li>
        <!-- <li class="navbar-item px-4">
          <a href="#products" class="nav-link navbar-link px-0">Products</a>
        </li> -->
        <li class="navbar-item px-4">
          <a href="#projects" class="nav-link navbar-link px-0">Projects</a>
        </li>
        <li class="navbar-item px-4">
          <a href="{{ route('career.index') }}" class="nav-link navbar-link px-0 active">Careers</a>
        </li>
        <li class="navbar-item px-4">
          <a href="#contact" class="nav-link navbar-link px-0">Contact</a>
        </li>
      </ul>
      <div class="navbar-button d-flex align-items-center d-lg-none">
        <button class="hamburger-lines bg-transparent border-0" id="humburger-menu-toggle">
          <span class="line line1"></span>
          <span class="line line2"></span>
          <span class="line line3"></span>
        </button>
      </div>
    </div>
  </div>
</nav>
<div class="navbar-mobile position-fixed top-0 end-0 w-75 ms-auto d-lg-none">
  <div class="container">
    <div class="navbar-close text-end py-4 px-2">
      <button type="button" class="p-0 border-0 bg-transparent" id="btn-close-navbar">
        <i class="fas fa-times fs-4"></i>
      </button>
    </div>
    <ul class="ps-0 text-center">
      <li class="navbar-item py-3">
        <a href="#home" class="nav-link navbar-link">Home</a>
      </li>
      <li class="navbar-item py-3">
        <a href="#about" class="nav-link navbar-link">About</a>
      </li>
      <!-- <li class="navbar-item py-3">
        <a href="#projects" class="nav-link navbar-link">Projects</a>
      </li> -->
      <li class="navbar-item py-3">
        <a href="#contact" class="nav-link navbar-link">Contact</a>
      </li>
    </ul>
  </div>
</div>
