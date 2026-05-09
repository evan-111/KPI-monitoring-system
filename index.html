<?php
/**
 * SAKMS - Sales Assistant Profiles Page
 */

// ── Profile picture upload handler ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_profile_picture') {
    header('Content-Type: application/json');
    require_once '../includes/db_config.php';
    require_once '../includes/functions.php';

    $emp_id = (int)($_POST['emp_id'] ?? 0);
    $file   = $_FILES['file'] ?? null;

    if (!$emp_id || !$file) { echo json_encode(['success'=>false,'msg'=>'Invalid request']); exit; }

    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($file['type'], $allowed))      { echo json_encode(['success'=>false,'msg'=>'Invalid file type']); exit; }
    if ($file['size'] > 2 * 1024 * 1024)         { echo json_encode(['success'=>false,'msg'=>'File too large']);   exit; }

    $upload_dir = '../assets/uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $filename = 'profile_' . $emp_id . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
        $stmt = $conn->prepare("UPDATE staff SET profile_picture=? WHERE staff_id=?");
        $stmt->bind_param("si", $filename, $emp_id);
        $stmt->execute();
        echo json_encode(['success'=>true,'msg'=>'Profile updated','file'=>$filename]);
    } else {
        echo json_encode(['success'=>false,'msg'=>'Upload failed']);
    }
    exit;
}

// ── KPI calculation by date range ───────────────────────────────────────────
function prfCalcByRange($conn, $staff_id, $from, $to): array {
    $stmt = $conn->prepare("
        SELECT ksec.section_name AS section, kg.weight_percentage AS group_weight, ks.score
        FROM kpi_score ks
        JOIN kpi_item    ki   ON ks.kpi_item_id  = ki.kpi_item_id
        JOIN kpi_group   kg   ON ki.kpi_group_id = kg.kpi_group_id
        JOIN kpi_section ksec ON kg.section_id   = ksec.section_id
        WHERE ks.staff_id=? AND ks.date_recorded BETWEEN ? AND ?
        ORDER BY ksec.section_id, kg.kpi_group_id, ki.kpi_item_id
    ");
    $stmt->bind_param("iss", $staff_id, $from, $to);
    $stmt->execute();
    $result = $stmt->get_result();

    $groups = [];
    while ($row = $result->fetch_assoc()) {
        $s = $row['section'];
        $g = $row['group_weight'];
        if (!isset($groups[$s][$g])) $groups[$s][$g] = ['scores'=>[],'weight'=>(float)$g/100];
        $groups[$s][$g]['scores'][] = (int)$row['score'];
    }
    if (empty($groups)) return ['overall'=>0,'section1'=>0,'section2'=>0,'rating'=>'No Data'];

    $s1 = $s2 = 0;
    foreach ($groups as $section => $sg) {
        foreach ($sg as $gd) {
            $w = array_sum($gd['scores']) / count($gd['scores']) * $gd['weight'];
            $section === 'Core Competencies' ? $s1 += $w : $s2 += $w;
        }
    }
    $score = max(1, min(5, round($s1 + $s2, 2)));
    return ['overall'=>$score,'section1'=>round($s1,2),'section2'=>round($s2,2),'rating'=>KPICalculator::getRatingLabel($score)];
}

// ── KPI group scores by date range ──────────────────────────────────────────
function prfGetGroupsByRange($conn, $staff_id, $from, $to): array {
    $stmt = $conn->prepare("
        SELECT kg.group_name AS kpi_group, AVG(ks.score) AS avg_score, COUNT(*) AS count
        FROM kpi_score ks
        JOIN kpi_item  ki ON ks.kpi_item_id  = ki.kpi_item_id
        JOIN kpi_group kg ON ki.kpi_group_id = kg.kpi_group_id
        WHERE ks.staff_id=? AND ks.date_recorded BETWEEN ? AND ?
        GROUP BY kg.kpi_group_id, kg.group_name ORDER BY avg_score DESC
    ");
    $stmt->bind_param("iss", $staff_id, $from, $to);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ── Score colour helper (mirrors dshScoreColor in dashboard.php) ─────────────
function prfScoreColor(float $s): string {
    if ($s >= 5.0) return '#22c55e'; // Green  – Excellent
    if ($s >= 4.0) return '#3b82f6'; // Blue   – Good      (4.00–4.99)
    if ($s >= 3.0) return '#f59e0b'; // Amber  – Satisfactory (3.00–3.99)
    if ($s >= 2.0) return '#ef4444'; // Red    – Poor      (2.00–2.99)
    return '#7f1d1d';                // Maroon – Critical  (0–1.99)
}
?>

<style>
/* ════════════════════════════════════════
   PROFILES PAGE
════════════════════════════════════════ */
@keyframes prf-fadeInUp  { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:none} }
@keyframes prf-slideRight{ from{opacity:0;transform:translateX(-14px)} to{opacity:1;transform:none} }
@keyframes prf-barGrow   { from{width:0!important} }
@keyframes prf-avatarIn  { from{transform:scale(0.7) rotate(-6deg);opacity:0} to{transform:none;opacity:1} }
@keyframes prf-cardPop   { from{transform:scale(0.96);opacity:0} to{transform:none;opacity:1} }
/* ***************************
   Title: Pure CSS Skeleton Loading Animation With Shimmer
   Author: leopoglia
   Date: 2021
   Type: Source Code
   Availability: https://gist.github.com/leopoglia/497be76c88a985b9b007481b27a38603
*************************** */
@keyframes prf-shimmer   { 0%{background-position:-400px 0} 100%{background-position:400px 0} }
@keyframes prf-decoBarGrow{ from{height:0;opacity:0} to{opacity:.55} }
@keyframes prf-pulseGreen{ 0%{box-shadow:0 0 0 0 rgba(34,197,94,.45)} 70%{box-shadow:0 0 0 8px rgba(34,197,94,0)} 100%{box-shadow:0 0 0 0 rgba(34,197,94,0)} }
@keyframes prf-forecast-pulse{ 0%,100%{box-shadow:0 0 0 0 rgba(59,130,246,.4)} 50%{box-shadow:0 0 0 6px rgba(59,130,246,.1)} }
@keyframes prf-chart-icon-pulse{ 0%,100%{transform:scale(1);box-shadow:0 0 12px rgba(59,130,246,.2)} 50%{transform:scale(1.08);box-shadow:0 0 20px rgba(59,130,246,.4)} }
@keyframes prf-topline   { 0%{opacity:0;transform:translateX(-100%)} 50%{opacity:1} 100%{opacity:0;transform:translateX(100%)} }
@keyframes prf-chart-fade{ 0%{opacity:0;transform:translateY(8px)} 100%{opacity:1;transform:none} }

/* ── Reveal ─────────────────────────── */
.prf-reveal { opacity:0; transform:translateY(18px); transition:opacity .5s ease,transform .5s ease; }
.prf-reveal.visible { opacity:1; transform:none; }

/* ── Layout ─────────────────────────── */
.prf-page  { display:flex; flex-direction:column; gap:20px; padding-bottom:40px; }
.prf-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.prf-full  { grid-column:1/-1; }
@media(max-width:860px){ .prf-grid2{ grid-template-columns:1fr; } }

/* ── Base card ──────────────────────── */
.prf-card { background:var(--bg-card); border:1px solid var(--border); border-radius:16px; padding:22px 26px; box-shadow:0 2px 16px rgba(0,0,0,.25); }

/* ── Section heading ────────────────── */
.prf-sh      { display:flex; align-items:center; gap:10px; margin-bottom:18px; }
.prf-sh-icon { width:32px; height:32px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0; }
.prf-sh-title{ font-family:'Sora',sans-serif; font-size:13px; font-weight:700; color:var(--text-primary); }
.prf-sh-sub  { font-size:11px; color:var(--text-muted); margin-top:1px; }

/* ── List view ──────────────────────── */
.prf-list-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(270px,1fr)); gap:14px; }
.prf-emp-card  { background:var(--bg-card); border:1px solid var(--border); border-radius:14px; padding:18px; display:flex; flex-direction:column; gap:12px; transition:all .2s ease; animation:prf-cardPop .35s ease both; position:relative; overflow:hidden; }
.prf-emp-card::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg,var(--accent),var(--accent-2)); opacity:0; transition:opacity .2s; }
.prf-emp-card:hover { border-color:rgba(59,130,246,.3); transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.35); }
.prf-emp-card:hover::before { opacity:1; }

.prf-avatar-row  { display:flex; align-items:center; gap:12px; }
.prf-avatar-wrap { position:relative; flex-shrink:0; width:52px; height:52px; }
.prf-avatar      { width:52px; height:52px; border-radius:50%; object-fit:cover; border:2px solid var(--border); animation:prf-avatarIn .4s ease both; background:var(--bg-input); }
.prf-avatar-dot  { position:absolute; bottom:1px; right:1px; width:11px; height:11px; border-radius:50%; background:#22c55e; border:2px solid var(--bg-card); animation:prf-pulseGreen 2s ease infinite; }
.prf-emp-name { font-family:'Sora',sans-serif; font-size:13px; font-weight:700; color:var(--text-primary); }
.prf-emp-role { font-size:11px; color:var(--text-muted); margin-top:2px; }
.prf-emp-id   { font-size:10px; color:var(--text-muted); font-family:monospace; margin-top:2px; }

.prf-pill       { display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:20px; font-size:10px; font-weight:600; background:rgba(255,255,255,.05); border:1px solid var(--border); color:var(--text-secondary); }
.prf-prog-label { display:flex; justify-content:space-between; margin-bottom:5px; }
.prf-prog-name  { font-size:11px; color:var(--text-muted); }
.prf-prog-val   { font-family:'Sora',sans-serif; font-size:12px; font-weight:800; }
.prf-prog-track { height:6px; border-radius:3px; background:rgba(255,255,255,.06); overflow:hidden; }
.prf-prog-fill  { height:100%; border-radius:3px; animation:prf-barGrow .9s cubic-bezier(.4,0,.2,1) both; position:relative; }
.prf-prog-fill::after { content:''; position:absolute; inset:0; background:linear-gradient(90deg,transparent,rgba(255,255,255,.18),transparent); background-size:200% 100%; animation:prf-shimmer 2.2s infinite linear; }
.prf-view-btn   { display:flex; align-items:center; justify-content:center; gap:6px; padding:8px; background:rgba(59,130,246,.07); border:1px solid rgba(59,130,246,.2); border-radius:9px; color:var(--accent); font-size:11px; font-weight:600; text-decoration:none; transition:all .18s; }
.prf-view-btn:hover { background:rgba(59,130,246,.15); border-color:rgba(59,130,246,.4); transform:translateY(-1px); }

/* ── Search ─────────────────────────── */
.prf-search-wrap { position:relative; }
.prf-search-wrap svg { position:absolute; left:11px; top:50%; transform:translateY(-50%); color:var(--text-muted); pointer-events:none; }
.prf-search-input { width:100%; padding:9px 12px 9px 34px; background:var(--bg-input); border:1px solid var(--border); border-radius:10px; color:var(--text-primary); font-size:13px; font-family:'DM Sans',sans-serif; transition:border-color .15s; }
.prf-search-input:focus { outline:none; border-color:var(--accent); }
.prf-search-input::placeholder { color:var(--text-muted); }

/* ── Summary chips ──────────────────── */
.prf-summary-bar { display:flex; gap:10px; flex-wrap:wrap; }
.prf-sum-chip    { display:inline-flex; align-items:center; gap:6px; padding:7px 12px; border-radius:9px; background:rgba(255,255,255,.04); border:1px solid var(--border); font-size:12px; color:var(--text-secondary); }
.prf-sum-chip strong { color:var(--text-primary); font-weight:700; font-family:'Sora',sans-serif; }
.prf-sum-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }

/* ── Detail view ────────────────────── */
.prf-back { display:inline-flex; align-items:center; gap:6px; color:var(--text-secondary); text-decoration:none; font-size:12px; font-weight:500; padding:7px 13px; border-radius:8px; background:rgba(255,255,255,.04); border:1px solid var(--border); transition:all .18s; width:fit-content; }
.prf-back:hover { color:var(--accent); border-color:rgba(59,130,246,.3); background:rgba(59,130,246,.06); }

/* ── Hero card ──────────────────────── */
.prf-hero { background:linear-gradient(135deg,#0f1a2e 0%,#162035 50%,#1a2233 100%); border:1px solid var(--border); border-radius:16px; padding:26px 30px; position:relative; overflow:hidden; animation:prf-fadeInUp .4s ease both; }
.prf-hero::before { content:''; position:absolute; top:-60px; right:-60px; width:280px; height:280px; background:radial-gradient(circle,rgba(59,130,246,.07) 0%,transparent 70%); border-radius:50%; pointer-events:none; }
.prf-hero-inner { display:grid; grid-template-columns:auto 1fr auto; gap:20px; align-items:center; position:relative; z-index:2; }
@media(max-width:900px){ .prf-hero-inner{ grid-template-columns:auto 1fr; } .prf-hero-right{ display:none; } }

.prf-hero-avatar { width:76px; height:76px; border-radius:50%; object-fit:cover; border:3px solid rgba(59,130,246,.35); box-shadow:0 0 0 6px rgba(59,130,246,.08); animation:prf-avatarIn .5s ease both; background:var(--bg-input); flex-shrink:0; }
.prf-avatar-upload-btn { position:absolute; bottom:-5px; right:-5px; width:32px; height:32px; border-radius:50%; background:var(--accent); border:2px solid var(--bg-card); display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all .2s; flex-shrink:0; box-shadow:0 2px 8px rgba(59,130,246,.3); }
.prf-avatar-upload-btn:hover { background:#2563eb; transform:scale(1.1); box-shadow:0 4px 12px rgba(59,130,246,.5); }
#prf-avatar-input { display:none; }
.prf-hero-name { font-family:'Sora',sans-serif; font-size:20px; font-weight:700; color:var(--text-primary); letter-spacing:-.3px; }
.prf-hero-sub  { font-size:12px; color:var(--text-secondary); margin-top:4px; }
.prf-hero-id   { display:inline-flex; align-items:center; gap:5px; font-size:10px; color:var(--text-muted); margin-top:8px; background:rgba(255,255,255,.05); border:1px solid var(--border); padding:3px 10px; border-radius:20px; font-family:monospace; }

.prf-hero-stats { display:flex; flex-direction:column; gap:10px; padding:0 16px; border-left:1px solid rgba(255,255,255,.07); }
.prf-stat-item  { display:flex; flex-direction:column; gap:2px; }
.prf-stat-lbl   { font-size:10px; text-transform:uppercase; letter-spacing:.7px; color:var(--text-muted); font-weight:600; }
.prf-stat-val   { font-family:'Sora',sans-serif; font-size:14px; font-weight:700; color:var(--text-primary); }

.prf-hero-ring-block { display:flex; flex-direction:column; align-items:center; gap:8px; }
/* ***************************
   Title: Pure CSS animated SVG circular progress ring
   Author: Tobias Ahlin
   Date: 2020
   Type: Source Code
   Availability: https://github.com/tobiasahlin/SpinKit
*************************** */
.prf-ring-container  { position:relative; width:96px; height:96px; }
.prf-ring-svg        { transform:rotate(-90deg); display:block; }
.prf-ring-bg         { fill:none; stroke:rgba(255,255,255,.06); stroke-width:9; }
.prf-ring-fg         { fill:none; stroke-width:9; stroke-linecap:round; stroke-dasharray:245; stroke-dashoffset:245; transition:stroke-dashoffset 1.3s cubic-bezier(.4,0,.2,1); }
.prf-ring-text       { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; pointer-events:none; }
.prf-ring-num        { font-family:'Sora',sans-serif; font-size:20px; font-weight:800; color:var(--text-primary); line-height:1; }
.prf-ring-max        { font-size:9px; color:var(--text-muted); font-weight:600; margin-top:2px; }
.prf-rating-badge    { padding:5px 14px; border-radius:20px; font-size:11px; font-weight:700; letter-spacing:.3px; }

/* ── Mini ring ──────────────────────── */
.prf-mini-ring-wrap { position:relative; width:64px; height:64px; flex-shrink:0; }
.prf-mini-ring-svg  { transform:rotate(-90deg); display:block; }
.prf-mini-ring-bg   { fill:none; stroke:rgba(255,255,255,.06); stroke-width:7; }
.prf-mini-ring-fg   { fill:none; stroke-width:7; stroke-linecap:round; stroke-dasharray:163; stroke-dashoffset:163; transition:stroke-dashoffset 1.1s cubic-bezier(.4,0,.2,1) .2s; }
.prf-mini-ring-text { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-family:'Sora',sans-serif; font-size:12px; font-weight:800; }

/* ── KPI group rows ─────────────────── */
.prf-group-row   { display:flex; align-items:center; gap:10px; padding:11px 0; border-bottom:1px solid rgba(255,255,255,.04); animation:prf-slideRight .4s ease both; }
.prf-group-row:last-child { border-bottom:none; }
.prf-group-name  { flex:1; font-size:12px; color:var(--text-primary); font-weight:500; }
.prf-group-bar-track { width:100px; height:5px; border-radius:3px; background:rgba(255,255,255,.06); overflow:hidden; }
.prf-group-bar-fill  { height:100%; border-radius:3px; animation:prf-barGrow .9s cubic-bezier(.4,0,.2,1) both; }
.prf-group-badge { min-width:36px; text-align:center; padding:3px 7px; border-radius:6px; font-size:11px; font-weight:700; }
.prf-group-count { font-size:11px; color:var(--text-muted); min-width:44px; text-align:right; }

/* ── Comments ───────────────────────── */
.prf-comment-box      { background:rgba(255,255,255,.03); border-radius:10px; padding:13px 16px; font-size:12px; color:var(--text-primary); line-height:1.7; }
.prf-comment-enhanced { background:linear-gradient(135deg,rgba(168,85,247,.04),rgba(236,72,153,.04))!important; border:1px solid rgba(168,85,247,.15)!important; border-left:4px solid #a855f7!important; transition:all .3s ease; }
.prf-comment-enhanced:hover { background:linear-gradient(135deg,rgba(6,182,212,.08),rgba(59,130,246,.08))!important; box-shadow:0 4px 16px rgba(6,182,212,.15)!important; }
.prf-comment-card { transition:all .3s ease; }
.prf-comment-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(6,182,212,.2); }

/* ── Training ───────────────────────── */
.prf-training-item, .prf-training-enhanced {
    background:rgba(34,197,94,.05); border-left:3px solid #22c55e;
    border-radius:0 10px 10px 0; padding:13px 16px; margin-bottom:10px; transition:all .25s ease;
}
.prf-training-item:last-child, .prf-training-enhanced:last-child { margin-bottom:0; }
.prf-training-enhanced { background:rgba(34,197,94,.06)!important; }
.prf-training-enhanced:hover { background:rgba(34,197,94,.1)!important; transform:translateX(4px); }
.prf-training-lbl  { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:#22c55e; margin-bottom:4px; }
.prf-training-text { font-size:12px; color:var(--text-primary); line-height:1.6; }

/* ── Forecast ───────────────────────── */
.prf-forecast-card { transition:all .3s ease; }
.prf-forecast-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(34,197,94,.15); }
.prf-forecast-enhanced { background:linear-gradient(135deg,rgba(59,130,246,.06),rgba(34,197,94,.04)); border:1px solid rgba(59,130,246,.15); border-radius:12px; padding:16px; display:flex; align-items:center; gap:16px; margin-top:12px; transition:all .3s ease; }
.prf-forecast-enhanced:hover { background:linear-gradient(135deg,rgba(59,130,246,.1),rgba(34,197,94,.08)); box-shadow:0 4px 16px rgba(59,130,246,.15); transform:translateY(-2px); }
.prf-forecast-icon-animated { width:48px; height:48px; border-radius:12px; background:linear-gradient(135deg,rgba(59,130,246,.15),rgba(34,197,94,.1)); border:1px solid rgba(59,130,246,.2); display:flex; align-items:center; justify-content:center; flex-shrink:0; animation:prf-forecast-pulse 2s ease-in-out infinite; }
.prf-forecast-vals   { display:flex; align-items:center; gap:12px; margin:4px 0; }
.prf-forecast-val    { font-family:'Sora',sans-serif; font-size:24px; font-weight:800; color:var(--accent); }
.prf-forecast-rating { font-size:11px; font-weight:700; padding:4px 10px; border-radius:6px; background:rgba(59,130,246,.1); border:1px solid rgba(59,130,246,.2); }
.prf-forecast-lbl    { font-size:10px; text-transform:uppercase; letter-spacing:.7px; color:var(--text-muted); font-weight:600; }
.prf-forecast-sub    { font-size:11px; color:var(--text-secondary); margin-top:6px; display:flex; align-items:center; }

/* ── Trend chart ────────────────────── */
.prf-card.prf-full { position:relative; overflow:hidden; }
.prf-card.prf-full::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg,transparent,#3b82f6,transparent); opacity:0; animation:prf-topline 2s ease-out; pointer-events:none; }
#prfTrendChart { animation:prf-chart-fade 0.8s ease-out; }
.prf-trend-card { transition:all .3s ease; }
.prf-trend-card:hover { background:linear-gradient(135deg,rgba(59,130,246,.02),rgba(34,197,94,.02)); box-shadow:0 8px 24px rgba(59,130,246,.1); }

/* ── Tooltip ────────────────────────── */
[data-ptip]{ position:relative; cursor:default; }
[data-ptip]::after{ content:attr(data-ptip); position:absolute; bottom:calc(100% + 6px); left:50%; transform:translateX(-50%); background:#0f1a2e; border:1px solid #243047; color:#f0f4ff; font-size:11px; padding:4px 8px; border-radius:6px; white-space:nowrap; pointer-events:none; opacity:0; transition:opacity .14s; z-index:500; }
[data-ptip]:hover::after{ opacity:1; }

/* ── Period dropdown ────────────────── */
.prf-period-wrap { position:relative; }
.prf-period-btn  { display:inline-flex; align-items:center; gap:7px; padding:8px 14px; border-radius:9px; background:rgba(255,255,255,.05); border:1px solid var(--border); color:var(--text-primary); font-size:12px; font-weight:600; cursor:pointer; transition:all .18s; white-space:nowrap; font-family:'DM Sans',sans-serif; }
.prf-period-btn:hover { background:rgba(255,255,255,.09); border-color:rgba(59,130,246,.3); }
.prf-period-btn svg { color:var(--text-muted); flex-shrink:0; }
.prf-period-dropdown { display:none; position:fixed; z-index:99999; width:340px; max-height:80vh; overflow-y:auto; background:#131e30; border:1px solid rgba(59,130,246,.25); border-radius:14px; padding:0; box-shadow:0 20px 60px rgba(0,0,0,.6),0 0 0 1px rgba(255,255,255,.04); scrollbar-width:thin; scrollbar-color:#243047 transparent; }
.prf-period-dropdown::-webkit-scrollbar { width:4px; }
.prf-period-dropdown::-webkit-scrollbar-thumb { background:#243047; border-radius:2px; }
.prf-period-dropdown.open { display:block; animation:prf-fadeInUp .18s ease; }

.prf-dd-head      { display:flex; align-items:center; gap:9px; padding:14px 16px 12px; border-bottom:1px solid rgba(255,255,255,.07); background:linear-gradient(135deg,rgba(59,130,246,.08),rgba(6,182,212,.04)); }
.prf-dd-head-icon { width:28px; height:28px; border-radius:8px; background:rgba(59,130,246,.15); border:1px solid rgba(59,130,246,.25); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.prf-dd-head-title{ font-family:'Sora',sans-serif; font-size:12px; font-weight:700; color:var(--text-primary); }
.prf-dd-head-sub  { font-size:10px; color:var(--text-muted); margin-top:1px; }
.prf-dd-body      { padding:14px 16px; display:flex; flex-direction:column; gap:12px; }
.prf-dd-section-lbl { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:var(--text-muted); margin-bottom:7px; }
.prf-dd-divider   { border:none; border-top:1px solid rgba(255,255,255,.07); margin:0; }

.prf-qs-list  { display:flex; flex-direction:column; gap:3px; }
.prf-qs-btn   { display:flex; align-items:center; justify-content:space-between; width:100%; padding:9px 11px; background:transparent; border:1px solid transparent; border-radius:8px; color:var(--text-secondary); font-size:12px; font-weight:500; cursor:pointer; text-align:left; transition:all .14s; font-family:'DM Sans',sans-serif; }
.prf-qs-btn:hover  { background:rgba(59,130,246,.1); border-color:rgba(59,130,246,.2); color:var(--text-primary); }
.prf-qs-btn.active { background:rgba(59,130,246,.15); border-color:rgba(59,130,246,.35); color:#60a5fa; font-weight:700; }
.prf-qs-check { width:15px; height:15px; border-radius:50%; background:var(--accent); display:flex; align-items:center; justify-content:center; flex-shrink:0; opacity:0; transition:opacity .14s; }
.prf-qs-btn.active .prf-qs-check { opacity:1; }
.prf-qs-sub { font-size:10px; color:var(--text-muted); font-weight:400; }
.prf-qs-btn.active .prf-qs-sub { color:rgba(96,165,250,.7); }

.prf-dd-month-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:5px; }
.prf-dd-month-pill { padding:8px 4px; text-align:center; border-radius:8px; font-size:11px; font-weight:600; color:var(--text-muted); border:1px solid var(--border); background:transparent; cursor:default; transition:all .14s; font-family:'DM Sans',sans-serif; }
.prf-dd-month-pill.has-data { color:var(--text-primary); background:rgba(59,130,246,.08); border-color:rgba(59,130,246,.2); cursor:pointer; }
.prf-dd-month-pill.has-data:hover { background:rgba(59,130,246,.18); }
.prf-dd-month-pill.active { background:var(--accent)!important; border-color:var(--accent)!important; color:#fff!important; }

.prf-dd-date-row    { display:flex; align-items:center; gap:7px; }
.prf-dd-date-wrapper{ position:relative; flex:1; display:flex; align-items:center; }
.prf-dd-date-input  { flex:1; padding:8px 9px; background:var(--bg-input); border:1px solid var(--border); border-radius:7px; color:var(--text-primary); font-size:11px; font-family:'DM Sans',sans-serif; transition:border-color .14s; cursor:pointer; }
.prf-dd-date-input:focus { outline:none; border-color:var(--accent); }
.prf-dd-calendar-icon { position:absolute; right:8px; pointer-events:none; color:var(--text-muted); font-size:13px; opacity:0.7; }
.prf-dd-sep   { color:var(--text-muted); font-size:12px; flex-shrink:0; }
.prf-dd-apply { width:100%; padding:9px 0; margin-top:8px; background:linear-gradient(135deg,#2563eb,#3b82f6); color:#fff; border:none; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; font-family:'DM Sans',sans-serif; box-shadow:0 3px 10px rgba(59,130,246,.3); transition:all .15s; }
.prf-dd-apply:hover { background:linear-gradient(135deg,#1d4ed8,#2563eb); transform:translateY(-1px); }

.prf-dd-year-block  { border-bottom:1px solid rgba(255,255,255,.05); padding-bottom:8px; margin-bottom:4px; }
.prf-dd-year-block:last-child { border-bottom:none; margin-bottom:0; padding-bottom:0; }
.prf-dd-year-toggle { display:flex; align-items:center; justify-content:space-between; width:100%; padding:8px 11px; cursor:pointer; background:rgba(255,255,255,.03); border:1px solid var(--border); border-radius:8px; font-size:12px; font-weight:600; color:var(--text-secondary); font-family:'DM Sans',sans-serif; transition:all .14s; }
.prf-dd-year-toggle:hover { background:rgba(59,130,246,.08); border-color:rgba(59,130,246,.2); color:var(--text-primary); }
.prf-dd-year-toggle.open { color:var(--accent); border-color:rgba(59,130,246,.35); background:rgba(59,130,246,.1); }
.prf-dd-year-months { display:none; padding:8px 4px 0; }
.prf-dd-year-months.open { display:block; animation:prf-fadeInUp .2s ease; }

/* ── Period selector (list view) ────── */
.dsh-filter-select { appearance:none!important; -webkit-appearance:none!important; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2360a5fa' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 8px center; padding-right:28px!important; }
.dsh-filter-select option { background:#0f1a2e!important; color:var(--text-primary)!important; }
.dsh-filter-select option:checked { background-color:#3b82f6!important; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<?php
// ══════════════════════════════════════════════════════════════════
// DETAIL VIEW
// ══════════════════════════════════════════════════════════════════
if ($employee_id):
    $profile = getEmployeeProfile($conn, $employee_id);
    if (!$profile) {
        echo '<div class="content active" style="padding:40px;text-align:center;color:var(--text-muted);">Employee not found.</div>';
        exit();
    }

    // ── Available dates ──────────────────────────────────────────────
    $availStmt = $conn->prepare("
        SELECT DISTINCT YEAR(date_recorded) AS yr, MONTH(date_recorded) AS mo, DATE(date_recorded) AS day_val
        FROM kpi_score WHERE staff_id=? ORDER BY day_val
    ");
    $availStmt->bind_param("i", $employee_id);
    $availStmt->execute();
    $availRows = $availStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $availDaysFlat  = array_column($availRows, 'day_val');
    $availMonthsMap = [];
    $availYears     = [];
    foreach ($availRows as $r) {
        $availMonthsMap[$r['yr']][(int)$r['mo']] = true;
        $availYears[$r['yr']] = true;
    }
    $availYears = array_keys($availYears);
    sort($availYears);

    $empDateMin = count($availDaysFlat) ? min($availDaysFlat) : date('Y-m-d');
    $empDateMax = count($availDaysFlat) ? max($availDaysFlat) : date('Y-m-d');

    // ── Parse view mode ──────────────────────────────────────────────
    $prf_view  = $_GET['prf_view']  ?? 'overall';
    $prf_year  = isset($_GET['prf_year'])  ? (int)$_GET['prf_year']  : null;
    $prf_month = isset($_GET['prf_month']) ? (int)$_GET['prf_month'] : null;

    if ($prf_view === 'custom') {
        $date_from = $_GET['prf_date_from'] ?? $empDateMin;
        $date_to   = $_GET['prf_date_to']   ?? $empDateMax;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) $date_from = $empDateMin;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to))   $date_to   = $empDateMax;
        if ($date_from > $date_to) [$date_from, $date_to] = [$date_to, $date_from];
        $sel_label = date('d M Y', strtotime($date_from)) . ' – ' . date('d M Y', strtotime($date_to));
        $cal_year  = (int)date('Y', strtotime($date_to));
    } elseif ($prf_view === 'month' && $prf_year && $prf_month) {
        $date_from = sprintf('%04d-%02d-01', $prf_year, $prf_month);
        $date_to   = date('Y-m-t', strtotime($date_from));
        $sel_label = date('F Y', strtotime($date_from));
        $cal_year  = $prf_year;
    } elseif ($prf_view === 'year' && $prf_year) {
        $date_from = "{$prf_year}-01-01";
        $date_to   = "{$prf_year}-12-31";
        $sel_label = "Full Year {$prf_year}";
        $cal_year  = $prf_year;
    } else {
        $prf_view  = 'overall';
        $period_stmt = $conn->prepare("SELECT year FROM evaluation_period WHERE period_id=?");
        $period_stmt->bind_param("i", $period_id);
        $period_stmt->execute();
        $period_row = $period_stmt->get_result()->fetch_assoc();
        $sel_label  = 'Year ' . ($period_row['year'] ?? date('Y'));
        $date_from  = $empDateMin;
        $date_to    = $empDateMax;
        $cal_year   = count($availYears) ? end($availYears) : (int)date('Y');
    }

    // ── Compute KPI ──────────────────────────────────────────────────
    if ($prf_view === 'overall' || empty($availDaysFlat)) {
        $kpi = KPICalculator::calculateKPI($conn, $employee_id, $period_id);
    } else {
        $kpi = prfCalcByRange($conn, $employee_id, $date_from, $date_to);
        if ($kpi['overall'] === 0) $kpi = KPICalculator::calculateKPI($conn, $employee_id, $period_id);
    }

    $score      = $kpi['overall'];
    $rating     = $kpi['rating'];
    $scoreColor = prfScoreColor($score);
    $ringOffset = 245 - round(($score / 5) * 245, 1);
    $s1Pct      = $kpi['section1'] > 0 ? round(($kpi['section1'] / 1.25) * 100, 1) : 0;
    $s2Pct      = $kpi['section2'] > 0 ? round(($kpi['section2'] / 3.75) * 100, 1) : 0;
    $s1RingOffset = round(163 - (163 * min($s1Pct, 100) / 100), 1);
    $s2RingOffset = round(163 - (163 * min($s2Pct, 100) / 100), 1);

    // ── Group scores ─────────────────────────────────────────────────
    $groups = prfGetGroupsByRange($conn, $employee_id, $date_from, $date_to);
    if (empty($groups)) {
        $gr = getKPIGroupScores($conn, $employee_id, $period_id);
        while ($g = $gr->fetch_assoc()) $groups[] = $g;
    }

    $risk      = predictPerformanceRisk($conn, $employee_id, $period_id);
    $riskChg   = $risk['change'] ?? 0;
    $comments  = getSupervisorComments($conn, $employee_id, $period_id);
    $training  = getTrainingRecommendations($conn, $employee_id, $period_id);
    $forecast  = max(1, min(5, $score + ($riskChg * 0.5)));
    $kpi_target= isset($_GET['kpi_target']) ? (float)$_GET['kpi_target'] : null;
    $avatar    = buildAvatarUrl($profile['name']);
    $empIdBase = (int)$employee_id;
    $baseUrl   = "index.php?page=profiles&emp_id={$empIdBase}";

    // ── Trend data ───────────────────────────────────────────────────
    $trendStmt = $conn->prepare("
        SELECT DATE_FORMAT(date_recorded,'%b %Y') AS month_label, AVG(ks.score) AS avg_score
        FROM kpi_score ks
        WHERE ks.staff_id=? AND date_recorded BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(date_recorded,'%Y-%m')
        ORDER BY DATE_FORMAT(date_recorded,'%Y-%m')
    ");
    $trendStmt->bind_param("iss", $employee_id, $date_from, $date_to);
    $trendStmt->execute();
    $trendRows   = $trendStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $trendLabels = array_column($trendRows, 'month_label');
    $trendScores = array_map(fn($r) => round((float)$r['avg_score'], 2), $trendRows);
    $trendDataJSON = json_encode(['labels'=>$trendLabels,'scores'=>$trendScores]);
?>

<div class="content active prf-page">

  <a href="index.php?page=profiles&period=<?= (int)$period_id ?>" class="prf-back">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    Back to Profiles
  </a>

  <!-- Hero -->
  <div class="prf-hero">
    <div class="prf-hero-inner">
      <div style="display:flex;align-items:center;gap:16px;">
        <div style="position:relative;">
          <img src="<?= $avatar ?>" alt="<?= safe($profile['name']) ?>" id="prf-avatar-preview" class="prf-hero-avatar"
               onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($profile['name']) ?>&background=1a2540&color=60a5fa&size=76'">
          <button id="prf-avatar-btn" class="prf-avatar-upload-btn" type="button" title="Update Profile Photo">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
          </button>
        </div>
        <input type="file" id="prf-avatar-input" accept="image/jpeg,image/png,image/gif,image/webp">
        <div>
          <div class="prf-hero-name"><?= safe($profile['name']) ?></div>
          <div class="prf-hero-sub"><?= safe($profile['role']) ?> &bull; <?= safe($profile['department']) ?></div>
          <div class="prf-hero-id">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 3H8l-2 4h12z"/></svg>
            <?= safe($profile['staff_id']) ?>
          </div>
        </div>
      </div>

      <div class="prf-hero-stats">
        <?php
          $riskClr  = match($risk['risk_level']) { 'Critical'=>'#ef4444','High'=>'#f97316','Medium'=>'#f59e0b', default=>'#22c55e' };
          $trendClr = $risk['trend']==='Improving' ? '#22c55e' : ($risk['trend']==='Declining' ? '#ef4444' : '#f59e0b');
          $trendIcon= $risk['trend']==='Improving' ? '↑' : ($risk['trend']==='Declining' ? '↓' : '→');
          $changeClr= $riskChg > 0 ? '#22c55e' : '#ef4444';
        ?>
        <div class="prf-stat-item" data-ptip="Based on period comparison">
          <div class="prf-stat-lbl">Risk Level</div>
          <div class="prf-stat-val" style="color:<?= $riskClr ?>"><?= safe($risk['risk_level']) ?></div>
        </div>
        <div class="prf-stat-item">
          <div class="prf-stat-lbl">Trend</div>
          <div class="prf-stat-val" style="display:flex;align-items:center;gap:4px;">
            <span style="color:<?= $trendClr ?>;font-size:16px;"><?= $trendIcon ?></span>
            <span><?= safe($risk['trend']) ?></span>
          </div>
        </div>
        <div class="prf-stat-item">
          <div class="prf-stat-lbl">Period Change</div>
          <div class="prf-stat-val" style="color:<?= $changeClr ?>"><?= ($riskChg>0?'+':'').number_format($riskChg,2) ?></div>
        </div>
      </div>

      <div class="prf-hero-ring-block">
        <div class="prf-ring-container">
          <svg class="prf-ring-svg" width="96" height="96" viewBox="0 0 96 96">
            <circle class="prf-ring-bg" cx="48" cy="48" r="39"/>
            <circle class="prf-ring-fg" id="heroRingFg" cx="48" cy="48" r="39" stroke="<?= $scoreColor ?>" stroke-dashoffset="245"/>
          </svg>
          <div class="prf-ring-text">
            <div class="prf-ring-num" id="heroRingVal">0</div>
            <div class="prf-ring-max">/ 5.00</div>
          </div>
        </div>
        <span class="prf-rating-badge" style="background:<?= $scoreColor ?>22;border:1px solid <?= $scoreColor ?>44;color:<?= $scoreColor ?>">
          <?= safe($rating) ?>
        </span>
      </div>
    </div>
  </div>

  <!-- Period Dropdown -->
  <div style="display:flex;align-items:center;gap:10px;">
    <div class="prf-period-wrap">
      <button class="prf-period-btn" id="prfPeriodBtn" type="button">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <span id="prfPeriodLabel"><?= safe($sel_label) ?></span>
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </button>
      <div class="prf-period-dropdown" id="prfPeriodDropdown">
        <div class="prf-dd-head">
          <div class="prf-dd-head-icon">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          </div>
          <div>
            <div class="prf-dd-head-title">Performance Period</div>
            <div class="prf-dd-head-sub">Filter KPI data by date range</div>
          </div>
        </div>
        <div class="prf-dd-body">
          <div>
            <div class="prf-dd-section-lbl">Quick Select</div>
            <div class="prf-qs-list">
              <button class="prf-qs-btn <?= $prf_view==='overall'?'active':'' ?>" onclick="window.location='<?= $baseUrl ?>&prf_view=overall'" type="button">
                <div>
                  <div>All Available Data</div>
                  <div class="prf-qs-sub"><?= safe(date('d M Y',strtotime($empDateMin))) ?> – <?= safe(date('d M Y',strtotime($empDateMax))) ?></div>
                </div>
                <div class="prf-qs-check"><svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>
              </button>
            </div>
          </div>
          <div class="prf-dd-divider"></div>
          <div>
            <div class="prf-dd-section-lbl">Monthly</div>
            <?php
            $MONTH_ABBR = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            foreach (array_reverse($availYears) as $yr):
              $isOpenYear = ($prf_view==='month' && $prf_year===$yr) || ($prf_view==='custom' && ($cal_year??null)===$yr);
            ?>
            <div class="prf-dd-year-block">
              <button class="prf-dd-year-toggle <?= $isOpenYear?'open':'' ?>" onclick="prfToggleYear(this)" type="button">
                <span><?= $yr ?></span>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
              </button>
              <div class="prf-dd-year-months <?= $isOpenYear?'open':'' ?>">
                <div class="prf-dd-month-grid">
                  <?php for ($mo=1; $mo<=12; $mo++):
                    $hasM  = !empty($availMonthsMap[$yr][$mo]);
                    $isSel = ($prf_view==='month' && $prf_year===$yr && $prf_month===$mo);
                    $cls   = 'prf-dd-month-pill'.($hasM?' has-data':'').($isSel?' active':'');
                    $click = $hasM ? "onclick=\"window.location='{$baseUrl}&prf_view=month&prf_year={$yr}&prf_month={$mo}'\"" : '';
                  ?>
                  <div class="<?= $cls ?>" <?= $click ?>><?= $MONTH_ABBR[$mo-1] ?></div>
                  <?php endfor; ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="prf-dd-divider"></div>
          <div>
            <div class="prf-dd-section-lbl">Annual</div>
            <div class="prf-qs-list">
              <?php foreach (array_reverse($availYears) as $yr):
                $isSel = ($prf_view==='year' && $prf_year===$yr); ?>
              <button class="prf-qs-btn <?= $isSel?'active':'' ?>" onclick="window.location='<?= $baseUrl ?>&prf_view=year&prf_year=<?= $yr ?>'" type="button">
                <div>
                  <div>Year <?= $yr ?></div>
                  <div class="prf-qs-sub"><?= $yr ?>-01-01 – <?= $yr ?>-12-31</div>
                </div>
                <div class="prf-qs-check"><svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>
              </button>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="prf-dd-divider"></div>
          <div>
            <div class="prf-dd-section-lbl">Custom Date Range</div>
            <div class="prf-dd-date-row">
              <div class="prf-dd-date-wrapper">
                <input type="date" id="prfDateFrom" class="prf-dd-date-input" value="<?= safe($date_from) ?>" min="2020-01-01" max="2025-12-31">
                <span class="prf-dd-calendar-icon">📅</span>
              </div>
              <span class="prf-dd-sep">–</span>
              <div class="prf-dd-date-wrapper">
                <input type="date" id="prfDateTo" class="prf-dd-date-input" value="<?= safe($date_to) ?>" min="2020-01-01" max="2025-12-31">
                <span class="prf-dd-calendar-icon">📅</span>
              </div>
            </div>
            <button id="prfApplyBtn" class="prf-dd-apply" type="button">Apply Range</button>
          </div>
        </div>
      </div>
    </div>
    <span style="font-size:11px;color:var(--text-muted);">KPI data for: <strong style="color:var(--text-secondary);"><?= safe($sel_label) ?></strong></span>
  </div>

  <!-- Section breakdown -->
  <div class="prf-grid2">

    <!-- Core Competencies -->
    <div class="prf-card prf-reveal">
      <div class="prf-sh">
        <div class="prf-sh-icon" style="background:rgba(59,130,246,.1);color:#3b82f6;">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        </div>
        <div>
          <div class="prf-sh-title">Core Competencies</div>
          <div class="prf-sh-sub">Section 1 &bull; 25% weight</div>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:16px;">
        <div class="prf-mini-ring-wrap">
          <svg class="prf-mini-ring-svg" width="64" height="64" viewBox="0 0 64 64">
            <circle class="prf-mini-ring-bg" cx="32" cy="32" r="26"/>
            <circle class="prf-mini-ring-fg" id="s1Ring" cx="32" cy="32" r="26" stroke="#3b82f6" stroke-dashoffset="163"/>
          </svg>
          <div class="prf-mini-ring-text" style="color:#3b82f6;"><?= (int)$s1Pct ?>%</div>
        </div>
        <div style="flex:1;">
          <div class="prf-prog-label">
            <span class="prf-prog-name">Score</span>
            <span class="prf-prog-val" style="color:#3b82f6;"><?= number_format($kpi['section1'],2) ?> / 1.25</span>
          </div>
          <div class="prf-prog-track"><div class="prf-prog-fill" style="width:<?= min($s1Pct,100) ?>%;background:linear-gradient(90deg,#3b82f6,#60a5fa);animation-delay:.3s;"></div></div>
          <div style="font-size:11px;color:var(--text-muted);margin-top:6px;"><?= $s1Pct>=80?'Strong foundation':($s1Pct>=60?'Meets standard':'Needs improvement') ?></div>
        </div>
      </div>
    </div>

    <!-- KPI Achievement -->
    <div class="prf-card prf-reveal">
      <div class="prf-sh">
        <div class="prf-sh-icon" style="background:rgba(6,182,212,.1);color:#06b6d4;">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
        <div>
          <div class="prf-sh-title">KPI Achievement</div>
          <div class="prf-sh-sub">Section 2 &bull; 75% weight</div>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:16px;">
        <div class="prf-mini-ring-wrap">
          <svg class="prf-mini-ring-svg" width="64" height="64" viewBox="0 0 64 64">
            <circle class="prf-mini-ring-bg" cx="32" cy="32" r="26"/>
            <circle class="prf-mini-ring-fg" id="s2Ring" cx="32" cy="32" r="26" stroke="#06b6d4" stroke-dashoffset="163"/>
          </svg>
          <div class="prf-mini-ring-text" style="color:#06b6d4;"><?= (int)$s2Pct ?>%</div>
        </div>
        <div style="flex:1;">
          <div class="prf-prog-label">
            <span class="prf-prog-name">Score</span>
            <span class="prf-prog-val" style="color:#06b6d4;"><?= number_format($kpi['section2'],2) ?> / 3.75</span>
          </div>
          <div class="prf-prog-track"><div class="prf-prog-fill" style="width:<?= min($s2Pct,100) ?>%;background:linear-gradient(90deg,#06b6d4,#22d3ee);animation-delay:.35s;"></div></div>
          <div style="font-size:11px;color:var(--text-muted);margin-top:6px;"><?= $s2Pct>=80?'Exceeds targets':($s2Pct>=60?'On track':'Below target') ?></div>
        </div>
      </div>
    </div>

    <!-- KPI Group Breakdown -->
    <div class="prf-card prf-full prf-reveal">
      <div class="prf-sh">
        <div class="prf-sh-icon" style="background:rgba(139,92,246,.1);color:#a78bfa;">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
        </div>
        <div>
          <div class="prf-sh-title">Performance by KPI Group</div>
          <div class="prf-sh-sub"><?= count($groups) ?> groups &bull; <?= safe($sel_label) ?></div>
        </div>
      </div>
      <?php if (empty($groups)): ?>
        <div style="text-align:center;padding:24px;color:var(--text-muted);font-size:13px;">No KPI data for selected period.</div>
      <?php else: foreach ($groups as $i => $g):
        $gc  = prfScoreColor((float)$g['avg_score']);
        $gp  = min(((float)$g['avg_score'] / 5) * 100, 100);
        $del = 0.25 + $i * 0.06; ?>
      <div class="prf-group-row" style="animation-delay:<?= $del ?>s;">
        <div class="prf-group-name"><?= safe($g['kpi_group']) ?></div>
        <div class="prf-group-bar-track"><div class="prf-group-bar-fill" style="width:<?= $gp ?>%;background:<?= $gc ?>;animation-delay:<?= $del ?>s;"></div></div>
        <div class="prf-group-badge" style="background:<?= $gc ?>22;color:<?= $gc ?>;border:1px solid <?= $gc ?>44;"><?= number_format((float)$g['avg_score'],1) ?></div>
        <div class="prf-group-count"><?= (int)$g['count'] ?> items</div>
      </div>
      <?php endforeach; endif; ?>
    </div>

    <!-- Performance Trend Chart -->
    <div class="prf-card prf-full prf-reveal prf-trend-card">
      <div class="prf-sh">
        <div class="prf-sh-icon" style="background:linear-gradient(135deg,rgba(59,130,246,.15),rgba(34,197,94,.1));color:#3b82f6;box-shadow:0 0 12px rgba(59,130,246,.2);animation:prf-chart-icon-pulse 2s ease-in-out infinite;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
        </div>
        <div>
          <div class="prf-sh-title">Performance Trend Over Time</div>
          <div class="prf-sh-sub">KPI score progression across evaluation periods</div>
        </div>
      </div>
      <div style="position:relative;height:320px;margin-top:16px;border-radius:10px;padding:8px;background:rgba(59,130,246,.02);border:1px solid rgba(59,130,246,.08);">
        <canvas id="prfTrendChart"></canvas>
      </div>
    </div>

    <!-- Supervisor Comments -->
    <div class="prf-card prf-reveal prf-comment-card">
      <div class="prf-sh">
        <div class="prf-sh-icon" style="background:linear-gradient(135deg,rgba(168,85,247,.15),rgba(236,72,153,.15));color:#a855f7;box-shadow:0 0 12px rgba(168,85,247,.2);">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div>
          <div class="prf-sh-title">Supervisor Comments</div>
          <div class="prf-sh-sub">Latest evaluation feedback</div>
        </div>
      </div>
      <div class="prf-comment-box prf-comment-enhanced">
        <div style="display:flex;gap:12px;align-items:flex-start;">
          <div style="width:4px;background:linear-gradient(180deg,#a855f7,#ec4899);border-radius:2px;flex-shrink:0;min-height:40px;"></div>
          <div style="font-style:italic;color:var(--text-primary);line-height:1.6;font-size:12.5px;">
            <?= nl2br(safe($comments['supervisor_comments'] ?? 'No comments recorded for this period.')) ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Training & Forecast -->
    <div class="prf-card prf-reveal prf-forecast-card" style="background:linear-gradient(135deg,rgba(34,197,94,.04),rgba(59,130,246,.04));border:1px solid rgba(34,197,94,.1);">
      <div class="prf-sh">
        <div class="prf-sh-icon" style="background:linear-gradient(135deg,rgba(34,197,94,.15),rgba(16,185,129,.15));color:#10b981;box-shadow:0 0 12px rgba(16,185,129,.2);">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/><path d="M9 14l2 2 4-4"/></svg>
        </div>
        <div>
          <div class="prf-sh-title">Goal-Setting & Forecast</div>
          <div class="prf-sh-sub">Recommendations &amp; predicted KPI</div>
        </div>
      </div>
      <div class="prf-training-item prf-training-enhanced">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.2"><path d="M15 13l-3 3m0 0l-3-3m3 3V8m0 0a5 5 0 110 10 5 5 0 010-10z"/></svg>
          <div class="prf-training-lbl">RECOMMENDED TRAINING</div>
        </div>
        <div class="prf-training-text"><?= nl2br(safe($training ?: 'No specific training required.')) ?></div>
      </div>
      <div class="prf-forecast prf-forecast-enhanced">
        <div class="prf-forecast-icon prf-forecast-icon-animated">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><path d="M23 6l-9.5 9.5-5-5L1 18M17 6h6v6"/></svg>
        </div>
        <div style="flex:1;">
          <div class="prf-forecast-lbl">Predicted Performance</div>
          <div class="prf-forecast-vals">
            <span class="prf-forecast-val"><?= number_format($forecast,2) ?></span>
            <span class="prf-forecast-rating" style="color:<?= KPICalculator::getPerformanceColor($forecast) ?>;"><?= KPICalculator::getRatingLabel($forecast) ?></span>
          </div>
          <div class="prf-forecast-sub">
            <?php
              $trendPts = $risk['trend']==='Improving' ? '23 6 13.5 15.5 8.5 10.5 1 18' : ($risk['trend']==='Declining' ? '23 18 13.5 8.5 8.5 13.5 1 6' : '12 5 12 19');
              $trendPts2= $risk['trend']==='Improving' ? '17 6 23 6 23 12' : ($risk['trend']==='Declining' ? '17 18 23 18 23 12' : '');
            ?>
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="<?= $trendClr ?>" stroke-width="2" style="display:inline;margin-right:4px;">
              <polyline points="<?= $trendPts ?>"/><?= $trendPts2 ? "<polyline points='{$trendPts2}'/>" : '' ?>
            </svg>
            <?= safe($risk['trend']) ?> trajectory &bull; based on current data
          </div>
        </div>
      </div>
    </div>

  </div><!-- /prf-grid2 -->

  <!-- KPI Target Likelihood -->
  <div class="prf-card prf-full prf-reveal" style="border-color:rgba(139,92,246,.2);background:linear-gradient(135deg,rgba(139,92,246,.04),rgba(59,130,246,.04));">
    <div class="prf-sh">
      <div class="prf-sh-icon" style="background:rgba(139,92,246,.15);color:#a78bfa;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
      </div>
      <div>
        <div class="prf-sh-title">KPI Target Likelihood</div>
        <div class="prf-sh-sub">Set a target KPI score to see the probability of achieving it</div>
      </div>
    </div>
    <form method="GET" action="" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:18px;">
      <input type="hidden" name="page" value="profiles">
      <input type="hidden" name="emp_id" value="<?= $empIdBase ?>">
      <input type="hidden" name="prf_view" value="<?= safe($prf_view) ?>">
      <?php if($prf_year): ?><input type="hidden" name="prf_year" value="<?= $prf_year ?>"><?php endif; ?>
      <?php if($prf_month): ?><input type="hidden" name="prf_month" value="<?= $prf_month ?>"><?php endif; ?>
      <label style="font-size:12px;color:var(--text-muted);font-weight:600;">Target KPI Score (1.0 – 5.0):</label>
      <input type="number" name="kpi_target" min="1.0" max="5.0" step="0.1"
             value="<?= $kpi_target ? number_format($kpi_target,1) : '' ?>" placeholder="e.g. 2.5"
             style="width:100px;padding:8px 10px;background:var(--bg-input);border:1px solid var(--border);border-radius:8px;color:var(--text-primary);font-size:13px;outline:none;">
      <button type="submit" style="padding:8px 16px;background:linear-gradient(135deg,#7c3aed,#a78bfa);border:none;border-radius:8px;color:#fff;font-size:12px;font-weight:700;cursor:pointer;">Calculate</button>
      <?php if($kpi_target): ?>
      <a href="index.php?page=profiles&emp_id=<?= $empIdBase ?>" style="font-size:11px;color:var(--text-muted);text-decoration:none;padding:8px 10px;border:1px solid var(--border);border-radius:8px;">Clear</a>
      <?php endif; ?>
    </form>

    <?php if ($kpi_target):
      $likelihood = $likelihood_label = null;
      $likelihood_color = '#4d5f80';
      $months_needed = null;
      if (count($trendScores) >= 2) {
          $improvements = [];
          for ($i = 1; $i < count($trendScores); $i++) $improvements[] = $trendScores[$i] - $trendScores[$i-1];
          $avg_improvement = array_sum($improvements) / count($improvements);
          $gap = $kpi_target - $score;
          $consistency = count($improvements) > 0 ? count(array_filter($improvements, fn($x) => $x > 0)) / count($improvements) : 0;
          if ($gap <= 0) {
              $likelihood = 95.0; $months_needed = 0;
          } elseif ($avg_improvement <= 0) {
              $likelihood = max(5, round(($score / $kpi_target) * 15, 2));
          } else {
              $months_needed = ceil($gap / $avg_improvement);
              $likelihood = max(5, min(95, round($consistency * 100 - min(50, $gap * 20), 2)));
          }
          if     ($likelihood >= 75) { $likelihood_label = 'High Likelihood';     $likelihood_color = '#22c55e'; }
          elseif ($likelihood >= 50) { $likelihood_label = 'Moderate Likelihood'; $likelihood_color = '#f59e0b'; }
          elseif ($likelihood >= 25) { $likelihood_label = 'Low Likelihood';      $likelihood_color = '#f97316'; }
          else                       { $likelihood_label = 'Very Unlikely';        $likelihood_color = '#ef4444'; }
      }
    ?>
    <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:stretch;">
      <div style="flex:1;min-width:220px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:20px;display:flex;flex-direction:column;align-items:center;gap:10px;">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.7px;color:var(--text-muted);font-weight:700;">Likelihood of Achieving</div>
        <div style="font-family:'Sora',sans-serif;font-size:13px;font-weight:700;color:var(--text-secondary);">
          <?= number_format($score,2) ?> → <span style="color:<?= $likelihood_color ?>"><?= number_format($kpi_target,1) ?></span>
        </div>
        <div style="font-family:'Sora',sans-serif;font-size:48px;font-weight:800;color:<?= $likelihood_color ?>;line-height:1;">
          <?= $likelihood !== null ? number_format($likelihood,2).'%' : 'N/A' ?>
        </div>
        <div style="padding:5px 14px;border-radius:20px;font-size:11px;font-weight:700;background:<?= $likelihood_color ?>22;border:1px solid <?= $likelihood_color ?>44;color:<?= $likelihood_color ?>">
          <?= safe($likelihood_label) ?>
        </div>
        <div style="width:100%;height:8px;background:rgba(255,255,255,.06);border-radius:4px;overflow:hidden;margin-top:4px;">
          <div style="height:100%;width:<?= min($likelihood??0,100) ?>%;background:<?= $likelihood_color ?>;border-radius:4px;transition:width 1s ease;"></div>
        </div>
      </div>
      <div style="flex:2;min-width:260px;display:flex;flex-direction:column;gap:10px;">
        <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:14px 16px;">
          <div style="font-size:10px;text-transform:uppercase;letter-spacing:.7px;color:var(--text-muted);font-weight:700;margin-bottom:8px;">Analysis Summary</div>
          <div style="display:flex;flex-direction:column;gap:7px;">
            <?php
              $rows = [
                ['Current KPI',     number_format($score,2),      prfScoreColor($score)],
                ['Target KPI',      number_format($kpi_target,1), $likelihood_color],
                ['Gap to Close',    $kpi_target>$score ? '+'.number_format($kpi_target-$score,2) : 'Already achieved', $kpi_target>$score?'#f59e0b':'#22c55e'],
              ];
              if ($months_needed !== null && $months_needed > 0) $rows[] = ['Est. Months Needed', $months_needed.' month'.($months_needed>1?'s':''), 'var(--text-primary)'];
              elseif ($months_needed === 0) $rows[] = ['Status', 'Target already met ✓', '#22c55e'];
              $rows[] = ['Data Points Used', count($trendScores).' months', 'var(--text-primary)'];
              foreach ($rows as $row): ?>
            <div style="display:flex;justify-content:space-between;font-size:12px;">
              <span style="color:var(--text-muted)"><?= $row[0] ?></span>
              <span style="font-weight:700;color:<?= $row[2] ?>"><?= $row[1] ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php if (count($trendScores) < 2): ?>
        <div style="background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.2);border-radius:10px;padding:12px 14px;font-size:12px;color:#f59e0b;">
          ⚠ Not enough historical data for accurate prediction. At least 2 months of data required.
        </div>
        <?php else: ?>
        <div style="background:rgba(59,130,246,.06);border:1px solid rgba(59,130,246,.15);border-radius:10px;padding:12px 14px;font-size:12px;color:var(--text-secondary);line-height:1.6;">
          📊 Based on <strong><?= count($trendScores) ?> months</strong> of historical KPI data.
          <?php if(isset($avg_improvement) && $avg_improvement > 0): ?>
            Average monthly improvement of <strong style="color:#22c55e;">+<?= number_format($avg_improvement,3) ?></strong> points detected.
          <?php elseif(isset($avg_improvement) && $avg_improvement < 0): ?>
            Average monthly decline of <strong style="color:#ef4444;"><?= number_format($avg_improvement,3) ?></strong> points detected — improvement plan recommended.
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:24px;color:var(--text-muted);">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 10px;display:block;opacity:.4;"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
      <div style="font-size:13px;font-weight:600;margin-bottom:4px;">No target set yet</div>
      <div style="font-size:12px;">Enter a target KPI score above to calculate the likelihood of achievement.</div>
    </div>
    <?php endif; ?>
  </div>

</div><!-- /prf-page -->

<script>
(function(){
  var TARGET = <?= $score ?>, OFFSET = <?= $ringOffset ?>, S1OFF = <?= $s1RingOffset ?>, S2OFF = <?= $s2RingOffset ?>;

  function countUp(el, target, dur) {
    var start = null;
    function step(ts) {
      if (!start) start = ts;
      var e = 1 - Math.pow(1 - Math.min((ts-start)/dur,1), 3);
      el.textContent = (e * target).toFixed(2);
      if (e < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  requestAnimationFrame(function(){
    setTimeout(function(){
      var heroFg = document.getElementById('heroRingFg');
      var heroVal= document.getElementById('heroRingVal');
      if (heroFg)  heroFg.style.strokeDashoffset = OFFSET;
      if (heroVal) countUp(heroVal, TARGET, 1200);
      var s1 = document.getElementById('s1Ring');
      var s2 = document.getElementById('s2Ring');
      if (s1) s1.style.strokeDashoffset = S1OFF;
      if (s2) s2.style.strokeDashoffset = S2OFF;
    }, 250);
  });
  // ***************************
  // Title: Scroll Reveal Animation using IntersectionObserver
  // Author: wes337
  // Date: 2022
  // Type: Source Code
  // Availability: https://github.com/wes337/io-animate
  // ***************************
  // Scroll reveal
  var io = new IntersectionObserver(function(en){
    en.forEach(function(e){ if(e.isIntersecting){ e.target.classList.add('visible'); io.unobserve(e.target); }});
  },{threshold:.08});
  document.querySelectorAll('.prf-reveal').forEach(function(el){ io.observe(el); });

  // Period dropdown
  (function(){
    var btn = document.getElementById('prfPeriodBtn');
    var dd  = document.getElementById('prfPeriodDropdown');
    var EMP_ID = <?= $empIdBase ?>;
    if (!btn || !dd) return;
    document.body.appendChild(dd);

    function pos() {
      var r = btn.getBoundingClientRect(), dropH = dd.scrollHeight||400;
      var top = r.bottom+8, left = r.left;
      if (top + Math.min(dropH,window.innerHeight*.8) > window.innerHeight-16) top = r.top-dropH-8;
      if (left+340 > window.innerWidth-8) left = window.innerWidth-340-8;
      dd.style.top = Math.max(8,top)+'px'; dd.style.left = Math.max(8,left)+'px';
    }
    btn.addEventListener('click', function(e){ e.stopPropagation(); dd.classList.toggle('open'); if(dd.classList.contains('open')) setTimeout(pos,0); });
    window.addEventListener('scroll', function(){ if(dd.classList.contains('open')) pos(); }, true);
    window.addEventListener('resize', function(){ if(dd.classList.contains('open')) pos(); });
    dd.addEventListener('click', function(e){ e.stopPropagation(); });
    document.addEventListener('click', function(){ dd.classList.remove('open'); });

    var df = document.getElementById('prfDateFrom');
    var dt = document.getElementById('prfDateTo');
    var ab = document.getElementById('prfApplyBtn');
    if (ab && df && dt) {
      ab.addEventListener('click', function(){
        if (!df.value || !dt.value) return;
        var from = df.value<=dt.value ? df.value : dt.value;
        var to   = df.value<=dt.value ? dt.value : df.value;
        window.location.href = 'index.php?page=profiles&emp_id='+EMP_ID+'&prf_view=custom&prf_date_from='+from+'&prf_date_to='+to;
      });
    }
  })();

  window.prfToggleYear = function(el) {
    var months = el.nextElementSibling, isOpen = months.classList.contains('open');
    document.querySelectorAll('.prf-dd-year-months').forEach(function(m){ m.classList.remove('open'); });
    document.querySelectorAll('.prf-dd-year-toggle').forEach(function(t){ t.classList.remove('open'); });
    if (!isOpen) { months.classList.add('open'); el.classList.add('open'); }
  };

  // Avatar upload
  (function(){
    var btn = document.getElementById('prf-avatar-btn');
    var inp = document.getElementById('prf-avatar-input');
    var pre = document.getElementById('prf-avatar-preview');
    if (!btn || !inp) return;
    btn.addEventListener('click', function(e){ e.preventDefault(); inp.click(); });
    inp.addEventListener('change', function(){
      var file = this.files[0]; if (!file) return;
      var reader = new FileReader();
      reader.onload = function(e){ pre.src = e.target.result; uploadAvatar(file); };
      reader.readAsDataURL(file);
    });
    function uploadAvatar(file) {
      var fd = new FormData();
      fd.append('action','upload_profile_picture');
      fd.append('emp_id',<?= $empIdBase ?>);
      fd.append('file',file);
      fetch('index.php?page=profiles',{method:'POST',body:fd})
        .then(function(r){ return r.json(); })
        .catch(function(e){ console.error('Upload error:',e); });
    }
  })();

  // Trend chart
  (function(){
    var trendData = <?= $trendDataJSON ?>;
    if (!trendData || !trendData.labels || !trendData.labels.length) return;
    var ctx = document.getElementById('prfTrendChart');
    if (!ctx) return;
    var gradient = ctx.getContext('2d').createLinearGradient(0,0,0,300);
    gradient.addColorStop(0,'rgba(59,130,246,0.15)');
    gradient.addColorStop(1,'rgba(59,130,246,0)');
    new Chart(ctx, {
      type:'line',
      data:{
        labels:trendData.labels,
        datasets:[{
          label:'KPI Score', data:trendData.scores,
          borderColor:'#3b82f6', backgroundColor:gradient,
          borderWidth:3, fill:true, tension:0.4,
          pointRadius:6, pointBackgroundColor:'#3b82f6', pointBorderColor:'#fff', pointBorderWidth:2,
          pointHoverRadius:10, pointHoverBackgroundColor:'#1e40af', pointHoverBorderWidth:3
        }]
      },
      options:{
        responsive:true, maintainAspectRatio:false,
        animation:{ duration:1200, easing:'easeInOutQuart', delay:function(c){ return c.type==='data'&&c.mode==='default' ? c.dataIndex*80 : 0; } },
        plugins:{
          legend:{ display:true, labels:{color:'#9ca3af',font:{size:12,weight:'600'},usePointStyle:true,padding:16} },
          tooltip:{
            backgroundColor:'rgba(17,24,39,0.95)', titleColor:'#60a5fa', titleFont:{size:13,weight:'bold'},
            bodyColor:'#f3f4f6', bodyFont:{size:12}, borderColor:'#3b82f6', borderWidth:2,
            padding:14, cornerRadius:8, caretPadding:12,
            callbacks:{
              label:function(c){ var s=c.parsed.y.toFixed(2); var r=s>=4.5?'Excellent':s>=3.5?'Good':s>=2.5?'Satisfactory':s>=1.5?'Poor':'Very Poor'; return 'KPI Score: '+s+' / 5.00 ('+r+')'; },
              afterLabel:function(c){ if(c.dataIndex>0){ var ch=(c.parsed.y-trendData.scores[c.dataIndex-1]).toFixed(2); return (ch>0?'↑':ch<0?'↓':'→')+' Change: '+(ch>0?'+':'')+ch; } return ''; }
            }
          }
        },
        interaction:{intersect:false,mode:'index'},
        scales:{
          y:{ beginAtZero:true, max:5, ticks:{color:'#9ca3af',font:{size:11},stepSize:0.5,padding:8}, grid:{color:'rgba(156,163,175,0.08)',drawBorder:false} },
          x:{ ticks:{color:'#9ca3af',font:{size:11,weight:'500'},padding:8}, grid:{display:false,drawBorder:false} }
        }
      }
    });
  })();
})();
</script>

<?php

// ══════════════════════════════════════════════════════════════════
// LIST VIEW
// ══════════════════════════════════════════════════════════════════
else:
    $all_periods = $conn->query("SELECT period_id, year FROM evaluation_period ORDER BY year DESC")->fetch_all(MYSQLI_ASSOC);

    $period_label_list = 'Current Period';
    foreach ($all_periods as $p) {
        if ((int)$p['period_id'] === (int)$period_id) { $period_label_list = 'Year '.$p['year']; break; }
    }

    $all_emps = getAllEmployeesSummary($conn, $period_id);
    $profiles_data = [];
    while ($emp = $all_emps->fetch_assoc()) {
        $chk = $conn->prepare("SELECT COUNT(*) as cnt FROM kpi_score ks JOIN evaluation_period ep ON ep.period_id=? WHERE ks.staff_id=? AND ks.date_recorded BETWEEN ep.start_date AND ep.end_date");
        $chk->bind_param("ii", $period_id, $emp['employee_id']);
        $chk->execute();
        if ($chk->get_result()->fetch_assoc()['cnt'] == 0) continue;
        $kpi = KPICalculator::calculateKPI($conn, $emp['employee_id'], $period_id);
        $emp['kpi_score'] = $kpi['overall'];
        $emp['rating']    = $kpi['rating'];
        $profiles_data[]  = $emp;
    }
    usort($profiles_data, function($a,$b){ $d=strcmp($a['department'],$b['department']); return $d!==0?$d:strcmp($a['name'],$b['name']); });

    $total  = count($profiles_data);
    $topCnt = count(array_filter($profiles_data, fn($e) => $e['kpi_score'] >= 4.0));
    $atRisk = count(array_filter($profiles_data, fn($e) => $e['kpi_score'] <  3.0));
    $avgKPI = $total ? round(array_sum(array_column($profiles_data,'kpi_score')) / $total, 2) : 0;
?>

<div class="content active prf-page">

  <!-- Header -->
  <div class="prf-card" style="background:linear-gradient(135deg,#0f1a2e 0%,#162035 55%,#1a2233 100%);position:relative;overflow:hidden;">
    <div style="position:absolute;top:-60px;right:-60px;width:240px;height:240px;background:radial-gradient(circle,rgba(59,130,246,.07),transparent 70%);border-radius:50%;pointer-events:none;"></div>
    <div style="position:relative;z-index:2;">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px;">
        <div style="display:flex;align-items:center;gap:12px;">
          <div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,rgba(59,130,246,.25),rgba(6,182,212,.15));border:1px solid rgba(59,130,246,.3);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
          </div>
          <div>
            <div style="font-family:'Sora',sans-serif;font-size:17px;font-weight:700;color:var(--text-primary);">Sales Assistant Profiles</div>
            <div style="font-size:12px;color:var(--text-secondary);margin-top:2px;">SAKMS employee directory &bull; <strong><?= htmlspecialchars($period_label_list) ?></strong></div>
          </div>
        </div>
        <form method="GET" style="display:flex;align-items:center;gap:8px;margin-left:auto;">
          <input type="hidden" name="page" value="profiles">
          <select name="period" class="dsh-filter-select" onchange="this.form.submit()" style="min-width:130px;padding:8px 12px;background:rgba(15,26,46,.8);border:1px solid rgba(59,130,246,.3);border-radius:8px;color:var(--text-primary);font-size:12px;font-weight:600;cursor:pointer;">
            <?php foreach ($all_periods as $p): ?>
              <option value="<?= $p['period_id'] ?>" <?= ((int)$p['period_id']===(int)$period_id)?'selected':'' ?>>Year <?= htmlspecialchars($p['year']) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
      <div class="prf-summary-bar">
        <div class="prf-sum-chip"><span class="prf-sum-dot" style="background:#3b82f6;"></span>Total: <strong><?= $total ?></strong></div>
        <div class="prf-sum-chip"><span class="prf-sum-dot" style="background:#22c55e;"></span>Top Performers: <strong><?= $topCnt ?></strong></div>
        <div class="prf-sum-chip"><span class="prf-sum-dot" style="background:#ef4444;"></span>At Risk: <strong><?= $atRisk ?></strong></div>
        <div class="prf-sum-chip"><span class="prf-sum-dot" style="background:#f59e0b;"></span>Avg KPI: <strong><?= $avgKPI ?></strong></div>
      </div>
    </div>
  </div>

  <!-- Search -->
  <div class="prf-search-wrap">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
    <input class="prf-search-input" type="text" id="prfSearch" placeholder="Search by name, role or staff ID..." oninput="prfFilter()">
  </div>

  <!-- Card grid -->
  <div class="prf-list-grid" id="prfGrid">
    <?php foreach ($profiles_data as $i => $emp):
      $sc    = $emp['kpi_score'];
      $color = prfScoreColor($sc);
      $avatar= buildAvatarUrl($emp['name']);
      $delay = ($i % 12) * 0.045;
    ?>
    <div class="prf-emp-card prf-card-item" style="animation-delay:<?= $delay ?>s;"
         data-search="<?= strtolower(safe($emp['name']).' '.safe($emp['role']).' '.safe($emp['staff_id'])) ?>">
      <div class="prf-avatar-row">
        <div class="prf-avatar-wrap">
          <img src="<?= $avatar ?>" alt="<?= safe($emp['name']) ?>" class="prf-avatar"
               onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($emp['name']) ?>&background=1a2540&color=60a5fa&size=52'">
          <div class="prf-avatar-dot" data-ptip="Active"></div>
        </div>
        <div style="min-width:0;flex:1;">
          <div class="prf-emp-name"><?= safe($emp['name']) ?></div>
          <div class="prf-emp-role"><?= safe($emp['role']) ?></div>
          <div class="prf-emp-id"><?= safe($emp['staff_id']) ?></div>
        </div>
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;">
        <div class="prf-pill">
          <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/></svg>
          <?= safe($emp['department']) ?>
        </div>
        <div class="prf-pill" style="color:<?= $color ?>;border-color:<?= $color ?>44;background:<?= $color ?>11;"><?= safe($emp['rating']) ?></div>
      </div>
      <div>
        <div class="prf-prog-label">
          <span class="prf-prog-name">Overall KPI</span>
          <span class="prf-prog-val" style="color:<?= $color ?>"><?= number_format($sc,2) ?></span>
        </div>
        <div class="prf-prog-track">
          <div class="prf-prog-fill" style="width:<?= min(($sc/5)*100,100) ?>%;background:<?= $color ?>;animation-delay:<?= $delay+0.15 ?>s;"></div>
        </div>
      </div>
      <a href="index.php?page=profiles&emp_id=<?= (int)$emp['employee_id'] ?>&period=<?= (int)$period_id ?>" class="prf-view-btn">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        View Full Profile
      </a>
    </div>
    <?php endforeach; ?>
  </div>

  <div id="prfEmpty" style="display:none;text-align:center;padding:40px;color:var(--text-muted);">
    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 10px;display:block;opacity:.4;"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
    <div style="font-size:13px;font-weight:600;">No profiles match your search</div>
  </div>
</div>

<script>
function prfFilter(){
  var q = document.getElementById('prfSearch').value.toLowerCase(), vis = 0;
  document.querySelectorAll('.prf-card-item').forEach(function(c){
    var show = !q || c.dataset.search.includes(q);
    c.style.display = show ? '' : 'none';
    if (show) vis++;
  });
  document.getElementById('prfEmpty').style.display = vis===0 ? 'block' : 'none';
}
(function(){
  var io = new IntersectionObserver(function(en){
    en.forEach(function(e){ if(e.isIntersecting){ e.target.style.opacity='1'; e.target.style.transform='none'; }});
  },{threshold:.05});
  document.querySelectorAll('.prf-emp-card').forEach(function(el){ io.observe(el); });
})();
</script>

<?php endif; ?>
