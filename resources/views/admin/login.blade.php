<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <title>Login</title>
  <meta name="csrf-token" content="{{ csrf_token() }}"><!-- CSRF for AJAX -->

  <link rel="stylesheet" href="{{ asset('assets/css/app.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/bundles/bootstrap-social/bootstrap-social.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/components.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
  <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/img/favicon.ico') }}" />
</head>

<body>
  <div class="loader" style="display:none;"></div>
  <div id="app">
    <section class="section">
      <div class="container mt-5">
        <div class="row">
          <div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
            <div class="card card-primary">
              <div class="card-header">
                <h4>Login</h4>
              </div>
              <div class="card-body">
                <form id="loginForm" class="needs-validation" novalidate>
                  @csrf
                  <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" type="email" class="form-control" name="email" tabindex="1" required autofocus>
                    <div class="invalid-feedback">Please fill in your email</div>
                  </div>
                  <div class="form-group">
                    <div class="d-block">
                      <label for="password" class="control-label">Password</label>
                    </div>
                    <input id="password" type="password" class="form-control" name="password" tabindex="2" required>
                    <div class="invalid-feedback">Please fill in your password</div>
                  </div>
                  <div class="form-group">
                    <button id="btnLogin" type="submit" class="btn btn-primary btn-lg btn-block" tabindex="2">
                      Login
                    </button>
                  </div>
                  <div id="loginError" class="text-danger small" style="display:none;"></div>
                </form>
              </div>
            </div>
            {{-- Optional: footer / small links --}}
          </div>
        </div>
      </div>
    </section>
  </div>

  <script src="{{ asset('assets/js/app.min.js') }}"></script>
  <script src="{{ asset('assets/js/scripts.js') }}"></script>
  <script src="{{ asset('assets/js/custom.js') }}"></script>

  <script>
  (function(){
    // CSRF header for all AJAX
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }});

    const $form = $('#loginForm');
    const $btn  = $('#btnLogin');
    const $err  = $('#loginError');

    function toast(title, text, icon) {
      if (window.Swal) { Swal.fire(title, text || '', icon || 'info'); }
      else { alert((title ? title + '\n' : '') + (text || '')); }
    }

    $form.on('submit', function(e){
      e.preventDefault();
      $err.hide().text('');

      if (this.checkValidity() === false) {
        e.stopPropagation();
        $form.addClass('was-validated');
        return;
      }

      $btn.prop('disabled', true).addClass('btn-progress'); // Otika spinner on .btn-progress
      $('.loader').show();

      $.post("{{ route('admin.login.submit') }}", {
        email: $('#email').val(),
        password: $('#password').val()
      })
      .done(function(res){
        if (res.ok && res.redirect) {
          window.location.href = res.redirect;
        } else {
          const msg = res.message || 'Login failed';
          $err.text(msg).show();
          toast('Error', msg, 'error');
          $btn.prop('disabled', false).removeClass('btn-progress');
          $('.loader').hide();
        }
      })
      .fail(function(xhr){
        let msg = 'Login failed';
        if (xhr.status === 422) {
          const data = xhr.responseJSON || {};
          if (data.errors) {
            const firstField = Object.keys(data.errors)[0];
            if (firstField) msg = data.errors[firstField][0];
          } else if (data.message) {
            msg = data.message;
          }
        } else if (xhr.status === 403) {
          msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Not an admin user';
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
          msg = xhr.responseJSON.message;
        }
        $err.text(msg).show();
        toast('Error', msg, 'error');
        $btn.prop('disabled', false).removeClass('btn-progress');
        $('.loader').hide();
      });
    });
  })();
  </script>
</body>
</html>
