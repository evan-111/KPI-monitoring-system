<?php
/**
 * SAKMS - Supervisor Dashboard
 */

// ── Evaluation periods ────────────────────────────────────────────────────────
$all_periods = $conn->query(
    "SELECT period_id, year, start_date, end_date FROM evaluation_period ORDER BY year DESC"
)->fetch_all(MYSQLI_ASSOC);

// ── Core data — filter staff with actual data for this period ─────────────────
$all_employees_res = getAllEmployeesSummary($conn, $period_id);
$employee_data     = [];
$total_employees   = 0;

while ($emp = $all_employees_res->fetch_assoc()) {
    $chk = $conn->prepare("
        SELECT COUNT(*) as cnt FROM kpi_score ks
        JOIN evaluation_period ep ON ep.period_id = ?
        WHERE ks.staff_id = ? AND ks.date_recorded BETWEEN ep.start_date AND ep.end_date
    ");
    $chk->bind_param("ii", $period_id, $emp['employee_id']);
    $chk->execute();
    if ($chk->get_result()->fetch_assoc()['cnt'] == 0) continue;

    $kpi = KPICalculator::calculateKPI($conn, $emp['employee_id'], $period_id);
    $emp['kpi_score'] = $kpi['overall'];
    $emp['rating']    = $kpi['rating'];
    $employee_data[]  = $emp;
    $total_employees++;
}

usort($employee_data, fn($a, $b) => $b['kpi_score'] <=> $a['kpi_score']);

$top_performers    = array_values(array_filter($employee_data, fn($e) => $e['kpi_score'] >= 4.25));
$needs_improvement = array_values(array_filter($employee_data, fn($e) => $e['kpi_score'] < 3.0));

// ── Most improved (current vs previous period) ────────────────────────────────
$prev_period_id = null;
foreach ($all_periods as $i => $p) {
    if ((int)$p['period_id'] === (int)$period_id && isset($all_periods[$i + 1])) {
        $prev_period_id = (int)$all_periods[$i + 1]['period_id'];
        break;
    }
}

$most_improved = [];
if ($prev_period_id) {
    foreach ($employee_data as $emp) {
        $prev_kpi = KPICalculator::calculateKPI($conn, $emp['employee_id'], $prev_period_id);
        if ($prev_kpi['overall'] > 0) {
            $change = round($emp['kpi_score'] - $prev_kpi['overall'], 2);
            if ($change > 0) {
                $most_improved[] = array_merge($emp, ['prev_score'=>$prev_kpi['overall'],'change'=>$change]);
            }
        }
    }
    usort($most_improved, fn($a, $b) => $b['kpi_score'] <=> $a['kpi_score']);
    $most_improved = array_slice($most_improved, 0, 5);
}

// ── Department & distribution data ───────────────────────────────────────────
$dept_data = [];
$dept_res  = getAverageByDepartment($conn, $period_id);
while ($d = $dept_res->fetch_assoc()) $dept_data[] = $d;
usort($dept_data, fn($a, $b) => (float)($b['avg_score']??0) <=> (float)($a['avg_score']??0));

$perf_dist = getPerformanceDistribution($conn, $period_id);

// ── Training needs ────────────────────────────────────────────────────────────
$training_needs = [];
foreach ($needs_improvement as $emp) {
    $rec = getTrainingRecommendations($conn, $emp['employee_id'], $period_id);
    if ($rec && trim($rec) !== '' && trim($rec) !== 'No recommendations available') {
        $training_needs[] = ['name'=>$emp['name'],'kpi_score'=>$emp['kpi_score'],'rating'=>$emp['rating'],'recommendation'=>trim($rec)];
    }
}

// ── Summary metrics ───────────────────────────────────────────────────────────
$avg_score           = $total_employees ? round(array_sum(array_column($employee_data,'kpi_score'))/$total_employees,2) : 0;
$top_count           = count($top_performers);
$at_risk_count       = count($needs_improvement);
$most_improved_count = count($most_improved);

// ── Period label ──────────────────────────────────────────────────────────────
$period_label = 'Current Period';
foreach ($all_periods as $p) {
    if ((int)$p['period_id'] === (int)$period_id) { $period_label = 'Year '.$p['year']; break; }
}

// ── Helper functions ──────────────────────────────────────────────────────────
function dshRiskBadge(float $score): array {
    if ($score < 2.0) return ['label'=>'Critical','color'=>'#ef4444','bg'=>'rgba(239,68,68,0.12)'];
    if ($score < 2.5) return ['label'=>'High',    'color'=>'#f97316','bg'=>'rgba(249,115,22,0.12)'];
    return                   ['label'=>'Medium',  'color'=>'#f59e0b','bg'=>'rgba(245,158,11,0.12)'];
}
function dshScorePct(float $score): int { return (int)round(($score/5.0)*100); }
function dshRankMedal(int $i): string {
    return match($i) {
        0=>'<span class="dsh-medal">🥇</span>',1=>'<span class="dsh-medal">🥈</span>',
        2=>'<span class="dsh-medal">🥉</span>',default=>'<span class="dsh-rank-num">'.($i+1).'</span>',
    };
}
function dshScoreColor(float $score): string {
    if ($score >= 5.0) return '#22c55e'; // Green  – Excellent
    if ($score >= 4.0) return '#3b82f6'; // Blue   – Good      (4.00–4.99)
    if ($score >= 3.0) return '#f59e0b'; // Amber  – Satisfactory (3.00–3.99)
    if ($score >= 2.0) return '#ef4444'; // Red    – Poor      (2.00–2.99)
    return '#7f1d1d';                    // Maroon – Critical  (0–1.99)
}
?>

<style>
/* ══════════════════════════════════════════
   SUPERVISOR DASHBOARD  prefix: dsh-
══════════════════════════════════════════ */

/* ── Keyframes ──────────────────────────── */
@keyframes dshFadeUp  { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
@keyframes dshSlideIn { from{opacity:0;transform:translateX(-14px)} to{opacity:1;transform:translateX(0)} }
@keyframes dshPulseRed{ 0%{box-shadow:0 0 0 0 rgba(239,68,68,.45)} 70%{box-shadow:0 0 0 10px rgba(239,68,68,0)} 100%{box-shadow:0 0 0 0 rgba(239,68,68,0)} }
@keyframes dshOrbFloat{ 0%,100%{transform:translate(0,0) scale(1);opacity:.45} 33%{transform:translate(10px,-14px) scale(1.08);opacity:.65} 66%{transform:translate(-8px,8px) scale(.93);opacity:.35} }
@keyframes dshPulseDot{ 0%{box-shadow:0 0 0 0 rgba(52,211,153,.45)} 70%{box-shadow:0 0 0 6px rgba(52,211,153,0)} 100%{box-shadow:0 0 0 0 rgba(52,211,153,0)} }

/* ── Scroll reveal ──────────────────────── */
.dsh-reveal { opacity:0; transform:translateY(20px); transition:opacity .5s ease,transform .5s ease; }
.dsh-reveal.dsh-visible { opacity:1; transform:translateY(0); }

/* ── Layout ─────────────────────────────── */
.dsh-page    { display:flex; flex-direction:column; gap:24px; padding-bottom:48px; }
.dsh-two-col { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
@media(max-width:960px){ .dsh-two-col{ grid-template-columns:1fr; } }

/* ── Base card ──────────────────────────── */
.dsh-card { background:var(--bg-card); border:1px solid var(--border); border-radius:16px; padding:24px 28px; box-shadow:0 2px 16px rgba(0,0,0,.22); transition:border-color .2s,box-shadow .2s; }
.dsh-card:hover { border-color:rgba(59,130,246,.25); box-shadow:0 4px 28px rgba(0,0,0,.3); }

/* ── Section heading ────────────────────── */
.dsh-section-head  { display:flex; align-items:center; gap:10px; margin-bottom:20px; }
.dsh-section-icon  { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
.dsh-section-title { font-family:'Sora',sans-serif; font-size:14px; font-weight:700; color:var(--text-primary); letter-spacing:-.2px; }
.dsh-section-sub   { font-size:11px; color:var(--text-muted); font-weight:400; margin-top:1px; }
.dsh-section-badge { margin-left:auto; background:rgba(59,130,246,.12); border:1px solid rgba(59,130,246,.2); color:#60a5fa; font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; }
.dsh-divider       { border:none; border-top:1px solid var(--border); margin:0 0 20px; }

/* ── Header card ────────────────────────── */
.dsh-header-card { background:linear-gradient(135deg,#111c2e 0%,#1a2540 60%,#1a2233 100%); border:1px solid var(--border); border-radius:16px; padding:28px 32px; position:relative; overflow:hidden; animation:dshFadeUp .45s ease both; }
.dsh-header-card::before { content:''; position:absolute; top:-50px; right:-50px; width:240px; height:240px; background:radial-gradient(circle,rgba(59,130,246,.1) 0%,transparent 70%); border-radius:50%; pointer-events:none; }
.dsh-header-orb  { position:absolute; border-radius:50%; pointer-events:none; z-index:1; animation:dshOrbFloat 6s ease-in-out infinite; }
.dsh-header-top  { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap; position:relative; z-index:10; }
.dsh-header-title-block { display:flex; align-items:center; gap:14px; }
.dsh-header-icon-wrap   { width:50px; height:50px; background:linear-gradient(135deg,rgba(59,130,246,.25),rgba(6,182,212,.15)); border:1px solid rgba(59,130,246,.3); border-radius:13px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.dsh-header-title    { font-family:'Sora',sans-serif; font-size:22px; font-weight:700; color:var(--text-primary); letter-spacing:-.4px; }
.dsh-header-subtitle { font-size:12px; color:var(--text-secondary); margin-top:3px; }
.dsh-header-actions  { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.dsh-header-meta { display:flex; align-items:center; gap:20px; margin-top:22px; padding-top:18px; border-top:1px solid rgba(255,255,255,.07); flex-wrap:wrap; position:relative; z-index:10; }
.dsh-meta-chip   { display:flex; align-items:center; gap:6px; font-size:12px; color:var(--text-secondary); }
.dsh-meta-chip svg { color:var(--text-muted); flex-shrink:0; }
.dsh-meta-chip strong { color:var(--text-primary); font-weight:600; }
.dsh-meta-dot    { width:4px; height:4px; border-radius:50%; background:var(--border); flex-shrink:0; }

.dsh-header-deco { position:absolute; right:32px; top:50%; transform:translateY(-50%); display:flex; align-items:flex-end; gap:6px; pointer-events:none; opacity:.45; z-index:1; }
@media(max-width:860px){ .dsh-header-deco{ display:none; } }
.dsh-deco-bar { width:6px; border-radius:3px; background:linear-gradient(180deg,#3b82f6,#06b6d4); animation:dshFadeUp 1.2s ease both; }

/* ── Buttons ────────────────────────────── */
.dsh-btn { display:inline-flex; align-items:center; gap:6px; padding:9px 16px; border-radius:9px; font-size:12px; font-weight:600; cursor:pointer; border:none; text-decoration:none; transition:all .18s ease; white-space:nowrap; font-family:'DM Sans',sans-serif; }
.dsh-btn-ghost { background:rgba(255,255,255,.06); color:var(--text-primary); border:1px solid var(--border); }
.dsh-btn-ghost:hover { background:rgba(255,255,255,.1); border-color:rgba(59,130,246,.4); }
.dsh-btn-blue  { background:linear-gradient(135deg,#2563eb,#3b82f6); color:#fff; box-shadow:0 2px 8px rgba(59,130,246,.3); }
.dsh-btn-blue:hover { background:linear-gradient(135deg,#1d4ed8,#2563eb); transform:translateY(-1px); }

/* ── Summary stat cards ─────────────────── */
.dsh-summary-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:16px; }
@media(max-width:1100px){ .dsh-summary-grid{ grid-template-columns:repeat(3,1fr); } }
@media(max-width:700px) { .dsh-summary-grid{ grid-template-columns:repeat(2,1fr); } }
@media(max-width:420px) { .dsh-summary-grid{ grid-template-columns:1fr; } }

.dsh-stat-card { background:var(--bg-input); border:1px solid var(--border); border-top:3px solid; border-radius:14px; padding:20px 22px; position:relative; overflow:hidden; transition:transform .2s ease,box-shadow .2s ease; cursor:default; animation:dshFadeUp .5s ease both; }
.dsh-stat-card:hover { transform:translateY(-4px); box-shadow:0 12px 32px rgba(0,0,0,.4); }
.dsh-stat-card::after { content:''; position:absolute; top:0; right:0; width:80px; height:80px; border-radius:50%; opacity:.06; transform:translate(28px,-28px); pointer-events:none; }
.dsh-stat-card.blue  { border-top-color:#3b82f6; } .dsh-stat-card.blue::after  { background:#3b82f6; }
.dsh-stat-card.green { border-top-color:#22c55e; } .dsh-stat-card.green::after { background:#22c55e; }
.dsh-stat-card.red   { border-top-color:#ef4444; } .dsh-stat-card.red::after   { background:#ef4444; }
.dsh-stat-card.amber { border-top-color:#f59e0b; } .dsh-stat-card.amber::after { background:#f59e0b; }
.dsh-stat-card.purple{ border-top-color:#8b5cf6; } .dsh-stat-card.purple::after{ background:#8b5cf6; }
.dsh-stat-card.red.has-risk { animation:dshPulseRed 2.5s ease-out infinite; }

.dsh-stat-icon  { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; margin-bottom:14px; }
.dsh-stat-icon.blue  { background:rgba(59,130,246,.15); }
.dsh-stat-icon.green { background:rgba(34,197,94,.15); }
.dsh-stat-icon.red   { background:rgba(239,68,68,.15); }
.dsh-stat-icon.amber { background:rgba(245,158,11,.15); }
.dsh-stat-icon.purple{ background:rgba(139,92,246,.15); }
.dsh-stat-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:var(--text-muted); margin-bottom:5px; }
.dsh-stat-value { font-family:'Sora',sans-serif; font-size:30px; font-weight:700; color:var(--text-primary); line-height:1; margin-bottom:6px; }
.dsh-stat-micro { font-size:11px; color:var(--text-secondary); line-height:1.4; }

/* ── Filter toolbar ─────────────────────── */
.dsh-filter-bar    { background:var(--bg-card); border:1px solid var(--border); border-radius:14px; padding:18px 24px; display:flex; align-items:flex-end; gap:14px; flex-wrap:wrap; }
.dsh-filter-group  { display:flex; flex-direction:column; gap:6px; }
.dsh-filter-label  { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:var(--text-muted); }
.dsh-filter-input, .dsh-filter-select { padding:9px 12px; background:var(--bg-input); border:1px solid var(--border); border-radius:8px; color:var(--text-primary); font-size:12px; font-family:'DM Sans',sans-serif; transition:border-color .15s; min-width:140px; }
.dsh-filter-input:focus, .dsh-filter-select:focus { outline:none; border-color:var(--accent); }
.dsh-filter-search  { flex:1; min-width:180px; }
.dsh-filter-actions { display:flex; gap:8px; margin-left:auto; align-items:flex-end; }
.dsh-filter-reset   { padding:9px 14px; background:rgba(255,255,255,.05); border:1px solid var(--border); border-radius:8px; color:var(--text-secondary); font-size:12px; font-weight:600; cursor:pointer; font-family:'DM Sans',sans-serif; transition:all .15s; display:flex; align-items:center; gap:5px; }
.dsh-filter-reset:hover { border-color:rgba(239,68,68,.4); color:#ef4444; }
.dsh-filter-count   { font-size:11px; color:var(--text-muted); align-self:flex-end; padding-bottom:10px; white-space:nowrap; }

/* ── Performer rows ─────────────────────── */
.dsh-performer-list { display:flex; flex-direction:column; gap:8px; }
.dsh-performer-row  { display:flex; align-items:center; gap:12px; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); border-radius:10px; padding:11px 14px; transition:background .2s; position:relative; overflow:hidden; opacity:0; }
.dsh-performer-row.dsh-row-visible { animation:dshSlideIn .4s ease both; opacity:1; }
.dsh-performer-row:hover { background:rgba(255,255,255,.06); }
.dsh-performer-row::before { content:''; position:absolute; top:0; left:-100%; width:60%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,.03),transparent); transition:left .5s ease; pointer-events:none; }
.dsh-performer-row:hover::before { left:150%; }

.dsh-medal    { font-size:22px; line-height:1; flex-shrink:0; }
.dsh-rank-num { display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; background:var(--bg-input); border:1px solid var(--border); border-radius:6px; font-size:11px; font-weight:700; color:var(--text-muted); flex-shrink:0; }
.dsh-performer-info { flex:1; min-width:0; }
.dsh-performer-name { font-size:13px; font-weight:600; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; text-decoration:none; }
.dsh-performer-name:hover { color:var(--accent); }
.dsh-performer-role { font-size:11px; color:var(--text-muted); margin-top:2px; }
.dsh-performer-score-wrap { display:flex; flex-direction:column; align-items:flex-end; gap:5px; flex-shrink:0; }
.dsh-score-pill   { font-size:12px; font-weight:700; padding:3px 10px; border-radius:20px; color:#fff; line-height:1.4; }
.dsh-score-bar-wrap{ width:80px; height:4px; background:rgba(255,255,255,.08); border-radius:2px; overflow:hidden; }
.dsh-score-bar    { height:100%; border-radius:2px; width:0; transition:width 1s ease; }
.dsh-rating-tag   { font-size:10px; color:var(--text-muted); font-weight:500; }

/* ── Delta badge ────────────────────────── */
.dsh-delta-badge { display:inline-flex; align-items:center; gap:3px; padding:4px 9px; background:rgba(16,185,129,.12); border:1px solid rgba(16,185,129,.25); border-radius:20px; font-size:11px; font-weight:700; color:#34d399; flex-shrink:0; }
.dsh-badge-dot   { display:inline-block; width:6px; height:6px; border-radius:50%; background:#34d399; animation:dshPulseDot 1.8s ease-out infinite; }

/* ── At-risk rows ───────────────────────── */
.dsh-risk-row { display:flex; align-items:center; gap:12px; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); border-radius:10px; padding:13px 15px; transition:background .2s; opacity:0; }
.dsh-risk-row.dsh-row-visible { animation:dshSlideIn .4s ease both; opacity:1; }
.dsh-risk-row:hover { background:rgba(255,255,255,.05); }
.dsh-risk-info  { flex:1; min-width:0; }
.dsh-risk-name  { font-size:13px; font-weight:600; color:var(--text-primary); text-decoration:none; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:block; }
.dsh-risk-name:hover { color:var(--accent); }
.dsh-risk-meta  { display:flex; align-items:center; gap:8px; margin-top:4px; flex-wrap:wrap; }
.dsh-risk-score { font-size:12px; font-weight:600; color:var(--text-secondary); }
.dsh-risk-badge { font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; border:1px solid; }
/***************************
   Title: Pure CSS animated SVG circular progress ring
   Author: Tobias Ahlin
   Date: 2020
   Type: Source Code
   Availability: https://github.com/tobiasahlin/SpinKit
***************************/
/* ── Score ring (SVG) ───────────────────── */
.dsh-ring-wrap     { position:relative; width:52px; height:52px; flex-shrink:0; }
.dsh-ring-wrap svg { transform:rotate(-90deg); }
.dsh-ring-bg  { fill:none; stroke:rgba(255,255,255,.07); stroke-width:5; }
.dsh-ring-fg  { fill:none; stroke-width:5; stroke-linecap:round; stroke-dasharray:134; stroke-dashoffset:134; transition:stroke-dashoffset 1.1s cubic-bezier(.4,0,.2,1); }
.dsh-ring-val { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-family:'Sora',sans-serif; font-size:11px; font-weight:700; }

/* ── Most improved rows ─────────────────── */
.dsh-mi-row:hover .dsh-mi-shimmer { transform:translateX(100%)!important; }
.dsh-mi-row.dsh-row-visible { opacity:1!important; }

/* ── Dept bars & chart ──────────────────── */
.dsh-chart-wrap    { position:relative; height:240px; margin-top:4px; }
.dsh-dept-list     { display:flex; flex-direction:column; gap:12px; margin-top:4px; }
.dsh-dept-header   { display:flex; justify-content:space-between; align-items:center; margin-bottom:5px; }
.dsh-dept-name     { font-size:12px; font-weight:600; color:var(--text-primary); }
.dsh-dept-meta     { font-size:11px; color:var(--text-muted); }
.dsh-dept-bar-track{ height:8px; background:rgba(255,255,255,.06); border-radius:4px; overflow:hidden; }
.dsh-dept-bar-fill { height:100%; border-radius:4px; width:0; transition:width 1.1s cubic-bezier(.4,0,.2,1); }

/* ── Alert banner ───────────────────────── */
.dsh-alert-banner { display:flex; align-items:center; gap:14px; border-radius:12px; padding:14px 18px; border-left:4px solid; }
.dsh-alert-banner + .dsh-alert-banner { margin-top:10px; }
.dsh-alert-icon { font-size:22px; flex-shrink:0; }
.dsh-alert-title{ font-size:12px; font-weight:700; }
.dsh-alert-desc { font-size:11px; color:var(--text-secondary); margin-top:2px; }

/* ── Staff table ────────────────────────── */
.dsh-staff-table-wrap { overflow-x:auto; margin-top:4px; }
.dsh-staff-table      { width:100%; border-collapse:collapse; font-size:13px; }
.dsh-staff-table th   { padding:10px 12px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:var(--text-muted); text-align:left; border-bottom:1px solid var(--border); white-space:nowrap; cursor:pointer; user-select:none; }
.dsh-staff-table th:hover { color:var(--text-primary); }
.dsh-staff-table th .dsh-sort-arrow { opacity:.4; margin-left:4px; }
.dsh-staff-table td   { padding:12px; border-bottom:1px solid rgba(255,255,255,.04); color:var(--text-secondary); vertical-align:middle; }
.dsh-staff-table tr:last-child td { border-bottom:none; }
.dsh-staff-table tr   { transition:background .15s; }
.dsh-staff-table tr:hover td { background:rgba(255,255,255,.03); }
.dsh-staff-table tr.dsh-hidden { display:none; }

.dsh-tbl-name  { font-weight:600; color:var(--text-primary); text-decoration:none; display:flex; align-items:center; gap:8px; }
.dsh-tbl-name:hover { color:var(--accent); }
.dsh-tbl-avatar{ width:30px; height:30px; border-radius:50%; background:linear-gradient(135deg,#6366f1,#3b82f6); display:inline-flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:#fff; flex-shrink:0; }
.dsh-tbl-badge { font-size:10px; font-weight:700; padding:3px 9px; border-radius:20px; color:#fff; display:inline-block; }
.dsh-tbl-bar-wrap  { display:flex; align-items:center; gap:8px; }
.dsh-tbl-bar-track { width:70px; height:5px; background:rgba(255,255,255,.08); border-radius:3px; overflow:hidden; }
.dsh-tbl-bar-fill  { height:100%; border-radius:3px; width:0; transition:width .9s ease; }
.dsh-tbl-score-val { font-weight:700; color:var(--text-primary); white-space:nowrap; }

/* ── Empty states ───────────────────────── */
.dsh-empty-state { text-align:center; padding:48px 24px; }
.dsh-empty-icon  { font-size:40px; margin-bottom:12px; opacity:.5; }
.dsh-empty-title { font-size:14px; font-weight:600; color:var(--text-secondary); margin-bottom:4px; }
.dsh-empty-desc  { font-size:12px; color:var(--text-muted); }

/* ── Tooltip ────────────────────────────── */
[data-dsh-tip]{ position:relative; cursor:default; }
[data-dsh-tip]::after{ content:attr(data-dsh-tip); position:absolute; bottom:calc(100% + 7px); left:50%; transform:translateX(-50%); background:#111c2e; border:1px solid #243047; color:#f0f4ff; font-size:11px; font-weight:500; padding:5px 9px; border-radius:6px; white-space:nowrap; pointer-events:none; opacity:0; transition:opacity .15s ease; z-index:500; }
[data-dsh-tip]:hover::after{ opacity:1; }

/* ── Responsive ─────────────────────────── */
@media(max-width:768px){
    .dsh-header-card{ padding:20px; }
    .dsh-card{ padding:18px; }
    .dsh-filter-bar{ flex-direction:column; align-items:stretch; }
    .dsh-filter-actions{ margin-left:0; }
    .dsh-filter-search{ min-width:unset; }
    .dsh-staff-table td,.dsh-staff-table th{ padding:10px 8px; }
}
</style>

<div class="content active fade-in">
<div class="dsh-page">

<!-- 1. HEADER ──────────────────────────────────────────────────── -->
<div class="dsh-header-card">
  <div class="dsh-header-orb" style="width:140px;height:140px;top:-40px;left:-40px;background:radial-gradient(circle,rgba(59,130,246,.07),transparent 70%);animation-delay:0s;"></div>
  <div class="dsh-header-orb" style="width:100px;height:100px;bottom:-20px;left:30%;background:radial-gradient(circle,rgba(6,182,212,.06),transparent 70%);animation-delay:2s;"></div>
  <div class="dsh-header-deco">
    <?php foreach ([28,44,36,56,40,64] as $delay => $h): ?>
    <div class="dsh-deco-bar" style="height:<?= $h ?>px;animation-delay:<?= 0.3+$delay*0.2 ?>s;"></div>
    <?php endforeach; ?>
  </div>
  <div class="dsh-header-top">
    <div class="dsh-header-title-block">
      <div class="dsh-header-icon-wrap">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2">
          <rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/>
          <rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/>
        </svg>
      </div>
      <div>
        <div class="dsh-header-title">Supervisor Dashboard</div>
        <div class="dsh-header-subtitle">Sales Assistant KPI Monitoring &amp; Performance Overview</div>
      </div>
    </div>
    <div class="dsh-header-actions">
      <form method="GET" style="display:flex;align-items:center;gap:8px;">
        <input type="hidden" name="page" value="dashboard">
        <select name="period" class="dsh-filter-select" onchange="this.form.submit()" style="min-width:130px;">
          <?php foreach ($all_periods as $p): ?>
            <option value="<?= $p['period_id'] ?>" <?= ((int)$p['period_id']===(int)$period_id)?'selected':'' ?>>
              Year <?= htmlspecialchars($p['year']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>
      <a href="index.php?page=report" class="dsh-btn dsh-btn-blue">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Full Report
      </a>
    </div>
  </div>
  <div class="dsh-header-meta">
    <div class="dsh-meta-chip">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      <span>Evaluation Period: <strong><?= htmlspecialchars($period_label) ?></strong></span>
    </div>
    <div class="dsh-meta-dot"></div>
    <div class="dsh-meta-chip">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
      <span><strong><?= $total_employees ?></strong> Active Sales Assistants</span>
    </div>
    <div class="dsh-meta-dot"></div>
    <div class="dsh-meta-chip">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
      <span>Team Average KPI: <strong style="color:<?= dshScoreColor($avg_score) ?>;"><?= number_format($avg_score,2) ?> / 5.00</strong></span>
    </div>
    <div class="dsh-meta-dot"></div>
    <div class="dsh-meta-chip">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      <span>Last updated: <strong><?= date('d M Y') ?></strong></span>
    </div>
  </div>
</div>

<!-- 2. SUMMARY CARDS ───────────────────────────────────────────── -->
<div class="dsh-summary-grid dsh-reveal">
  <div class="dsh-stat-card blue" style="animation-delay:.05s;">
    <div class="dsh-stat-icon blue">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </div>
    <div class="dsh-stat-label">Total Staff</div>
    <div class="dsh-stat-value"><?= $total_employees ?></div>
    <div class="dsh-stat-micro">Active sales assistants</div>
  </div>
  <div class="dsh-stat-card green" style="animation-delay:.10s;">
    <div class="dsh-stat-icon green">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
    </div>
    <div class="dsh-stat-label">Top Performers</div>
    <div class="dsh-stat-value"><?= $top_count ?></div>
    <div class="dsh-stat-micro">KPI score ≥ 4.5 &mdash; Excellent</div>
  </div>
  <div class="dsh-stat-card red <?= $at_risk_count>0?'has-risk':'' ?>" style="animation-delay:.15s;">
    <div class="dsh-stat-icon red">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    </div>
    <div class="dsh-stat-label">At-Risk Staff</div>
    <div class="dsh-stat-value"><?= $at_risk_count ?></div>
    <div class="dsh-stat-micro">KPI below 3.0 &mdash; needs support</div>
  </div>
  <div class="dsh-stat-card amber" style="animation-delay:.20s;">
    <div class="dsh-stat-icon amber">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
    </div>
    <div class="dsh-stat-label">Avg KPI Score</div>
    <div class="dsh-stat-value" style="font-size:24px;"><?= number_format($avg_score,2) ?></div>
    <div class="dsh-stat-micro" style="color:<?= dshScoreColor($avg_score) ?>;"><?= KPICalculator::getRatingLabel($avg_score) ?> &mdash; out of 5.00</div>
  </div>
  <div class="dsh-stat-card purple" style="animation-delay:.25s;">
    <div class="dsh-stat-icon purple">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
    </div>
    <div class="dsh-stat-label">Most Improved</div>
    <div class="dsh-stat-value"><?= $most_improved_count ?></div>
    <div class="dsh-stat-micro">
      <?php if ($most_improved_count > 0): ?>
        Top: <strong style="color:#c4b5fd;"><?= htmlspecialchars($most_improved[0]['name']) ?></strong> (+<?= $most_improved[0]['change'] ?>)
      <?php else: ?>
        <?= $prev_period_id ? 'No improvement data' : 'No previous period' ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- 3. RISK ALERT BANNER ───────────────────────────────────────── -->
<?php
$high_risk_ct = $med_risk_ct = 0;
foreach ($needs_improvement as $emp) {
    $r = predictPerformanceRisk($conn, $emp['employee_id']);
    in_array($r['risk_level'],['Critical','High']) ? $high_risk_ct++ : $med_risk_ct++;
}
$banner = $high_risk_ct > 0
    ? ['bg'=>'rgba(239,68,68,.08)','color'=>'#ef4444','icon'=>'🔴','title'=>'Critical Alert &mdash; Immediate Action Required','desc'=>"<strong>{$high_risk_ct}</strong> employee(s) show critical or high performance risk with declining KPI trends. Review their profiles and initiate coaching or intervention plans.",'link'=>true]
    : ($med_risk_ct > 0
    ? ['bg'=>'rgba(245,158,11,.08)','color'=>'#f59e0b','icon'=>'⚠️','title'=>'Performance Warning &mdash; Monitor Closely','desc'=>"<strong>{$med_risk_ct}</strong> employee(s) require performance monitoring and improvement support this period.",'link'=>true]
    : ['bg'=>'rgba(34,197,94,.08)','color'=>'#22c55e','icon'=>'✅','title'=>'All Clear &mdash; Team Performing Well','desc'=>'No critical performance risks detected. All employees are performing within acceptable KPI ranges this period.','link'=>false]);
?>
<div class="dsh-reveal" style="transition-delay:.1s;">
  <div class="dsh-alert-banner" style="background:<?= $banner['bg'] ?>;border-left-color:<?= $banner['color'] ?>;">
    <div class="dsh-alert-icon"><?= $banner['icon'] ?></div>
    <div class="dsh-alert-body">
      <div class="dsh-alert-title" style="color:<?= $banner['color'] ?>;"><?= $banner['title'] ?></div>
      <div class="dsh-alert-desc"><?= $banner['desc'] ?></div>
    </div>
    <?php if ($banner['link']): ?>
    <a href="index.php?page=report" class="dsh-btn dsh-btn-ghost" style="flex-shrink:0;font-size:11px;">View Report</a>
    <?php endif; ?>
  </div>
</div>

<!-- 4. PERFORMANCE CLASSIFICATION ─────────────────────────────── -->
<div class="dsh-two-col dsh-reveal" style="transition-delay:.15s;">

  <!-- Top Performers -->
  <div class="dsh-card" id="top-performers" style="background:linear-gradient(135deg,#0a1f1f 0%,#0f2d2d 50%,#0a1f1f 100%);border-color:rgba(6,182,212,.3);position:relative;overflow:hidden;">
    <div style="position:absolute;top:-40px;right:-40px;width:200px;height:200px;background:radial-gradient(circle,rgba(6,182,212,.1),transparent 70%);pointer-events:none;border-radius:50%;"></div>
    <div style="position:absolute;bottom:-30px;left:-30px;width:150px;height:150px;background:radial-gradient(circle,rgba(20,184,166,.07),transparent 70%);pointer-events:none;border-radius:50%;"></div>
    <div class="dsh-section-head">
      <div class="dsh-section-icon" style="background:rgba(34,197,94,.12);">⭐</div>
      <div><div class="dsh-section-title">Top Performers</div><div class="dsh-section-sub">KPI score ≥ 4.5 &mdash; Excellent rating</div></div>
      <div class="dsh-section-badge"><?= $top_count ?> staff</div>
    </div>
    <hr class="dsh-divider">
    <?php if (count($top_performers) > 0): ?>
    <div class="dsh-performer-list">
      <?php foreach ($top_performers as $i => $emp):
        $color = dshScoreColor($emp['kpi_score']); $pct = dshScorePct($emp['kpi_score']); ?>
      <div class="dsh-performer-row" style="animation-delay:<?= $i*.06 ?>s;">
        <?= dshRankMedal($i) ?>
        <div class="dsh-performer-info">
          <a href="index.php?page=profiles&emp_id=<?= $emp['employee_id'] ?>" class="dsh-performer-name"><?= htmlspecialchars($emp['name']) ?></a>
          <div class="dsh-performer-role"><?= htmlspecialchars($emp['department']) ?></div>
        </div>
        <div class="dsh-performer-score-wrap">
          <span class="dsh-score-pill" style="background:<?= $color ?>;"><?= number_format($emp['kpi_score'],2) ?></span>
          <div class="dsh-score-bar-wrap"><div class="dsh-score-bar" style="background:<?= $color ?>;" data-width="<?= $pct ?>%"></div></div>
          <span class="dsh-rating-tag"><?= htmlspecialchars($emp['rating']) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="dsh-empty-state"><div class="dsh-empty-icon">🏆</div><div class="dsh-empty-title">No top performers yet</div><div class="dsh-empty-desc">Staff achieving KPI ≥ 4.5 will appear here.</div></div>
    <?php endif; ?>
  </div>

  <!-- Performance Improvement -->
  <div class="dsh-card" id="needs-improvement" style="background:linear-gradient(135deg,#130a1f 0%,#1f0f2d 50%,#130a1f 100%);border-color:rgba(139,92,246,.3);position:relative;overflow:hidden;">
    <div style="position:absolute;top:-40px;right:-40px;width:200px;height:200px;background:radial-gradient(circle,rgba(139,92,246,.1),transparent 70%);pointer-events:none;border-radius:50%;"></div>
    <div style="position:absolute;bottom:-30px;left:-30px;width:150px;height:150px;background:radial-gradient(circle,rgba(167,139,250,.07),transparent 70%);pointer-events:none;border-radius:50%;"></div>
    <div class="dsh-section-head">
      <div class="dsh-section-icon" style="background:rgba(139,92,246,.15);">📋</div>
      <div><div class="dsh-section-title">Performance Improvement</div><div class="dsh-section-sub">KPI score &lt; 3.0 &mdash; requires coaching</div></div>
      <div class="dsh-section-badge" style="background:rgba(139,92,246,.1);border-color:rgba(139,92,246,.25);color:#c4b5fd;"><?= $at_risk_count ?> staff</div>
    </div>
    <hr class="dsh-divider">
    <?php if (count($needs_improvement) > 0): ?>
    <div class="dsh-performer-list">
      <?php foreach ($needs_improvement as $i => $emp):
        $color  = dshScoreColor($emp['kpi_score']);
        $pct    = dshScorePct($emp['kpi_score']);
        $risk   = dshRiskBadge($emp['kpi_score']);
        $offset = round(134*(1-$pct/100),1); ?>
      <div class="dsh-risk-row" style="animation-delay:<?= $i*.06 ?>s;">
        <div class="dsh-ring-wrap">
          <svg width="52" height="52" viewBox="0 0 52 52">
            <circle class="dsh-ring-bg" cx="26" cy="26" r="21.4"/>
            <circle class="dsh-ring-fg" cx="26" cy="26" r="21.4" stroke="<?= $color ?>" style="stroke-dashoffset:<?= $offset ?>;" data-offset="<?= $offset ?>"/>
          </svg>
          <div class="dsh-ring-val" style="color:<?= $color ?>;"><?= number_format($emp['kpi_score'],1) ?></div>
        </div>
        <div class="dsh-risk-info">
          <a href="index.php?page=profiles&emp_id=<?= $emp['employee_id'] ?>" class="dsh-risk-name"><?= htmlspecialchars($emp['name']) ?></a>
          <div class="dsh-risk-meta">
            <span class="dsh-risk-score"><?= htmlspecialchars($emp['department']) ?></span>
            <span class="dsh-risk-badge" style="color:<?= $risk['color'] ?>;background:<?= $risk['bg'] ?>;border-color:<?= $risk['color'] ?>;"><?= $risk['label'] ?> Risk</span>
          </div>
        </div>
        <div style="flex-shrink:0;text-align:right;">
          <div style="font-size:10px;color:var(--text-muted);margin-bottom:3px;">KPI Score</div>
          <div class="dsh-score-pill" style="background:<?= $color ?>;"><?= number_format($emp['kpi_score'],2) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="dsh-empty-state"><div class="dsh-empty-icon">✅</div><div class="dsh-empty-title">No staff needing improvement</div><div class="dsh-empty-desc">All employees are scoring 3.0 or above.</div></div>
    <?php endif; ?>
  </div>

</div>

<!-- 5. MOST IMPROVED ───────────────────────────────────────────── -->
<?php if (count($most_improved) > 0): ?>
<div class="dsh-card dsh-reveal" id="most-improved-section" style="transition-delay:.2s;background:linear-gradient(135deg,#0f1a2e,#1a1535,#0f1a2e);border-color:rgba(139,92,246,.2);overflow:hidden;position:relative;">
  <div style="position:absolute;top:-40px;right:-40px;width:200px;height:200px;background:radial-gradient(circle,rgba(139,92,246,.08),transparent 70%);pointer-events:none;border-radius:50%;"></div>
  <div style="position:absolute;bottom:-40px;left:-40px;width:160px;height:160px;background:radial-gradient(circle,rgba(6,182,212,.06),transparent 70%);pointer-events:none;border-radius:50%;"></div>
  <div class="dsh-section-head" style="position:relative;z-index:2;">
    <div class="dsh-section-icon" style="background:linear-gradient(135deg,rgba(139,92,246,.2),rgba(6,182,212,.1));border:1px solid rgba(139,92,246,.3);">🏆</div>
    <div><div class="dsh-section-title" style="font-size:15px;">Most Improved This Period</div><div class="dsh-section-sub">Leaderboard &bull; Compared against previous evaluation period</div></div>
    <div class="dsh-section-badge" style="background:rgba(139,92,246,.1);border-color:rgba(139,92,246,.25);color:#c4b5fd;"><?= count($most_improved) ?> staff</div>
  </div>
  <hr class="dsh-divider">
  <div style="display:flex;flex-direction:column;gap:10px;position:relative;z-index:2;">
    <?php
    $rank_styles = [
      ['bg'=>'linear-gradient(135deg,rgba(255,215,0,.12),rgba(255,165,0,.06))','border'=>'rgba(255,215,0,.3)','glow'=>'rgba(255,215,0,.15)','label_bg'=>'linear-gradient(135deg,#b7791f,#d97706)'],
      ['bg'=>'linear-gradient(135deg,rgba(192,192,192,.12),rgba(148,163,184,.06))','border'=>'rgba(192,192,192,.3)','glow'=>'rgba(192,192,192,.1)','label_bg'=>'linear-gradient(135deg,#64748b,#94a3b8)'],
      ['bg'=>'linear-gradient(135deg,rgba(205,127,50,.12),rgba(180,100,40,.06))','border'=>'rgba(205,127,50,.3)','glow'=>'rgba(205,127,50,.1)','label_bg'=>'linear-gradient(135deg,#92400e,#b45309)'],
    ];
    $medals = ['🥇','🥈','🥉'];
    foreach ($most_improved as $i => $emp):
      $color    = dshScoreColor($emp['kpi_score']);
      $deltaPct = min(100,(int)round(($emp['change']/5)*100));
      $rs       = $rank_styles[$i] ?? ['bg'=>'rgba(255,255,255,.03)','border'=>'rgba(255,255,255,.08)','glow'=>'transparent','label_bg'=>'rgba(99,102,241,.3)'];
      $medal    = $medals[$i] ?? null;
    ?>
    <div class="dsh-mi-row" style="background:<?= $rs['bg'] ?>;border:1px solid <?= $rs['border'] ?>;border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:14px;transition:all .25s ease;cursor:pointer;position:relative;overflow:hidden;animation:dshSlideIn .4s ease both;animation-delay:<?= $i*.08 ?>s;opacity:0;"
         onmouseover="this.style.transform='translateX(6px)';this.style.boxShadow='0 8px 24px <?= $rs['glow'] ?>';"
         onmouseout="this.style.transform='';this.style.boxShadow='';"
         onclick="window.location='index.php?page=profiles&emp_id=<?= $emp['employee_id'] ?>'">
      <div style="position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.03),transparent);transform:translateX(-100%);transition:transform .6s ease;pointer-events:none;" class="dsh-mi-shimmer"></div>
      <div style="width:42px;height:42px;border-radius:10px;background:<?= $rs['label_bg'] ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:<?= $medal?'22px':'14px' ?>;font-weight:800;color:#fff;box-shadow:0 4px 12px <?= $rs['glow'] ?>;">
        <?= $medal ?? ($i+1) ?>
      </div>
      <div style="flex:1;min-width:0;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
          <span style="font-size:14px;font-weight:700;color:var(--text-primary);"><?= htmlspecialchars($emp['name']) ?></span>
          <span style="font-size:10px;color:var(--text-muted);"><?= htmlspecialchars($emp['department']) ?></span>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
          <span style="font-size:12px;color:var(--text-muted);font-weight:600;text-decoration:line-through;text-decoration-color:rgba(255,255,255,.3);"><?= number_format($emp['prev_score'],2) ?></span>
          <span style="font-size:11px;color:var(--text-muted);">→</span>
          <span style="font-size:13px;font-weight:800;color:<?= $color ?>;"><?= number_format($emp['kpi_score'],2) ?></span>
          <div style="flex:1;max-width:120px;height:5px;background:rgba(255,255,255,.07);border-radius:3px;overflow:hidden;">
            <div style="height:100%;width:<?= $deltaPct ?>%;background:linear-gradient(90deg,#10b981,#34d399);border-radius:3px;"></div>
          </div>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;align-items:center;gap:3px;flex-shrink:0;">
        <div style="display:inline-flex;align-items:center;gap:4px;padding:6px 12px;background:rgba(16,185,129,.15);border:1px solid rgba(16,185,129,.3);border-radius:20px;font-size:13px;font-weight:800;color:#34d399;">
          <span style="font-size:14px;">↑</span>+<?= number_format($emp['change'],2) ?>
        </div>
        <span style="font-size:10px;color:var(--text-muted);">improvement</span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- 6. WORKFORCE OVERVIEW ──────────────────────────────────────── -->
<div class="dsh-two-col dsh-reveal" style="transition-delay:.25s;">

  <!-- Performance Distribution -->
  <div class="dsh-card" style="background:linear-gradient(135deg,#0f1a2e 0%,#1a1f35 50%,#0f1a2e 100%);border-color:rgba(59,130,246,.2);position:relative;overflow:hidden;">
    <div style="position:absolute;top:-40px;right:-40px;width:180px;height:180px;background:radial-gradient(circle,rgba(59,130,246,.07),transparent 70%);pointer-events:none;border-radius:50%;"></div>
    <div style="position:absolute;bottom:-30px;left:-30px;width:140px;height:140px;background:radial-gradient(circle,rgba(34,197,94,.05),transparent 70%);pointer-events:none;border-radius:50%;"></div>
    <div class="dsh-section-head">
      <div class="dsh-section-icon" style="background:rgba(59,130,246,.12);">📊</div>
      <div><div class="dsh-section-title">Performance Distribution</div><div class="dsh-section-sub">Number of staff per KPI rating tier</div></div>
    </div>
    <hr class="dsh-divider">
    <div class="dsh-chart-wrap"><canvas id="dshDistributionChart"></canvas></div>
    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:14px;">
      <?php foreach ([['Excellent','#22c55e','4.5–5.0'],['Good','#3b82f6','3.5–4.4'],['Satisfactory','#f59e0b','2.5–3.4'],['Poor','#ef4444','1.5–2.4'],['Very Poor','#7f1d1d','1.0–1.4']] as [$lbl,$clr,$rng]): ?>
      <div style="display:flex;align-items:center;gap:5px;font-size:11px;color:var(--text-secondary);">
        <div style="width:9px;height:9px;border-radius:3px;background:<?= $clr ?>;flex-shrink:0;"></div>
        <span><?= $lbl ?></span><span style="color:var(--text-muted);">(<?= $rng ?>)</span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Department Comparison -->
  <div class="dsh-card" style="background:linear-gradient(135deg,#0f1a2e 0%,#1a1535 50%,#0f1f2e 100%);border-color:rgba(6,182,212,.2);position:relative;overflow:hidden;">
    <div style="position:absolute;top:-40px;right:-40px;width:180px;height:180px;background:radial-gradient(circle,rgba(6,182,212,.07),transparent 70%);pointer-events:none;border-radius:50%;"></div>
    <div style="position:absolute;bottom:-30px;left:-30px;width:140px;height:140px;background:radial-gradient(circle,rgba(245,158,11,.05),transparent 70%);pointer-events:none;border-radius:50%;"></div>
    <div class="dsh-section-head">
      <div class="dsh-section-icon" style="background:rgba(6,182,212,.12);">🏢</div>
      <div><div class="dsh-section-title">Department / Role Comparison</div><div class="dsh-section-sub">Average KPI score by role group</div></div>
    </div>
    <hr class="dsh-divider">
    <?php if (count($dept_data) > 0):
      $max_dept = max(array_map(fn($d) => (float)($d['avg_score']??0), $dept_data)); ?>
    <div class="dsh-dept-list">
      <?php foreach ($dept_data as $dept):
        $score = round((float)($dept['avg_score']??0),2);
        $color = dshScoreColor($score);
        $barW  = $max_dept > 0 ? round(($score/$max_dept)*100,1) : 0; ?>
      <div class="dsh-dept-row">
        <div class="dsh-dept-header">
          <span class="dsh-dept-name"><?= htmlspecialchars($dept['department']) ?></span>
          <span class="dsh-dept-meta"><?= $dept['emp_count'] ?> staff &mdash; <strong style="color:<?= $color ?>;"><?= number_format($score,2) ?></strong></span>
        </div>
        <div class="dsh-dept-bar-track"><div class="dsh-dept-bar-fill" style="background:<?= $color ?>;" data-width="<?= $barW ?>%"></div></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="dsh-empty-state"><div class="dsh-empty-icon">🏢</div><div class="dsh-empty-title">No department data</div><div class="dsh-empty-desc">Department KPI data will appear here once scores are recorded.</div></div>
    <?php endif; ?>
  </div>

</div>

<!-- 7. REPORTING INSIGHTS ──────────────────────────────────────── -->
<div class="dsh-two-col dsh-reveal" style="transition-delay:.3s;">

  <!-- At-Risk Report -->
  <div class="dsh-card" id="at-risk-report">
    <div class="dsh-section-head">
      <div class="dsh-section-icon" style="background:rgba(239,68,68,.12);">🚨</div>
      <div><div class="dsh-section-title">At-Risk Staff Report</div><div class="dsh-section-sub">Priority staff for performance intervention</div></div>
      <div class="dsh-section-badge" style="background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.25);color:#f87171;"><?= $at_risk_count ?></div>
    </div>
    <hr class="dsh-divider">
    <?php if (count($needs_improvement) > 0): ?>
    <div style="display:flex;flex-direction:column;gap:10px;">
      <?php foreach ($needs_improvement as $emp):
        $risk  = dshRiskBadge($emp['kpi_score']);
        $trend = predictPerformanceRisk($conn, $emp['employee_id']);
        $color = dshScoreColor($emp['kpi_score']);
        $trendClr = $trend['trend']==='Improving'?'#22c55e':($trend['trend']==='Declining'?'#ef4444':'#f59e0b');
        $action = match($trend['risk_level']) {
            'Critical' => 'Immediate 1-on-1 coaching session and performance improvement plan required.',
            'High'     => 'Schedule performance review and set clear improvement targets.',
            default    => 'Monitor weekly progress and provide additional training support.',
        };
      ?>
      <div style="background:<?= $risk['bg'] ?>;border:1px solid <?= $risk['color'] ?>33;border-radius:10px;padding:13px 15px;">
        <div style="display:flex;align-items:flex-start;gap:10px;">
          <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
              <a href="index.php?page=profiles&emp_id=<?= $emp['employee_id'] ?>" style="font-size:13px;font-weight:700;color:var(--text-primary);text-decoration:none;"><?= htmlspecialchars($emp['name']) ?></a>
              <span class="dsh-risk-badge" style="color:<?= $risk['color'] ?>;background:transparent;border-color:<?= $risk['color'] ?>;"><?= $risk['label'] ?> Risk</span>
            </div>
            <div style="font-size:11px;color:var(--text-secondary);"><?= htmlspecialchars($emp['department']) ?> &mdash; Trend: <strong style="color:<?= $trendClr ?>"><?= $trend['trend'] ?></strong></div>
          </div>
          <div style="flex-shrink:0;text-align:right;">
            <div style="font-size:10px;color:var(--text-muted);margin-bottom:2px;">KPI</div>
            <div style="font-family:'Sora',sans-serif;font-size:18px;font-weight:700;color:<?= $color ?>;"><?= number_format($emp['kpi_score'],2) ?></div>
          </div>
        </div>
        <?php if ($trend['risk_level'] !== 'Unknown'): ?>
        <div style="margin-top:8px;padding-top:8px;border-top:1px solid rgba(255,255,255,.07);font-size:11px;color:var(--text-secondary);">
          <strong style="color:var(--text-primary);">Recommended action:</strong> <?= $action ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="dsh-empty-state"><div class="dsh-empty-icon">✅</div><div class="dsh-empty-title">No at-risk staff</div><div class="dsh-empty-desc">All employees are performing within acceptable ranges.</div></div>
    <?php endif; ?>
  </div>

  <!-- Training Needs -->
  <div class="dsh-card">
    <div class="dsh-section-head">
      <div class="dsh-section-icon" style="background:rgba(99,102,241,.12);">🎓</div>
      <div><div class="dsh-section-title">Training Needs Summary</div><div class="dsh-section-sub">Supervisor-recommended training for at-risk staff</div></div>
      <div class="dsh-section-badge" style="background:rgba(99,102,241,.1);border-color:rgba(99,102,241,.25);color:#a5b4fc;"><?= count($training_needs) ?></div>
    </div>
    <hr class="dsh-divider">
    <?php if (count($training_needs) > 0):
      $grad_colors = ['#6366f1','#8b5cf6','#06b6d4','#10b981','#f59e0b'];
    ?>
    <div class="dsh-training-list">
      <?php foreach ($training_needs as $idx => $item):
        $color = dshScoreColor($item['kpi_score']);
        $gc    = $grad_colors[$idx % count($grad_colors)]; ?>
      <div style="background:linear-gradient(135deg,<?= $gc ?>18,<?= $gc ?>08);border:1px solid <?= $gc ?>33;border-left:3px solid <?= $gc ?>;border-radius:10px;padding:13px 15px;transition:background .2s;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:6px;">
          <span style="font-size:13px;font-weight:700;color:var(--text-primary);"><?= htmlspecialchars($item['name']) ?></span>
          <span style="font-size:11px;font-weight:700;padding:2px 9px;border-radius:20px;color:#fff;flex-shrink:0;background:<?= $color ?>;"><?= number_format($item['kpi_score'],2) ?></span>
        </div>
        <div style="font-size:12px;color:var(--text-secondary);line-height:1.5;margin-bottom:8px;"><?= htmlspecialchars($item['recommendation']) ?></div>
        <div style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:6px;background:<?= $gc ?>18;border:1px solid <?= $gc ?>33;font-size:10px;font-weight:600;color:<?= $gc ?>;">
          <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          Training Required
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="dsh-empty-state"><div class="dsh-empty-icon">🎓</div><div class="dsh-empty-title">No training recommendations</div><div class="dsh-empty-desc">Add supervisor feedback with training recommendations for at-risk staff to see them here.</div></div>
    <?php endif; ?>
  </div>

</div>

<!-- 8. FILTER + STAFF TABLE ────────────────────────────────────── -->
<div class="dsh-reveal" style="transition-delay:.35s;">
  <div class="dsh-filter-bar" style="margin-bottom:16px;">
    <div class="dsh-filter-group" style="flex:1;">
      <label class="dsh-filter-label">Search Staff</label>
      <input type="text" id="dshSearchInput" class="dsh-filter-input dsh-filter-search" placeholder="Search by name..." oninput="dshApplyFilters()">
    </div>
    <div class="dsh-filter-group">
      <label class="dsh-filter-label">KPI Range</label>
      <div style="display:flex;align-items:center;gap:6px;">
        <input type="number" id="dshKpiMin" min="1" max="5" step="0.1" placeholder="Min" class="dsh-filter-input" style="width:70px;" oninput="dshApplyFilters()">
        <span style="color:var(--text-muted);font-size:12px;">–</span>
        <input type="number" id="dshKpiMax" min="1" max="5" step="0.1" placeholder="Max" class="dsh-filter-input" style="width:70px;" oninput="dshApplyFilters()">
      </div>
    </div>
    <div class="dsh-filter-group">
      <label class="dsh-filter-label">Department</label>
      <select id="dshDeptFilter" class="dsh-filter-select" onchange="dshApplyFilters()">
        <option value="">All Departments</option>
        <?php
          $dept_options = array_unique(array_column($employee_data,'department'));
          sort($dept_options);
          foreach ($dept_options as $d) echo "<option value='".htmlspecialchars($d)."'>".htmlspecialchars($d)."</option>";
        ?>
      </select>
    </div>
    <div class="dsh-filter-group">
      <label class="dsh-filter-label">KPI Tier</label>
      <select id="dshTierFilter" class="dsh-filter-select" onchange="dshApplyFilters()">
        <option value="">All Tiers</option>
        <option value="excellent">Excellent (≥4.5)</option>
        <option value="good">Good (3.5–4.4)</option>
        <option value="satisfactory">Satisfactory (2.5–3.4)</option>
        <option value="poor">Poor (1.5–2.4)</option>
        <option value="verypoor">Very Poor (&lt;1.5)</option>
      </select>
    </div>
    <div class="dsh-filter-group">
      <label class="dsh-filter-label">Sort By</label>
      <select id="dshSortSelect" class="dsh-filter-select" onchange="dshApplyFilters()">
        <option value="score-desc">KPI Score (High → Low)</option>
        <option value="score-asc">KPI Score (Low → High)</option>
        <option value="name-asc">Name (A → Z)</option>
        <option value="name-desc">Name (Z → A)</option>
      </select>
    </div>
    <div class="dsh-filter-actions">
      <span class="dsh-filter-count" id="dshFilterCount"><?= $total_employees ?> staff shown</span>
      <button class="dsh-filter-reset" onclick="dshResetFilters()">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4"/></svg>
        Reset
      </button>
    </div>
  </div>

  <div class="dsh-card" style="padding:0;">
    <div style="padding:20px 24px 0;display:flex;align-items:center;gap:10px;">
      <div class="dsh-section-icon" style="background:rgba(59,130,246,.1);">👥</div>
      <div>
        <div class="dsh-section-title">All Sales Assistants</div>
        <div class="dsh-section-sub">Complete staff KPI overview for <?= htmlspecialchars($period_label) ?></div>
      </div>
    </div>
    <div class="dsh-staff-table-wrap" style="padding:12px 0 4px;">
      <table class="dsh-staff-table" id="dshStaffTable">
        <thead>
          <tr>
            <th onclick="dshSortTable('name')" style="padding-left:24px;">Name <span class="dsh-sort-arrow">↕</span></th>
            <th onclick="dshSortTable('dept')">Department <span class="dsh-sort-arrow">↕</span></th>
            <th onclick="dshSortTable('score')">KPI Score <span class="dsh-sort-arrow">↕</span></th>
            <th>Rating</th><th>Progress</th><th>Classification</th>
          </tr>
        </thead>
        <tbody id="dshStaffTbody">
          <?php foreach ($employee_data as $emp):
            $color  = dshScoreColor($emp['kpi_score']);
            $pct    = dshScorePct($emp['kpi_score']);
            $avatar = buildAvatarUrl($emp['name']);
            $initials = strtoupper(substr($emp['name'],0,1)) . (strpos($emp['name'],' ')!==false ? strtoupper(substr($emp['name'],strpos($emp['name'],' ')+1,1)) : '');
            $classification = KPICalculator::classifyPerformance($emp['kpi_score']);
            $tier = $emp['kpi_score']>=4.5?'excellent':($emp['kpi_score']>=3.5?'good':($emp['kpi_score']>=2.5?'satisfactory':($emp['kpi_score']>=1.5?'poor':'verypoor')));
          ?>
          <tr data-name="<?= strtolower(htmlspecialchars($emp['name'])) ?>"
              data-dept="<?= strtolower(htmlspecialchars($emp['department'])) ?>"
              data-score="<?= $emp['kpi_score'] ?>" data-tier="<?= $tier ?>">
            <td style="padding-left:24px;">
              <a href="index.php?page=profiles&emp_id=<?= $emp['employee_id'] ?>" class="dsh-tbl-name">
                <img src="<?= htmlspecialchars($avatar) ?>" alt="<?= htmlspecialchars($emp['name']) ?>" class="dsh-tbl-avatar" style="object-fit:cover;"
                     onerror="this.outerHTML='<span class=\'dsh-tbl-avatar\'><?= htmlspecialchars($initials) ?></span>'">
                <?= htmlspecialchars($emp['name']) ?>
              </a>
            </td>
            <td><?= htmlspecialchars($emp['department']) ?></td>
            <td>
              <span style="font-family:'Sora',sans-serif;font-size:15px;font-weight:700;color:<?= $color ?>;"><?= number_format($emp['kpi_score'],2) ?></span>
              <span style="font-size:10px;color:var(--text-muted);margin-left:3px;">/ 5.00</span>
            </td>
            <td><span class="dsh-tbl-badge" style="background:<?= $color ?>;"><?= htmlspecialchars($emp['rating']) ?></span></td>
            <td>
              <div class="dsh-tbl-bar-wrap">
                <div class="dsh-tbl-bar-track"><div class="dsh-tbl-bar-fill" style="background:<?= $color ?>;" data-width="<?= $pct ?>%"></div></div>
                <span class="dsh-tbl-score-val" style="font-size:11px;color:var(--text-secondary);"><?= $pct ?>%</span>
              </div>
            </td>
            <td style="font-size:11px;color:var(--text-secondary);"><?= htmlspecialchars($classification) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if ($total_employees === 0): ?>
      <div class="dsh-empty-state"><div class="dsh-empty-icon">👥</div><div class="dsh-empty-title">No staff data</div><div class="dsh-empty-desc">No active employees found for this evaluation period.</div></div>
      <?php endif; ?>
    </div>
    <div id="dshNoResults" style="display:none;" class="dsh-empty-state">
      <div class="dsh-empty-icon">🔍</div><div class="dsh-empty-title">No results match your filters</div><div class="dsh-empty-desc">Try adjusting your search or filter criteria.</div>
    </div>
    <div style="padding:12px 24px 16px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
      <span style="font-size:12px;color:var(--text-muted);" id="dshTableFooter">Showing <?= $total_employees ?> of <?= $total_employees ?> staff</span>
      <a href="index.php?page=report" style="font-size:12px;color:var(--accent);text-decoration:none;font-weight:600;">View Full Report →</a>
    </div>
  </div>
</div>

</div><!-- /dsh-page -->
</div><!-- /content -->

<script>
(function(){
'use strict';
/***************************
   Title: Chart.js — Bar Chart with Custom Tooltip
   Author: Chart.js Contributors
   Date: 2023
   Type: Source Code (MIT License)
   Availability: https://github.com/chartjs/Chart.js
***************************/
/* ── Chart.js ───────────────────────────────────────────────── */
const distCtx = document.getElementById('dshDistributionChart');
if (distCtx) {
    new Chart(distCtx.getContext('2d'), {
        type:'bar',
        data:{
            labels:Object.keys(<?= json_encode($perf_dist) ?>),
            datasets:[{label:'Staff Count',data:Object.values(<?= json_encode($perf_dist) ?>),
                backgroundColor:['#22c55e','#3b82f6','#f59e0b','#ef4444','#7f1d1d'],
                borderRadius:7,barThickness:36,borderSkipped:false}]
        },
        options:{
            responsive:true,maintainAspectRatio:false,
            plugins:{legend:{display:false},tooltip:{backgroundColor:'#111c2e',borderColor:'#243047',borderWidth:1,titleColor:'#f0f4ff',bodyColor:'#8b9bbf',
                callbacks:{label:c=>` ${c.parsed.y} employee${c.parsed.y!==1?'s':''}`}}},
            scales:{
                y:{beginAtZero:true,ticks:{color:'#4d5f80',font:{size:11},stepSize:1},grid:{color:'#243047',drawBorder:false}},
                x:{ticks:{color:'#8b9bbf',font:{size:11}},grid:{display:false}}
            }
        }
    });
}

/* ── Animate bars & rings ───────────────────────────────────── */
function animateBars() {
    document.querySelectorAll('[data-width]').forEach(el => requestAnimationFrame(()=>{ el.style.width=el.getAttribute('data-width'); }));
}
function animateRings() {
    document.querySelectorAll('.dsh-ring-fg').forEach(ring => {
        const off = parseFloat(ring.getAttribute('data-offset')||ring.style.strokeDashoffset);
        ring.style.strokeDashoffset='134';
        setTimeout(()=>{ ring.style.strokeDashoffset=off; },100);
    });
}

/* ── Scroll reveal ──────────────────────────────────────────── */
new IntersectionObserver(entries => {
    entries.forEach(e => { if(e.isIntersecting){ e.target.classList.add('dsh-visible'); animateBars(); } });
},{threshold:0.08}).observe = (function(orig){
    return function(el){ orig.call(this,el); };
})(IntersectionObserver.prototype.observe);

const revealObs = new IntersectionObserver(entries => {
    entries.forEach(e => { if(e.isIntersecting){ e.target.classList.add('dsh-visible'); revealObs.unobserve(e.target); animateBars(); }});
},{threshold:0.08});
document.querySelectorAll('.dsh-reveal').forEach(el => revealObs.observe(el));

/* ── Row staggered reveal ───────────────────────────────────── */
const rowObs = new IntersectionObserver(entries => {
    entries.forEach(e => {
        if(e.isIntersecting){
            e.target.querySelectorAll('.dsh-performer-row,.dsh-risk-row').forEach((r,i)=>setTimeout(()=>r.classList.add('dsh-row-visible'),i*70));
            rowObs.unobserve(e.target);
        }
    });
},{threshold:0.1});
document.querySelectorAll('.dsh-two-col .dsh-card,.dsh-card').forEach(c=>rowObs.observe(c));

/* ── Init ───────────────────────────────────────────────────── */
window.addEventListener('DOMContentLoaded',()=>{ animateBars(); animateRings(); setTimeout(animateRings,300); dshApplyFilters(); });
setTimeout(animateBars,200);

/* ── Filter & sort ──────────────────────────────────────────── */
let dshSortCol='score', dshSortDir='desc';

function dshApplyFilters(){
    const search  = document.getElementById('dshSearchInput').value.trim().toLowerCase();
    const dept    = document.getElementById('dshDeptFilter').value.trim().toLowerCase();
    const tier    = document.getElementById('dshTierFilter').value.trim();
    const kpiMin  = parseFloat(document.getElementById('dshKpiMin')?.value)||null;
    const kpiMax  = parseFloat(document.getElementById('dshKpiMax')?.value)||null;
    const [col,dir] = document.getElementById('dshSortSelect').value.split('-');
    dshSortCol=col; dshSortDir=dir;

    const tbody = document.getElementById('dshStaffTbody');
    const rows  = Array.from(tbody.querySelectorAll('tr'));
    let visible = 0;

    rows.forEach(row => {
        const name  = row.getAttribute('data-name')||'';
        const rdept = row.getAttribute('data-dept')||'';
        const rtier = row.getAttribute('data-tier')||'';
        const rscore= parseFloat(row.getAttribute('data-score')||0);
        const show  = (!search||name.includes(search)) && (!dept||rdept.includes(dept)) && (!tier||rtier===tier) && (!kpiMin||rscore>=kpiMin) && (!kpiMax||rscore<=kpiMax);
        row.classList.toggle('dsh-hidden',!show);
        if(show) visible++;
    });

    const visibleRows = rows.filter(r=>!r.classList.contains('dsh-hidden'));
    visibleRows.sort((a,b)=>{
        if(dshSortCol==='score'){ const av=parseFloat(a.getAttribute('data-score')||0),bv=parseFloat(b.getAttribute('data-score')||0); return dshSortDir==='asc'?av-bv:bv-av; }
        if(dshSortCol==='name') { const an=a.getAttribute('data-name')||'',bn=b.getAttribute('data-name')||''; return dshSortDir==='asc'?an.localeCompare(bn):bn.localeCompare(an); }
        return 0;
    });
    visibleRows.forEach(r=>tbody.appendChild(r));

    const total=rows.length;
    document.getElementById('dshFilterCount').textContent=`${visible} of ${total} staff shown`;
    document.getElementById('dshTableFooter').textContent=`Showing ${visible} of ${total} staff`;
    document.getElementById('dshNoResults').style.display=visible===0?'block':'none';
    setTimeout(animateBars,50);
}

function dshResetFilters(){
    ['dshSearchInput','dshDeptFilter','dshTierFilter','dshKpiMin','dshKpiMax'].forEach(id=>document.getElementById(id).value='');
    document.getElementById('dshSortSelect').value='score-desc';
    dshApplyFilters();
}

window.dshApplyFilters=dshApplyFilters;
window.dshResetFilters=dshResetFilters;

/* ── Count-up animation ─────────────────────────────────────── */
document.querySelectorAll('.dsh-stat-value').forEach(el=>{
    const target=parseFloat(el.textContent);
    if(isNaN(target)||target===0) return;
    const isDecimal=el.textContent.includes('.');
    const decimals=isDecimal?(el.textContent.split('.')[1]||'').length:0;
    let current=0;
    const step=target/30;
    const timer=setInterval(()=>{
        current=Math.min(current+step,target);
        el.textContent=isDecimal?current.toFixed(decimals):Math.round(current);
        if(current>=target){ el.textContent=isDecimal?target.toFixed(decimals):target; clearInterval(timer); }
    },28);
});

})();
</script>
