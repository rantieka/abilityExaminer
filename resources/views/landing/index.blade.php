@extends('layouts.landing')

@section('title', 'Home - MyCompany Recruitment')

@section('content')
<a href="https://api.whatsapp.com/send?phone=+628112108020&text=Hello,%20i%20want%20to%20ask%20about%20website" class="text-decoration-none whatsapp-image-wrapper shadow d-flex justify-content-center align-items-center">
  <img src="{{ asset('images/nineod/whatsapp.svg') }}" alt="whatsapp-image" class="whatsapp-image rounded-circle">
</a>
<div class="list-group position-fixed left-0 d-none d-sm-block">
  <ul class="pagination-section">
    <li class="page-item"><a href="#home" class="rounded-circle text-decoration-none"></a></li>
    <li class="page-item"><a href="#about" class="rounded-circle text-decoration-none"></a></li>
    <li class="page-item"><a href="#services" class="rounded-circle text-decoration-none"></a></li>
    <li class="page-item"><a href="#history" class="rounded-circle text-decoration-none"></a></li>
    <li class="page-item"><a href="#projects" class="rounded-circle text-decoration-none"></a></li>
    <li class="page-item"><a href="#testimonials" class="rounded-circle text-decoration-none"></a></li>
    <li class="page-item"><a href="#partners" class="rounded-circle text-decoration-none"></a></li>
    <li class="page-item"><a href="#contact"class="rounded-circle text-decoration-none"></a></li>
  </ul>
</div>
<section id="home" class="banner active-scroll my-5 mb-lg-0">
  <div class="container">
    <div class="banner-content row">
      <div class="col-lg-6 mb-5">
        <div class="banner-content-text text-center text-lg-start" data-aos="fade-right" data-aos-duration="2000">
          <h1 class="banner-content-headline fw-bold mb-4">We're Provide Enterprise Digital Solution</h1>
          <p class="banner-content-desc mb-4">Nineod is one of best IT Company in Indonesia We're specializing in software solutions and managed services. Our goal is to help our clients to give a digital solution with enterprise quality using top-notch technologies.</p>
          <!-- <a href="https://api.whatsapp.com/send?phone=+628112108020&text=Hello,%20i%20want%20to%20ask%20about%20website" class="btn-contact-us me-2 d-inline-block text-decoration-none btn-hover-yellow d-inline-flex rounded shadow-sm">
            <span class="m-auto">Contact Us</span>
          </a> -->
        </div>
      </div>
      <div class="col-lg-6">
        <div class="banner-content-img position-relative" data-aos="fade-left" data-aos-duration="2000">
          <div class="banner-blob-img d-lg-block d-none">
            <img src="{{ asset('images/nineod/blob-opt-1.png') }}" alt="blob-image" class="w-100">
          </div>
          <img src="{{ asset('images/nineod/image-hero.jpg') }}" alt="hero-image" class="img-hero shadow rounded">
        </div>
      </div>
    </div>
  </div>
</section>
<section id="about" class="about active-scroll">
  <div class="container">
    <div class="about-content row align-items-center">
      <div class="col-md-6 order-2 order-md-1">
        <div class="about-content-img" data-aos="fade-right" data-aos-duration="2000">
          <img src="{{ asset('images/nineod/about-image.jpg') }}" alt="about-image" class="w-100 mb-3 shadow rounded">
        </div>
      </div>
      <div class="col-md-6 order-1 order-md-2 mb-5">
        <div class="about-content-text" data-aos="fade-left" data-aos-duration="2000">
          <h1 class="fw-bold about-content-title">What We Have to Bussiness Owner</h1>
          <p class="about-content-desc">We mainly focus to help Business owner and support them using Our Digital Solution. And our client can focus with their idea, sales and leads.</p>
          <div class="d-flex align-items-center checklist-item mb-4">
            <i class="fas fa-check-circle fs-3 me-3"></i>
            <span class="fs-5 ">Trustworthy Partner</span>
          </div>
          <div class="d-flex align-items-center checklist-item mb-4">
            <i class="fas fa-check-circle fs-3 me-3"></i>
            <span class="fs-5 ">Quality Service</span>
          </div>
          <div class="d-flex align-items-center checklist-item mb-4">
            <i class="fas fa-check-circle fs-3 me-3"></i>
            <span class="fs-5 ">Competitive Price</span>
          </div>
          <!-- <div class="text-center text-sm-start w-100">
            <a href="https://api.whatsapp.com/send?phone=+628112108020&text=Hello,%20i%20want%20to%20ask%20about%20website" class="btn-contact-us me-2 d-inline-block text-decoration-none btn-hover-yellow d-inline-flex rounded shadow-sm"><span class="m-auto">Start Now</span></a>
          </div> -->
        </div>
      </div>
    </div>
  </div>
</section>
<section id="services" class="service active-scroll">
  <div class="container">
    <div class="service-header text-center mb-3">
      <h1 class="service-header-title fw-bold" data-aos="fade-in" data-aos-duration="2000">Our Work Process <span class="d-block">Strong Bussines</span></h1>
      <p class="service-header-desc" data-aos="fade-in" data-aos-duration="2500">We help our clients with 3 types of services.<span class="d-block">Custom Apps, Website Builder & Platform Builder</span></p>
    </div>
    <div class="row gy-3 service-list">
      <div class="col-md-4 service-item">
        <div class="service-item-content p-4 rounded shadow-sm" data-aos="fade-right" data-aos-duration="2000">
          <div class="service-item-img text-center mb-3">
            <img src="{{ asset('images/nineod/custom-web.svg') }}" alt="custom-web-image" class="about-types-image mb-3">
          </div>
          <div class="service-item-text text-center">
            <h4 class="fw-semibold mb-3">Custom Web App</h4>
            <p>We're provide a service to build a custom web app to match with our client needs or business process. Specially made for you.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4 service-item">
        <div class="service-item-content p-4 rounded shadow-sm" data-aos="fade-up" data-aos-duration="2000">
          <div class="service-item-img text-center mb-3">
            <img src="{{ asset('images/nineod/website-builder.svg') }}" alt="website-builder-image" class="about-types-image mb-3">
          </div>
          <div class="service-item-text text-center">
            <h4 class="fw-semibold mb-3">Website Builder</h4>
            <p>Build your own websites. Whether you’re promoting your business, showcasing your work, opening your store or starting a blog.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4 service-item">
        <div class="service-item-content p-4 rounded shadow-sm" data-aos="fade-left" data-aos-duration="2000">
          <div class="service-item-img text-center mb-3">
            <img src="{{ asset('images/nineod/platform.svg') }}" alt="platform-image" class="about-types-image mb-3">
          </div>
          <div class="service-item-text text-center">
            <h4 class="fw-semibold mb-3">Platform Builder</h4>
            <p>Platform Builder is the complete solution for entrepreneurs, influencers or retailers to starting an online business.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<section id="history" class="history active-scroll">
  <div class="container">
    <div class="history-header">
      <h1 class="history-header-title fw-bold" data-aos="fade-in" data-aos-duration="2000">Start Business <span class="d-block">Some of Our History</span></h1>
      <p class="history-header-desc" data-aos="fade-in" data-aos-duration="3000">We've helped many clients around the world’s and we also create our own histories from our great clients. And we do our business process remotely from our loved office to support our loved clients.</p>
    </div>
    <div class="history-body">
      <div class="row">
        <div class="col-md-6">
          <div class="history-body-wrap p-3 rounded h-100 mb-3 shadow-sm" data-aos="fade-right" data-aos-duration="2000">
            <div class="row px-3 py-2 h-100 align-items-center">
              <div class="col-md-6 history-item text-center pb-2 pb-md-0 mb-md-0">
                <span class="text-uppercase d-block">Project Complete</span>
                <span class="fs-1 fw-semibold">235</span>
              </div>
              <div class="col-md-6 history-item text-center py-2 py-md-0 mb-md-0">
                <span class="text-uppercase d-block">Manage Projects</span>
                <span class="fs-1 fw-semibold">132</span>
              </div>
              <div class="col-md-6 history-item text-center py-2 py-md-0 mb-md-0">
                <span class="text-uppercase d-block">New Client</span>
                <span class="fs-1 fw-semibold">164</span>
              </div>
              <div class="col-md-6 history-item text-center pt-2 pt-md-0">
                <span class="text-uppercase d-block">Happy Clients</span>
                <span class="fs-1 fw-semibold">132</span>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="mt-3 mt-sm-0 h-100" data-aos="fade-left" data-aos-duration="2000">
            <video src="https://res.cloudinary.com/hw6d6nsmh/video/upload/v1754716405/start-business_pn7jxl.mp4" 
                controls class="history-video object-cover shadow-sm rounded"></video>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- <section id="products" class="riungin active-scroll mb-3">
  <div class="container">
    <div class="riungin-header text-center m-auto">
      <h1 class="riungin-header-title fw-bold" data-aos="fade-in" data-aos-duration="2000">Meet Our New Product — Riung.in</h1>
      <p class="riungin-header-desc" data-aos="fade-in" data-aos-duration="3000">Your all-in-one platform to discover, book, and enjoy services — from lodging and sports venues to coworking spaces.</p>
    </div>
    <div class="riungin-body pt-3">
      <div class="row gy-3 service-list mb-3">
        <div class="col-md-4 service-item">
          <div class="card p-3 h-100 service-item-content text-center" data-aos="fade-right" data-aos-duration="2000">
            <img src="{{ asset('images/nineod/global-mono.png') }}" alt="global-mono-image" class="riungin-icon mx-auto my-2">
            <div class="riungin-desc">
              <h5 class="fw-semibold my-3">All services on a single platform</h5>
              <p>
                From lodging, sports fields, to coworking spaces, you can find and book everything directly on Riungin without any hassle.
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-4 service-item">
          <div class="card p-3 h-100 service-item-content text-center" data-aos="fade-up" data-aos-duration="2000">
            <img src="{{ asset('images/nineod/idea-mono.png') }}" alt="idea-mono-image" class="riungin-icon mx-auto my-2">
            <div class="book-riungin-desc">
              <h5 class="fw-semibold my-3">Smart recommendations tailored to your needs</h5>
              <p>
                Riungin helps you find the best options based on your location, category, and favorite activities.
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-4 service-item">
          <div class="card p-3 h-100 service-item-content text-center" data-aos="fade-left" data-aos-duration="2000">
            <img src="{{ asset('images/nineod/discount-mono.png') }}" alt="discount-mono-image" class="riungin-icon mx-auto my-2">
            <div class="book-riungin-desc">
              <h5 class="fw-semibold my-3">Exclusive offers and attractive promotions</h5>
              <p>
                Get special discounts only available at Riungin, for a more economical and enjoyable booking experience.
              </p>
            </div>
          </div>
        </div>
      </div>
      <div class="text-center" data-aos="fade-up" data-aos-duration="2000">
        <a href="https://www.riung.in/id" target="_blank" class="btn-mono d-inline-block text-decoration-none d-inline-flex rounded shadow-sm px-4 py-3 d-flex gap-2 m-auto">
          <i class="bi bi-rocket-takeoff fs-5"></i>
          <span class="m-auto">Explore Riungin</span>
        </a>
      </div>
    </div>
  </div>
</section> -->
<section id="projects" class="projects active-scroll">
  <div class="projects-header">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center flex-column flex-lg-row ">
        <h1 class="fw-bold text-center text-lg-start" data-aos="fade-right" data-aos-duration="2000">You Can See <span class="d-block">Our New Projects</span></h1>
        <p class="text-center text-lg-end" data-aos="fade-left" data-aos-duration="2000">Here is our histories. We've build and help <span class="d-block">many projects from our clients</span></p>
      </div>
    </div>
  </div>
  <div class="projects-body">
    <div class="container position-relative">
      <div class="projects-items row position-relative" style="width: 95%; margin: auto;">
        <div class="col-md-4">
          <div class="projects-body-detail p-3 mb-3 rounded shadow-sm">
            <div class="projects-category d-flex align-items-center gap-2">
              <span class="line"></span>
              <span class="text">Web Apps</span>
            </div>
            <div class="projects-category d-flex align-items-center justify-content-between gap-2">
              <span>Fabricantion.net</span>
              <a href="https://fabrication.net/" target="_blank" class="projects-link rounded-circle btn-hover-yellow text-decoration-none"><i class="fas fa-external-link-alt m-auto"></i></a>
            </div>
          </div>
          <div class="projects-body-img">
            <img src="{{ asset('images/nineod/fabrication-website.jpg') }}" alt="fabrication-website" class="w-100 shadow rounded">
          </div>
        </div>
        <div class="col-md-4">
          <div class="projects-body-img mb-3">
            <img src="{{ asset('images/nineod/nineodfreight-website.jpg') }}" alt="fabrication-website" class="w-100 shadow rounded">
          </div>
          <div class="projects-body-detail p-3 rounded shadow-sm">
            <div class="projects-category d-flex align-items-center gap-2">
              <span class="line"></span>
              <span class="text">Profile Company</span>
            </div>
            <div class="projects-category d-flex align-items-center justify-content-between gap-2">
              <span>Nineod Freight</span>
              <a href="http://www.nineodfreight.com/" target="_blank" class="projects-link rounded-circle btn-hover-yellow text-decoration-none"><i class="fas fa-external-link-alt m-auto"></i></a>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="projects-body-detail p-3 mb-3 rounded shadow-sm">
            <div class="projects-category d-flex align-items-center gap-2">
              <span class="line"></span>
              <span class="text">Web Apps</span>
            </div>
            <div class="projects-category d-flex align-items-center justify-content-between gap-2">
              <span>TQG</span>
              <a href="https://tqg2u.my/" target="_blank" class="projects-link rounded-circle btn-hover-yellow text-decoration-none"><i class="fas fa-external-link-alt m-auto"></i></a>
            </div>
          </div>
          <div class="projects-body-img">
            <img src="{{ asset('images/nineod/tqg-website.png') }}" alt="fabrication-website" class="w-100 shadow rounded">
          </div>
        </div>
        <div class="col-md-4">
          <div class="projects-body-img mb-3">
            <img src="{{ asset('images/nineod/edkl-website.png') }}" alt="fabrication-website" class="w-100 shadow rounded">
          </div>
          <div class="projects-body-detail p-3 rounded shadow-sm">
            <div class="projects-category d-flex align-items-center gap-2">
              <span class="line"></span>
              <span class="text">Web Apps</span>
            </div>
            <div class="projects-category d-flex align-items-center justify-content-between gap-2">
              <span>EDKL Website</span>
              <a href="https://www.eatdrinkkl.com/" target="_blank" class="projects-link rounded-circle btn-hover-yellow text-decoration-none"><i class="fas fa-external-link-alt m-auto"></i></a>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="projects-body-detail p-3 mb-3 rounded shadow-sm">
            <div class="projects-category d-flex align-items-center gap-2">
              <span class="line"></span>
              <span class="text">Web Apps</span>
            </div>
            <div class="projects-category d-flex align-items-center justify-content-between gap-2">
              <span>TVMS Website</span>
              <a href="https://tvms.tzuchi.com.my/" target="_blank" class="projects-link rounded-circle btn-hover-yellow text-decoration-none"><i class="fas fa-external-link-alt m-auto"></i></a>
            </div>
          </div>
          <div class="projects-body-img">
            <img src="{{ asset('images/nineod/tvms-website.png') }}" alt="fabrication-website" class="w-100 shadow rounded">
          </div>
        </div>
        <div class="col-md-4">
          <div class="projects-body-img mb-3">
            <img src="{{ asset('images/nineod/tcmc-website.png') }}" alt="fabrication-website" class="w-100 shadow rounded">
          </div>
          <div class="projects-body-detail p-3 rounded shadow-sm">
            <div class="projects-category d-flex align-items-center gap-2">
              <span class="line"></span>
              <span class="text">Web Apps</span>
            </div>
            <div class="projects-category d-flex align-items-center justify-content-between gap-2">
              <span>TCMC Website</span>
              <a href="https://tcmc.tzuchi.com.my/" target="_blank" class="projects-link rounded-circle btn-hover-yellow text-decoration-none"><i class="fas fa-external-link-alt m-auto"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<section id="testimonials" class="testimonials py-5 active-scroll">
  <div class="container">
    <div class="testimonials-header mb-4 text-center" data-aos="fade-in" data-aos-duration="2000">
      <h1 class="fw-bold">What Say <span class="d-block">Our Profesional Clients</span></h1>
    </div>
    <div class="testimonials-body" data-aos="fade-up" data-aos-duration="2000">
      <div class="testimonials-wrap position-relative rounded shadow-sm">
        <div class="testimonial-item p-4">
          <p>"Good Rails developer and I enjoyed working with their. Speed and communication were great and their Rails skills were good."</p>
          <div class="testimonials-user d-flex gap-2">
            <div class="testimonials-user-img">
              <img src="{{ asset('images/nineod/janet.jpg') }}" alt="janet-img" class="rounded-circle border border-2">
            </div>
            <div class="testimonial-user-name">
              <span class="d-block">Janet</span>
              <span class="testimonial-rating">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="testimonial-item p-4">
          <p>"The required work was done quickly and perfectly. Very effective. Thank you very much."</p>
          <div class="testimonials-user d-flex gap-2">
            <div class="testimonials-user-img">
              <img src="{{ asset('images/nineod/anna.jpg') }}" alt="anna-img" class="rounded-circle border border-2">
            </div>
            <div class="testimonial-user-name">
              <span class="d-block">Anna</span>
              <span class="testimonial-rating">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="testimonial-item p-4">
          <p>"Great communicator!Flexible and patient with adjustments on my end."</p>
          <div class="testimonials-user d-flex gap-2">
            <div class="testimonials-user-img">
              <img src="{{ asset('images/nineod/sergey.jpg') }}" alt="sergey-img" class="rounded-circle border border-2">
            </div>
            <div class="testimonial-user-name">
              <span class="d-block">Sergey</span>
              <span class="testimonial-rating">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<section id="partners" class="active-scroll">
  <div class="partners">
    <div class="container">
      <div class="partners-content row justify-content-center text-center" data-aos="fade-in" data-aos-duration="2000">
        <div class="col-6 col-md-3">
          <img src="{{ asset('images/nineod/github.png') }}" alt="github-img" class="partners-img">
        </div>
        <div class="col-6 col-md-3">
          <img src="{{ asset('images/nineod/heroku.png') }}" alt="github-img" class="partners-img">
        </div>
        <div class="col-6 col-md-3">
          <img src="{{ asset('images/nineod/toptal.png') }}" alt="github-img" class="partners-img">
        </div>
        <div class="col-6 col-md-3">
          <img src="{{ asset('images/nineod/nws.png') }}" alt="github-img" class="partners-img">
        </div>
        <div class="col-6 col-md-3">
          <img src="{{ asset('images/nineod/loop-yellow.png') }}" alt="github-img" class="partners-img">
        </div>
        <div class="col-6 col-md-3">
          <img src="{{ asset('images/nineod/hubstaff.png') }}" alt="github-img" class="partners-img">
        </div>
        <div class="col-6 col-md-3">
          <img src="{{ asset('images/nineod/open-build.png') }}" alt="github-img" class="partners-img">
        </div>
        <div class="col-6 col-md-3">
          <img src="{{ asset('images/nineod/slack.png') }}" alt="github-img" class="partners-img">
        </div>
        <div class="col-6 col-md-3">
          <img src="{{ asset('images/nineod/trello.png') }}" alt="github-img" class="partners-img">
        </div>
        <div class="col-6 col-md-3">
          <img src="{{ asset('images/nineod/upwork.png') }}" alt="github-img" class="partners-img">
        </div>
      </div>
    </div>
  </div>
  <!-- <div class="team py-5">
    <div class="container">
      <div class="team-header">
        <h1 class="fw-bold text-center" data-aos="fade-in" data-aos-duration="2000">Meet The Team</h1>
      </div>
      <div class="team-body d-flex justify-content-between align-items-center flex-column-reverse flex-lg-row">
        <div class="team-user-name text-center text-lg-start" data-aos="fade-right" data-aos-duration="2000">
          <span class="fs-2 d-block fw-semibold">Elvin Alvian</span>
          <p>FullStack Ruby on Rails Developer with Responsive Design experience</p>
          <div class="d-flex gap-2 justify-content-center justify-content-lg-start">
            <a href="https://www.upwork.com/freelancers/~0156c4edbc7b84b60c/" class="team-link text-decoration-none d-block p-2 rounded-circle btn-hover-yellow" target="_blank"><img src="{{ asset('images/nineod/upwork-logo.png') }}" alt="github-img" class="team-user-icons"></a>
            <a href="https://www.linkedin.com/in/3lviend/" class="team-link text-decoration-none d-block p-2 rounded-circle btn-hover-yellow" target="_blank"><img src="{{ asset('images/nineod/linkedin-logo.png') }}" alt="github-img" class="team-user-icons"></a>
          </div>
        </div>
        <div class="team-image-wrap" data-aos="fade-left" data-aos-duration="2000">
          <img src="{{ asset('images/nineod/ceo.png') }}" alt="github-img" class="img-fluid team-user-img mb-3">
        </div>
      </div>
    </div>
  </div> -->
</section>
<section id="contact" class="contact py-5 active-scroll">
  <div class="container">
    <div class="contact-header text-center">
      <h1 class="fw-bold">Interested in discussing?</h1>
      <p>We're open for any suggestion or just to have a chat</p>
    </div>
    <div class="contact-body">
      <div class="contact-body-info mb-4">
        <div class="row">
          <div class="col-md-4">
            <a href="tel:+62%20811-2108-020" target="_blank" class="contact-body-detail text-decoration-none text-center p-3 rounded d-flex flex-sm-column align-items-center w-100">
              <i class="fas fa-phone d-block fs-3 me-3 me-sm-0 mb-sm-2"></i>
              <span class="text-decoration-none">+62 811-2108-020</span>
            </a>
          </div>
          <div class="col-md-4">
            <a href="mailto:admin@nineod.com" target="_blank" class="contact-body-detail text-center p-3 rounded d-flex flex-sm-column gx-3 align-items-center text-decoration-none w-100">
              <i class="fas fa-envelope d-block fs-3 mb-sm-2 me-3 me-sm-0"></i>
              <span class="">admin@nineod.com</span>
            </a>
          </div>
          <div class="col-md-4">
            <a href="https://goo.gl/maps/PYH4nFhexuqaSpYR9" target="_blank" class="contact-body-detail text-center p-3 rounded d-flex flex-sm-column gx-3 align-items-center text-decoration-none w-100">
              <i class="fa fa-map-marker-alt d-block fs-3 mb-sm-2 me-3 me-sm-0"></i>
              <span class="text-decoration-none">Jl. Sentra Raya No. 11</span>
            </a>
          </div>
        </div>
      </div>
      <div class="contact-body-form p-4 rounded shadow-sm">
        <form action="#" method="POST" class="row gy-3" id="form-contact">
          <div class="col-md-6">
            <div class="form-group">
              <input type="text" name="name" class="contact-input" placeholder="Name" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <input type="email" name="email" class="contact-input" placeholder="Email" required>
            </div>
          </div>
          <div class="col-md-12">
            <div class="form-group">
              <input type="text" name="title" class="contact-input" placeholder="Title" required>
            </div>
          </div>
          <div class="col-md-12">
            <div class="form-group">
              <textarea name="message" class="contact-input" placeholder="Message" cols="30" rows="7" required></textarea>
            </div>
          </div>
          <div class="col-md-12 text-center">
            <button type="submit" class="btn-contact-submit w-100 rounded">Send</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
@endsection