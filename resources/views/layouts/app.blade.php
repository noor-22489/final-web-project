<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'LMS') }}</title>
    
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    @php
        $brandLogoDiskPath = public_path('images/lms/logo.png');
        $brandLogoVersion = file_exists($brandLogoDiskPath) ? filemtime($brandLogoDiskPath) : null;
        $brandLogoUrl = $brandLogoVersion ? (asset('images/lms/logo.png') . '?v=' . $brandLogoVersion) : null;
    @endphp

    @if($brandLogoUrl)
        <link rel="icon" type="image/png" href="{{ $brandLogoUrl }}">
        <link rel="apple-touch-icon" href="{{ $brandLogoUrl }}">
    @endif
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            background-color: var(--bs-tertiary-bg);
        }

        .lms-header .navbar-brand {
            font-weight: 800;
            letter-spacing: 0.2px;
            font-size: 1.25rem;
        }

        .lms-header .nav-link {
            font-weight: 600;
            font-size: 1.05rem;
            opacity: 0.95;
        }

        .lms-header .nav-link:hover {
            opacity: 1;
        }

        .lms-hero {
            position: relative;
            overflow: hidden;
            border-radius: var(--bs-border-radius-lg);
            background-size: cover;
            background-position: center;
            box-shadow: var(--bs-box-shadow-sm);
        }

        .lms-hero-half {
            min-height: 50vh;
        }

        .lms-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                90deg,
                rgba(var(--bs-dark-rgb), 0.70),
                rgba(var(--bs-dark-rgb), 0.30)
            );
        }

        .lms-hero > * {
            position: relative;
            z-index: 1;
        }

        .hover-lift {
            transition: transform 180ms ease, box-shadow 180ms ease;
        }

        .hover-lift:hover {
            transform: translateY(-3px);
            box-shadow: var(--bs-box-shadow);
        }

        .reveal {
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 420ms ease, transform 420ms ease;
            will-change: opacity, transform;
        }

        .reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        @media (prefers-reduced-motion: reduce) {
            .hover-lift,
            .reveal {
                transition: none !important;
                transform: none !important;
            }
        }
    </style>

    @yield('styles')
</head>
<body>

    @include('includes.navbar') 

    <main class="container py-4">
        @yield('content')
    </main>

    <footer class="text-center py-4 text-muted border-top bg-white mt-auto">
        <p class="mb-0">&copy; {{ date('Y') }} {{ config('app.name', 'LMS') }}. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Global helper to add CSRF token to every fetch request automatically
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function setUserData(user) {
            if (!user) return;
            // Keep backward compatibility with older keys
            if (user.role) localStorage.setItem('user_role', user.role);
            localStorage.setItem('user', JSON.stringify(user));
        }

        function getUserData() {
            try {
                const raw = localStorage.getItem('user');
                if (raw) return JSON.parse(raw);
            } catch (e) {
                // ignore
            }
            const role = localStorage.getItem('user_role');
            if (role) return { role };
            return null;
        }

        function getAuthToken() {
            return localStorage.getItem('auth_token');
        }

        async function apiFetch(url, options = {}) {
            const token = getAuthToken();
            if (!token) {
                // No JWT token, force login
                window.location.href = '/login';
                throw 'No token found';
            }

            const headers = {
                'Accept': 'application/json',
                ...(options.headers || {}),
                'Authorization': `Bearer ${token}`,
            };

            // If this is a non-GET request, include CSRF token for safety (even if API uses JWT)
            if ((options.method || 'GET').toUpperCase() !== 'GET') {
                headers['X-CSRF-TOKEN'] = CSRF_TOKEN;
            }

            const response = await fetch(url, {
                ...options,
                headers,
            });

            // Token expired / unauthorized => reset and send to login
            if (response.status === 401) {
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user_role');
                localStorage.removeItem('user');
                window.location.href = '/login';
                throw 'Unauthorized';
            }

            return response;
        }

        async function logout() {
            // Clear browser JWT state
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user_role');
            localStorage.removeItem('user');

            // Also clear any leftover Laravel web-session auth (from earlier attempts)
            try {
                await fetch('/logout', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });
            } catch (e) {
                // ignore
            }

            window.location.href = '/login';
        }

        function updateNavbarAuthState() {
            const token = getAuthToken();
            const loggedInEls = document.querySelectorAll('.logged-in-only');
            const loggedOutEls = document.querySelectorAll('.logged-out-only');

            if (token) {
                loggedInEls.forEach(el => el.style.display = '');
                loggedOutEls.forEach(el => el.style.display = 'none');
            } else {
                loggedInEls.forEach(el => el.style.display = 'none');
                loggedOutEls.forEach(el => el.style.display = '');
            }
        }

        document.addEventListener('DOMContentLoaded', updateNavbarAuthState);

        document.addEventListener('DOMContentLoaded', function () {
            const els = Array.from(document.querySelectorAll('.reveal'));
            if (!els.length) return;

            if ('IntersectionObserver' in window) {
                const io = new IntersectionObserver((entries) => {
                    for (const entry of entries) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            io.unobserve(entry.target);
                        }
                    }
                }, { threshold: 0.12 });

                els.forEach(el => io.observe(el));
            } else {
                els.forEach(el => el.classList.add('is-visible'));
            }
        });

        // Logic to clear local storage on logout if the logout is triggered via Blade
        document.addEventListener('click', function (e) {
            if (e.target.closest('#logout-btn')) {
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user_role');
                localStorage.removeItem('user');
            }
        });
    </script>
    
    @yield('scripts')
</body>
</html>