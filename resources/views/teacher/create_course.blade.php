@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-lg border-0">
            <div class="card-header bg-info text-white">
                <h3 class="mb-0">Create New Course</h3>
            </div>
            <div class="card-body p-4">
                <form id="createCourseForm">
                    <div class="mb-3">
                        <label for="title" class="form-label">Course Title</label>
                        <input type="text" class="form-control" id="title" required placeholder="e.g., Advanced JavaScript">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" rows="5" required placeholder="A detailed overview of the course content."></textarea>
                    </div>
                    <button type="submit" class="btn btn-info w-100 text-white">Publish Course</button>
                </form>
                <div id="courseMessage" class="mt-3 alert d-none" role="alert"></div>
            </div>
            <div class="card-footer text-center">
                <small><a href="{{ route('dashboard') }}">Back to Dashboard</a></small>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('createCourseForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const messageBox = document.getElementById('courseMessage');
        messageBox.classList.add('d-none');
        
        // 1. Gather form data
        const title = document.getElementById('title').value;
        const description = document.getElementById('description').value;
        
        // 2. Prepare payload
        const payload = { 
            title: title, 
            description: description
        };

        // 3. Make API call using the JWT helper from auth.js
        apiFetch('/api/teacher/courses', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                // JWT Token is automatically added by apiFetch()
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(({ status, body }) => {
            if (status === 201 && body.status === true) {
                // SUCCESS
                messageBox.textContent = 'Course "' + body.data.title + '" created successfully!';
                messageBox.className = 'mt-3 alert alert-success';
                messageBox.classList.remove('d-none');
                document.getElementById('createCourseForm').reset(); // Clear form
                
            } else {
                // FAILURE
                let errorMessage = body.message || 'Course creation failed.';
                if (body.errors) {
                    errorMessage += ': ' + Object.values(body.errors).flat().join(', ');
                }
                
                messageBox.textContent = errorMessage;
                messageBox.className = 'mt-3 alert alert-danger';
                messageBox.classList.remove('d-none');
            }
        })
        .catch(error => {
            messageBox.textContent = 'A network error occurred.';
            messageBox.className = 'mt-3 alert alert-danger';
            messageBox.classList.remove('d-none');
            console.error('Error:', error);
        });
    });
</script>
@endsection