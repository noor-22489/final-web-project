<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top lms-header py-3">
  <div class="container-fluid px-4">
    @php
      $brandLogoDiskPath = public_path('images/lms/logo.png');
      $brandLogoVersion = file_exists($brandLogoDiskPath) ? filemtime($brandLogoDiskPath) : null;
      $brandLogoUrl = $brandLogoVersion ? (asset('images/lms/logo.png') . '?v=' . $brandLogoVersion) : null;
    @endphp

    <a class="navbar-brand d-flex align-items-center gap-2" href="/">
      @if($brandLogoUrl)
        <img src="{{ $brandLogoUrl }}" alt="Logo" width="28" height="28" class="rounded" style="object-fit: cover;">
      @endif
      <span>{{ config('app.name', 'LMS') }}</span>
    </a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        {{-- Public Course Catalog --}}
        <li class="nav-item">
            <a class="nav-link" href="{{ route('courses.index') }}">Courses</a>
        </li>
      </ul>
      
      <ul class="navbar-nav ms-auto" id="auth-links">
        {{-- These links are initially visible. JavaScript will hide/show them. --}}
        
        <li class="nav-item logged-out-only">
          <a class="nav-link" href="{{ route('login') }}">Login</a>
        </li>
        <li class="nav-item logged-out-only">
          <a class="nav-link" href="{{ route('register') }}">Register</a>
        </li>
        
        {{-- Dashboard Link (Visible only after JS confirms login) --}}
        <li class="nav-item logged-in-only" style="display: none;">
          <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
        </li>

        {{-- Logout Button (Visible only after JS confirms login) --}}
        <li class="nav-item logged-in-only" style="display: none;">
            <button class="btn btn-sm btn-outline-light" onclick="logout()">Logout</button>
        </li>
      </ul>
    </div>
  </div>
</nav>