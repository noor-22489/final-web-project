@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <h2 class="mb-4 border-bottom pb-2">Quiz Results</h2>
        <input type="hidden" id="quizId" value="{{ $quizId }}">

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Quiz</div>
                        <div class="fw-semibold" id="quizTitle">Loading...</div>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-secondary" href="/teacher/quiz/{{ $quizId }}/manage_questions">Manage Questions</a>
                        <button class="btn btn-primary" id="downloadCsvBtn" type="button">Download CSV</button>
                        <button class="btn btn-success" id="finalizeBtn" type="button">Finalize Results</button>
                    </div>
                </div>
                <div class="mt-2 small text-muted" id="finalizedHint" style="display:none"></div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body" id="resultsWrap">
                <div class="text-muted">Loading attempts...</div>
            </div>
        </div>

        <a href="{{ route('teacher.my_courses') }}" class="btn btn-outline-secondary mt-3">← Back to My Courses</a>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const quizId = document.getElementById('quizId').value;

    function escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    async function loadResults() {
        const wrap = document.getElementById('resultsWrap');
        wrap.innerHTML = '<div class="text-muted">Loading attempts...</div>';

        const res = await apiFetch(`/api/teacher/quizzes/${quizId}/results`);
        const data = await res.json();

        if (!data.status) {
            wrap.innerHTML = `<div class="alert alert-danger">${escapeHtml(data.message || 'Failed to load results')}</div>`;
            return;
        }

        const quiz = data.data.quiz;
        const attempts = data.data.attempts || [];
        document.getElementById('quizTitle').textContent = quiz?.title || `Quiz #${quizId}`;

        const finalizedAt = quiz?.results_finalized_at;
        const finalizeBtn = document.getElementById('finalizeBtn');
        const hint = document.getElementById('finalizedHint');
        if (finalizedAt) {
            finalizeBtn.disabled = true;
            hint.textContent = `Results finalized at: ${finalizedAt}`;
            hint.style.display = '';
        } else {
            finalizeBtn.disabled = false;
            hint.style.display = 'none';
        }

        if (!attempts.length) {
            wrap.innerHTML = '<div class="text-muted">No attempts yet.</div>';
            return;
        }

        wrap.innerHTML = `
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Correct</th>
                            <th>Wrong</th>
                            <th>Total</th>
                            <th>Marks</th>
                            <th>Time (s)</th>
                            <th>Taken At</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${attempts.map(a => {
                            const student = a.user ? `${a.user.name} (${a.user.email})` : `User #${a.user_id}`;
                            return `
                                <tr>
                                    <td>${escapeHtml(student)}</td>
                                    <td>${a.correct_answers ?? a.score ?? 0}</td>
                                    <td>${a.wrong_answers ?? 0}</td>
                                    <td>${a.total_questions ?? 0}</td>
                                    <td>${a.marks ?? ''}</td>
                                    <td>${a.time_taken_seconds ?? ''}</td>
                                    <td>${escapeHtml(a.created_at ?? '')}</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    async function finalizeResults() {
        if (!confirm('Finalize results now? This will lock submissions and issue certificates to top ranks.')) return;
        const btn = document.getElementById('finalizeBtn');
        btn.disabled = true;

        const res = await apiFetch(`/api/teacher/quizzes/${quizId}/finalize`, { method: 'POST' });
        const data = await res.json();
        if (!data.status) {
            alert(data.message || 'Finalize failed');
            btn.disabled = false;
            return;
        }
        await loadResults();
        alert('Results finalized and certificates issued.');
    }

    async function downloadCsv() {
        const token = getAuthToken();
        if (!token) {
            window.location.href = '/login';
            return;
        }

        const res = await fetch(`/api/teacher/quizzes/${quizId}/results.csv`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });

        if (!res.ok) {
            alert('Failed to download CSV');
            return;
        }

        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `quiz_${quizId}_results.csv`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    }

    document.getElementById('downloadCsvBtn').addEventListener('click', downloadCsv);
    document.getElementById('finalizeBtn').addEventListener('click', finalizeResults);
    document.addEventListener('DOMContentLoaded', loadResults);
</script>
@endsection
