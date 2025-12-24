@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <h2 class="mb-4 border-bottom pb-2">Leaderboard & Certificate Rules</h2>

        <div id="msgBox" class="alert d-none" role="alert"></div>

        <div class="card shadow-sm">
            <div class="card-body">
                <form id="rulesForm" class="row g-3">
                    <div class="col-md-6">
                        <label for="leaderboardTopN" class="form-label">Leaderboard Top N</label>
                        <input type="number" min="1" max="100" class="form-control" id="leaderboardTopN" required>
                        <div class="form-text">How many students appear in the leaderboard.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="certificateTopN" class="form-label">Certificate Top N</label>
                        <input type="number" min="1" max="10" class="form-control" id="certificateTopN" required>
                        <div class="form-text">How many top ranks receive certificates (default 3).</div>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary" type="submit" id="saveBtn">Save Rules</button>
                        <a href="/admin/dashboard" class="btn btn-outline-secondary">Back to Dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function showMsg(type, text) {
        const box = document.getElementById('msgBox');
        box.className = `alert alert-${type}`;
        box.textContent = text;
        box.classList.remove('d-none');
    }

    async function loadRules() {
        const res = await apiFetch('/api/admin/rules');
        const data = await res.json();
        if (!data.status) {
            showMsg('danger', data.message || 'Failed to load rules');
            return;
        }
        document.getElementById('leaderboardTopN').value = data.data.leaderboard_top_n;
        document.getElementById('certificateTopN').value = data.data.certificate_top_n;
    }

    async function saveRules(e) {
        e.preventDefault();
        const btn = document.getElementById('saveBtn');
        btn.disabled = true;

        const payload = {
            leaderboard_top_n: parseInt(document.getElementById('leaderboardTopN').value, 10),
            certificate_top_n: parseInt(document.getElementById('certificateTopN').value, 10),
        };

        try {
            const res = await apiFetch('/api/admin/rules', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (!data.status) {
                showMsg('danger', data.message || 'Failed to save rules');
                return;
            }
            showMsg('success', 'Rules saved successfully.');
        } catch (e) {
            showMsg('danger', 'Failed to save rules');
        } finally {
            btn.disabled = false;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const user = getUserData();
        if (!getAuthToken() || !user) {
            window.location.href = '/login';
            return;
        }
        if (user.role !== 'admin') {
            window.location.href = user.role === 'teacher' ? '/teacher/dashboard' : '/student/dashboard';
            return;
        }

        document.getElementById('rulesForm').addEventListener('submit', saveRules);
        loadRules();
    });
</script>
@endsection
