@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <h2 class="mb-4 border-bottom pb-2">Manage Course: <span id="courseTitle">Loading...</span></h2>

        <input type="hidden" id="courseId" value="{{ $id }}">

        <ul class="nav nav-tabs mb-4" id="managementTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="section-tab" data-bs-toggle="tab" data-bs-target="#sections" type="button" role="tab">Sections</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="material-tab" data-bs-toggle="tab" data-bs-target="#materials" type="button" role="tab">Materials</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="quiz-tab" data-bs-toggle="tab" data-bs-target="#quizzes" type="button" role="tab">Quizzes</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="assignment-tab" data-bs-toggle="tab" data-bs-target="#assignments" type="button" role="tab">Assignments</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="announcement-tab" data-bs-toggle="tab" data-bs-target="#announcements" type="button" role="tab">Announcements</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="enrollment-tab" data-bs-toggle="tab" data-bs-target="#enrollments" type="button" role="tab">Enrollments</button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="sections" role="tabpanel" aria-labelledby="section-tab">
                <div class="card shadow-lg mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0">Add New Section (Module)</h4>
                    </div>
                    <div class="card-body p-4">
                        <form id="createSectionForm">
                            <div class="mb-3">
                                <label for="sectionTitle" class="form-label">Section Title</label>
                                <input type="text" class="form-control" id="sectionTitle" required placeholder="e.g., Introduction to CSS">
                            </div>
                            <div class="mb-4">
                                <label for="sortOrder" class="form-label">Order</label>
                                <input type="number" class="form-control" id="sortOrder" value="0" min="0">
                            </div>
                            <button type="submit" class="btn btn-warning w-100">Create Section</button>
                        </form>
                        <div id="sectionMessage" class="mt-3 alert d-none" role="alert"></div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="materials" role="tabpanel" aria-labelledby="material-tab">
                <div class="card shadow-lg mb-4">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">Add New Material (Lesson)</h4>
                    </div>
                    <div class="card-body p-4">
                        <form id="createMaterialForm" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="materialSectionId" class="form-label">Assign to Section</label>
                                <select class="form-select" id="materialSectionId" required>
                                    <option value="" disabled selected>Loading Sections...</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="materialType" class="form-label">Material Type</label>
                                <select class="form-select" id="materialType" required>
                                    <option value="" disabled selected>Select Type</option>
                                    <option value="text">Text/Article</option>
                                    <option value="video">Video (URL or Upload)</option>
                                    <option value="link">External Link</option>
                                    <option value="pdf">PDF File (Upload)</option>
                                    <option value="ppt">PowerPoint (Upload)</option>
                                    <option value="document">Document (Upload)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="materialTitle" class="form-label">Material Title</label>
                                <input type="text" class="form-control" id="materialTitle" required placeholder="e.g., CSS Selectors Video">
                            </div>

                            <div class="mb-3">
                                <label for="content" class="form-label" id="contentLabel">Content (Text/URL/File)</label>
                                <textarea class="form-control" id="content" rows="3" placeholder="Paste text or URL here."></textarea>
                                <input type="file" class="form-control mt-2 d-none" id="file_upload" name="file_upload">
                            </div>

                            <button type="submit" class="btn btn-success w-100">Add Material</button>
                        </form>
                        <div id="materialMessage" class="mt-3 alert d-none" role="alert"></div>
                    </div>
                </div>

                <div class="card shadow-lg">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Existing Materials</h5>
                    </div>
                    <div class="card-body" id="materialsList">
                        <div class="text-muted">Loading...</div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="quizzes" role="tabpanel" aria-labelledby="quiz-tab">
                <div class="card shadow-lg mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Create New Quiz</h4>
                    </div>
                    <div class="card-body p-4">
                        <form id="createQuizForm">
                            <div class="mb-3">
                                <label for="quizSectionId" class="form-label">Assign to Section</label>
                                <select class="form-select" id="quizSectionId" required>
                                    <option value="" disabled selected>Loading Sections...</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="quizTitle" class="form-label">Quiz Title</label>
                                <input type="text" class="form-control" id="quizTitle" required placeholder="e.g., Basic CSS Knowledge Check">
                            </div>
                            <div class="mb-3">
                                <label for="quizDuration" class="form-label">Duration (Minutes)</label>
                                <input type="number" class="form-control" id="quizDuration" value="30" min="1">
                            </div>
                            <div class="mb-3">
                                <label for="quizDeadline" class="form-label">Deadline (Optional)</label>
                                <input type="datetime-local" class="form-control" id="quizDeadline">
                            </div>
                            <div class="mb-3">
                                <label for="quizNegative" class="form-label">Negative Mark per Wrong (Optional)</label>
                                <input type="number" class="form-control" id="quizNegative" value="0" min="0" step="0.25">
                            </div>
                            <div class="mb-3">
                                <label for="quizMaxAttempts" class="form-label">Max Attempts (Optional)</label>
                                <input type="number" class="form-control" id="quizMaxAttempts" min="1" placeholder="e.g., 1">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Create Quiz</button>
                        </form>
                        <div id="quizMessage" class="mt-3 alert d-none" role="alert"></div>
                    </div>
                </div>

                <div class="card shadow-lg">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Existing Quizzes</h5>
                    </div>
                    <div class="card-body" id="quizzesList">
                        <div class="text-muted">Loading...</div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="assignments" role="tabpanel" aria-labelledby="assignment-tab">
                <div class="card shadow-lg mb-4">
                    <div class="card-header bg-dark text-white">
                        <h4 class="mb-0">Create Assignment</h4>
                    </div>
                    <div class="card-body p-4">
                        <form id="createAssignmentForm">
                            <div class="mb-3">
                                <label for="assignmentSectionId" class="form-label">Assign to Section</label>
                                <select class="form-select" id="assignmentSectionId" required>
                                    <option value="" disabled selected>Loading Sections...</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="assignmentTitle" class="form-label">Title</label>
                                <input type="text" class="form-control" id="assignmentTitle" required placeholder="e.g., Week 1 Homework">
                            </div>
                            <div class="mb-3">
                                <label for="assignmentDueAt" class="form-label">Due Date (Optional)</label>
                                <input type="datetime-local" class="form-control" id="assignmentDueAt">
                            </div>
                            <div class="mb-3">
                                <label for="assignmentMaxMarks" class="form-label">Max Marks (Optional)</label>
                                <input type="number" class="form-control" id="assignmentMaxMarks" min="0" placeholder="e.g., 20">
                            </div>
                            <div class="mb-3">
                                <label for="assignmentDescription" class="form-label">Description (Optional)</label>
                                <textarea class="form-control" id="assignmentDescription" rows="3" placeholder="Instructions for students..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-dark w-100">Create Assignment</button>
                        </form>
                        <div id="assignmentMessage" class="mt-3 alert d-none" role="alert"></div>
                    </div>
                </div>

                <div class="card shadow-lg">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Existing Assignments</h5>
                    </div>
                    <div class="card-body" id="assignmentsList">
                        <div class="text-muted">Loading...</div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="announcements" role="tabpanel" aria-labelledby="announcement-tab">
                <div class="card shadow-lg mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h4 class="mb-0">Post Announcement</h4>
                    </div>
                    <div class="card-body p-4">
                        <form id="createAnnouncementForm">
                            <div class="mb-3">
                                <label for="announcementTitle" class="form-label">Title</label>
                                <input type="text" class="form-control" id="announcementTitle" required placeholder="e.g., Quiz on Friday">
                            </div>
                            <div class="mb-3">
                                <label for="announcementBody" class="form-label">Message</label>
                                <textarea class="form-control" id="announcementBody" rows="4" required placeholder="Write your announcement..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-secondary w-100">Post</button>
                        </form>
                        <div id="announcementMessage" class="mt-3 alert d-none" role="alert"></div>
                    </div>
                </div>

                <div class="card shadow-lg">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Recent Announcements</h5>
                    </div>
                    <div class="card-body" id="announcementsList">
                        <div class="text-muted">Loading...</div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="enrollments" role="tabpanel" aria-labelledby="enrollment-tab">
                <div class="card shadow-lg mb-4">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0">Enroll Student</h4>
                    </div>
                    <div class="card-body p-4">
                        <form id="enrollStudentForm">
                            <div class="mb-3">
                                <label for="enrollStudentEmail" class="form-label">Student Email</label>
                                <input type="email" class="form-control" id="enrollStudentEmail" required placeholder="student@example.com">
                                <div class="form-text">Students can’t self-enroll; you add them here.</div>
                            </div>
                            <button type="submit" class="btn btn-info w-100 text-white">Enroll</button>
                        </form>
                        <div id="enrollmentMessage" class="mt-3 alert d-none" role="alert"></div>
                    </div>
                </div>

                <div class="card shadow-lg">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Enrolled Students</h5>
                    </div>
                    <div class="card-body" id="enrollmentsList">
                        <div class="text-muted">Loading...</div>
                    </div>
                </div>
            </div>
        </div>

        <a href="{{ route('teacher.my_courses') }}" class="btn btn-outline-secondary mt-3">← Back to My Courses</a>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const COURSE_ID = document.getElementById('courseId').value;
    const sectionMessageBox = document.getElementById('sectionMessage');
    const materialMessageBox = document.getElementById('materialMessage');
    const quizMessageBox = document.getElementById('quizMessage');
    const assignmentMessageBox = document.getElementById('assignmentMessage');
    const announcementMessageBox = document.getElementById('announcementMessage');
    const enrollmentMessageBox = document.getElementById('enrollmentMessage');

    let SECTIONS = [];

    function escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function toDatetimeLocalValue(dt) {
        if (!dt) return '';
        const d = new Date(dt);
        if (isNaN(d.getTime())) return '';
        const pad = (n) => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    async function fetchAndPopulateSections() {
        const materialSelect = document.getElementById('materialSectionId');
        const quizSelect = document.getElementById('quizSectionId');
        const assignmentSelect = document.getElementById('assignmentSectionId');
        const materialsList = document.getElementById('materialsList');
        const quizzesList = document.getElementById('quizzesList');
        const assignmentsList = document.getElementById('assignmentsList');
        const announcementsList = document.getElementById('announcementsList');
        const enrollmentsList = document.getElementById('enrollmentsList');

        materialSelect.innerHTML = '<option value="" disabled selected>Loading Sections...</option>';
        quizSelect.innerHTML = '<option value="" disabled selected>Loading Sections...</option>';
        if (assignmentSelect) assignmentSelect.innerHTML = '<option value="" disabled selected>Loading Sections...</option>';
        if (materialsList) materialsList.innerHTML = '<div class="text-muted">Loading...</div>';
        if (assignmentsList) assignmentsList.innerHTML = '<div class="text-muted">Loading...</div>';
        if (announcementsList) announcementsList.innerHTML = '<div class="text-muted">Loading...</div>';
        if (enrollmentsList) enrollmentsList.innerHTML = '<div class="text-muted">Loading...</div>';

        try {
            const res = await apiFetch(`/api/teacher/courses/${COURSE_ID}/sections`);
            const data = await res.json();

            if (data.status === true && Array.isArray(data.data)) {
                SECTIONS = data.data.slice().sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
            } else {
                SECTIONS = [];
            }

            if (SECTIONS.length > 0) {
                const options = SECTIONS
                    .map(s => `<option value="${s.id}">${escapeHtml(s.title)} (Order: ${s.sort_order || 0})</option>`)
                    .join('');

                materialSelect.innerHTML = '<option value="" disabled selected>Select Section</option>' + options;
                quizSelect.innerHTML = '<option value="" disabled selected>Select Section</option>' + options;
                if (assignmentSelect) assignmentSelect.innerHTML = '<option value="" disabled selected>Select Section</option>' + options;
                document.getElementById('courseTitle').textContent = `Course ID #${COURSE_ID} (Ready)`;
            } else {
                materialSelect.innerHTML = '<option value="" disabled selected>No Sections Found</option>';
                quizSelect.innerHTML = '<option value="" disabled selected>No Sections Found</option>';
                if (assignmentSelect) assignmentSelect.innerHTML = '<option value="" disabled selected>No Sections Found</option>';
            }

            await loadMaterials();
            await loadQuizzes();
            await loadAssignments();
            await loadAnnouncements();
            await loadEnrollments();
        } catch (error) {
            console.error('Error fetching sections:', error);
            materialSelect.innerHTML = '<option value="" disabled selected>Error Loading</option>';
            quizSelect.innerHTML = '<option value="" disabled selected>Error Loading</option>';
            if (assignmentSelect) assignmentSelect.innerHTML = '<option value="" disabled selected>Error Loading</option>';
            if (materialsList) materialsList.innerHTML = '<div class="text-danger">Failed to load materials.</div>';
            if (quizzesList) quizzesList.innerHTML = '<div class="text-danger">Failed to load quizzes.</div>';
            if (assignmentsList) assignmentsList.innerHTML = '<div class="text-danger">Failed to load assignments.</div>';
            if (announcementsList) announcementsList.innerHTML = '<div class="text-danger">Failed to load announcements.</div>';
            if (enrollmentsList) enrollmentsList.innerHTML = '<div class="text-danger">Failed to load enrollments.</div>';
        }
    }

    async function loadEnrollments() {
        const wrap = document.getElementById('enrollmentsList');
        if (!wrap) return;

        wrap.innerHTML = '<div class="text-muted">Loading...</div>';

        try {
            const res = await apiFetch(`/api/teacher/courses/${COURSE_ID}/enrollments`);
            const data = await res.json();
            const items = (data.status === true && Array.isArray(data.data)) ? data.data : [];

            if (!items.length) {
                wrap.innerHTML = '<div class="text-muted">No students enrolled yet.</div>';
                return;
            }

            wrap.innerHTML = `<table class="table table-sm mb-0">
                <thead><tr><th>Student</th><th>Email</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    ${items.map(e => `
                        <tr>
                            <td class="fw-semibold">${escapeHtml(e.user?.name || '')}</td>
                            <td class="text-muted">${escapeHtml(e.user?.email || '')}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-danger" type="button" onclick="removeEnrollment(${e.id})">Remove</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>`;
        } catch (e) {
            wrap.innerHTML = '<div class="text-danger">Failed to load enrollments.</div>';
        }
    }

    async function removeEnrollment(id) {
        if (!confirm('Remove this student from the course?')) return;
        try {
            const res = await apiFetch(`/api/teacher/enrollments/${id}`, { method: 'DELETE' });
            const data = await res.json();
            if (!data.status) {
                alert(data.message || 'Failed to remove enrollment');
                return;
            }
            await loadEnrollments();
        } catch (e) {
            alert('Failed to remove enrollment');
        }
    }

    async function loadAnnouncements() {
        const wrap = document.getElementById('announcementsList');
        if (!wrap) return;

        wrap.innerHTML = '<div class="text-muted">Loading...</div>';

        try {
            const res = await apiFetch(`/api/teacher/courses/${COURSE_ID}/announcements`);
            const data = await res.json();
            const items = (data.status === true && Array.isArray(data.data)) ? data.data : [];

            if (!items.length) {
                wrap.innerHTML = '<div class="text-muted">No announcements yet.</div>';
                return;
            }

            wrap.innerHTML = `<table class="table table-sm mb-0">
                <thead><tr><th>Title</th><th>Message</th><th>Date</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    ${items.map(a => `
                        <tr>
                            <td class="fw-semibold">${escapeHtml(a.title || '')}</td>
                            <td>${escapeHtml(a.body || '')}</td>
                            <td class="text-muted small">${a.created_at ? escapeHtml(a.created_at) : ''}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-danger" type="button" onclick="deleteAnnouncement(${a.id})">Delete</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>`;
        } catch (e) {
            wrap.innerHTML = '<div class="text-danger">Failed to load announcements.</div>';
        }
    }

    async function deleteAnnouncement(id) {
        if (!confirm('Delete this announcement?')) return;
        try {
            const res = await apiFetch(`/api/teacher/announcements/${id}`, { method: 'DELETE' });
            const data = await res.json();
            if (!data.status) {
                alert(data.message || 'Failed to delete');
                return;
            }
            await loadAnnouncements();
        } catch (e) {
            alert('Failed to delete');
        }
    }

    async function loadAssignments() {
        const wrap = document.getElementById('assignmentsList');
        if (!wrap) return;

        if (!SECTIONS.length) {
            wrap.innerHTML = '<div class="text-muted">No sections yet.</div>';
            return;
        }

        let html = '';
        for (const s of SECTIONS) {
            let items = [];
            try {
                const res = await apiFetch(`/api/teacher/sections/${s.id}/assignments`);
                const data = await res.json();
                if (data.status === true && Array.isArray(data.data)) {
                    items = data.data;
                }
            } catch (e) {
                // ignore
            }

            html += `<div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div><strong>${escapeHtml(s.title)}</strong></div>
                    <small class="text-muted">${items.length} assignment(s)</small>
                </div>
                <div class="card-body p-0">
                    ${items.length ? `<table class="table table-sm mb-0">
                        <thead><tr>
                            <th>Title</th><th>Due</th><th>Submissions</th><th class="text-end">Actions</th>
                        </tr></thead>
                        <tbody>
                            ${items.map(a => `
                                <tr>
                                    <td>
                                        <div class="fw-semibold">${escapeHtml(a.title || '')}</div>
                                        ${a.description ? `<div class="text-muted small">${escapeHtml(a.description)}</div>` : ''}
                                    </td>
                                    <td>${a.due_at ? escapeHtml(a.due_at) : '<span class="text-muted">—</span>'}</td>
                                    <td>${a.submissions_count ?? 0}</td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="/teacher/assignments/${a.id}/submissions?courseId=${COURSE_ID}">Submissions</a>
                                        <button class="btn btn-sm btn-outline-danger" type="button" onclick="deleteAssignment(${a.id})">Delete</button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>` : `<div class="p-3 text-muted">No assignments in this section.</div>`}
                </div>
            </div>`;
        }

        wrap.innerHTML = html;
    }

    async function deleteAssignment(id) {
        if (!confirm('Delete this assignment?')) return;
        try {
            const res = await apiFetch(`/api/teacher/assignments/${id}`, { method: 'DELETE' });
            const data = await res.json();
            if (!data.status) {
                alert(data.message || 'Failed to delete');
                return;
            }
            await loadAssignments();
        } catch (e) {
            alert('Failed to delete');
        }
    }

    async function loadMaterials() {
        const wrap = document.getElementById('materialsList');
        if (!wrap) return;

        if (!SECTIONS.length) {
            wrap.innerHTML = '<div class="text-muted">No sections yet.</div>';
            return;
        }

        let html = '';
        for (const s of SECTIONS) {
            let items = [];
            try {
                const res = await apiFetch(`/api/teacher/sections/${s.id}/materials`);
                const data = await res.json();
                if (data.status === true && Array.isArray(data.data)) {
                    items = data.data;
                }
            } catch (e) {
                // ignore per-section failures
            }

            html += `<div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div><strong>${escapeHtml(s.title)}</strong></div>
                    <small class="text-muted">${items.length} material(s)</small>
                </div>
                <div class="card-body p-0">
                    ${items.length ? `<table class="table table-sm mb-0">
                        <thead><tr>
                            <th>Title</th><th>Type</th><th>File/Link</th><th class="text-end">Actions</th>
                        </tr></thead>
                        <tbody>
                            ${items.map(m => `
                                <tr>
                                    <td>
                                        <div class="fw-semibold">${escapeHtml(m.title || '')}</div>
                                        ${m.description ? `<div class="text-muted small">${escapeHtml(m.description)}</div>` : ''}
                                    </td>
                                    <td><span class="badge text-bg-secondary">${escapeHtml(m.type || '')}</span></td>
                                    <td>
                                        ${m.file_path ? `<a href="${m.file_path}" target="_blank">Open</a>` : (m.content ? `<span class="small">${escapeHtml(m.content)}</span>` : `<span class="text-muted small">—</span>`) }
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-danger" type="button" onclick="deleteMaterial(${m.id})">Delete</button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>` : `<div class="p-3 text-muted">No materials in this section.</div>`}
                </div>
            </div>`;
        }

        wrap.innerHTML = html;
    }

    async function deleteMaterial(id) {
        if (!confirm('Delete this material?')) return;
        try {
            const res = await apiFetch(`/api/teacher/materials/${id}`, { method: 'DELETE' });
            const data = await res.json();
            if (!data.status) {
                alert(data.message || 'Failed to delete');
                return;
            }
            await loadMaterials();
        } catch (e) {
            alert('Failed to delete');
        }
    }

    async function loadQuizzes() {
        const wrap = document.getElementById('quizzesList');
        if (!wrap) return;

        if (!SECTIONS.length) {
            wrap.innerHTML = '<div class="text-muted">No sections yet.</div>';
            return;
        }

        let html = '';
        for (const s of SECTIONS) {
            let quizzes = [];
            try {
                const res = await apiFetch(`/api/teacher/sections/${s.id}/quizzes`);
                const data = await res.json();
                if (data.status === true && Array.isArray(data.data)) {
                    quizzes = data.data;
                }
            } catch (e) {
                // ignore
            }

            html += `<div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div><strong>${escapeHtml(s.title)}</strong></div>
                    <small class="text-muted">${quizzes.length} quiz(zes)</small>
                </div>
                <div class="card-body p-0">
                    ${quizzes.length ? `<table class="table table-sm mb-0">
                        <thead><tr>
                            <th>Title</th>
                            <th>Duration</th>
                            <th>Deadline</th>
                            <th class="text-end">Actions</th>
                        </tr></thead>
                        <tbody>
                            ${quizzes.map(q => `
                                <tr>
                                    <td>${escapeHtml(q.title || '')}</td>
                                    <td>${q.duration_minutes ?? ''}</td>
                                    <td>${q.deadline_at ? escapeHtml(q.deadline_at) : ''}</td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="/teacher/quiz/${q.id}/manage_questions">Questions</a>
                                        <a class="btn btn-sm btn-outline-secondary" href="/teacher/quiz/${q.id}/results">Results</a>
                                        <button class="btn btn-sm btn-outline-warning" type="button" onclick="duplicateQuiz(${q.id})">Duplicate</button>
                                        <div class="d-inline-flex align-items-center gap-2 ms-2" style="vertical-align: middle;">
                                            <input type="datetime-local" class="form-control form-control-sm" style="max-width: 210px" id="dl_${q.id}" value="${toDatetimeLocalValue(q.deadline_at)}">
                                            <button class="btn btn-sm btn-outline-success" type="button" onclick="updateQuizDeadline(${q.id})">Save</button>
                                            <button class="btn btn-sm btn-outline-danger" type="button" onclick="deleteQuiz(${q.id})">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>` : `<div class="p-3 text-muted">No quizzes in this section.</div>`}
                </div>
            </div>`;
        }

        wrap.innerHTML = html;
    }

    async function duplicateQuiz(id) {
        if (!confirm('Duplicate this quiz?')) return;
        try {
            const res = await apiFetch(`/api/teacher/quizzes/${id}/duplicate`, { method: 'POST' });
            const data = await res.json();
            if (!data.status) {
                alert(data.message || 'Failed to duplicate');
                return;
            }
            await loadQuizzes();
        } catch (e) {
            alert('Failed to duplicate');
        }
    }

    async function updateQuizDeadline(id) {
        const input = document.getElementById(`dl_${id}`);
        if (!input) return;
        const val = input.value;
        const deadline_at = val ? new Date(val).toISOString() : null;

        try {
            const res = await apiFetch(`/api/teacher/quizzes/${id}/deadline`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ deadline_at })
            });
            const data = await res.json();
            if (!data.status) {
                alert(data.message || 'Failed to update deadline');
                return;
            }
            await loadQuizzes();
        } catch (e) {
            alert('Failed to update deadline');
        }
    }

    async function deleteQuiz(id) {
        if (!confirm('Delete this quiz? This will also remove its questions and attempts.')) return;
        try {
            const res = await apiFetch(`/api/teacher/quizzes/${id}`, { method: 'DELETE' });
            const data = await res.json();
            if (!data.status) {
                alert(data.message || 'Failed to delete quiz');
                return;
            }
            await loadQuizzes();
        } catch (e) {
            alert('Failed to delete quiz');
        }
    }

    document.getElementById('materialType').addEventListener('change', function() {
        const type = this.value;
        const contentInput = document.getElementById('content');
        const fileInput = document.getElementById('file_upload');
        const contentLabel = document.getElementById('contentLabel');

        contentInput.classList.remove('d-none');
        fileInput.classList.add('d-none');
        contentInput.removeAttribute('required');
        fileInput.removeAttribute('required');

        if (type === 'text' || type === 'video' || type === 'link') {
            contentInput.setAttribute('required', 'required');
            contentLabel.textContent = `Content (${type.charAt(0).toUpperCase() + type.slice(1)}/URL)`;
            contentInput.placeholder = (type === 'video') ? 'Paste video URL here (or upload a video file).' :
                (type === 'link') ? 'Paste external link (URL) here.' :
                'Paste text content here.';
        } else if (type === 'pdf' || type === 'ppt' || type === 'document') {
            fileInput.classList.remove('d-none');
            contentInput.classList.add('d-none');
            fileInput.setAttribute('required', 'required');
            contentLabel.textContent = `Content (Upload ${type.toUpperCase()} File)`;
        }

        fileInput.value = '';
    });

    document.getElementById('createSectionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        sectionMessageBox.classList.add('d-none');

        const title = document.getElementById('sectionTitle').value;
        const sort_order = document.getElementById('sortOrder').value;

        apiFetch(`/api/teacher/courses/${COURSE_ID}/sections`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title, sort_order })
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(({ status, body }) => {
            if (status === 201 && body.status === true) {
                sectionMessageBox.textContent = 'Section "' + body.data.title + '" created successfully!';
                sectionMessageBox.className = 'mt-3 alert alert-success';
                sectionMessageBox.classList.remove('d-none');
                document.getElementById('createSectionForm').reset();
                fetchAndPopulateSections();
            } else {
                sectionMessageBox.textContent = body.message || 'Section creation failed.';
                sectionMessageBox.className = 'mt-3 alert alert-danger';
                sectionMessageBox.classList.remove('d-none');
            }
        });
    });

    document.getElementById('createMaterialForm').addEventListener('submit', function(e) {
        e.preventDefault();
        materialMessageBox.classList.add('d-none');

        const section_id = document.getElementById('materialSectionId').value;
        const title = document.getElementById('materialTitle').value;
        const type = document.getElementById('materialType').value;
        const fileInput = document.getElementById('file_upload');

        const formData = new FormData();
        formData.append('section_id', section_id);
        formData.append('title', title);
        formData.append('type', type);

        if (type === 'pdf' || type === 'ppt' || type === 'document') {
            if (fileInput.files.length > 0) {
                formData.append('file', fileInput.files[0]);
            } else {
                materialMessageBox.textContent = 'Please select a file to upload.';
                materialMessageBox.className = 'mt-3 alert alert-danger';
                materialMessageBox.classList.remove('d-none');
                return;
            }
        } else {
            const content = document.getElementById('content').value;
            formData.append('content', content);
        }

        apiFetch(`/api/teacher/materials`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(({ status, body }) => {
            if (status === 201 && body.status === true) {
                materialMessageBox.textContent = 'Material "' + body.data.title + '" added successfully!';
                materialMessageBox.className = 'mt-3 alert alert-success';
                materialMessageBox.classList.remove('d-none');
                document.getElementById('createMaterialForm').reset();
                loadMaterials();
            } else {
                let errorMessage = body.message || 'Material creation failed.';
                if (body.errors && body.errors.file) {
                    errorMessage += ': ' + body.errors.file.join(', ');
                }
                materialMessageBox.textContent = errorMessage;
                materialMessageBox.className = 'mt-3 alert alert-danger';
                materialMessageBox.classList.remove('d-none');
            }
        });
    });

    document.getElementById('createQuizForm').addEventListener('submit', function(e) {
        e.preventDefault();
        quizMessageBox.classList.add('d-none');

        const section_id = document.getElementById('quizSectionId').value;
        const title = document.getElementById('quizTitle').value;
        const duration_minutes = document.getElementById('quizDuration').value;
        const deadlineInput = document.getElementById('quizDeadline').value;
        const negative_mark_per_wrong = document.getElementById('quizNegative').value;
        const max_attempts = document.getElementById('quizMaxAttempts').value;

        // Convert datetime-local to ISO string (server validates as date)
        const deadline_at = deadlineInput ? new Date(deadlineInput).toISOString() : null;

        apiFetch(`/api/teacher/sections/${section_id}/quizzes`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                section_id,
                title,
                duration_minutes,
                deadline_at,
                negative_mark_per_wrong,
                max_attempts: max_attempts || null,
            })
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(({ status, body }) => {
            if (status === 201 && body.status === true) {
                const quizId = body.data.id;
                quizMessageBox.textContent = `Quiz "${body.data.title}" created successfully! Now redirecting to add questions...`;
                quizMessageBox.className = 'mt-3 alert alert-success';
                quizMessageBox.classList.remove('d-none');
                document.getElementById('createQuizForm').reset();
                loadQuizzes();

                setTimeout(() => {
                    window.location.href = `/teacher/quiz/${quizId}/manage_questions`;
                }, 1500);
            } else {
                quizMessageBox.textContent = body.message || 'Quiz creation failed.';
                quizMessageBox.className = 'mt-3 alert alert-danger';
                quizMessageBox.classList.remove('d-none');
            }
        });
    });

    document.getElementById('createAssignmentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        assignmentMessageBox.classList.add('d-none');

        const section_id = document.getElementById('assignmentSectionId').value;
        const title = document.getElementById('assignmentTitle').value;
        const description = document.getElementById('assignmentDescription').value;
        const dueInput = document.getElementById('assignmentDueAt').value;
        const maxMarks = document.getElementById('assignmentMaxMarks').value;
        const due_at = dueInput ? new Date(dueInput).toISOString() : null;

        apiFetch(`/api/teacher/sections/${section_id}/assignments`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                title,
                description: description || null,
                due_at,
                max_marks: maxMarks || null,
            })
        })
        .then(r => r.json().then(data => ({ status: r.status, body: data })))
        .then(({ status, body }) => {
            if (status === 201 && body.status === true) {
                assignmentMessageBox.textContent = 'Assignment created.';
                assignmentMessageBox.className = 'mt-3 alert alert-success';
                assignmentMessageBox.classList.remove('d-none');
                document.getElementById('createAssignmentForm').reset();
                loadAssignments();
            } else {
                assignmentMessageBox.textContent = body.message || 'Assignment creation failed.';
                assignmentMessageBox.className = 'mt-3 alert alert-danger';
                assignmentMessageBox.classList.remove('d-none');
            }
        });
    });

    document.getElementById('createAnnouncementForm').addEventListener('submit', function(e) {
        e.preventDefault();
        announcementMessageBox.classList.add('d-none');

        const title = document.getElementById('announcementTitle').value;
        const body = document.getElementById('announcementBody').value;

        apiFetch(`/api/teacher/courses/${COURSE_ID}/announcements`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title, body })
        })
        .then(r => r.json().then(data => ({ status: r.status, body: data })))
        .then(({ status, body }) => {
            if (status === 201 && body.status === true) {
                announcementMessageBox.textContent = 'Announcement posted.';
                announcementMessageBox.className = 'mt-3 alert alert-success';
                announcementMessageBox.classList.remove('d-none');
                document.getElementById('createAnnouncementForm').reset();
                loadAnnouncements();
            } else {
                announcementMessageBox.textContent = body.message || 'Failed to post announcement.';
                announcementMessageBox.className = 'mt-3 alert alert-danger';
                announcementMessageBox.classList.remove('d-none');
            }
        });
    });

    document.getElementById('enrollStudentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        if (!enrollmentMessageBox) return;

        enrollmentMessageBox.classList.add('d-none');
        const student_email = document.getElementById('enrollStudentEmail').value;

        apiFetch(`/api/teacher/courses/${COURSE_ID}/enrollments`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ student_email })
        })
        .then(r => r.json().then(data => ({ status: r.status, body: data })))
        .then(({ status, body }) => {
            if ((status === 201 || status === 200) && body.status === true) {
                enrollmentMessageBox.textContent = body.message || 'Student enrolled.';
                enrollmentMessageBox.className = 'mt-3 alert alert-success';
                enrollmentMessageBox.classList.remove('d-none');
                document.getElementById('enrollStudentForm').reset();
                loadEnrollments();
            } else {
                let msg = body.message || 'Enrollment failed.';
                if (body.errors) {
                    msg += ': ' + Object.values(body.errors).flat().join(', ');
                }
                enrollmentMessageBox.textContent = msg;
                enrollmentMessageBox.className = 'mt-3 alert alert-danger';
                enrollmentMessageBox.classList.remove('d-none');
            }
        })
        .catch(() => {
            enrollmentMessageBox.textContent = 'Enrollment failed.';
            enrollmentMessageBox.className = 'mt-3 alert alert-danger';
            enrollmentMessageBox.classList.remove('d-none');
        });
    });

    document.addEventListener('DOMContentLoaded', fetchAndPopulateSections);
</script>
@endsection
