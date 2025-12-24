@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <h2 class="mb-4 border-bottom pb-2">All Available Courses</h2>
    </div>
</div>

<div class="row" id="courseList">
    <div class="col-12 text-center py-5" id="loadingMessage">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading courses...</span>
        </div>
        <p class="mt-2">Loading courses from the database...</p>
    </div>
    
    <div class="col-12 text-center py-5 d-none" id="noCoursesMessage">
        <p class="fs-4">No courses are currently available. Check back later!</p>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const courseList = document.getElementById('courseList');
        const loadingMessage = document.getElementById('loadingMessage');
        const noCoursesMessage = document.getElementById('noCoursesMessage');

        // Function to create an individual course card HTML
        function createCourseCard(course) {
            // Check if user is logged in to show the Enroll button
            const user = getUserData();
            const isLoggedIn = !!getAuthToken(); 
            const isStudent = !!user && user.role === 'student';

            return `
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title text-primary">${course.title}</h5>
                            <h6 class="card-subtitle mb-2 text-muted">By: ${course.teacher.name}</h6>
                            <p class="card-text">${course.description.substring(0, 100)}...</p>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            ${!isLoggedIn ?
                                `<a href="{{ route('login') }}" class="btn btn-sm btn-outline-secondary">Login to Continue</a>`
                                : (isStudent
                                    ? `<span class="small text-muted">Enrollment is managed by Teacher</span>`
                                    : `<span class="small text-muted">Login as Student to enroll</span>`
                                )
                            }
                        </div>
                    </div>
                </div>
            `;
        }

        // Fetching the public course list
        fetch('/api/courses')
            .then(response => response.json())
            .then(data => {
                loadingMessage.classList.add('d-none');
                
                if (data.status === true && data.data.length > 0) {
                    // Success: Courses found
                    data.data.forEach(course => {
                        courseList.insertAdjacentHTML('beforeend', createCourseCard(course));
                    });
                } else {
                    // No courses returned
                    noCoursesMessage.classList.remove('d-none');
                }
            })
            .catch(error => {
                loadingMessage.innerHTML = `<p class="text-danger">Error loading courses. Please try again.</p>`;
                console.error('Error fetching courses:', error);
            });
    });

</script>
@endsection