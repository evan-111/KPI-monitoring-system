<?php
require_once 'includes/auth.php';

requireLogout(); // Redirect if already logged in

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = $_POST['email']    ?? '';
    $password = $_POST['password'] ?? '';

    if (loginSupervisor($email, $password)) {
        header('Location: index.php');
        exit();
    } else {
        $error = 'Invalid email or password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>SAKMS – Supervisor Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:       #0c1425;
            --card:     #152135;
            --input-bg: #0f1c2e;
            --border:   #1d3350;
            --accent:   #3b82f6;
            --accent-h: #2563eb;
            --danger:   #ef4444;
            --text:     #f0f4ff;
            --muted:    #6b82a8;
            --muted2:   #3d5070;
        }

        html, body {
            height: 100%;
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        /* ── Two-column shell ───────────────────────── */
        .login-shell {
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
        }

        /* ══════════════════════════════════════════════
           LEFT PANEL
        ══════════════════════════════════════════════ */
        .login-left {
            flex: 1 1 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 40px 48px 60px;
            position: relative;
            min-height: 100vh;
        }

        /* Floating icon badges */
        .float-badge {
            position: absolute;
            width: 46px;
            height: 46px;
            border-radius: 13px;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px rgba(59,130,246,0.35);
        }
        .float-badge svg {
            width: 22px;
            height: 22px;
            color: #fff;
            stroke: #fff;
        }
        /* Position each badge to match wireframe */
        .badge-chart  { bottom: 28%; left:  14%; }
        .badge-trend  { top:    25%; right: 20%; }
        .badge-people { top:    48%; right: 14%; }

        /* Illustration */
        .illus-wrap {
            width: 100%;
            max-width: 460px;
            margin-bottom: 32px;
            filter: drop-shadow(0 20px 60px rgba(0,0,0,0.5));
        }
        .illus-wrap svg { width: 100%; height: auto; display: block; }

        /* Brand text */
        .brand-block { text-align: center; }
        .brand-name {
            font-family: 'Sora', sans-serif;
            font-size: 52px;
            font-weight: 800;
            color: #fff;
            letter-spacing: 3px;
            line-height: 1;
            margin-bottom: 10px;
        }
        .brand-tagline {
            font-size: 15px;
            font-weight: 500;
            color: var(--text);
            margin-bottom: 5px;
            letter-spacing: 0.2px;
        }
        .brand-sub {
            font-size: 12px;
            color: var(--muted);
        }

        /* ══════════════════════════════════════════════
           RIGHT PANEL
        ══════════════════════════════════════════════ */
        .login-right {
            flex: 0 0 420px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 60px 48px 20px;
            min-height: 100vh;
        }

        .login-card {
            width: 100%;
            max-width: 340px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 38px 32px 30px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.45);
        }

        /* Card heading */
        .card-heading {
            font-family: 'Sora', sans-serif;
            font-size: 21px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 26px;
        }

        /* Error alert */
        .alert-error {
            display: none;
            padding: 10px 13px;
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.5);
            border-radius: 8px;
            color: var(--danger);
            font-size: 12px;
            margin-bottom: 18px;
            line-height: 1.5;
        }
        .alert-error.show { display: block; }

        /* Form fields */
        .form-group { margin-bottom: 18px; }
        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 7px;
        }
        .form-input {
            width: 100%;
            padding: 11px 14px;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 13px;
            font-family: 'DM Sans', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .form-input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
        }
        .form-input::placeholder { color: var(--muted); }

        /* Password with eye toggle */
        .pw-wrap { position: relative; }
        .pw-wrap .form-input { padding-right: 44px; }
        .eye-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            transition: color 0.15s;
            line-height: 0;
        }
        .eye-btn:hover { color: var(--text); }

        /* Remember me */
        .remember-row {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 22px;
            font-size: 13px;
            color: var(--muted);
            cursor: pointer;
            user-select: none;
        }
        .remember-row input[type="checkbox"] {
            width: 15px;
            height: 15px;
            accent-color: var(--accent);
            cursor: pointer;
            flex-shrink: 0;
            border-radius: 3px;
        }

        /* Login button */
        .btn-login {
            width: 100%;
            padding: 12px;
            background: var(--accent);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            letter-spacing: 0.3px;
        }
        .btn-login:hover {
            background: var(--accent-h);
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(59,130,246,0.38);
        }
        .btn-login:active { transform: translateY(0); box-shadow: none; }

        /* Forgot password */
        .forgot-link {
            display: block;
            text-align: center;
            margin-top: 16px;
            font-size: 13px;
            color: var(--accent);
            text-decoration: none;
            transition: color 0.15s;
        }
        .forgot-link:hover { color: #60a5fa; text-decoration: underline; }

        /* Card footer */
        .card-footer {
            margin-top: 28px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
            text-align: center;
            font-size: 11px;
            color: var(--muted2);
            letter-spacing: 0.3px;
        }

        /* ── Responsive ───────────────────────────────── */
        @media (max-width: 860px) {
            .login-shell   { flex-direction: column; }
            .login-left    { min-height: auto; padding: 60px 32px 30px; }
            .login-right   { flex: none; width: 100%; min-height: auto; padding: 0 24px 60px; }
            .float-badge   { display: none; }
            .brand-name    { font-size: 40px; }
        }
    </style>
</head>
<body>

<div class="login-shell">

  <!-- ════════════════════════
       LEFT PANEL — Branding
  ════════════════════════════ -->
  <div class="login-left">

    <!-- Floating icon badges -->
    <div class="float-badge badge-chart">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="20" x2="18" y2="10"/>
        <line x1="12" y1="20" x2="12" y2="4"/>
        <line x1="6"  y1="20" x2="6"  y2="14"/>
      </svg>
    </div>

    <div class="float-badge badge-trend">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="23 6 13.5 15.5 8.5 10.5 1 17"/>
        <polyline points="17 6 23 6 23 12"/>
      </svg>
    </div>

    <div class="float-badge badge-people">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
      </svg>
    </div>

    <!-- KPI Dashboard Illustration -->
    <div class="illus-wrap">
      <svg viewBox="0 0 460 320" xmlns="http://www.w3.org/2000/svg">

        <!-- ── Organic blob background ── -->
        <path d="M90,55 Q45,95 52,175 Q60,248 138,272 Q218,295 292,268
                 Q366,240 385,168 Q402,96 358,52 Q315,10 210,22 Q125,10 90,55 Z"
              fill="#c8dff5" opacity="0.10"/>

        <!-- ── Orange accent circle (top-right) ── -->
        <circle cx="372" cy="55" r="32" fill="#f97316" opacity="0.9"/>
        <circle cx="372" cy="55" r="22" fill="#fb923c" opacity="0.6"/>

        <!-- ── Tablet device frame ── -->
        <rect x="100" y="48" width="248" height="188" rx="14"
              fill="#1e3a5f" stroke="#2d5586" stroke-width="1.5"/>
        <!-- Screen -->
        <rect x="110" y="57" width="228" height="170" rx="9" fill="#0d1f3c"/>

        <!-- Screen — KPI heading -->
        <text x="125" y="80"
              font-family="Arial,sans-serif" font-size="13" font-weight="700" fill="#60a5fa">KPI</text>

        <!-- Screen — thin progress bar -->
        <rect x="125" y="86" width="90" height="4" rx="2" fill="#1e3a5f"/>
        <rect x="125" y="86" width="62" height="4" rx="2" fill="#3b82f6"/>

        <!-- Screen — stat chips -->
        <rect x="125" y="97" width="60" height="30" rx="5" fill="#162d4f"/>
        <text x="131" y="109" font-family="Arial" font-size="7" fill="#6b82a8">Avg Score</text>
        <text x="131" y="121" font-family="Arial" font-size="11" font-weight="700" fill="#f0f4ff">4.2</text>

        <rect x="192" y="97" width="60" height="30" rx="5" fill="#162d4f"/>
        <text x="198" y="109" font-family="Arial" font-size="7" fill="#6b82a8">Top Perf</text>
        <text x="198" y="121" font-family="Arial" font-size="11" font-weight="700" fill="#22c55e">92%</text>

        <!-- Screen — donut ring chart (right) -->
        <circle cx="286" cy="115" r="34" fill="none" stroke="#1e3a5f" stroke-width="12"/>
        <!-- Segment 1: blue (main) -->
        <circle cx="286" cy="115" r="34" fill="none" stroke="#3b82f6" stroke-width="12"
                stroke-dasharray="112 101" stroke-dashoffset="25.5"
                transform="rotate(-90 286 115)"/>
        <!-- Segment 2: cyan -->
        <circle cx="286" cy="115" r="34" fill="none" stroke="#06b6d4" stroke-width="12"
                stroke-dasharray="53 160" stroke-dashoffset="-86.5"
                transform="rotate(-90 286 115)"/>
        <!-- Donut label -->
        <text x="279" y="112" font-family="Arial" font-size="9"  font-weight="700" fill="#f0f4ff">76%</text>
        <text x="277" y="122" font-family="Arial" font-size="6.5" fill="#6b82a8">Target</text>

        <!-- Screen — bar chart -->
        <rect x="125" y="173" width="13" height="28" rx="2" fill="#3b82f6"/>
        <rect x="143" y="158" width="13" height="43" rx="2" fill="#3b82f6" opacity="0.82"/>
        <rect x="161" y="166" width="13" height="35" rx="2" fill="#3b82f6" opacity="0.68"/>
        <rect x="179" y="148" width="13" height="53" rx="2" fill="#3b82f6"/>
        <rect x="197" y="161" width="13" height="40" rx="2" fill="#06b6d4" opacity="0.75"/>
        <rect x="215" y="154" width="13" height="47" rx="2" fill="#3b82f6" opacity="0.9"/>

        <!-- Screen — sparkline -->
        <polyline points="125,148 143,138 161,143 179,128 197,134 215,118 233,122"
                  fill="none" stroke="#22c55e" stroke-width="1.8"
                  stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="233" cy="122" r="3" fill="#22c55e"/>

        <!-- ── Ladder (right side) ── -->
        <line x1="396" y1="108" x2="396" y2="228" stroke="#2d4f7c" stroke-width="3" stroke-linecap="round"/>
        <line x1="412" y1="108" x2="412" y2="228" stroke="#2d4f7c" stroke-width="3" stroke-linecap="round"/>
        <line x1="396" y1="128" x2="412" y2="128" stroke="#2d4f7c" stroke-width="2.2"/>
        <line x1="396" y1="150" x2="412" y2="150" stroke="#2d4f7c" stroke-width="2.2"/>
        <line x1="396" y1="172" x2="412" y2="172" stroke="#2d4f7c" stroke-width="2.2"/>
        <line x1="396" y1="194" x2="412" y2="194" stroke="#2d4f7c" stroke-width="2.2"/>
        <line x1="396" y1="216" x2="412" y2="216" stroke="#2d4f7c" stroke-width="2.2"/>

        <!-- ── Person figures ── -->

        <!-- Person on ladder (top right) — teal -->
        <circle cx="404" cy="100" r="9"  fill="#34d399"/>
        <rect   cx="404" cy="112" x="398" y="110" width="12" height="22" rx="3" fill="#0d9488"/>
        <!-- arms reaching up -->
        <line x1="398" y1="116" x2="390" y2="106" stroke="#0d9488" stroke-width="3" stroke-linecap="round"/>
        <line x1="410" y1="116" x2="396" y2="108" stroke="#0d9488" stroke-width="3" stroke-linecap="round"/>

        <!-- Person left side, standing — orange -->
        <circle cx="72"  cy="152" r="11" fill="#f97316"/>
        <rect   x="64"   y="164"  width="16" height="30" rx="5" fill="#2563eb"/>
        <!-- left arm up -->
        <line x1="64"  y1="170" x2="52"  y2="158" stroke="#2563eb" stroke-width="3" stroke-linecap="round"/>
        <!-- right arm -->
        <line x1="80"  y1="170" x2="90"  y2="176" stroke="#2563eb" stroke-width="3" stroke-linecap="round"/>
        <!-- legs -->
        <line x1="68"  y1="194" x2="65"  y2="212" stroke="#2563eb" stroke-width="3" stroke-linecap="round"/>
        <line x1="76"  y1="194" x2="79"  y2="212" stroke="#2563eb" stroke-width="3" stroke-linecap="round"/>

        <!-- Person bottom-left, small — yellow -->
        <circle cx="118" cy="248" r="9"  fill="#fbbf24"/>
        <rect   x="111"  y="258"  width="14" height="24" rx="4" fill="#0891b2"/>
        <line x1="111"  y1="264" x2="103" y2="260" stroke="#0891b2" stroke-width="2.5" stroke-linecap="round"/>
        <line x1="125"  y1="264" x2="133" y2="268" stroke="#0891b2" stroke-width="2.5" stroke-linecap="round"/>

        <!-- Person bottom-right — pink/purple -->
        <circle cx="340" cy="244" r="10" fill="#f9a8d4"/>
        <rect   x="333"  y="255"  width="14" height="26" rx="4" fill="#7c3aed"/>
        <line x1="333"  y1="261" x2="323" y2="267" stroke="#7c3aed" stroke-width="2.5" stroke-linecap="round"/>
        <line x1="347"  y1="261" x2="357" y2="257" stroke="#7c3aed" stroke-width="2.5" stroke-linecap="round"/>

        <!-- Person right side reaching — blue -->
        <circle cx="378" cy="176" r="9"  fill="#fb923c"/>
        <rect   x="372"  y="186"  width="12" height="22" rx="3" fill="#7c3aed"/>
        <line x1="372"  y1="190" x2="364" y2="182" stroke="#7c3aed" stroke-width="2.5" stroke-linecap="round"/>

        <!-- ── Decorative dots ── -->
        <circle cx="48"  cy="72"  r="4.5" fill="#3b82f6" opacity="0.45"/>
        <circle cx="430" cy="270" r="4"   fill="#06b6d4"  opacity="0.35"/>
        <circle cx="80"  cy="280" r="3"   fill="#8b5cf6"  opacity="0.4"/>
        <circle cx="160" cy="30"  r="3"   fill="#3b82f6"  opacity="0.4"/>

      </svg>
    </div>

    <!-- Brand block -->
    <div class="brand-block">
      <div class="brand-name">SAKMS</div>
      <div class="brand-tagline">Sales Assistant KPI Monitoring System</div>
      <div class="brand-sub">Supervisor Performance Monitoring Portal</div>
    </div>

  </div><!-- /login-left -->

  <!-- ════════════════════════
       RIGHT PANEL — Login Card
  ════════════════════════════ -->
  <div class="login-right">
    <div class="login-card">

      <h2 class="card-heading">Supervisor Login</h2>

      <!-- Error message -->
      <div class="alert-error <?php echo $error ? 'show' : ''; ?>">
        <?php echo htmlspecialchars($error); ?>
      </div>

      <form method="POST" autocomplete="off" novalidate>

        <!-- Email -->
        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input
            class="form-input"
            type="email"
            id="email"
            name="email"
            placeholder="supervisor@company.com"
            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
            required
            autofocus
          />
        </div>

        <!-- Password -->
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <div class="pw-wrap">
            <input
              class="form-input"
              type="password"
              id="password"
              name="password"
              placeholder="Enter your password"
              required
            />
            <button type="button" class="eye-btn" id="eyeBtn" aria-label="Show/hide password">
              <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24"
                   fill="none" stroke="currentColor" stroke-width="2"
                   stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Remember me -->
        <label class="remember-row">
          <input type="checkbox" name="remember" id="remember">
          Remember me
        </label>

        <!-- Submit -->
        <button type="submit" class="btn-login">Login</button>

      </form>

      <!-- Forgot password -->
      <a href="#" class="forgot-link">Forgot password?</a>

      <!-- Footer note -->
      <div class="card-footer">Internal System Access Only</div>

    </div>
  </div><!-- /login-right -->

</div><!-- /login-shell -->

<script>
// ── Password eye toggle ────────────────────────────────────────────────────
(function () {
    const btn   = document.getElementById('eyeBtn');
    const input = document.getElementById('password');
    const icon  = document.getElementById('eyeIcon');

    const SVG_SHOW = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                      <circle cx="12" cy="12" r="3"/>`;
    const SVG_HIDE = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8
                              a18.45 18.45 0 0 1 5.06-5.94
                              M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8
                              a18.5 18.5 0 0 1-2.16 3.19
                              m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                      <line x1="1" y1="1" x2="23" y2="23"/>`;

    btn.addEventListener('click', function () {
        const visible = input.type === 'text';
        input.type    = visible ? 'password' : 'text';
        icon.innerHTML = visible ? SVG_SHOW : SVG_HIDE;
    });
})();
</script>

</body>
</html>
