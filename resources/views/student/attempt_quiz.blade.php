@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0" id="quizTitle">Loading Quiz...</h2>
            <a href="/course/{{ $courseId }}/view" class="btn btn-outline-secondary">← Back</a>
        </div>

        <div class="alert alert-info" id="quizInfo" style="display:none"></div>

        <div class="card shadow-sm mb-3" id="timerCard" style="display:none">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div><strong>Time Remaining:</strong> <span id="timeRemaining">--:--</span></div>
                <button class="btn btn-outline-danger btn-sm" id="submitBtn" type="button">Submit Quiz</button>
            </div>
        </div>

        <form id="quizForm"></form>

        <div id="resultBox" class="mt-3"></div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const QUIZ_ID = {{ (int)$quizId }};
    const COURSE_ID = {{ (int)$courseId }};

    let quizData = null;
    let timerInterval = null;
    let remainingSeconds = null;
    let startedAtMs = null;

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

    function renderQuiz(quiz) {
        document.getElementById('quizTitle').textContent = quiz.title;

        startedAtMs = Date.now();

        const info = document.getElementById('quizInfo');
        const parts = [];
        parts.push(`Duration: ${quiz.duration_minutes ? quiz.duration_minutes + ' min' : 'No time limit'}`);
        parts.push(`Deadline: ${quiz.deadline_at ? formatDate(quiz.deadline_at) : 'No deadline'}`);
        if (quiz.max_attempts) parts.push(`Max attempts: ${quiz.max_attempts}`);
        if (quiz.negative_mark_per_wrong !== null && quiz.negative_mark_per_wrong !== undefined) parts.push(`Negative mark/wrong: ${quiz.negative_mark_per_wrong}`);
        parts.push(`Total questions: ${(quiz.questions || []).length}`);
        info.innerHTML = parts.join(' | ');
        info.style.display = '';

        const form = document.getElementById('quizForm');
        form.innerHTML = (quiz.questions || []).map((q, idx) => {
            const optionsHtml = (q.options || []).map(o => {
                return `
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="q_${q.id}" id="opt_${o.id}" value="${o.id}">
                        <label class="form-check-label" for="opt_${o.id}">${escapeHtml(o.option_text)}</label>
                    </div>
                `;
            }).join('');

            return `
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <h5 class="mb-2">Q${idx + 1}. ${escapeHtml(q.question_text)}</h5>
                        ${optionsHtml}
                    </div>
                </div>
            `;
        }).join('');

        // Start timer if duration exists
        if (quiz.duration_minutes && quiz.duration_minutes > 0) {
            remainingSeconds = quiz.duration_minutes * 60;
            document.getElementById('timerCard').style.display = '';
            tickTimer();
            timerInterval = setInterval(tickTimer, 1000);
        }
    }

    function getElapsedSeconds() {
        if (!startedAtMs) return null;
        const sec = Math.floor((Date.now() - startedAtMs) / 1000);
        return sec < 0 ? 0 : sec;
    }

    function tickTimer() {
        if (remainingSeconds === null) return;
        const min = Math.floor(remainingSeconds / 60);
        const sec = remainingSeconds % 60;
        document.getElementById('timeRemaining').textContent = `${String(min).padStart(2,'0')}:${String(sec).padStart(2,'0')}`;
        remainingSeconds -= 1;
        if (remainingSeconds < 0) {
            clearInterval(timerInterval);
            submitQuiz(true);
        }
    }

    function buildAnswersPayload() {
        const answers = [];
        (quizData.questions || []).forEach(q => {
            const selected = document.querySelector(`input[name="q_${q.id}"]:checked`);
            if (selected) {
                answers.push({ question_id: q.id, option_id: parseInt(selected.value, 10) });
            }
        });
        return {
            answers,
            time_taken_seconds: getElapsedSeconds(),
        };
    }

    function renderResults(body) {
        const box = document.getElementById('resultBox');
        const results = body.results;
        const review = body.review || [];

        const header = `
            <div class="alert alert-success">
                <strong>Submitted!</strong>
                <div class="mt-2">Correct: ${results.correct} | Wrong: ${results.wrong} | Total: ${results.total} | Marks: ${results.marks}</div>
            </div>
        `;

        const items = review.map((r, idx) => {
            const ok = r.is_correct;
            return `
                <div class="card mb-2 ${ok ? 'border-success' : 'border-danger'}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <strong>Q${idx + 1}. ${escapeHtml(r.question_text)}</strong>
                            <span class="badge ${ok ? 'bg-success' : 'bg-danger'}">${ok ? 'Correct' : 'Incorrect'}</span>
                        </div>
                        <div class="mt-2"><span class="text-muted">Your answer:</span> ${escapeHtml(r.selected_option_text || '-')}
                        </div>
                        <div><span class="text-muted">Correct answer:</span> ${escapeHtml(r.correct_option_text || '-')}
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        box.innerHTML = header + items;

        // After showing results, load leaderboard + certificate state.
        loadLeaderboard();
    }

    function renderLeaderboardTable(payload) {
        const box = document.getElementById('resultBox');
        const data = payload?.data;
        if (!data) return;

        const finalizedAt = data.results_finalized_at;
        const entries = data.entries || [];

        const statusLine = finalizedAt
            ? `<div class="alert alert-success mb-2">Results finalized. Certificates are available for top ranks.</div>`
            : `<div class="alert alert-warning mb-2">Results not finalized yet. Ranking may change until the teacher finalizes.</div>`;

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
                <div class="d-flex align-items-center justify-content-between gap-2 mt-2">
                    <div class="text-muted">You are ranked <strong>#${cert.rank}</strong>. Certificate available.</div>
                    <button class="btn btn-success btn-sm" id="downloadCertBtn" type="button">Download Certificate</button>
                </div>
            `
            : '';

        const leaderboardHtml = `
            <div class="mt-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h4 class="mb-0">Leaderboard</h4>
                    <a class="btn btn-outline-secondary btn-sm" href="/student/quiz/${QUIZ_ID}/leaderboard?courseId=${COURSE_ID}">Open Page</a>
                </div>
                ${statusLine}
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
            </div>
        `;

        box.insertAdjacentHTML('beforeend', leaderboardHtml);

        const btn = document.getElementById('downloadCertBtn');
        if (btn) {
            btn.addEventListener('click', downloadCertificate);
        }
    }

    async function loadLeaderboard() {
        try {
            const res = await apiFetch(`/api/quizzes/${QUIZ_ID}/leaderboard`);
            const data = await res.json();
            if (!data.status) return;
            renderLeaderboardTable(data);
        } catch {
            // ignore
        }
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

    function submitQuiz(isAuto=false) {
        const btn = document.getElementById('submitBtn');
        if (btn) btn.disabled = true;

        apiFetch(`/api/student/quizzes/${QUIZ_ID}/submit`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(buildAnswersPayload())
        })
        .then(r => r.json().then(data => ({ status: r.status, body: data })))
        .then(({ status, body }) => {
            if (status === 201 && body.status === true) {
                if (timerInterval) clearInterval(timerInterval);
                renderResults(body);
            } else {
                const box = document.getElementById('resultBox');
                box.innerHTML = `<div class="alert alert-danger">${escapeHtml(body.message || 'Submit failed')}</div>`;
                if (btn) btn.disabled = false;
            }
        })
        .catch(() => {
            const box = document.getElementById('resultBox');
            box.innerHTML = `<div class="alert alert-danger">Submit failed</div>`;
            if (btn) btn.disabled = false;
        });
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

        apiFetch(`/api/student/quizzes/${QUIZ_ID}`)
            .then(r => r.json())
            .then(data => {
                if (data.status === true && data.data) {
                    quizData = data.data;
                    renderQuiz(quizData);
                } else {
                    document.getElementById('quizTitle').textContent = 'Quiz not found';
                }
            })
            .catch(() => {
                document.getElementById('quizTitle').textContent = 'Error loading quiz';
            });

        document.getElementById('submitBtn').addEventListener('click', function () {
            if (!confirm('Submit your quiz now?')) return;
            submitQuiz(false);
        });
    });
</script>
@endsection
