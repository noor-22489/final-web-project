@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Quiz Leaderboard</h2>
            <a href="/course/{{ $courseId }}/view" class="btn btn-outline-secondary">← Back</a>
        </div>

        <input type="hidden" id="quizId" value="{{ (int)$quizId }}">

        <div id="statusBox" class="alert d-none" role="alert"></div>

        <div class="card shadow-sm">
            <div class="card-body" id="leaderboardWrap">
                <div class="text-muted">Loading leaderboard...</div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const QUIZ_ID = parseInt(document.getElementById('quizId').value, 10);
    const COURSE_ID = {{ (int)$courseId }};

    function escapeHtml(str) {
        return String(str ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function showStatus(type, text) {
        const box = document.getElementById('statusBox');
        box.className = `alert alert-${type}`;
        box.textContent = text;
        box.classList.remove('d-none');
    }

    async function downloadCertificate() {
        const token = getAuthToken();
        if (!token) {
            window.location.href = '/login';
            return;
        }

        const res = await fetch(`/api/student/quizzes/${QUIZ_ID}/certificate/download`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });

        if (!res.ok) {
            alert('Certificate not available');
            return;
        }

        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'certificate.pdf';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    }

    async function loadLeaderboard() {
        const wrap = document.getElementById('leaderboardWrap');
        wrap.innerHTML = '<div class="text-muted">Loading leaderboard...</div>';

        const res = await apiFetch(`/api/quizzes/${QUIZ_ID}/leaderboard`);
        const payload = await res.json();

        if (!payload.status) {
            wrap.innerHTML = `<div class="alert alert-danger">${escapeHtml(payload.message || 'Failed to load leaderboard')}</div>`;
            return;
        }

        const data = payload.data;
        const finalizedAt = data.results_finalized_at;
        const entries = data.entries || [];

        if (finalizedAt) {
            showStatus('success', 'Results finalized.');
        } else {
            showStatus('warning', 'Results not finalized yet.');
        }

        const rows = entries.map(e => {
            const user = e.user ? `${escapeHtml(e.user.name)} (${escapeHtml(e.user.email)})` : 'Unknown';
            return `
                <tr>
                    <td class="fw-semibold">#${e.rank}</td>
                    <td>${user}</td>
                    <td>${e.marks ?? ''}</td>
                    <td>${e.time_taken_seconds ?? ''}</td>
                </tr>
            `;
        }).join('');

        const cert = data.my_certificate;
        const certHtml = cert && finalizedAt
            ? `
                <div class="d-flex align-items-center justify-content-between gap-2 mt-3">
                    <div class="text-muted">You are ranked <strong>#${cert.rank}</strong>. Certificate available.</div>
                    <button class="btn btn-success btn-sm" id="downloadCertBtn" type="button">Download Certificate</button>
                </div>
            `
            : '';

        wrap.innerHTML = `
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Student</th>
                            <th>Marks</th>
                            <th>Time (s)</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows || '<tr><td colspan="4" class="text-muted">No attempts yet.</td></tr>'}
                    </tbody>
                </table>
            </div>
            ${certHtml}
        `;

        const btn = document.getElementById('downloadCertBtn');
        if (btn) btn.addEventListener('click', downloadCertificate);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const user = getUserData();
        if (!getAuthToken() || !user) {
            window.location.href = '/login';
            return;
        }
        if (user.role !== 'student') {
            window.location.href = user.role === 'admin' ? '/admin/dashboard' : '/teacher/dashboard';
            return;
        }
        loadLeaderboard();
    });
</script>
@endsection
