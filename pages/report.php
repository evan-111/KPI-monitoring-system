<?php
// ══════════════════════════════════════════════════════════
//  REPORT.PHP  –  Performance & Training Report
//  Sections: Header · Summary Overview
// ══════════════════════════════════════════════════════════

// ── 1. Date range: read bounds from DB, then apply GET params ──
$bounds    = $conn->query("SELECT MIN(date_recorded) mn, MAX(date_recorded) mx FROM kpi_score")->fetch_assoc();
$db_min    = ($bounds && $bounds['mn'] && $bounds['mn'] !== '0000-00-00') ? $bounds['mn'] : '2022-01-01';
$db_max    = ($bounds && $bounds['mx'] && $bounds['mx'] !== '0000-00-00') ? $bounds['mx'] : date('Y-12-31');

$date_from = $_GET['date_from'] ?? $db_min;
$date_to   = $_GET['date_to']   ?? $db_max;

// Validate and swap if needed
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from) || $date_from === '0000-00-00') $date_from = $db_min;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)   || $date_to   === '0000-00-00') $date_to   = $db_max;
if ($date_from > $date_to) [$date_from, $date_to] = [$date_to, $date_from];

// ── 2. KPI score for one staff member in a date range ──────
// Calculate weighted KPI score split between Core Competencies and KPI Achievement sections
function calcKPI($conn, $staff_id, $from, $to) {
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
    $s1 = $s2 = 0.0;
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $w = (float)$row['avg_score'] * (float)$row['weight'];
        if ($row['section'] === 'Core Competencies') $s1 += $w;
        else                                         $s2 += $w;
    }
    $overall = round(max(0, min(5, $s1 + $s2)), 2);
    return ['overall' => $overall, 'rating' => KPICalculator::getRatingLabel($overall)];
}

// ── 3. Load all active staff who have scores in the date range ──
// Fetch distinct employees with KPI records, calculate their scores, and rank them
$stmt = $conn->prepare("
    SELECT DISTINCT s.staff_id AS id, s.full_name AS name, s.staff_code, s.role
    FROM staff s
    JOIN kpi_score ks ON ks.staff_id = s.staff_id
    WHERE s.status = 'Active' AND ks.date_recorded BETWEEN ? AND ?
    ORDER BY s.full_name
");
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();

$all_staff = [];
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $s) {
    $kpi           = calcKPI($conn, $s['id'], $date_from, $date_to);
    $s['score']    = $kpi['overall'];
    $s['rating']   = $kpi['rating'];
    $all_staff[]   = $s;
}
usort($all_staff, fn($a, $b) => $b['score'] <=> $a['score']);

// ── 4. Calculate summary statistics for cards ────────────────────────────
// Count staff in each performance tier: excellent, good, satisfactory, at-risk
$top_cnt  = count(array_filter($all_staff, fn($s) => $s['score'] >= 4.0));
$risk_cnt = count(array_filter($all_staff, fn($s) => $s['score'] <  3.0));

// ── 5. Fetch evaluation periods for date-picker dropdown ────────────────
// Load year-based periods to allow quick-select filtering by year
$periods = $conn->query("
    SELECT period_id, year,
           COALESCE(NULLIF(start_date,'0000-00-00'), CONCAT(year,'-01-01')) AS start_date,
           COALESCE(NULLIF(end_date,  '0000-00-00'), CONCAT(year,'-12-31')) AS end_date
    FROM evaluation_period ORDER BY year ASC
")->fetch_all(MYSQLI_ASSOC);

// ── 6. Calculate tier distribution (excellent, good, satisfactory, at-risk) ──
// Break down staff into 4 performance tiers for donut chart visualization
$total    = count($all_staff);
$avg      = $total ? round(array_sum(array_column($all_staff, 'score')) / $total, 2) : 0;
$tiers    = [
    'excellent'    => count(array_filter($all_staff, fn($s) => $s['score'] >= 4.5)),
    'good'         => count(array_filter($all_staff, fn($s) => $s['score'] >= 3.5 && $s['score'] < 4.5)),
    'satisfactory' => count(array_filter($all_staff, fn($s) => $s['score'] >= 2.5 && $s['score'] < 3.5)),
    'at_risk'      => count(array_filter($all_staff, fn($s) => $s['score'] <  2.5)),
];

// ── 7. Prepare display labels for date range reporting ────────────────
// Format dates and determine if showing all data or a specific range
$label_from  = date('d M Y', strtotime($date_from));
$label_to    = date('d M Y', strtotime($date_to));
$is_all      = ($date_from === $db_min && $date_to === $db_max);
$range_label = $is_all ? 'All Years' : ($date_from === $date_to ? $label_from : "$label_from – $label_to");
?>

<style>
/* ── Layout ─────────────────────────────────────────────── */
.rpt-page { display:flex; flex-direction:column; gap:20px; padding-bottom:40px; }

.rpt-card {
    background:var(--bg-card);
    border:1px solid var(--border);
    border-radius:12px;
    padding:24px;
}
.rpt-card-title {
    display:flex; align-items:center; gap:8px;
    font-size:14px; font-weight:700; color:var(--text-primary);
    margin-bottom:16px; padding-bottom:12px;
    border-bottom:1px solid var(--border);
}
.rpt-empty { text-align:center; padding:32px; color:var(--text-muted); font-size:13px; }

/* ── Summary cards + donut ───────────────────────────────── */
.rpt-summary-wrap {
    display:grid;
    grid-template-columns:1fr 230px;
    gap:16px;
    align-items:start;
}
@media (max-width:900px) { .rpt-summary-wrap { grid-template-columns:1fr; } }

.rpt-stat-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.rpt-stat {
    background:var(--bg-input); border:1px solid var(--border);
    border-top:3px solid; border-radius:10px; padding:16px;
}
.rpt-stat-label { font-size:10px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; }
.rpt-stat-value { font-size:26px; font-weight:700; color:var(--text-primary); line-height:1; }
.rpt-stat-note  { font-size:11px; color:var(--text-secondary); margin-top:5px; }

.rpt-donut-panel {
    background:var(--bg-input); border:1px solid var(--border);
    border-radius:10px; padding:16px;
    display:flex; flex-direction:column; align-items:center; gap:10px;
}
.rpt-donut-title { font-size:10px; font-weight:700; color:var(--text-muted); text-transform:uppercase; }
.rpt-donut-wrap  { position:relative; width:140px; height:140px; }
.rpt-donut-center {
    position:absolute; inset:0;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    pointer-events:none;
}
.rpt-donut-num   { font-size:20px; font-weight:700; color:var(--text-primary); }
.rpt-donut-sub   { font-size:9px; color:var(--text-muted); text-transform:uppercase; }
.rpt-legend      { width:100%; display:flex; flex-direction:column; gap:5px; }
.rpt-legend-row  { display:flex; align-items:center; gap:6px; font-size:11px; color:var(--text-secondary); }
.rpt-legend-dot  { width:8px; height:8px; border-radius:2px; flex-shrink:0; }
.rpt-legend-cnt  { margin-left:auto; font-weight:700; color:var(--text-primary); }


/* ── Date-picker dropdown ────────────────────────────────── */
.rpt-period-wrap  { position:relative; }
.rpt-date-dropdown {
    display:none; position:fixed; z-index:9999;
    width:340px; background:#131e30;
    border:1px solid rgba(59,130,246,0.25); border-radius:12px;
    box-shadow:0 16px 48px rgba(0,0,0,0.5); overflow:hidden;
}
.rpt-date-dropdown.open { display:block; }
.rpt-dd-header  {
    padding:14px 16px; font-size:13px; font-weight:700; color:var(--text-primary);
    border-bottom:1px solid rgba(255,255,255,0.07);
    background:rgba(59,130,246,0.06);
}
.rpt-dd-body    { padding:14px 16px; display:flex; flex-direction:column; gap:12px; }
.rpt-dd-label   { font-size:10px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.5px; margin-bottom:5px; }
.rpt-date-row   { display:flex; align-items:center; gap:8px; }
.rpt-date-input {
    flex:1; padding:8px 10px; background:var(--bg-input);
    border:1px solid var(--border); border-radius:7px;
    color:var(--text-primary); font-size:12px; font-family:'DM Sans',sans-serif;
}
.rpt-date-input:focus { outline:none; border-color:var(--accent); }
.rpt-apply-btn {
    width:100%; padding:9px; background:#2563eb; color:#fff;
    border:none; border-radius:8px; font-size:12px; font-weight:700;
    cursor:pointer; font-family:'DM Sans',sans-serif;
}
.rpt-apply-btn:hover { background:#1d4ed8; }
.rpt-qs-list { display:flex; flex-direction:column; gap:3px; }
.rpt-qs-btn {
    display:flex; align-items:center; justify-content:space-between;
    width:100%; padding:9px 10px; background:transparent;
    border:1px solid transparent; border-radius:7px;
    color:var(--text-secondary); font-size:13px; cursor:pointer; text-align:left;
    font-family:'DM Sans',sans-serif; transition:background .15s;
}
.rpt-qs-btn:hover   { background:rgba(59,130,246,0.1); color:var(--text-primary); }
.rpt-qs-btn.active  { background:rgba(59,130,246,0.15); border-color:rgba(59,130,246,0.3); color:#60a5fa; font-weight:600; }
.rpt-qs-range { font-size:10px; color:var(--text-muted); }

/* ── Action buttons ──────────────────────────────────────── */
.rpt-btn {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 14px; border-radius:7px; font-size:12px; font-weight:600;
    cursor:pointer; border:none; text-decoration:none; white-space:nowrap;
    transition:opacity .15s;
}
.rpt-btn:hover    { opacity:.85; }
.rpt-btn-ghost    { background:var(--bg-input); color:var(--text-primary); border:1px solid var(--border); }
.rpt-btn-green    { background:#059669; color:#fff; }
.rpt-btn-blue     { background:#2563eb; color:#fff; }

/* ── Print ───────────────────────────────────────────────── */
@media print {
    .sidebar,.topbar,.rpt-btn,.rpt-period-wrap { display:none !important; }
    .shell  { display:block !important; }
    .main   { margin-left:0 !important; }
    .rpt-card,.rpt-stat { background:#fff !important; color:#111 !important; border-color:#ddd !important; }
    .rpt-card-title,.rpt-stat-value { color:#111 !important; }
    .rpt-stat-label,.rpt-stat-note  { color:#555 !important; }
}
</style>

<div class="content active fade-in">
<div class="rpt-page">

  <!-- ════════════════════════════════════════
       HEADER — title, date picker, export
  ════════════════════════════════════════ -->
  <div class="rpt-card">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">

      <!-- Title block -->
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
          <div style="width:42px;height:42px;border-radius:10px;background:rgba(59,130,246,0.15);border:1px solid rgba(59,130,246,0.3);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
              <line x1="16" y1="13" x2="8" y2="13"/>
              <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
          </div>
          <div>
            <div style="font-size:18px;font-weight:700;color:var(--text-primary)">Performance &amp; Training Report</div>
            <div style="font-size:12px;color:var(--text-secondary);margin-top:2px">Sales Assistant KPI Monitoring System</div>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;font-size:12px;color:var(--text-secondary);margin-top:8px">
          <span>📅 <strong style="color:var(--text-primary)"><?= htmlspecialchars($range_label) ?></strong></span>
          <span style="color:var(--border)">·</span>
          <span>🕐 Generated <?= date('d M Y, h:i A') ?></span>
          <span style="color:var(--border)">·</span>
          <span>👥 <strong style="color:var(--text-primary)"><?= $total ?></strong> active staff</span>
        </div>
      </div>

      <!-- Action buttons -->
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">

        <!-- Date picker button + dropdown -->
        <div class="rpt-period-wrap">
          <button class="rpt-btn rpt-btn-ghost" id="rptPeriodBtn" type="button">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="18" rx="2"/>
              <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
              <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <span id="rptPeriodLabel"><?= htmlspecialchars($range_label) ?></span>
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </button>

          <div class="rpt-date-dropdown" id="rptPeriodDropdown">
            <div class="rpt-dd-header">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" style="vertical-align:middle;margin-right:6px">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
              </svg>
              Date Filter
            </div>
            <div class="rpt-dd-body">

              <!-- Custom range -->
              <div>
                <div class="rpt-dd-label">Custom Range</div>
                <div class="rpt-date-row">
                  <input type="date" id="rptDateFrom" class="rpt-date-input"
                         value="<?= htmlspecialchars($date_from) ?>"
                         min="<?= htmlspecialchars($db_min) ?>" max="<?= htmlspecialchars($db_max) ?>">
                  <span style="color:var(--text-muted)">–</span>
                  <input type="date" id="rptDateTo" class="rpt-date-input"
                         value="<?= htmlspecialchars($date_to) ?>"
                         min="<?= htmlspecialchars($db_min) ?>" max="<?= htmlspecialchars($db_max) ?>">
                </div>
                <button class="rpt-apply-btn" id="rptApplyBtn" type="button" style="margin-top:8px">
                  Apply Range
                </button>
              </div>

              <hr style="border:none;border-top:1px solid rgba(255,255,255,0.07)">

              <!-- Quick-select year buttons -->
              <div>
                <div class="rpt-dd-label">Quick Select</div>
                <div class="rpt-qs-list">
                  <?php $is_all_active = ($date_from === $db_min && $date_to === $db_max); ?>
                  <button class="rpt-qs-btn <?= $is_all_active ? 'active' : '' ?>"
                          data-from="<?= $db_min ?>" data-to="<?= $db_max ?>" type="button">
                    <span>All Years</span>
                    <span class="rpt-qs-range"><?= date('d M Y', strtotime($db_min)) ?> – <?= date('d M Y', strtotime($db_max)) ?></span>
                  </button>
                  <?php foreach (array_reverse($periods) as $p):
                      $is_active = ($p['start_date'] === $date_from && $p['end_date'] === $date_to);
                  ?>
                    <button class="rpt-qs-btn <?= $is_active ? 'active' : '' ?>"
                            data-from="<?= $p['start_date'] ?>" data-to="<?= $p['end_date'] ?>" type="button">
                      <span>Year <?= $p['year'] ?></span>
                      <span class="rpt-qs-range">
                        <?= date('d M Y', strtotime($p['start_date'])) ?> –
                        <?= date('d M Y', strtotime($p['end_date'])) ?>
                      </span>
                    </button>
                  <?php endforeach; ?>
                </div>
              </div>

            </div>
          </div>
        </div>

        <a href="includes/export.php?date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>"
           class="rpt-btn rpt-btn-green">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="12" y1="18" x2="12" y2="12"/>
            <polyline points="9 15 12 18 15 15"/>
          </svg>
          Export PDF
        </a>

        <button class="rpt-btn rpt-btn-blue" onclick="window.print()" type="button">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 6 2 18 2 18 9"/>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
            <rect x="6" y="14" width="12" height="8"/>
          </svg>
          Print / PDF
        </button>

      </div>
    </div>
  </div>


  <!-- ════════════════════════════════════════
       SUMMARY OVERVIEW — 4 stat cards + donut
  ════════════════════════════════════════ -->
  <div class="rpt-card">
    <div class="rpt-card-title">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
      </svg>
      Summary Overview
      <span style="font-size:11px;font-weight:400;color:var(--text-muted);margin-left:4px">— <?= htmlspecialchars($range_label) ?></span>
    </div>

    <div class="rpt-summary-wrap">
      <!-- 4 stat cards -->
      <div class="rpt-stat-grid">
        <div class="rpt-stat" style="border-top-color:#3b82f6">
          <div class="rpt-stat-label">Total Staff</div>
          <div class="rpt-stat-value"><?= $total ?></div>
          <div class="rpt-stat-note">Active staff evaluated</div>
        </div>
        <div class="rpt-stat" style="border-top-color:#8b5cf6">
          <div class="rpt-stat-label">Average KPI Score</div>
          <div class="rpt-stat-value"><?= number_format($avg, 2) ?></div>
          <div class="rpt-stat-note">Team average out of 5.0</div>
        </div>
        <div class="rpt-stat" style="border-top-color:#10b981">
          <div class="rpt-stat-label">Top Performers</div>
          <div class="rpt-stat-value"><?= $top_cnt ?></div>
          <div class="rpt-stat-note">Scoring 4.0 or above</div>
        </div>
        <div class="rpt-stat" style="border-top-color:#ef4444">
          <div class="rpt-stat-label">At-Risk Staff</div>
          <div class="rpt-stat-value"><?= $risk_cnt ?></div>
          <div class="rpt-stat-note">Scoring below 3.0</div>
        </div>
      </div>

      <!-- Donut chart -->
      <div class="rpt-donut-panel">
        <div class="rpt-donut-title">Team Distribution</div>
        <div class="rpt-donut-wrap">
          <canvas id="chartDonut" width="140" height="140"></canvas>
          <div class="rpt-donut-center">
            <div class="rpt-donut-num"><?= $total ?></div>
            <div class="rpt-donut-sub">Staff</div>
          </div>
        </div>
        <div class="rpt-legend">
          <div class="rpt-legend-row">
            <div class="rpt-legend-dot" style="background:#22c55e"></div>Excellent (≥4.5)
            <span class="rpt-legend-cnt"><?= $tiers['excellent'] ?></span>
          </div>
          <div class="rpt-legend-row">
            <div class="rpt-legend-dot" style="background:#3b82f6"></div>Good (3.5–4.5)
            <span class="rpt-legend-cnt"><?= $tiers['good'] ?></span>
          </div>
          <div class="rpt-legend-row">
            <div class="rpt-legend-dot" style="background:#f59e0b"></div>Satisfactory (2.5–3.5)
            <span class="rpt-legend-cnt"><?= $tiers['satisfactory'] ?></span>
          </div>
          <div class="rpt-legend-row">
            <div class="rpt-legend-dot" style="background:#ef4444"></div>At-Risk (&lt;2.5)
            <span class="rpt-legend-cnt"><?= $tiers['at_risk'] ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>



</div><!-- end .rpt-page -->
</div><!-- end .content -->

<script>
/***************************
Title: Chart.js Doughnut Chart Configuration
Author: Chartjs Contributors
Date: 13rd October 2025
Type: Library & Documentation Reference
Availability: https://www.chartjs.org/docs/latest/charts/doughnut.html
***************************/

// ── Donut chart (team tier distribution) ──────────────────
(function () {
    const ctx = document.getElementById('chartDonut');
    if (!ctx) return;
    const counts = [<?= $tiers['excellent'] ?>, <?= $tiers['good'] ?>, <?= $tiers['satisfactory'] ?>, <?= $tiers['at_risk'] ?>];
    if (counts.every(v => v === 0)) return;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Excellent', 'Good', 'Satisfactory', 'At-Risk'],
            datasets: [{
                data: counts,
                backgroundColor: ['#22c55e', '#3b82f6', '#f59e0b', '#ef4444'],
                borderColor: '#1a2233',
                borderWidth: 3,
                hoverOffset: 5
            }]
        },
        options: {
            cutout: '68%',
            responsive: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#111c2e',
                    borderColor: '#243047',
                    borderWidth: 1,
                    callbacks: { label: c => ` ${c.label}: ${c.parsed} staff` }
                }
            }
        }
    });
})();

// ── Date-picker dropdown ───────────────────────────────────
/***************************
Title: Element: getBoundingClientRect() method
Author: MDN Web Docs Contributors
Date: 07th May 2022
Type: Web API
Availability: https://developer.mozilla.org/en-US/docs/Web/API/Element/getBoundingClientRect
***************************/

document.addEventListener('DOMContentLoaded', function () {
    const btn      = document.getElementById('rptPeriodBtn');
    const dropdown = document.getElementById('rptPeriodDropdown');
    const dateFrom = document.getElementById('rptDateFrom');
    const dateTo   = document.getElementById('rptDateTo');
    const applyBtn = document.getElementById('rptApplyBtn');

    // Attach to body so it isn't clipped by overflow:hidden parents
    document.body.appendChild(dropdown);

    // Position dropdown below button, adjusting horizontally if it would go off-screen
    function position() {
        const r    = btn.getBoundingClientRect();
        let   left = r.left;
        // Prevent dropdown from extending past right edge of viewport
        if (left + 340 > window.innerWidth - 8) left = window.innerWidth - 348;
        dropdown.style.top  = (r.bottom + 6) + 'px';
        dropdown.style.left = Math.max(8, left) + 'px';
    }

    // Toggle dropdown visibility on button click
    btn.addEventListener('click', e => {
        e.stopPropagation();
        dropdown.classList.toggle('open');
        if (dropdown.classList.contains('open')) position();
    });

    // Prevent clicks inside dropdown from closing it
    dropdown.addEventListener('click', e => e.stopPropagation());
    // Close dropdown when clicking elsewhere on page
    document.addEventListener('click', () => dropdown.classList.remove('open'));
    // Re-position dropdown while scrolling to keep it aligned with button
    window.addEventListener('scroll', () => { if (dropdown.classList.contains('open')) position(); }, true);
    // Re-position dropdown on window resize
    window.addEventListener('resize', () => { if (dropdown.classList.contains('open')) position(); });

    // Navigate to report with new date range
    function go(from, to) {
        window.location.href = '?page=report&date_from=' + from + '&date_to=' + to;
    }

    // Apply button: validate dates and navigate
    applyBtn.addEventListener('click', () => {
        if (!dateFrom.value || !dateTo.value) return;
        // Swap dates if from > to
        const f = dateFrom.value <= dateTo.value ? dateFrom.value : dateTo.value;
        const t = dateFrom.value <= dateTo.value ? dateTo.value   : dateFrom.value;
        go(f, t);
    });

    // Quick-select buttons: navigate to preset date ranges by period
    document.querySelectorAll('.rpt-qs-btn').forEach(b =>
        b.addEventListener('click', () => go(b.dataset.from, b.dataset.to))
    );
});
</script>
