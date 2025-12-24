@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0" id="pageTitle">Assignment Submissions</h2>
            <a href="{{ route('teacher.manage_course', ['id' => $courseId]) }}" class="btn btn-outline-secondary">← Back</a>
        </div>

        <div id="meta" class="alert alert-info d-none"></div>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <strong>Submissions</strong>
            </div>
            <div class="card-body" id="submissionsWrap">
                <div class="text-muted">Loading...</div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const ASSIGNMENT_ID = {{ (int)$assignmentId }};

    function escapeHtml(str) {
        return String(str ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function formatDate(dt) {
        if (!dt) return '';
        try { return new Date(dt).toLocaleString(); } catch { return dt; }
    }

    function showMsg(row, ok, text) {
        const box = row.querySelector('[data-field="msg"]');
        if (!box) return;
        box.className = `alert mt-2 ${ok ? 'alert-success' : 'alert-danger'}`;
        box.textContent = text;
        box.classList.remove('d-none');
    }

    function render(data) {
        const wrap = document.getElementById('submissionsWrap');
        const assignment = data.assignment;
        const subs = data.submissions || [];

        document.getElementById('pageTitle').textContent = `Assignment: ${assignment.title}`;
        const meta = document.getElementById('meta');
        meta.innerHTML = `Due: ${assignment.due_at ? formatDate(assignment.due_at) : 'No deadline'}${assignment.max_marks ? ' | Max Marks: ' + assignment.max_marks : ''}`;
        meta.classList.remove('d-none');

        if (!subs.length) {
            wrap.innerHTML = `<div class="alert alert-secondary">No submissions yet.</div>`;
            return;
        }

        wrap.innerHTML = `
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>File</th>
                            <th style="width:110px">Marks</th>
                            <th>Feedback</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${subs.map(s => {
                            const status = s.is_late ? '<span class="badge bg-warning text-dark">Late</span>' : '<span class="badge bg-success">On time</span>';
                            return `
                                <tr data-submission-id="${s.id}">
                                    <td>
                                        <div class="fw-semibold">${escapeHtml(s.user?.name || '')}</div>
                                        <div class="text-muted small">${escapeHtml(s.user?.email || '')}</div>
                                    </td>
                                    <td>${formatDate(s.submitted_at)}</td>
                                    <td>${status}</td>
                                    <td>${s.file_path ? `<a href="${s.file_path}" target="_blank" download>Download</a>` : '<span class="text-muted">—</span>'}</td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" step="0.25" min="0" value="${s.marks ?? ''}" data-field="marks">
                                    </td>
                                    <td>
                                        <textarea class="form-control form-control-sm" rows="2" data-field="feedback">${escapeHtml(s.feedback ?? '')}</textarea>
                                        <div class="alert d-none" data-field="msg" role="alert"></div>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-primary" type="button" data-action="save">Save</button>
                                    </td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;

        wrap.addEventListener('click', function (e) {
            const btn = e.target.closest('button[data-action="save"]');
            if (!btn) return;
            const row = btn.closest('[data-submission-id]');
            const id = row.getAttribute('data-submission-id');
            const marks = row.querySelector('[data-field="marks"]').value;
            const feedback = row.querySelector('[data-field="feedback"]').value;

            apiFetch(`/api/teacher/assignment-submissions/${id}/grade`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    marks: marks === '' ? null : parseFloat(marks),
                    feedback
                })
            })
            .then(r => r.json().then(data => ({ status: r.status, body: data })))
            .then(({ status, body }) => {
                if (status === 200 && body.status === true) {
                    showMsg(row, true, 'Saved');
                } else {
                    showMsg(row, false, body.message || 'Save failed');
                }
            })
            .catch(() => showMsg(row, false, 'Save failed'));
        });
    }

    function load() {
        apiFetch(`/api/teacher/assignments/${ASSIGNMENT_ID}/submissions`)
            .then(r => r.json())
            .then(data => {
                if (data.status === true && data.data) {
                    render(data.data);
                } else {
                    document.getElementById('submissionsWrap').innerHTML = `<div class="alert alert-danger">Failed to load submissions.</div>`;
                }
            })
            .catch(() => {
                document.getElementById('submissionsWrap').innerHTML = `<div class="alert alert-danger">Failed to load submissions.</div>`;
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const user = getUserData();
        if (!getAuthToken() || !user) {
            window.location.href = '/login';
            return;
        }
        if (user.role !== 'teacher') {
            window.location.href = user.role === 'admin' ? '/admin/dashboard' : '/student/dashboard';
            return;
        }
        load();
    });
</script>
@endsection
