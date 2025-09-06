@livewireStyles
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">

<head>
  <meta charset="utf-8" />
  <title>Ecomus - Ultimate HTML</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="author" content="themesflat.com" />
  <meta
    name="viewport"
    content="width=device-width, initial-scale=1, maximum-scale=1" />
  <meta
    name="description"
    content="Themesflat Ecomus - A modern and versatile eCommerce template designed for various online stores, including fashion, furniture, electronics, and more. SEO-friendly, fast-loading, and highly customizable." />

  <!-- font -->
  <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}" />
  <!-- Icons -->
  <link rel="stylesheet" href="{{ asset('fonts/font-icons.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/image-compare-viewer.min.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/swiper-bundle.min.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/animate.css') }}" />
  <link rel="stylesheet" type="text/css" href="{{ asset('css/styles.css') }}" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />

  <!-- Favicon and Touch Icons  -->
  <link rel="shortcut icon" href="{{ asset('images/logo/favicon.png') }}" />
  <link rel="apple-touch-icon-precomposed" href="{{ asset('images/logo/favicon.png') }}" />

  <style>
    .recovery-input {
      letter-spacing: 1px;
      text-transform: uppercase;
    }
  </style>


  <style>
    .service-card {
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      width: 100px;
      height: 100px;
      border: 1px solid #ddd;
      border-radius: 15px;
      margin: 10px;
      padding: 10px 5px;
      text-align: center;
      transition: transform 0.2s;
    }

    .service-card:hover {
      transform: scale(1.05);
    }

    .service-card i {
      font-size: 1rem;
      margin-bottom: 10px;
    }

    .service-card .card-title {
      font-size: 0.8rem;
      font-weight: 500;
      line-height: 1rem;
    }

    @media (max-width: 768px) {
      .service-card {
        width: 140px;
        height: 140px;
      }

      .service-card .card-title {
        font-size: 0.9rem;
      }
    }

    @media (max-width: 576px) {
      .service-card {
        width: 120px;
        height: 120px;
      }

      .service-card .card-title {
        font-size: 0.8rem;
      }
    }

    .card-title {
      font-weight: bold;
      font-size: 1rem;
    }

    .price {
      font-weight: 600;
    }

    .username {
      font-weight: 600;
    }

    .rating i {
      color: gold;
    }

    .badge-custom {
      font-size: 0.7rem;
      padding: 3px 8px;
      border-radius: 5px;
    }

    .vetted {
      background: #e5e5f7;
      color: #2f3a94;
    }

    .top-rated {
      background: #ffe3b3;
      color: #a85b00;
    }

    .text-section {
      font-size: 1.1rem;
      color: white;
    }

    .rounded-img {
      border-radius: 12px;
      width: 100%;
      height: auto;
      object-fit: cover;
    }

    .service-card {
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      width: 100px;
      height: 100px;
      border: 1px solid #ddd;
      border-radius: 15px;
      margin: 10px;
      padding: 10px 5px;
      text-align: center;
      transition: transform 0.2s;
    }

    .service-card:hover {
      transform: scale(1.05);
    }

    .service-card i {
      font-size: 1rem;
      margin-bottom: 10px;
    }

    .service-card .card-title {
      font-size: 0.8rem;
      font-weight: 500;
      line-height: 1rem;
    }

    @media (max-width: 768px) {
      .service-card {
        width: 140px;
        height: 140px;
      }

      .service-card .card-title {
        font-size: 0.9rem;
      }
    }

    @media (max-width: 576px) {
      .service-card {
        width: 120px;
        height: 120px;
      }

      .service-card .card-title {
        font-size: 0.8rem;
      }
    }

    .card-title {
      font-weight: bold;
      font-size: 1rem;
    }

    .price {
      font-weight: 600;
    }

    .username {
      font-weight: 600;
    }

    .rating i {
      color: gold;
    }

    .badge-custom {
      font-size: 0.7rem;
      padding: 3px 8px;
      border-radius: 5px;
    }

    .vetted {
      background: #e5e5f7;
      color: #2f3a94;
    }

    .top-rated {
      background: #ffe3b3;
      color: #a85b00;
    }

    .text-section {
      font-size: 1.1rem;
      color: #5a5a5a;
    }

    .rounded-img {
      border-radius: 12px;
      width: 100%;
      height: auto;
      object-fit: cover;
    }

    .category-wrapper {
      position: relative;
    }

    .scroll-container {
      overflow-x: auto;
      white-space: nowrap;
      -webkit-overflow-scrolling: touch;
      scroll-behavior: smooth;
      padding: 10px 40px;
      /* space for arrows */
    }

    .scroll-container::-webkit-scrollbar {
      display: none;
    }

    .category-btn {
      display: inline-block;
      margin-right: 10px;
      border-radius: 50px;
      padding: 10px 20px;
      font-size: 0.95rem;
      font-weight: 500;
      background-color: #f1f1f1;
      border: 1px solid #ccc;
      transition: 0.3s ease;
      white-space: nowrap;
    }

    .category-btn:hover {
      background-color: #e0e0e0;
    }

    .category-btn.active {
      background-color: #Ceff1b;
      color: black;
      border-color: #Ceff1b;
    }

    .scroll-arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background-color: white;
      border: 1px solid #ccc;
      border-radius: 50%;
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      z-index: 10;
    }

    .scroll-arrow.left {
      left: 0;
    }

    .scroll-arrow.right {
      right: 0;
    }
  </style>

  <style>
    /* Force open when we add .is-open on wrappers */
    .tf-form-search.is-open .search-suggests-results,
    #canvasSearch .tf-search-sticky.is-open .search-suggests-results {
      display: block !important;
      opacity: 1;
      visibility: visible;
      background-color: black;
    }

    .tf-form-search:hover .search-suggests-results {
      opacity: 1 !important;
      pointer-events: auto !important;
    }

    .search-suggests-results .popular-badge {
      display: inline-flex !important;
      /* not full-width */
      align-items: center;
      justify-content: center;
      padding: 2px 10px;
      height: 20px;
      border-radius: 9999px;
      background: #CEFF1B;
      /* your theme lime */
      color: #000;
      font-weight: 700;
      font-size: 11px;
      line-height: 1;
      letter-spacing: .2px;
      width: auto !important;
      /* override any 100%/block rules */
      min-width: 0 !important;
      text-transform: uppercase;
      margin-top: 2px;
      margin-left: 5px;
      /* slight baseline align */
    }

    .tf-form-search .search-suggests-results {
      box-shadow: none !important;
    }

    /* Suggest item layout like your screenshot */
    .search-suggests-results .search-result-item {
      display: flex;
      gap: 10px;
      align-items: center;

      padding: 8px;
      border-radius: 8px;
      text-decoration: none;
    }

    .search-suggests-results .search-result-item:hover {
      border: 1px solid #Ceff1b;
    }

    .search-suggests-results .img-box {
      width: 48px;
      height: 48px;
      flex: 0 0 48px;
      overflow: hidden;
      border-radius: 6px;
    }

    .search-suggests-results .img-box img {
      width: 48px;
      height: 48px;
      object-fit: cover;
      display: block;
    }

    .search-suggests-results .box-content {
      line-height: 1.25;
    }

    .search-suggests-results .box-content .title {
      margin: 0 0 2px 0;
      font-weight: 600;
      font-size: 14px;
    }

    .search-suggests-results .box-content .rating {
      font-size: 14px;
      color: #888;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .search-suggests-results .box-content .rating .star {
      color: #CEFF1B;
      margin-right: 2px;
      font-size: 14px;
    }

    .search-suggests-results .box-content .price {
      margin-top: 2px;
      font-weight: 700;
    }

    /* “No results” row */
    .search-suggests-results .empty-row {
      padding: 10px;
      color: #888;
    }

    /* section label + divider */
    .search-suggests-results .section-label {
      padding: 6px 10px;
      font-size: 12px;
      color: #6b7280;
      letter-spacing: .02em;
    }

    .search-suggests-results .section-divider {
      height: 1px;
      background: #f1f5f9;
      margin: 6px 0;
    }

    /* Position + visibility control */
    .tf-form-search {
      position: relative;
    }

    .tf-form-search .search-suggests-results {
      position: absolute;
      top: calc(100% + 8px);
      left: 0;
      width: 100%;
      max-width: min(92vw, 640px);
      max-height: 420px;
      overflow-y: auto;
      background: #0b0b0b;
      /* matches your dark header */
      border: 1px solid #2a2a2a;
      border-radius: 12px;
      box-shadow: 0 12px 24px rgba(0, 0, 0, .35);
      opacity: 0;
      visibility: hidden;
      transform: translateY(4px);
      transition: opacity .15s, transform .15s, visibility .15s;
      pointer-events: none;
      z-index: 3000;
    }

    /* Open state (set by JS) */
    .tf-form-search .search-suggests-results.open {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
      pointer-events: auto;
    }

    /* Make each suggestion fully clickable */
    .search-suggests-results a.search-result-item {
      display: flex;
      gap: 12px;
      padding: 10px 12px;
      text-decoration: none;
      color: #fff;
    }

    .search-suggests-results a.search-result-item:hover {
      background: #141414;
    }

    .search-suggests-results .img-box img {
      width: 64px;
      height: 64px;
      object-fit: cover;
      border-radius: 8px;
    }

    /* "No products found" row */
    .search-suggests-results .no-results {
      padding: 12px;
      color: #9aa0a6;
    }

    /* Your small POPULAR pill badge beside title (optional) */
    .search-suggests-results .title-with-badge {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .search-suggests-results .popular-badge {
      display: inline-flex;
      padding: 2px 10px;
      border-radius: 999px;
      background: #CEFF1B;
      color: #000;
      font-weight: 700;
      font-size: 11px;
      line-height: 1;
    }
  </style>

</head>

<body class="preload-wrapper color-primary-10">
  <!-- RTL -->
  <a href="#" id="toggle-rtl" class="tf-btn animate-hover-btn btn-fill">RTL</a>
  <!-- /RTL  -->
  <!-- preload -->
  <div class="preload preload-container">
    <div class="preload-logo">
      <div class="spinner"></div>
    </div>
  </div>
  <!-- /preload -->
  <div id="wrapper">
    <!-- Top bar -->
    <!-- <div class="tf-top-bar bg_grey-7">
            <div class="px_15 lg-px_40">
                <div class="tf-top-bar_wrap grid-3 gap-30 align-items-center">
                    <div class="tf-top-bar_left">
                        <ul class="d-flex gap-20">
                            <li>
                                <a href="contact-1.html" class="fw-5">Contact</a>
                            </li>
                            <li>
                                <a href="blog-grid.html" class="fw-5">Blog</a>
                            </li>
                            <li>
                                <a href="order-tracking.html" class="fw-5">Order Tracking</a>
                            </li>
                        </ul>
                    </div>
                    <div class="text-center overflow-hidden">
                        <div dir="ltr" class="swiper tf-sw-top_bar" data-preview="1" data-space="0" data-loop="true"
                            data-speed="1000" data-delay="2000">
                            <div class="swiper-wrapper">
                                <div class="swiper-slide">
                                    <p class="top-bar-text fw-5">Spring Sale: Sweet Crunchy Salad. <a
                                            href="shop-default.html" title="all collection" class="tf-btn btn-line">Shop
                                            now<i class="icon icon-arrow1-top-left"></i></a></p>
                                </div>
                                <div class="swiper-slide">
                                    <p class="top-bar-text fw-5">Summer sale discount off 70%</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="top-bar-language tf-cur justify-content-end">
                        <div class="d-inline-block">
                            Need help?
                            <span class="fw-7">
                                Call Us: <a href="tel:+18001090" style="text-decoration: underline;"
                                    aria-describedby="external-message">+18001090</a>
                            </span>
                        </div>
                        <div class="tf-currencies">
                            <select class="image-select center style-default type-currencies">
                                <option data-thumbnail="images/country/fr.svg">EUR € | France</option>
                                <option data-thumbnail="images/country/de.svg">EUR € | Germany</option>
                                <option selected data-thumbnail="images/country/us.svg">USD $ | United States</option>
                                <option data-thumbnail="images/country/vn.svg">VND ₫ | Vietnam</option>
                            </select>
                        </div>
                        <div class="tf-languages">
                            <select class="image-select center style-default type-languages">
                                <option>English</option>
                                <option>العربية</option>
                                <option>简体中文</option>
                                <option>اردو</option>
                            </select>
                        </div>

                    </div>
                </div>
            </div>
        </div> -->
    <!-- /Top bar -->
    <!-- header -->
    <header id="header" class="header-default header-style-2 header-style-4">
      <div class="main-header line">
        <div class="container">
          <div class="row wrapper-header align-items-center">
            <div class="col-md-4 col-3 tf-lg-hidden">
              <a
                href="#mobileMenu"
                data-bs-toggle="offcanvas"
                aria-controls="offcanvasLeft">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="24"
                  height="16"
                  viewBox="0 0 24 16"
                  fill="none">
                  <path
                    d="M2.00056 2.28571H16.8577C17.1608 2.28571 17.4515 2.16531 17.6658 1.95098C17.8802 1.73665 18.0006 1.44596 18.0006 1.14286C18.0006 0.839753 17.8802 0.549063 17.6658 0.334735C17.4515 0.120408 17.1608 0 16.8577 0H2.00056C1.69745 0 1.40676 0.120408 1.19244 0.334735C0.978109 0.549063 0.857702 0.839753 0.857702 1.14286C0.857702 1.44596 0.978109 1.73665 1.19244 1.95098C1.40676 2.16531 1.69745 2.28571 2.00056 2.28571ZM0.857702 8C0.857702 7.6969 0.978109 7.40621 1.19244 7.19188C1.40676 6.97755 1.69745 6.85714 2.00056 6.85714H22.572C22.8751 6.85714 23.1658 6.97755 23.3801 7.19188C23.5944 7.40621 23.7148 7.6969 23.7148 8C23.7148 8.30311 23.5944 8.59379 23.3801 8.80812C23.1658 9.02245 22.8751 9.14286 22.572 9.14286H2.00056C1.69745 9.14286 1.40676 9.02245 1.19244 8.80812C0.978109 8.59379 0.857702 8.30311 0.857702 8ZM0.857702 14.8571C0.857702 14.554 0.978109 14.2633 1.19244 14.049C1.40676 13.8347 1.69745 13.7143 2.00056 13.7143H12.2863C12.5894 13.7143 12.8801 13.8347 13.0944 14.049C13.3087 14.2633 13.4291 14.554 13.4291 14.8571C13.4291 15.1602 13.3087 15.4509 13.0944 15.6653C12.8801 15.8796 12.5894 16 12.2863 16H2.00056C1.69745 16 1.40676 15.8796 1.19244 15.6653C0.978109 15.4509 0.857702 15.1602 0.857702 14.8571Z"
                    fill="currentColor"></path>
                </svg>
              </a>
            </div>
            <div class="col-md-4 col-6">
              <a href="index.html" class="logo-header">
                <img src="{{ asset('images/logo/logo.svg') }}" alt="logo" class="logo" />
              </a>
            </div>
            <div class="col-md-4 col-6 tf-md-hidden">
              <div class="tf-form-search">
                <form
                  action="#"
                  class="search-box">
                  <input type="text" placeholder="Search product" />
                  <button class="tf-btn">
                    <i class="icon icon-search"></i>
                  </button>
                </form>
                <div class="search-suggests-results">
                  <div class="search-suggests-results-inner">
                    <ul>
                      <li class="empty-row">Type to search</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4 col-3">
              <ul
                class="nav-icon d-flex justify-content-end align-items-center gap-20">
                <li class="nav-search">
                  <a
                    href="#canvasSearch"
                    data-bs-toggle="offcanvas"
                    aria-controls="offcanvasLeft"
                    class="nav-icon-item"><i class="icon icon-search"></i></a>
                </li>
                @php $isLogged = auth()->check() || session('user_id'); @endphp

                <li class="nav-account">
                  @if($isLogged)
                  {{-- Logged in: show only the icon, go to dashboard --}}
                  <a href="{{ route('user.admin.index') }}" class="nav-icon-item align-items-center gap-10" title="Dashboard">
                    <i class="icon icon-account"></i>
                  </a>
                  @else
                  {{-- Guest: show icon + text, open login modal --}}
                  <a href="#login" data-bs-toggle="modal" class="nav-icon-item align-items-center gap-10">
                    <i class="icon icon-account"></i>
                    <span class="text">Login</span>
                  </a>
                  @endif
                </li>


                <li class="nav-cart cart-lg">
                  <a href="#wishlistModal"
                    data-bs-toggle="modal"
                    class="nav-icon-item"
                    id="wishlistIconBtn">
                    <i class="icon icon-heart" style="font-size: 20px;"></i>
                    <span class="count-box" style="color: black !important;" id="wishlistCountBadge">0</span>
                  </a>
                </li>

              </ul>
            </div>
          </div>
        </div>
      </div>
      <div class="header-bottom line tf-md-hidden">
        <div class="container">
          <div
            class="wrapper-header d-flex justify-content-between align-items-center">
            <div class="box-left">
              <nav class="box-navigation text-center">
                <ul
                  class="box-nav-ul d-flex align-items-center justify-content-center gap-30">
                  <li class="menu-item">
                    <a href="{{ route('home') }}" class="item-link">Home</a>
                  </li>
                  <li class="menu-item">
                    <a href="{{ route('marketplace') }}" class="item-link">Marketplace</a>
                  </li>
                  <!-- <li class="menu-item">
    <a href="{{ route('forum') }}" class="item-link">Forum</a>
</li> -->

                  <!-- <li class="menu-item">
                                        <a href="blog-grid.html" class="item-link">Blog</a>
                                    </li> -->
                </ul>
              </nav>
            </div>
            <div class="box-right">
              <div class="icon">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="28"
                  height="28"
                  viewBox="0 0 28 28"
                  fill="none">
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M2.21989 13.7008C2.19942 13.7199 2.18295 13.743 2.17143 13.7685C2.1599 13.7941 2.15354 13.8217 2.15272 13.8497V18.5857C2.15272 19.4124 2.83298 20.0926 3.65962 20.0926H5.5256C5.64874 20.0926 5.74087 20.0005 5.74087 19.8774V13.8497C5.73977 13.793 5.71674 13.7389 5.6766 13.6987C5.63647 13.6586 5.58235 13.6356 5.5256 13.6345H2.36799C2.3118 13.6361 2.25855 13.66 2.21989 13.7008ZM0 13.8497C0.00339224 13.2228 0.253966 12.6224 0.697317 12.1791C1.14067 11.7357 1.74101 11.4851 2.36799 11.4817H5.5256C6.15335 11.4827 6.75513 11.7324 7.19902 12.1763C7.64291 12.6202 7.89268 13.222 7.89359 13.8497V19.8774C7.89428 20.1885 7.83349 20.4967 7.71473 20.7844C7.59597 21.072 7.42157 21.3333 7.20154 21.5533C6.98152 21.7733 6.7202 21.9477 6.4326 22.0665C6.14499 22.1852 5.83676 22.246 5.5256 22.2453H3.65962C1.64468 22.2453 0 20.6007 0 18.5857V13.8497Z"
                    fill="#253D4E"></path>
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M13.9927 2.15272C12.8144 2.1517 11.6476 2.38302 10.5588 2.83344C9.47008 3.28386 8.48083 3.94455 7.64769 4.77769C6.81455 5.61083 6.15387 6.60007 5.70345 7.68882C5.25303 8.77756 5.02171 9.94444 5.02273 11.1227V12.5719C5.02273 12.8574 4.90933 13.1311 4.70747 13.333C4.50561 13.5348 4.23184 13.6482 3.94637 13.6482C3.6609 13.6482 3.38712 13.5348 3.18527 13.333C2.98341 13.1311 2.87001 12.8574 2.87001 12.5719V11.1227C2.87001 4.97451 7.84451 0 13.9927 0C20.1409 0 25.1154 4.97451 25.1154 11.1227V12.5581C25.1154 12.8436 25.002 13.1174 24.8001 13.3192C24.5982 13.5211 24.3245 13.6345 24.039 13.6345C23.7535 13.6345 23.4798 13.5211 23.2779 13.3192C23.076 13.1174 22.9626 12.8436 22.9626 12.5581V11.1227C22.9626 6.16281 18.9525 2.15272 13.9927 2.15272ZM24.107 20.1133C24.2457 20.1411 24.3775 20.1959 24.495 20.2746C24.6124 20.3534 24.7132 20.4545 24.7916 20.5722C24.87 20.6899 24.9244 20.8219 24.9517 20.9607C24.979 21.0994 24.9788 21.2422 24.9509 21.3808C24.1914 25.1601 20.859 28 16.8627 28H15.4281C15.1426 28 14.8689 27.8866 14.667 27.6847C14.4652 27.4829 14.3518 27.2091 14.3518 26.9236C14.3518 26.6382 14.4652 26.3644 14.667 26.1625C14.8689 25.9607 15.1426 25.8473 15.4281 25.8473H16.8627C18.2705 25.8473 19.635 25.3603 20.7245 24.4688C21.8141 23.5773 22.5617 22.3362 22.8404 20.9563C22.8967 20.6766 23.0617 20.4307 23.2992 20.2726C23.5367 20.1146 23.8273 20.0572 24.107 20.1133Z"
                    fill="#253D4E"></path>
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M22.3117 13.7008C22.2912 13.7199 22.2747 13.743 22.2632 13.7685C22.2517 13.7941 22.2453 13.8217 22.2445 13.8497V19.8774C22.2445 19.9936 22.3444 20.0926 22.4598 20.0926H24.2543C25.124 20.0926 25.8326 19.3831 25.8326 18.5134V13.8497C25.8315 13.793 25.8085 13.7389 25.7684 13.6987C25.7282 13.6586 25.6741 13.6356 25.6174 13.6345H22.4598C22.4036 13.6361 22.3503 13.66 22.3117 13.7008ZM20.0918 13.8497C20.0952 13.2228 20.3457 12.6224 20.7891 12.1791C21.2324 11.7357 21.8328 11.4851 22.4598 11.4817H25.6174C26.2451 11.4827 26.8469 11.7324 27.2908 12.1763C27.7347 12.6202 27.9845 13.222 27.9854 13.8497V18.5134C27.9847 19.5028 27.5914 20.4515 26.8918 21.1512C26.1923 21.8509 25.2437 22.2444 24.2543 22.2453H22.4598C21.832 22.2444 21.2302 21.9947 20.7863 21.5508C20.3425 21.1069 20.0927 20.5051 20.0918 19.8774V13.8497Z"
                    fill="#253D4E"></path>
                </svg>
              </div>
              <div class="number d-grid">
                <a href="tel:1900100888" class="phone">1900100888</a>
                <span class="fw-5 text">Support Center</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>
    <!-- /header -->