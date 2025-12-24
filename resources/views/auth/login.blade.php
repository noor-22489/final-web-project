@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">Secure Login</h4>
                </div>
                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form id="loginForm">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                required
                                placeholder="name@example.com"
                                autofocus
                            >
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                required
                                placeholder="Enter your password"
                            >
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Log In</button>
                        <div id="authMessage" class="mt-3 alert d-none" role="alert"></div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    Don't have an account? <a href="{{ route('register') }}">Register Here</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function roleToDashboard(role) {
        if (role === 'admin') return '/admin/dashboard';
        if (role === 'teacher') return '/teacher/dashboard';
        return '/student/dashboard';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('loginForm');
        const messageBox = document.getElementById('authMessage');

        // If already logged in, go to correct dashboard
        const existingUser = getUserData();
        if (getAuthToken() && existingUser && existingUser.role) {
            window.location.href = roleToDashboard(existingUser.role);
            return;
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            messageBox.classList.add('d-none');

            const payload = {
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
            };

            fetch('/api/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json().then(data => ({ status: response.status, body: data })))
            .then(({ status, body }) => {
                if (status === 200 && body.access_token) {
                    localStorage.setItem('auth_token', body.access_token);
                    if (body.user) {
                        setUserData(body.user);
                    }

                    messageBox.textContent = 'Login successful! Redirecting...';
                    messageBox.className = 'mt-3 alert alert-success';
                    messageBox.classList.remove('d-none');

                    const role = body.user?.role || localStorage.getItem('user_role') || 'student';
                    setTimeout(() => {
                        window.location.href = roleToDashboard(role);
                    }, 300);
                } else {
                    messageBox.textContent = body.message || 'Login failed.';
                    messageBox.className = 'mt-3 alert alert-danger';
                    messageBox.classList.remove('d-none');
                }
            })
            .catch(error => {
                console.error('Login error:', error);
                messageBox.textContent = 'Network error. Could not connect to the server.';
                messageBox.className = 'mt-3 alert alert-danger';
                messageBox.classList.remove('d-none');
            });
        });
    });
</script>
@endsection
