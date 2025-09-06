@livewireStyles
<!DOCTYPE html>
<html lang="en">


<!-- index.html  21 Nov 2019 03:44:50 GMT -->

<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <title>Admin Dashboard</title>
  <script>
    (function($) {
      if (!$ || !$.fn) return;
      if (!$.fn.niceScroll) {
        $.fn.niceScroll = function() {
          return this;
        };
      }
    })(window.jQuery);
  </script>

  <!-- General CSS Files -->
  <link rel="stylesheet" href="{{ asset('assets/css/app.min.css') }}">
  <!-- Template CSS -->
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/components.css') }}">
  <!-- Custom style CSS -->
  <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
  <link rel='shortcut icon' type='image/x-icon' href='{{ asset('assets/img/favicon.ico') }}' />

  <link rel="stylesheet" href="{{ asset('assets/bundles/bootstrap-social/bootstrap-social.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/bundles/summernote/summernote-bs4.css') }}">
</head>

<style>
  #modeSwitchItem .custom-switch {
    padding-left: 0 !important;
  }
</style>

<body>
  <div class="loader"></div>
  <div id="app">
    <div class="main-wrapper main-wrapper-1">
      <div class="navbar-bg"></div>
      <nav class="navbar navbar-expand-lg main-navbar sticky">
        <div class="form-inline mr-auto">
          <ul class="navbar-nav mr-3">
            <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg
									collapse-btn"> <i data-feather="align-justify"></i></a></li>
            <li><a href="#" class="nav-link nav-link-lg fullscreen-btn">
                <i data-feather="maximize"></i>
              </a></li>
            <li>
            </li>
          </ul>
        </div>
        <ul class="navbar-nav navbar-right">
          <li class="dropdown dropdown-list-toggle"><a href="#" data-toggle="dropdown"
              class="nav-link nav-link-lg message-toggle"><i data-feather="mail"></i>
              <span class="badge headerBadge1">
                6 </span> </a>
            <div class="dropdown-menu dropdown-list dropdown-menu-right pullDown">
              <div class="dropdown-header">
                Messages
                <div class="float-right">
                  <a href="#">Mark All As Read</a>
                </div>
              </div>
              <div class="dropdown-list-content dropdown-list-message">
                <a href="#" class="dropdown-item"> <span class="dropdown-item-avatar
											text-white"> <img alt="image" src="{{ asset('assets/img/users/user-1.png') }}" class="rounded-circle">
                  </span> <span class="dropdown-item-desc"> <span class="message-user">John
                      Deo</span>
                    <span class="time messege-text">Please check your mail !!</span>
                    <span class="time">2 Min Ago</span>
                  </span>
                </a> <a href="#" class="dropdown-item"> <span class="dropdown-item-avatar text-white">
                    <img alt="image" src="{{ asset('assets/img/users/user-2.png') }}" class="rounded-circle">
                  </span> <span class="dropdown-item-desc"> <span class="message-user">Sarah
                      Smith</span> <span class="time messege-text">Request for leave
                      application</span>
                    <span class="time">5 Min Ago</span>
                  </span>
                </a> <a href="#" class="dropdown-item"> <span class="dropdown-item-avatar text-white">
                    <img alt="image" src="{{ asset('assets/img/users/user-5.png') }}" class="rounded-circle">
                  </span> <span class="dropdown-item-desc"> <span class="message-user">Jacob
                      Ryan</span> <span class="time messege-text">Your payment invoice is
                      generated.</span> <span class="time">12 Min Ago</span>
                  </span>
                </a> <a href="#" class="dropdown-item"> <span class="dropdown-item-avatar text-white">
                    <img alt="image" src="{{ asset('assets/img/users/user-4.png') }}" class="rounded-circle">
                  </span> <span class="dropdown-item-desc"> <span class="message-user">Lina
                      Smith</span> <span class="time messege-text">hii John, I have upload
                      doc
                      related to task.</span> <span class="time">30
                      Min Ago</span>
                  </span>
                </a> <a href="#" class="dropdown-item"> <span class="dropdown-item-avatar text-white">
                    <img alt="image" src="{{ asset('assets/img/users/user-3.png') }}" class="rounded-circle">
                  </span> <span class="dropdown-item-desc"> <span class="message-user">Jalpa
                      Joshi</span> <span class="time messege-text">Please do as specify.
                      Let me
                      know if you have any query.</span> <span class="time">1
                      Days Ago</span>
                  </span>
                </a> <a href="#" class="dropdown-item"> <span class="dropdown-item-avatar text-white">
                    <img alt="image" src="{{ asset('assets/img/users/user-2.png') }}" class="rounded-circle">
                  </span> <span class="dropdown-item-desc"> <span class="message-user">Sarah
                      Smith</span> <span class="time messege-text">Client Requirements</span>
                    <span class="time">2 Days Ago</span>
                  </span>
                </a>
              </div>
              <div class="dropdown-footer text-center">
                <a href="#">View All <i class="fas fa-chevron-right"></i></a>
              </div>
            </div>
          </li>
          <li class="dropdown dropdown-list-toggle"><a href="#" data-toggle="dropdown"
              class="nav-link notification-toggle nav-link-lg"><i data-feather="bell" class="bell"></i>
            </a>
            <div class="dropdown-menu dropdown-list dropdown-menu-right pullDown">
              <div class="dropdown-header">
                Notifications
                <div class="float-right">
                  <a href="#">Mark All As Read</a>
                </div>
              </div>
              <div class="dropdown-list-content dropdown-list-icons">
                <a href="#" class="dropdown-item dropdown-item-unread"> <span
                    class="dropdown-item-icon bg-primary text-white"> <i class="fas
												fa-code"></i>
                  </span> <span class="dropdown-item-desc"> Template update is
                    available now! <span class="time">2 Min
                      Ago</span>
                  </span>
                </a> <a href="#" class="dropdown-item"> <span class="dropdown-item-icon bg-info text-white"> <i class="far
												fa-user"></i>
                  </span> <span class="dropdown-item-desc"> <b>You</b> and <b>Dedik
                      Sugiharto</b> are now friends <span class="time">10 Hours
                      Ago</span>
                  </span>
                </a> <a href="#" class="dropdown-item"> <span class="dropdown-item-icon bg-success text-white"> <i
                      class="fas
												fa-check"></i>
                  </span> <span class="dropdown-item-desc"> <b>Kusnaedi</b> has
                    moved task <b>Fix bug header</b> to <b>Done</b> <span class="time">12
                      Hours
                      Ago</span>
                  </span>
                </a> <a href="#" class="dropdown-item"> <span class="dropdown-item-icon bg-danger text-white"> <i
                      class="fas fa-exclamation-triangle"></i>
                  </span> <span class="dropdown-item-desc"> Low disk space. Let's
                    clean it! <span class="time">17 Hours Ago</span>
                  </span>
                </a> <a href="#" class="dropdown-item"> <span class="dropdown-item-icon bg-info text-white"> <i class="fas
												fa-bell"></i>
                  </span> <span class="dropdown-item-desc"> Welcome to Otika
                    template! <span class="time">Yesterday</span>
                  </span>
                </a>
              </div>
              <div class="dropdown-footer text-center">
                <a href="#">View All <i class="fas fa-chevron-right"></i></a>
              </div>
            </div>
          </li>
          <li class="dropdown">
  <a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
    <img alt="image"
         src="{{ $avatar }}" style="height: 30px; object-fit: cover;"
         class="user-img-radious-style"
         onerror="this.src='{{ asset('assets/img/users/user-1.png') }}'">
    <span class="d-sm-none d-lg-inline-block"></span>
  </a>
  <div class="dropdown-menu dropdown-menu-right pullDown">
    <div class="dropdown-title">Hello {{ $displayName }}</div>

    <a href="{{ route('user.admin.profile') }}" class="dropdown-item has-icon">
      <i class="far fa-user"></i> Profile
    </a>

    <div class="dropdown-divider"></div>

    <a href="#"
       class="dropdown-item has-icon text-danger"
       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
    <form id="logout-form" action="{{ route('auth.logout') }}" method="POST" class="d-none">
      @csrf
    </form>
  </div>
</li>

        </ul>
      </nav>
      <div class="main-sidebar sidebar-style-2">
        <aside id="sidebar-wrapper">
          <div class="sidebar-brand">
            <!-- <a href="index.html"> <img alt="image" src="{{ asset('assets/img/logo.png') }}" class="header-logo" /> <span
                class="logo-name">Otika</span>
            </a> -->
          </div>
          <ul class="sidebar-menu">
            <li class="menu-header">MODE</li>
            <li id="modeSwitchItem" class="nav-item">
              <div class="nav-link d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                  <i class="fas fa-exchange-alt mr-2"></i>
                  <span id="siteSwitchText">Switch to Creator</span>
                </div>
                <label class="custom-switch m-0" style="cursor:pointer; padding-left:0!important;">
                  <input type="checkbox" id="siteSwitch" class="custom-switch-input">
                  <span class="custom-switch-indicator"></span>
                </label>

              </div>
            </li>


            <li class="menu-header">Main</li>

            <li class="dropdown {{ request()->routeIs('user.admin.index') ? 'active' : '' }}">
              <a href="{{ route('user.admin.index') }}" class="nav-link">
                <i data-feather="monitor"></i>
                <span>Dashboard</span>
              </a>
            </li>

            <li class="dropdown {{ request()->routeIs('user.admin.marketplace') ? 'active' : '' }}">
              <a href="{{ route('user.admin.marketplace') }}" class="nav-link">
                <i data-feather="layout"></i>
                <span>Marketplace</span>
              </a>
            </li>

            <li class="dropdown {{ request()->routeIs('user.admin.myteam') ? 'active' : '' }}">
              <a href="{{ route('user.admin.myteam') }}" class="nav-link">
                <i data-feather="user-check"></i>
                <span>My Team</span>
              </a>
            </li>

            <li class="dropdown {{ request()->routeIs('user.admin.wallet') ? 'active' : '' }}">
              <a href="{{ route('user.admin.wallet') }}" class="nav-link">
                <i class="fas fa-wallet"></i>
                <span>Wallet</span>
              </a>
            </li>

            <li class="dropdown {{ request()->is('user-admin/marketplace/boosts*') ? 'active' : '' }}">
              <a class="nav-link" href="{{ url('/user-admin/marketplace/boosts') }}">
                <i class="fas fa-bolt"></i> <span>Boosts</span>
              </a>
            </li>

            <li class="dropdown {{ request()->routeIs('user.admin.orders.page') ? 'active' : '' }}">
              <a class="nav-link" href="{{ route('user.admin.orders.page') }}">
                <i class="fas fa-shopping-bag"></i> <span>Orders</span>
              </a>
            </li>

            <li class="dropdown {{ Route::is('user.myorders.*') ? 'active' : '' }}">
              <a class="nav-link" href="{{ route('user.myorders.page') }}">
                <i class="fas fa-shopping-bag"></i> <span>My Orders</span>
              </a>
            </li>


            <li class="dropdown {{ request()->routeIs('user.messages*') ? 'active' : '' }}">
              <a class="nav-link" href="{{ route('user.messages') }}">
                <i class="fas fa-comments"></i> <span>Messages</span>
              </a>
            </li>



            <li class="dropdown {{ request()->routeIs('wishlist.page*') ? 'active' : '' }}">
              <a class="nav-link" href="{{ route('wishlist.page') }}">
                <i class="fas fa-heart"></i> <span>Wishlist</span>
              </a>
            </li>
















          </ul>
        </aside>
      </div>