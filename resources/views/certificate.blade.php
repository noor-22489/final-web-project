<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            background: #f2f2f2;
            font-family: DejaVu Sans, sans-serif;
            color: #111;
        }

        .page {
            padding: 26px;
        }

        .certificate {
            position: relative;
            height: 100%;
            background: #ffffff;
            box-sizing: border-box;
            padding: 54px 56px;
            border: 12px solid #f3f3f3;
            overflow: hidden;
        }

        .outer-stroke {
            position: absolute;
            top: 18px;
            left: 18px;
            right: 18px;
            bottom: 18px;
            border: 2px solid #caa24d;
        }

        .corner {
            position: absolute;
            top: 0;
            right: 0;
            width: 340px;
            height: 340px;
        }

        .medal {
            position: absolute;
            top: 44px;
            left: 44px;
            width: 64px;
            height: 64px;
        }

        .title {
            font-family: DejaVu Serif, serif;
            font-size: 44px;
            letter-spacing: 2px;
            text-align: center;
            font-weight: 700;
            margin: 0;
        }

        .subtitle {
            text-align: center;
            font-size: 13px;
            margin-top: 6px;
            letter-spacing: 6px;
            color: #666;
            text-transform: uppercase;
        }

        .presented {
            text-align: center;
            margin-top: 44px;
            font-size: 12px;
            letter-spacing: 4px;
            color: #777;
            text-transform: uppercase;
        }

        .student-name-wrap {
            text-align: center;
            margin-top: 16px;
        }

        .student-name {
            display: inline-block;
            font-family: DejaVu Serif, serif;
            font-size: 36px;
            padding: 0 26px 6px 26px;
            border-bottom: 2px solid #111;
            color: #111;
        }

        .description {
            text-align: center;
            font-size: 14px;
            margin-top: 22px;
            color: #444;
            line-height: 1.55;
        }

        .score {
            display: inline-block;
            margin-top: 16px;
            padding: 8px 14px;
            border: 1px solid #e5e5e5;
            border-radius: 999px;
            font-size: 12px;
            color: #222;
            background: #fafafa;
        }

        .signatures {
            position: absolute;
            bottom: 52px;
            left: 56px;
            right: 56px;
        }

        .sig {
            width: 44%;
            display: inline-block;
            text-align: center;
            vertical-align: top;
        }

        .sig.right {
            float: right;
        }

        .signature-line {
            width: 240px;
            border-top: 2px solid #111;
            margin: 0 auto 7px;
        }

        .role {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .subrole {
            font-size: 11px;
            color: #666;
        }

        .meta {
            position: absolute;
            bottom: 22px;
            left: 56px;
            right: 56px;
            font-size: 11px;
            color: #666;
        }

        .meta .left { float: left; }
        .meta .right { float: right; }
    </style>
</head>
<body>

    <div class="page">
        <div class="certificate">
            <div class="outer-stroke"></div>

            <!-- Decorative corner (SVG instead of clip-path for DomPDF compatibility) -->
            <svg class="corner" viewBox="0 0 100 100" preserveAspectRatio="none">
                <polygon points="100,0 0,0 100,100" fill="#1e1e1e" />
                <polygon points="100,0 65,0 100,35" fill="#caa24d" />
            </svg>

            <!-- Medal icon (inline SVG: works in DomPDF) -->
            <svg class="medal" viewBox="0 0 64 64">
                <path d="M20 2h10l2 10-7 7-7-7 2-10z" fill="#1e1e1e"/>
                <path d="M34 2h10l2 10-7 7-7-7 2-10z" fill="#1e1e1e"/>
                <circle cx="32" cy="40" r="16" fill="#caa24d"/>
                <circle cx="32" cy="40" r="12" fill="#f0d27b"/>
                <path d="M32 31l3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1 3-6z" fill="#caa24d"/>
            </svg>

            <h1 class="title">Certificate of Completion</h1>
            <div class="subtitle">QUIZ ACHIEVEMENT</div>

            <div class="presented">THIS IS TO CERTIFY THAT</div>

            <div class="student-name-wrap">
                <div class="student-name">{{ $user_name ?? $studentName }}</div>
            </div>

            <div class="description">
                has successfully passed the quiz <strong>{{ $quiz_title }}</strong>
                <div class="score">Score: <strong>{{ $score }}</strong> / {{ $total }}</div>
                <div style="margin-top: 10px; color: #666; font-size: 12px;">Issued on {{ $date }}</div>
            </div>

            <div class="signatures">
                <div class="sig">
                    <div class="signature-line"></div>
                    <div class="role">Instructor</div>
                    <div class="subrole">Signature</div>
                </div>
                <div class="sig right">
                    <div class="signature-line"></div>
                    <div class="role">Coordinator</div>
                    <div class="subrole">Signature</div>
                </div>
            </div>

            <div class="meta">
                <div class="left">Learning Management System</div>
                <div class="right">{{ $quiz_title }}</div>
            </div>
        </div>
    </div>

</body>
</html>