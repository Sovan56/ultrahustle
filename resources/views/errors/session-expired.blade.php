

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card shadow-sm border-0">
        <div class="card-body text-center p-5">
          <h1 class="mb-3">Session Expired</h1>
          <p class="text-muted mb-4">
            For your security your session timed out. Please log in again or go back to the homepage.
          </p>
          <div class="d-flex gap-2 justify-content-center">
            <a href="{{ route('home') }}" class="btn btn-outline-secondary">Go Home</a>
            <a href="#login" data-bs-toggle="modal" data-bs-target="#login" class="btn btn-primary">
              Open Login
            </a>
          </div>
        </div>
      </div>
      @if (session('error'))
        <div class="alert alert-danger mt-3">{{ session('error') }}</div>
      @endif
    </div>
  </div>
</div>

