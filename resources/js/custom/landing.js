
$(document).ready(function () {
  // Slick
  $(".projects-items").slick({
    prevArrow: '<button type="button" class="projects-items-prev slick-prev fs-3 border-0"><i class="fa-solid fa-chevron-left fs-4 text-dark"></i></button>',
    nextArrow: '<button type="button" class="projects-items-next slick-next fs-3 border-0"><i class="fa-solid fa-chevron-right fs-4 text-dark"></i></button>',
    dots: true,
    slidesToShow: 3,
    slidesToScroll: 3,
    responsive: [
      {
        breakpoint: 768,
        settings: {
          slidesToShow: 1,
          slidesToScroll: 1,
        },
      },
    ],
    speed: 800,
    cssEase: "cubic-bezier(.87,0,.13,1)",
  });

  $(".testimonials-wrap").slick({
    prevArrow: "",
    nextArrow: "",
    autoplay: false,
    dots: true,
    speed: 800,
    autoplaySpeed: 5000,
    cssEase: "cubic-bezier(.87,0,.13,1)",
  });

  // Active scroll
  function updateActiveState() {
    var scrollPos = $(window).scrollTop();
    var currentId = "";

    $(".active-scroll").each(function () {
      const sectionRowTop = $(this).offset().top - 200;
      if (scrollPos > sectionRowTop) {
        currentId = $(this).attr("id");
      }
    });

    if (currentId) {
      $(".navbar-link").removeClass("active");
      $('.navbar-link[href="#' + currentId + '"]').addClass("active");

      $(".page-item a").removeClass("active");
      $('.page-item a[href="#' + currentId + '"]').addClass("active");

      $(".active-scroll").removeClass("active");
      $("#" + currentId).addClass("active");
    }
  }

  $(window).scroll(function () {
    updateActiveState();
  });

  // Trigger on load
  updateActiveState();

  // Page scroll
  $(".nav-scroll, .navbar-link, .page-item a").click(function (e) {
    const element = $(this).attr("href").replace(/\//g, "");
    const goalElement = $(element);
    $("html").animate({
      scrollTop: goalElement.offset().top - 90,
    });
    $('.navbar-mobile').removeClass('active')
    $('.backdrop-navbar-mobile').fadeOut(400)
    $('body').removeClass('overflow-hidden')
    e.preventDefault();
  });

  // Burger menu
  $('#humburger-menu-toggle').click(function () {
    $('.navbar-mobile').addClass('active')
    $('.backdrop-navbar-mobile').fadeIn(400)
    $('body').addClass('overflow-hidden')
  })

  $('#btn-close-navbar').click(function () {
    $('.navbar-mobile').removeClass('active')
    $('.backdrop-navbar-mobile').fadeOut(400)
    $('body').removeClass('overflow-hidden')
  })

  $(document).mouseup(function (e) {
    var navbarMobile = $('.navbar-mobile');
    if (!navbarMobile.is(e.target) && navbarMobile.has(e.target).length === 0) {
      navbarMobile.removeClass('active')
      $('.backdrop-navbar-mobile').fadeOut(400)
      $('body').removeClass('overflow-hidden')
    }
  });

  // AOS animation
  AOS.init({
    once: true,
    disable: function () {
      var maxWidth = 768;
      return window.innerWidth < maxWidth;
    }
  });

  // Search On section faq
  $(".search-icon").click(function () {
    $(this).toggleClass("active");
    $(".input-search").toggleClass("active");
    $(".btn-close").toggleClass("active");
  });
  $(".btn-close").click(function () {
    $(this).toggleClass("active");
    $(".input-search").toggleClass("active");
    $(".search-icon").toggleClass("active");
  });
  var showError = true;
  $(".message-empty").hide();
  $(".input-search").on("keyup", function () {
    const value = $(this).val().toLowerCase();
    if (value === "") {
      $(".message-empty").hide();
    } else if (showError) {
      $(".message-empty").show();
    }
    $(".accordion-item").each(function () {
      const htxt = $(this).text().toString().toLowerCase();
      const accIndex = $(this).index();
      if (htxt.indexOf(value) > -1) {
        $(this).show();
        $("#accordion-item-" + accIndex).attr("filter-key", value).show();
        showError = false;
      } else if ($(".accordion-item:visible").length === 0) {
        $(".message-empty").show();
      } else {
        $(this).hide();
        $(".message-empty").hide();
      }
    });
  });
});
