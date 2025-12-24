@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-lg border-0">
            <div class="card-header bg-success text-white text-center">
                <h3 class="mb-0">LMS Registration</h3>
            </div>
            <div class="card-body p-4">
                <form id="registerForm">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" required>
                    </div>
                    <div class="mb-4">
    <label for="role" class="form-label">I want to register as...</label>
    <select class="form-select" id="role" required>
        <option value="" disabled selected>Select Role</option>
        {{-- ADDED ADMIN OPTION --}}
        <option value="admin">Admin</option>
        <option value="teacher">Teacher</option>
        <option value="student">Student</option>
    </select>
</div>
                    <button type="submit" class="btn btn-success w-100">Register</button>
                </form>
                <div id="registerMessage" class="mt-3 alert d-none" role="alert"></div>
            </div>
            <div class="card-footer text-center">
                <small>Already have an account? <a href="{{ route('login') }}">Login Here</a></small>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const messageBox = document.getElementById('registerMessage');
        messageBox.classList.add('d-none'); // Hide previous messages

        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const role = document.getElementById('role').value;

        fetch('/api/auth/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ name, email, password, role })
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(({ status, body }) => {
            if (status === 201 && body.status === true) {
                // SUCCESS: Registration succeeded, now send user to login
                messageBox.textContent = (body.message || 'Registered') + '. Redirecting to login...';
                messageBox.className = 'mt-3 alert alert-success';
                messageBox.classList.remove('d-none');

                // Ensure we don't keep any stale tokens around
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user_role');
                localStorage.removeItem('user');

                setTimeout(() => {
                    window.location.href = '/login';
                }, 800);
            } else {
                // FAILURE: Show error message
                // Check if validation errors were returned (status 422 usually)
                let errorMessage = body.message || 'Registration failed. Check your inputs.';
                if (body.errors) {
                    errorMessage += ': ' + Object.values(body.errors).flat().join(', ');
                }
                
                messageBox.textContent = errorMessage;
                messageBox.className = 'mt-3 alert alert-danger';
                messageBox.classList.remove('d-none');
            }
        })
        .catch(error => {
            messageBox.textContent = 'A network error occurred during registration.';
            messageBox.className = 'mt-3 alert alert-danger';
            messageBox.classList.remove('d-none');
            console.error('Error:', error);
        });
    });
</script>
@endsection