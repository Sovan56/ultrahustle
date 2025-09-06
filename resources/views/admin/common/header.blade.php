<!DOCTYPE html>
<html lang="en">


<!-- index.html  21 Nov 2019 03:44:50 GMT -->

<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <title>Admin Dashboard</title>
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
          <a href="#" id="btnLogout" class="dropdown-item has-icon text-danger">
            <i class="fas fa-sign-out-alt" style="font-size: 18px;"></i>
          </a>

          {{-- Hidden form that POSTs to logout --}}
          <form id="logoutForm" action="{{ route('auth.logout') }}" method="POST" style="display:none;">
            @csrf
          </form>

          <script>
            (function() {
              // (Optional) also set CSRF on jQuery AJAX globally
              if (window.$) {
                $.ajaxSetup({
                  headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                  }
                });
              }

              // Submit the hidden POST form on click
              const btn = document.getElementById('btnLogout');
              if (btn) {
                btn.addEventListener('click', function(e) {
                  e.preventDefault();
                  // If you want a confirm, uncomment:
                  // if (!confirm('Logout?')) return;
                  document.getElementById('logoutForm').submit();
                });
              }
            })();
          </script>
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
            <li class="menu-header">Main</li>

            <li class="dropdown {{ request()->routeIs('admin.members.page') ? 'active' : '' }}">
              <a href="{{ route('admin.members.page') }}" class="nav-link">
                <i data-feather="users"></i>
                <span>All Members</span>
              </a>
            </li>

            <li class="dropdown {{ request()->routeIs('admin.teams.page') ? 'active' : '' }}">
              <a href="{{ route('admin.teams.page') }}" class="nav-link">
                <i data-feather="users"></i>
                <span>My Team</span>
              </a>
            </li>

            <li class="dropdown {{ request()->routeIs('admin.legal.page') ? 'active' : '' }}">
              <a class="nav-link" href="{{ route('admin.legal.page') }}">
                <i class="fas fa-file-contract"></i>
                <span>Legal Pages</span>
              </a>
            </li>

            <li class="dropdown {{ request()->routeIs('admin.boost_plans.page') ? 'active' : '' }}">
              <a class="nav-link" href="{{ route('admin.boost_plans.page') }}">
                <i class="fas fa-bolt"></i> <span>Boost Plans</span>
              </a>
            </li>

            <li class="dropdown {{ request()->routeIs('admin.kyc.page') ? 'active' : '' }}">
              <a class="nav-link" href="{{ route('admin.kyc.page') }}">
                <i class="fas fa-user-check"></i> <span>KYC Requests</span>
              </a>
            </li>

<li class="dropdown {{ Route::is('admin.settings.fees') ? 'active' : '' }}">
  <a class="nav-link" href="{{ route('admin.settings.fees') }}">
    <i class="fas fa-percent"></i> <span>Fees &amp; GST</span>
  </a>
</li>

<li class="{{ request()->routeIs('admin.taxonomy.*') ? 'active' : '' }}">
  <a class="nav-link" href="{{ route('admin.taxonomy.page') }}">
    <i class="fas fa-sitemap"></i> <span>Product Taxonomy</span>
  </a>
</li>
          </ul>
        </aside>
      </div>