<?php
/**
 * SAKMS – PDF Export
 * Renders a styled HTML report page and prints it as a PDF via window.print().
 * Encoding-safe: all text output is UTF-8 with htmlspecialchars().
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/kpi_calculator.php';

requireLogin();

// ── DATE RANGE ────────────────────────────────────────────────────────────────
$bounds   = $conn->query("SELECT MIN(date_recorded) AS mn, MAX(date_recorded) AS mx FROM kpi_score")->fetch_assoc();
$db_min   = ($bounds && $bounds['mn'] && $bounds['mn'] !== '0000-00-00') ? $bounds['mn'] : '2022-01-01';
$db_max   = ($bounds && $bounds['mx'] && $bounds['mx'] !== '0000-00-00') ? $bounds['mx'] : date('Y-12-31');

$date_from = $_GET['date_from'] ?? $db_min;
$date_to   = $_GET['date_to']   ?? $db_max;

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from) || $date_from === '0000-00-00') $date_from = $db_min;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to) || $date_to === '0000-00-00')     $date_to   = $db_max;
if ($date_from > $date_to) [$date_from, $date_to] = [$date_to, $date_from];

// ── KPI CALCULATION ───────────────────────────────────────────────────────────

function exportCalcKPI($conn, $staff_id, $from, $to): array {
    $stmt = $conn->prepare("
        SELECT ksec.section_name AS section,
               kg.weight_percentage / 100 AS weight,
               AVG(ks.score) AS avg_score
        FROM kpi_score ks
        JOIN kpi_item    ki   ON ks.kpi_item_id  = ki.kpi_item_id
        JOIN kpi_group   kg   ON ki.kpi_group_id = kg.kpi_group_id
        JOIN kpi_section ksec ON kg.section_id   = ksec.section_id
        WHERE ks.staff_id = ? AND ks.date_recorded BETWEEN ? AND ?
        GROUP BY kg.kpi_group_id
    ");
    $stmt->bind_param("iss", $staff_id, $from, $to);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $s1 = $s2 = 0.0;
    foreach ($rows as $row) {
        $w = (float)$row['avg_score'] * (float)$row['weight'];
        if ($row['section'] === 'Core Competencies') $s1 += $w; else $s2 += $w;
    }
    $overall = round(max(0, min(5, $s1 + $s2)), 2);
    return ['overall' => $overall, 'rating' => KPICalculator::getRatingLabel($overall)];
}

// ── FETCH ALL STAFF ───────────────────────────────────────────────────────────
// Load active staff with KPI records in date range, calculate scores, and rank by performance
$stmt = $conn->prepare("
    SELECT DISTINCT s.staff_id AS employee_id, s.full_name AS name,
           s.staff_code AS staff_code, s.role, s.status
    FROM staff s
    JOIN kpi_score ks ON ks.staff_id = s.staff_id
    WHERE s.status = 'Active' AND ks.date_recorded BETWEEN ? AND ?
    ORDER BY s.full_name ASC
");
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$staff_result = $stmt->get_result();

$all_data = [];
while ($emp = $staff_result->fetch_assoc()) {
    $kpi = exportCalcKPI($conn, $emp['employee_id'], $date_from, $date_to);
    $emp['kpi_score'] = $kpi['overall'];
    $emp['rating']    = $kpi['rating'];
    $all_data[] = $emp;
}
usort($all_data, fn($a, $b) => $b['kpi_score'] <=> $a['kpi_score']);

// ── SEGMENTS ─────────────────────────────────────────────────────────────────
// Calculate performance segmentation: top performers, at-risk, training needs, tier distribution
$total_staff       = count($all_data);
$avg_score         = $total_staff ? round(array_sum(array_column($all_data, 'kpi_score')) / $total_staff, 2) : 0;
$top_performers    = array_values(array_filter($all_data, fn($e) => $e['kpi_score'] >= 4.0));
$at_risk           = array_values(array_filter($all_data, fn($e) => $e['kpi_score'] < 3.0));
$training_needs    = array_values(array_filter($all_data, fn($e) => $e['kpi_score'] < 3.5));
$tier_excellent    = count(array_filter($all_data, fn($e) => $e['kpi_score'] >= 4.5));
$tier_good         = count(array_filter($all_data, fn($e) => $e['kpi_score'] >= 3.5 && $e['kpi_score'] < 4.5));
$tier_satisfactory = count(array_filter($all_data, fn($e) => $e['kpi_score'] >= 2.5 && $e['kpi_score'] < 3.5));
$tier_poor         = count(array_filter($all_data, fn($e) => $e['kpi_score'] >= 1.5 && $e['kpi_score'] < 2.5));
$tier_verypoor     = count(array_filter($all_data, fn($e) => $e['kpi_score'] < 1.5));

// ── LABELS ────────────────────────────────────────────────────────────────────
// Format dates and prepare display labels for PDF report
$fmt_from    = date('d M Y', strtotime($date_from));
$fmt_to      = date('d M Y', strtotime($date_to));
$is_all      = ($date_from === $db_min && $date_to === $db_max);
$range_label = $is_all ? 'All Years (' . $fmt_from . ' &ndash; ' . $fmt_to . ')' : $fmt_from . ' &ndash; ' . $fmt_to;
$generated   = date('d M Y, h:i A');

// ── HELPERS ───────────────────────────────────────────────────────────────────
/***************************
Title: How to color MySQL data in PHP, based on certain conditions like marks obtained and categorizing them based on legend?
Author: Suchitra
Date: 26th March 2015
Type: Coloring Data Based on Conditions
Availability: https://www.nngroup.com/articles/semantic-color-in-visualization/
***************************/

function ratingColor(float $score): string {
    if ($score >= 4.5) return '#15803d';
    if ($score >= 3.5) return '#1d4ed8';
    if ($score >= 2.5) return '#b45309';
    if ($score >= 1.5) return '#b91c1c';
    return '#7f1d1d';
}
function ratingBg(float $score): string {
    if ($score >= 4.5) return '#dcfce7';
    if ($score >= 3.5) return '#dbeafe';
    if ($score >= 2.5) return '#fef3c7';
    if ($score >= 1.5) return '#fee2e2';
    return '#fecaca';
}
function ratingBorder(float $score): string {
    if ($score >= 4.5) return '#86efac';
    if ($score >= 3.5) return '#93c5fd';
    if ($score >= 2.5) return '#fcd34d';
    if ($score >= 1.5) return '#fca5a5';
    return '#f87171';
}
function riskLabel(float $score): string {
    if ($score < 2.0) return 'Critical';
    if ($score < 2.5) return 'High';
    return 'Medium';
}
function riskColor(float $score): string {
    if ($score < 2.0) return '#b91c1c';
    if ($score < 2.5) return '#c2410c';
    return '#b45309';
}
function trainingPriority(float $score): string {
    if ($score < 2.0) return 'Urgent';
    if ($score < 2.5) return 'High';
    if ($score < 3.0) return 'High';
    return 'Recommended';
}
function trainingFocus(float $score): string {
    if ($score < 2.0) return 'Formal PIP + intensive coaching';
    if ($score < 2.5) return 'Weekly 1-on-1 coaching & goal setting';
    if ($score < 3.0) return 'Targeted skill development & mentoring';
    return 'Continuous improvement & KPI coaching';
}
function scorePct(float $score): int {
    return (int) round(($score / 5) * 100);
}
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SAKMS &ndash; Performance &amp; Training Report</title>
<!-- 
/***************************
Title: Printing with CSS Media Queries
Author: MDN Web Docs Contributors
Date: 07th November 2025
Type: CSS Media Queries for Print
Availability: https://developer.mozilla.org/en-US/docs/Web/CSS/Guides/Media_queries/Printing
***************************/
-->
<style>
/* ═══════════════════════════════════════════════════════
   SAKMS Business Report — Print-Optimized Stylesheet
   Professional corporate report style, A4 portrait
═══════════════════════════════════════════════════════ */

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 10px;
    color: #1a1a2e;
    background: #e8ecf0;
    line-height: 1.45;
}

/* ── Report page (A4 width, auto height) ─────────────── */
.rp {
    width: 210mm;
    margin: 0 auto;
    background: #fff;
}

/* ── Top accent strip ────────────────────────────────── */
.rp-strip {
    height: 5px;
    background: linear-gradient(90deg, #1e3a5f 0%, #2563eb 60%, #0ea5e9 100%);
}

/* ── Report header ───────────────────────────────────── */
.rp-header {
    display: flex;
    align-items: stretch;
    justify-content: space-between;
    padding: 14px 18px 12px;
    border-bottom: 1.5px solid #1e3a5f;
}
.rp-header-brand {
    display: flex;
    align-items: flex-start;
    gap: 11px;
}
.rp-brand-icon {
    width: 36px; height: 36px;
    background: #1e3a5f;
    border-radius: 4px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 14px; font-weight: 800;
    flex-shrink: 0; margin-top: 1px;
}
.rp-brand-org {
    font-size: 8px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    margin-bottom: 3px;
}
.rp-brand-title {
    font-size: 17px;
    font-weight: 800;
    color: #1e3a5f;
    letter-spacing: -0.3px;
    line-height: 1.1;
}
.rp-brand-sub {
    font-size: 9px;
    color: #64748b;
    margin-top: 2px;
}
.rp-header-meta {
    text-align: right;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: flex-end;
    gap: 4px;
}
.rp-meta-row {
    font-size: 8.5px;
    color: #475569;
}
.rp-meta-row strong { color: #1a1a2e; font-weight: 700; }
.rp-period-badge {
    display: inline-block;
    background: #1e3a5f;
    color: #fff;
    font-size: 8.5px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 2px;
    letter-spacing: 0.3px;
}
.rp-confidential {
    font-size: 7.5px;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

/* ── Section wrapper ─────────────────────────────────── */
.rp-body { padding: 0 18px; }

.rp-section { padding: 12px 0 0; }
.rp-section + .rp-section { border-top: 1px solid #e2e8f0; }

.rp-section-title {
    font-size: 8.5px;
    font-weight: 700;
    color: #1e3a5f;
    text-transform: uppercase;
    letter-spacing: 1.1px;
    padding-left: 8px;
    border-left: 3px solid #2563eb;
    margin-bottom: 9px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.rp-section-title .title-note {
    font-size: 8px;
    font-weight: 500;
    color: #94a3b8;
    text-transform: none;
    letter-spacing: 0;
}

/* ── Executive summary row ───────────────────────────── */
.rp-summary-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    padding-bottom: 12px;
}
.rp-metric {
    border: 1px solid #e2e8f0;
    border-top: 3px solid;
    padding: 8px 10px 7px;
    background: #fafbfc;
}
.rp-metric.c-blue  { border-top-color: #2563eb; }
.rp-metric.c-teal  { border-top-color: #0891b2; }
.rp-metric.c-green { border-top-color: #15803d; }
.rp-metric.c-red   { border-top-color: #b91c1c; }
.rp-metric-label {
    font-size: 7.5px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 5px;
}
.rp-metric-value {
    font-size: 20px;
    font-weight: 800;
    color: #1a1a2e;
    line-height: 1;
    margin-bottom: 3px;
}
.rp-metric-sub {
    font-size: 8px;
    color: #94a3b8;
}
.rp-metric-sub.colored { font-weight: 700; }

/* ── Two-column layout ───────────────────────────────── */
.rp-two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    padding-bottom: 12px;
}
.rp-col-header {
    font-size: 8px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 6px;
    padding-bottom: 4px;
    border-bottom: 1px solid #e2e8f0;
}

/* ── Inline distribution bar rows ────────────────────── */
.dist-row {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 3.5px 0;
    border-bottom: 1px solid #f1f5f9;
}
.dist-row:last-child { border-bottom: none; }
.dist-label {
    width: 76px;
    font-size: 9px;
    font-weight: 600;
    flex-shrink: 0;
}
.dist-range {
    width: 48px;
    font-size: 8px;
    color: #94a3b8;
    flex-shrink: 0;
}
.dist-bar-track {
    flex: 1;
    height: 7px;
    background: #f1f5f9;
    border-radius: 2px;
    overflow: hidden;
}
.dist-bar-fill { height: 100%; border-radius: 2px; }
.dist-count-pct {
    font-size: 9px;
    font-weight: 700;
    min-width: 24px;
    text-align: right;
    flex-shrink: 0;
}
.dist-pct-text {
    font-size: 8px;
    color: #94a3b8;
    min-width: 28px;
    text-align: right;
    flex-shrink: 0;
}

/* ── Insights list ───────────────────────────────────── */
.insight-item {
    display: flex;
    gap: 8px;
    padding: 4px 0;
    border-bottom: 1px solid #f1f5f9;
    align-items: flex-start;
}
.insight-item:last-child { border-bottom: none; }
.insight-dot {
    width: 5px; height: 5px;
    border-radius: 50%;
    margin-top: 3px;
    flex-shrink: 0;
}
.insight-key {
    font-size: 8px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    width: 80px;
    flex-shrink: 0;
}
.insight-val {
    font-size: 9px;
    color: #1a1a2e;
    flex: 1;
}
.insight-val strong { font-weight: 700; }

/* ── Tables ──────────────────────────────────────────── */
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 9px;
}
thead tr { background: #1e3a5f; }
thead th {
    padding: 5.5px 8px;
    font-size: 8px;
    font-weight: 700;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-align: left;
    white-space: nowrap;
}
thead th.th-c { text-align: center; }
thead th.th-r { text-align: right; }
tbody tr { border-bottom: 1px solid #f1f5f9; }
tbody tr:nth-child(even) { background: #f8fafc; }
tbody td {
    padding: 5px 8px;
    color: #1a1a2e;
    vertical-align: middle;
}
tbody td.td-c { text-align: center; }
tbody td.td-r { text-align: right; }
tbody td.td-muted { color: #64748b; font-size: 8.5px; }
tfoot tr { background: #f1f5f9; }
tfoot td {
    padding: 5px 8px;
    font-size: 8px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    border-top: 1.5px solid #cbd5e1;
}

/* ── Score display (compact) ─────────────────────────── */
.score-val {
    font-size: 13px;
    font-weight: 800;
    line-height: 1;
}
.score-denom {
    font-size: 8px;
    color: #94a3b8;
    font-weight: 400;
}

/* ── Mini progress bar (in table cell) ──────────────── */
.mini-bar {
    display: flex;
    align-items: center;
    gap: 5px;
}
.mini-bar-track {
    width: 52px;
    height: 5px;
    background: #e2e8f0;
    border-radius: 2px;
    overflow: hidden;
    flex-shrink: 0;
}
.mini-bar-fill { height: 100%; border-radius: 2px; }
.mini-bar-pct {
    font-size: 8px;
    color: #94a3b8;
    min-width: 24px;
}

/* ── Rating pill ─────────────────────────────────────── */
.pill {
    display: inline-block;
    padding: 1.5px 6px;
    border-radius: 2px;
    font-size: 7.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    border: 1px solid;
    white-space: nowrap;
}

/* ── Rank number ─────────────────────────────────────── */
.rank-n {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px; height: 18px;
    border-radius: 50%;
    font-size: 8.5px;
    font-weight: 700;
    background: #f1f5f9;
    color: #475569;
    flex-shrink: 0;
}
.rank-n.r1 { background: #fef9c3; color: #854d0e; }
.rank-n.r2 { background: #e2e8f0; color: #334155; }
.rank-n.r3 { background: #fef3c7; color: #78350f; }

/* ── Risk priority label ─────────────────────────────── */
.risk-chip {
    display: inline-block;
    padding: 1.5px 7px;
    font-size: 7.5px;
    font-weight: 700;
    text-transform: uppercase;
    border-radius: 2px;
    letter-spacing: 0.3px;
}

/* ── Page break ──────────────────────────────────────── */
.pg-break { page-break-before: always; break-before: page; }
.no-break { page-break-inside: avoid; break-inside: avoid; }

/* ── Footer bar ──────────────────────────────────────── */
.rp-footer {
    margin: 12px 18px 0;
    padding: 7px 0 10px;
    border-top: 1.5px solid #1e3a5f;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 8px;
    color: #64748b;
}
.rp-footer-brand { font-weight: 700; color: #1e3a5f; }

/* ── Print action bar (screen only) ─────────────────── */
#print-bar {
    position: fixed; top: 0; left: 0; right: 0;
    background: #1e3a5f;
    color: #fff;
    display: flex; align-items: center; justify-content: space-between;
    padding: 9px 20px;
    z-index: 9999;
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.35);
}
#print-bar .bar-left { display: flex; align-items: center; gap: 10px; color: rgba(255,255,255,0.65); }
#print-bar .bar-title { font-weight: 700; color: #fff; font-size: 13px; }
#print-bar .bar-actions { display: flex; gap: 8px; }
.btn-print {
    background: #2563eb; color: #fff; border: none; cursor: pointer;
    padding: 7px 16px; border-radius: 5px; font-size: 12px; font-weight: 600;
    display: flex; align-items: center; gap: 6px;
}
.btn-print:hover { background: #1d4ed8; }
.btn-back {
    background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.8);
    border: 1px solid rgba(255,255,255,0.2); cursor: pointer;
    padding: 7px 13px; border-radius: 5px; font-size: 12px;
    text-decoration: none; display: flex; align-items: center; gap: 5px;
}
.btn-back:hover { background: rgba(255,255,255,0.18); }
body { padding-top: 46px; }

/* ── Print media ─────────────────────────────────────── */
@media print {
    body { background: #fff; padding-top: 0; }
    #print-bar { display: none !important; }
    .rp { width: 100%; }
    @page { size: A4 portrait; margin: 10mm 12mm 10mm 12mm; }
}
</style>
</head>
<body>

<!-- Print action bar -->
<div id="print-bar">
  <div class="bar-left">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    <span class="bar-title">SAKMS Performance Report</span>
    <span>&mdash; <?= $range_label ?></span>
  </div>
  <div class="bar-actions">
    <a href="javascript:history.back()" class="btn-back">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      Back
    </a>
    <button class="btn-print" onclick="window.print()">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Print / Save as PDF
    </button>
  </div>
</div>

<!-- ═══════════════════════════════════════
     REPORT DOCUMENT
═══════════════════════════════════════ -->
<div class="rp">

  <!-- Top color strip -->
  <div class="rp-strip"></div>

  <!-- ── REPORT HEADER ────────────────────────────────── -->
  <div class="rp-header">
    <div class="rp-header-brand">
      <div class="rp-brand-icon">S</div>
      <div>
        <div class="rp-brand-org">Sales Assistant KPI Monitoring System</div>
        <div class="rp-brand-title">Performance &amp; Training Report</div>
        <div class="rp-brand-sub">Supervisor Review &mdash; Internal Use Only</div>
      </div>
    </div>
    <div class="rp-header-meta">
      <div class="rp-period-badge"><?= $range_label ?></div>
      <div class="rp-meta-row"><strong>Generated:</strong> <?= h($generated) ?></div>
      <div class="rp-meta-row"><strong>Total Staff Evaluated:</strong> <?= $total_staff ?></div>
      <div class="rp-confidential">Confidential &mdash; Supervisor Use Only</div>
    </div>
  </div>

  <div class="rp-body">

    <!-- ── SECTION 1: EXECUTIVE SUMMARY ─────────────── -->
    <div class="rp-section">
      <div class="rp-section-title">Executive Summary</div>
      <div class="rp-summary-row">
        <div class="rp-metric c-blue">
          <div class="rp-metric-label">Total Staff Evaluated</div>
          <div class="rp-metric-value"><?= $total_staff ?></div>
          <div class="rp-metric-sub">Active employees</div>
        </div>
        <div class="rp-metric c-teal">
          <div class="rp-metric-label">Team Avg KPI Score</div>
          <div class="rp-metric-value"><?= number_format($avg_score, 2) ?></div>
          <div class="rp-metric-sub colored" style="color:<?= ratingColor($avg_score) ?>;"><?= h(KPICalculator::getRatingLabel($avg_score)) ?> &mdash; out of 5.00</div>
        </div>
        <div class="rp-metric c-green">
          <div class="rp-metric-label">Top Performers</div>
          <div class="rp-metric-value"><?= count($top_performers) ?></div>
          <div class="rp-metric-sub">KPI score &ge; 4.0</div>
        </div>
        <div class="rp-metric c-red">
          <div class="rp-metric-label">Staff Needing Support</div>
          <div class="rp-metric-value"><?= count($at_risk) ?></div>
          <div class="rp-metric-sub">KPI score &lt; 3.0 (at-risk)</div>
        </div>
      </div>
    </div>

    <!-- ── SECTION 2: DISTRIBUTION + INSIGHTS ──────── -->
    <div class="rp-section">
      <div class="rp-section-title">Performance Overview</div>
      <div class="rp-two-col">

        <!-- Left: distribution bars -->
        <div>
          <div class="rp-col-header">Performance Distribution &mdash; KPI Scale 1.0 &ndash; 5.0</div>
          <?php
            $tiers = [
              ['label'=>'Excellent',    'range'=>'4.5 &ndash; 5.0', 'count'=>$tier_excellent,    'color'=>'#15803d'],
              ['label'=>'Good',         'range'=>'3.5 &ndash; 4.4', 'count'=>$tier_good,         'color'=>'#1d4ed8'],
              ['label'=>'Satisfactory', 'range'=>'2.5 &ndash; 3.4', 'count'=>$tier_satisfactory, 'color'=>'#b45309'],
              ['label'=>'Poor',         'range'=>'1.5 &ndash; 2.4', 'count'=>$tier_poor,         'color'=>'#b91c1c'],
              ['label'=>'Very Poor',    'range'=>'&lt; 1.5',        'count'=>$tier_verypoor,     'color'=>'#7f1d1d'],
            ];
            foreach ($tiers as $t):
              $pct = $total_staff ? round(($t['count'] / $total_staff) * 100, 1) : 0;
              $barW = $total_staff ? max(2, ($t['count'] / $total_staff) * 100) : 0;
          ?>
          <div class="dist-row">
            <div class="dist-label" style="color:<?= $t['color'] ?>;"><?= $t['label'] ?></div>
            <div class="dist-range"><?= $t['range'] ?></div>
            <div class="dist-bar-track">
              <div class="dist-bar-fill" style="width:<?= $barW ?>%;background:<?= $t['color'] ?>;"></div>
            </div>
            <div class="dist-count-pct" style="color:<?= $t['color'] ?>;"><?= $t['count'] ?></div>
            <div class="dist-pct-text"><?= $pct ?>%</div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Right: key insights -->
        <div>
          <div class="rp-col-header">Key Highlights</div>
          <?php
            $best  = $all_data[0]  ?? null;
            $worst = $all_data[count($all_data)-1] ?? null;
          ?>
          <?php if ($best): ?>
          <div class="insight-item">
            <div class="insight-dot" style="background:#15803d;"></div>
            <div class="insight-key">Best Performer</div>
            <div class="insight-val"><strong><?= h($best['name']) ?></strong> &mdash; <?= number_format($best['kpi_score'],2) ?> / 5.00
              <span class="pill" style="background:<?= ratingBg($best['kpi_score']) ?>;color:<?= ratingColor($best['kpi_score']) ?>;border-color:<?= ratingBorder($best['kpi_score']) ?>;"><?= h($best['rating']) ?></span>
            </div>
          </div>
          <?php endif; ?>
          <?php if ($worst): ?>
          <div class="insight-item">
            <div class="insight-dot" style="background:#b91c1c;"></div>
            <div class="insight-key">Needs Attention</div>
            <div class="insight-val"><strong><?= h($worst['name']) ?></strong> &mdash; <?= number_format($worst['kpi_score'],2) ?> / 5.00
              <span class="pill" style="background:<?= ratingBg($worst['kpi_score']) ?>;color:<?= ratingColor($worst['kpi_score']) ?>;border-color:<?= ratingBorder($worst['kpi_score']) ?>;"><?= h($worst['rating']) ?></span>
            </div>
          </div>
          <?php endif; ?>
          <div class="insight-item">
            <div class="insight-dot" style="background:#1d4ed8;"></div>
            <div class="insight-key">Top Performers</div>
            <div class="insight-val"><strong><?= count($top_performers) ?> staff</strong> scored 4.0 or above</div>
          </div>
          <div class="insight-item">
            <div class="insight-dot" style="background:#b91c1c;"></div>
            <div class="insight-key">At-Risk</div>
            <div class="insight-val"><strong><?= count($at_risk) ?> staff</strong> below 3.0 &mdash; immediate action required</div>
          </div>
          <div class="insight-item">
            <div class="insight-dot" style="background:#b45309;"></div>
            <div class="insight-key">Training Needs</div>
            <div class="insight-val"><strong><?= count($training_needs) ?> staff</strong> below 3.5 &mdash; coaching recommended</div>
          </div>
          <div class="insight-item">
            <div class="insight-dot" style="background:#475569;"></div>
            <div class="insight-key">KPI Scale</div>
            <div class="insight-val">1.0 (Very Poor) to 5.0 (Excellent)</div>
          </div>
        </div>

      </div>
    </div>

    <!-- ── SECTION 3: STAFF KPI RANKINGS ────────────── -->
    <div class="rp-section no-break">
      <div class="rp-section-title">
        Staff KPI Rankings
        <span class="title-note">Sorted by KPI score, highest to lowest</span>
      </div>
      <table style="margin-bottom:12px;">
        <thead>
          <tr>
            <th class="th-c" style="width:28px;">#</th>
            <th style="width:54px;">Staff ID</th>
            <th style="width:90px;">Name</th>
            <th>Role</th>
            <th class="th-c" style="width:68px;">KPI Score</th>
            <th style="width:90px;">Progress</th>
            <th class="th-c" style="width:66px;">Rating</th>
            <th style="width:88px;">Classification</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($all_data as $rank => $emp):
            $col  = ratingColor($emp['kpi_score']);
            $bg   = ratingBg($emp['kpi_score']);
            $bdr  = ratingBorder($emp['kpi_score']);
            $pct  = scorePct($emp['kpi_score']);
            $rn   = $rank + 1;
            $rc   = $rn === 1 ? 'r1' : ($rn === 2 ? 'r2' : ($rn === 3 ? 'r3' : ''));
          ?>
          <tr class="no-break">
            <td class="td-c"><span class="rank-n <?= $rc ?>"><?= $rn ?></span></td>
            <td class="td-muted" style="font-family:monospace;"><?= h($emp['staff_code']) ?></td>
            <td style="font-weight:700;"><?= h($emp['name']) ?></td>
            <td class="td-muted"><?= h($emp['role']) ?></td>
            <td class="td-c">
              <span class="score-val" style="color:<?= $col ?>;"><?= number_format($emp['kpi_score'],2) ?></span>
              <span class="score-denom"> / 5.00</span>
            </td>
            <td>
              <div class="mini-bar">
                <div class="mini-bar-track">
                  <div class="mini-bar-fill" style="width:<?= $pct ?>%;background:<?= $col ?>;"></div>
                </div>
                <div class="mini-bar-pct"><?= $pct ?>%</div>
              </div>
            </td>
            <td class="td-c">
              <span class="pill" style="background:<?= $bg ?>;color:<?= $col ?>;border-color:<?= $bdr ?>;"><?= h($emp['rating']) ?></span>
            </td>
            <td class="td-muted"><?= h(KPICalculator::classifyPerformance($emp['kpi_score'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4">Team Average</td>
            <td class="td-c"><strong style="color:<?= ratingColor($avg_score) ?>;"><?= number_format($avg_score,2) ?> / 5.00</strong></td>
            <td colspan="3"><?= h(KPICalculator::getRatingLabel($avg_score)) ?> &mdash; <?= $total_staff ?> staff evaluated</td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- ── PAGE 2 START ──────────────────────────────── -->
    <?php if (!empty($at_risk)): ?>
    <div class="rp-section pg-break">
      <div class="rp-section-title" style="border-left-color:#b91c1c;">
        At-Risk Staff Report
        <span class="title-note" style="color:#b91c1c;">KPI &lt; 3.0 &mdash; Requires immediate supervisor attention</span>
      </div>
      <table style="margin-bottom:12px;">
        <thead>
          <tr>
            <th style="width:28px;" class="th-c">#</th>
            <th style="width:54px;">Staff ID</th>
            <th style="width:90px;">Name</th>
            <th>Role</th>
            <th class="th-c" style="width:68px;">KPI Score</th>
            <th class="th-c" style="width:62px;">Risk Level</th>
            <th>Recommended Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($at_risk as $i => $emp):
            $col    = ratingColor($emp['kpi_score']);
            $bg     = ratingBg($emp['kpi_score']);
            $bdr    = ratingBorder($emp['kpi_score']);
            $rl     = riskLabel($emp['kpi_score']);
            $rc     = riskColor($emp['kpi_score']);
            if ($emp['kpi_score'] < 2.0)    $action = 'Initiate formal Performance Improvement Plan (PIP) immediately';
            elseif ($emp['kpi_score'] < 2.5) $action = 'Schedule weekly 1-on-1 coaching and set measurable improvement targets';
            else                             $action = 'Monitor progress weekly and provide targeted training support';
          ?>
          <tr class="no-break">
            <td class="td-c" style="color:#94a3b8;font-size:8.5px;"><?= $i+1 ?></td>
            <td class="td-muted" style="font-family:monospace;"><?= h($emp['staff_code']) ?></td>
            <td style="font-weight:700;"><?= h($emp['name']) ?></td>
            <td class="td-muted"><?= h($emp['role']) ?></td>
            <td class="td-c">
              <span class="score-val" style="color:<?= $col ?>;"><?= number_format($emp['kpi_score'],2) ?></span>
              <span class="score-denom"> / 5.00</span>
            </td>
            <td class="td-c">
              <span class="risk-chip" style="background:<?= $rc ?>18;color:<?= $rc ?>;border:1px solid <?= $rc ?>40;"><?= $rl ?></span>
            </td>
            <td style="font-size:8.5px;color:#334155;"><?= h($action) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <!-- ── TRAINING NEEDS ─────────────────────────────── -->
    <?php if (!empty($training_needs)): ?>
    <div class="rp-section">
      <div class="rp-section-title" style="border-left-color:#b45309;">
        Training Needs Summary
        <span class="title-note" style="color:#b45309;">KPI &lt; 3.5 &mdash; Coaching and development recommended</span>
      </div>
      <table style="margin-bottom:12px;">
        <thead>
          <tr>
            <th style="width:28px;" class="th-c">#</th>
            <th style="width:90px;">Name</th>
            <th>Role</th>
            <th class="th-c" style="width:68px;">KPI Score</th>
            <th class="th-c" style="width:66px;">Rating</th>
            <th class="th-c" style="width:72px;">Priority</th>
            <th>Training Focus</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($training_needs as $i => $emp):
            $col = ratingColor($emp['kpi_score']);
            $bg  = ratingBg($emp['kpi_score']);
            $bdr = ratingBorder($emp['kpi_score']);
            $pri = trainingPriority($emp['kpi_score']);
            $foc = trainingFocus($emp['kpi_score']);
            $pc  = $emp['kpi_score'] < 2.5 ? '#b91c1c' : ($emp['kpi_score'] < 3.0 ? '#c2410c' : '#b45309');
          ?>
          <tr class="no-break">
            <td class="td-c" style="color:#94a3b8;font-size:8.5px;"><?= $i+1 ?></td>
            <td style="font-weight:700;"><?= h($emp['name']) ?></td>
            <td class="td-muted"><?= h($emp['role']) ?></td>
            <td class="td-c">
              <span class="score-val" style="color:<?= $col ?>;"><?= number_format($emp['kpi_score'],2) ?></span>
              <span class="score-denom"> / 5.00</span>
            </td>
            <td class="td-c">
              <span class="pill" style="background:<?= $bg ?>;color:<?= $col ?>;border-color:<?= $bdr ?>;"><?= h($emp['rating']) ?></span>
            </td>
            <td class="td-c">
              <span class="risk-chip" style="background:<?= $pc ?>18;color:<?= $pc ?>;border:1px solid <?= $pc ?>40;"><?= $pri ?></span>
            </td>
            <td style="font-size:8.5px;color:#334155;"><?= h($foc) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

  </div><!-- /rp-body -->

  <!-- ── REPORT FOOTER ──────────────────────────────── -->
  <div class="rp-footer">
    <div class="rp-footer-brand">SAKMS &mdash; Sales Assistant KPI Monitoring System</div>
    <div>Report Period: <?= $range_label ?></div>
    <div>Generated: <?= h($generated) ?> &mdash; Confidential</div>
  </div>

</div><!-- /rp -->

<script>
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 400);
});
</script>
</body>
</html>
