<?php
// ── Avatar helper (shared across all pages) ──────────────────────────────────
require_once __DIR__ . '/../includes/avatar.php';


// SECTION 1: FETCH & CALCULATE KPI SCORES FOR ALL EMPLOYEES
// 1. All employees KPI for the current period (sorted best → worst)
$all_emps = getAllEmployeesSummary($conn, $period_id);
$analytics_data = [];
while ($emp = $all_emps->fetch_assoc()) {
    $kpi = KPICalculator::calculateKPI($conn, $emp['employee_id'], $period_id);
    $emp['kpi_score'] = $kpi['overall'];
    $emp['rating']    = $kpi['rating'];
    $analytics_data[] = $emp;
}
usort($analytics_data, fn($a, $b) => $b['kpi_score'] <=> $a['kpi_score']);

// SECTION 2: CALCULATE SUMMARY METRICS FOR HEADER CARDS
// Summary metrics for ranking header cards
$top_score      = $analytics_data[0]['kpi_score'] ?? 0;  // Highest individual score
$avg_score      = count($analytics_data)                  // Average of all employees
    ? round(array_sum(array_column($analytics_data, 'kpi_score')) / count($analytics_data), 2)
    : 0;
$top_performers = count(array_filter($analytics_data, fn($e) => $e['kpi_score'] >= 4.0));  // Count of high performers

// SECTION 3: FETCH KPI TREND DATA (For trend chart over time)
// Historical KPI data grouped by date and category - used for line chart
$kpi_trend_result = $conn->query("
    SELECT ks.staff_id                                AS employee_id,
           DATE_FORMAT(ks.date_recorded, '%Y-%m-%d') AS evaluation_date,
           YEAR(ks.date_recorded)                    AS year,
           DATE_FORMAT(ks.date_recorded, '%Y-%m')    AS date_label,
           kg.group_name                             AS kpi_group,
           AVG(ks.score)                             AS avg_score
    FROM kpi_score ks
    JOIN kpi_item    ki   ON ks.kpi_item_id  = ki.kpi_item_id
    JOIN kpi_group   kg   ON ki.kpi_group_id = kg.kpi_group_id
    JOIN kpi_section ksec ON kg.section_id   = ksec.section_id
    WHERE ksec.section_name = 'KPI Achievement'
    GROUP BY ks.staff_id,
             DATE_FORMAT(ks.date_recorded, '%Y-%m-%d'),
             YEAR(ks.date_recorded),
             kg.kpi_group_id
    ORDER BY ks.staff_id, ks.date_recorded, kg.group_name
");
$kpi_trend_data = $kpi_trend_result->fetch_all(MYSQLI_ASSOC);

// SECTION 4: FETCH KPI CATEGORY AVERAGES (For radar chart)
// Average scores per KPI category - used for radar/spider chart
$cat_result = $conn->query("
    SELECT kg.group_name        AS kpi_group,
           ROUND(AVG(ks.score), 2) AS avg_score
    FROM kpi_score ks
    JOIN kpi_item    ki   ON ks.kpi_item_id  = ki.kpi_item_id
    JOIN kpi_group   kg   ON ki.kpi_group_id = kg.kpi_group_id
    JOIN kpi_section ksec ON kg.section_id   = ksec.section_id
    WHERE ksec.section_name = 'KPI Achievement'
    GROUP BY kg.kpi_group_id, kg.group_name
    ORDER BY avg_score DESC
");
$kpi_categories = $cat_result->fetch_all(MYSQLI_ASSOC);

// SECTION 5: BUILD AVATAR MAP FOR JAVASCRIPT
// Create mapping of employee names to avatar image URLs
$analytics_names = array_column($analytics_data, 'name');
$avatar_map_json = json_encode(buildAvatarMap($analytics_names));
?>

<style>

    /* =============================
       FILTER PANEL STYLES
       - Layout and appearance for filter controls (date, category)
    ============================= */
    .filter-row {
      display: flex;                /* Arrange filter groups in a row */
      gap: 24px;                    /* Space between filter groups */
      flex-wrap: wrap;              /* Allow wrapping on small screens */
    }
    .filter-group {
      position: relative;           /* Needed for dropdown positioning */
      flex: 1;                      /* Grow to fill row */
      min-width: 200px;             /* Minimum width for usability */
    }
    .filter-label {
      display: block;               /* Label on its own line */
      font-size: 11px;
      font-weight: 600;
      color: var(--text-secondary);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
    }
    .filter-btn {
      display: flex;                /* Button with icon and text */
      align-items: center;
      gap: 8px;
      width: 100%;
      padding: 8px 12px;
      background: var(--bg-input);
      border: 1px solid var(--border-color);
      border-radius: 6px;
      color: var(--text-primary);
      font-size: 13px;
      cursor: pointer;
    }
    .filter-btn:hover { border-color: var(--accent-blue); } /* Highlight on hover */
    .filter-btn svg:last-child { margin-left: auto; }       /* Push arrow icon to right */

    .filter-dropdown {
      display: none;                /* Hidden by default */
      position: absolute;
      top: calc(100% + 4px);        /* Below the button */
      left: 0;
      z-index: 100;
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      padding: 16px;
      min-width: 320px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    }
    .filter-dropdown.open { display: block; }               /* Show when open */

    .dropdown-section { margin-bottom: 12px; }              /* Space between dropdown sections */
    .dropdown-label {
      display: block;
      font-size: 11px;
      font-weight: 600;
      color: var(--text-secondary);
      text-transform: uppercase;
      margin-bottom: 6px;
    }
    .date-input-row {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .date-input {
      flex: 1;
      padding: 6px 8px;
      background: var(--bg-input);
      border: 1px solid var(--border-color);
      border-radius: 4px;
      color: var(--text-primary);
      font-size: 12px;
    }
    .date-sep { color: var(--text-secondary); font-size: 12px; } /* Separator dash */

    .preset-btn {
      display: inline-block;
      margin: 3px 3px 3px 0;
      padding: 4px 10px;
      background: var(--bg-input);
      border: 1px solid var(--border-color);
      border-radius: 4px;
      color: var(--text-primary);
      font-size: 11px;
      cursor: pointer;
    }
    .preset-btn:hover { background: var(--accent-blue); border-color: var(--accent-blue); } /* Highlight on hover */

    .checkbox-row {
      display: flex;                /* Checkbox and label in a row */
      align-items: center;
      gap: 10px;
      padding: 6px 4px;
      font-size: 13px;
      color: var(--text-primary);
      cursor: pointer;
      white-space: nowrap;
    }
    .checkbox-row input[type="checkbox"] {
      flex-shrink: 0;
      width: 16px;
      height: 16px;
      accent-color: var(--accent-blue);
      cursor: pointer;
    }
    .checkbox-row:hover { color: var(--accent-blue); }      /* Highlight on hover */


    /* =============================
       RANKING SUMMARY & METRICS
       - Styles for summary cards and best performer info
    ============================= */
    .rank-summary {
      display: flex;                /* Row of summary cards */
      gap: 16px;                    /* Space between cards */
      margin-bottom: 24px;
      flex-wrap: wrap;              /* Responsive wrapping */
    }
    .rank-metric {
      display: flex;                /* Icon and value in a row */
      align-items: center;
      gap: 12px;
      background: var(--bg-input);
      border: 1px solid var(--border-color);
      border-radius: 10px;
      padding: 14px 18px;
      flex: 1;
      min-width: 140px;
    }
    .rank-metric-icon {
      width: 40px;
      height: 40px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      flex-shrink: 0;
    }
    .rank-metric-label {
      font-size: 10px;
      font-weight: 600;
      color: var(--text-secondary);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 4px;
    }
    .rank-metric-value {
      font-size: 20px;
      font-weight: 700;
      color: var(--text-primary);
    }
    .rank-best-info {
      flex: 2;                      /* Best performer card is wider */
      min-width: 200px;
      background: linear-gradient(135deg, #3b82f6, #6366f1);
      border-radius: 10px;
      padding: 14px 18px;
      font-size: 13px;
      color: #fff;
      line-height: 1.6;
    }
    .rank-best-info .rank-best-label {
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.7px;
      opacity: 0.75;
      margin-bottom: 5px;
    }
    .rank-best-info .rank-best-name {
      font-family: 'Sora', sans-serif;
      font-size: 16px;
      font-weight: 700;
      color: #fff;
      display: block;
      margin-bottom: 4px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .rank-best-info .rank-best-meta {
      font-size: 12px;
      opacity: 0.88;
      white-space: nowrap;
    }
    .rank-best-info .rank-best-score {
      font-weight: 700;
      font-size: 14px;
    }
    .rank-best-info .rank-best-rating {
      display: inline-block;
      background: rgba(255,255,255,0.2);
      border-radius: 20px;
      padding: 1px 9px;
      font-weight: 700;
      font-size: 11px;
      margin-left: 4px;
    }


    /* =============================
       PODIUM & RANKING LIST
       - Podium for top 3, leaderboard for others
    ============================= */
    .rank-main {
      display: grid;                /* Podium and list side by side */
      grid-template-columns: 1fr 1fr;
      gap: 24px;
      align-items: end;
    }
    @media (max-width: 800px) {
      .rank-main { grid-template-columns: 1fr; } /* Stack on small screens */
    }

    .podium-section {
      display: flex;                /* 3 podium slots in a row */
      align-items: flex-end;
      justify-content: center;
      gap: 12px;
      padding-bottom: 0;
    }
    .podium-slot {
      display: flex;
      flex-direction: column;
      align-items: center;
      flex: 1;
      max-width: 130px;
    }
    .podium-avatar {
      width: 68px;
      height: 68px;
      border-radius: 50%;           /* Circular avatar */
      border: 3px solid #fff;
      object-fit: cover;
      background: var(--bg-input);
      margin-bottom: 8px;
    }
    .podium-name {
      font-size: 12px;
      font-weight: 600;
      color: var(--text-primary);
      text-align: center;
      margin-bottom: 2px;
      line-height: 1.3;
    }
    .podium-score {
      font-size: 14px;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 2px;
    }
    .podium-rating {
      font-size: 10px;
      color: var(--text-secondary);
      margin-bottom: 6px;
    }
    .podium-base {
      width: 100%;
      border-radius: 8px 8px 0 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 6px;
      padding: 10px 0;
    }
    .podium-pos-label {
      font-size: 15px;
      font-weight: 700;
      color: #fff;
    }
    .podium-icon {
      font-size: 28px;
    }


    .rank-list {
      display: flex;                /* Leaderboard as a column */
      flex-direction: column;
      gap: 10px;
    }
    .rank-list-header {
      display: grid;                /* Grid for header row */
      grid-template-columns: 28px 36px 1fr 80px 42px 36px;
      gap: 8px;
      padding: 0 4px 6px;
      border-bottom: 1px solid var(--border-color);
      font-size: 10px;
      font-weight: 600;
      color: var(--text-secondary);
      text-transform: uppercase;
    }
    .rank-list-row {
      display: grid;                /* Grid for each employee row */
      grid-template-columns: 28px 36px 1fr 80px 42px 36px;
      gap: 8px;
      align-items: center;
      padding: 8px 4px;
      border-radius: 6px;
    }
    .rank-list-row:hover { background: var(--bg-input); }   /* Highlight on hover */
    .rank-num {
      font-size: 12px;
      font-weight: 600;
      color: var(--text-secondary);
      text-align: center;
    }
    .rank-avatar-sm {
      width: 32px;
      height: 32px;
      border-radius: 50%;           /* Small circular avatar */
      object-fit: cover;
      background: var(--bg-input);
      border: 2px solid var(--border-color);
    }
    .rank-list-name {
      font-size: 13px;
      font-weight: 500;
      color: var(--text-primary);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .rank-bar-outer {
      background: var(--bg-input);
      height: 8px;
      border-radius: 4px;
      overflow: hidden;
    }
    .rank-bar-inner {
      height: 100%;
      border-radius: 4px;
      transition: width 0.4s ease;  /* Animate bar width */
    }
    .rank-list-score {
      font-size: 12px;
      font-weight: 600;
      color: var(--text-primary);
      text-align: right;
    }
    .rank-list-pct {
      font-size: 11px;
      color: var(--text-secondary);
      text-align: right;
    }


    /* =============================
       CHART PANELS
       - Layout for trend, distribution, radar charts
    ============================= */
    .chart-wrap {
      height: 320px;                /* Fixed height for charts */
      position: relative;
      margin-top: 16px;
    }
    .charts-row {
      display: grid;                /* Two charts side by side */
      grid-template-columns: 1fr 1fr;
      gap: 24px;
    }
    @media (max-width: 900px) {
      .charts-row { grid-template-columns: 1fr; } /* Stack on small screens */
    }


    /* =============================
       BADGE STYLING
       - For achievement badges (Gold, Silver, etc.)
    ============================= */
    .badge-row {
      display: flex;                /* Row of badges */
      gap: 6px;
      align-items: center;
      flex-wrap: wrap;
      margin-top: 4px;
    }
    .badge-item {
      position: relative;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: transform 0.2s ease, filter 0.2s ease; /* Animate on hover */
    }
    .badge-item:hover {
      transform: scale(1.12);      /* Slightly enlarge on hover */
      filter: brightness(1.15);
    }
    .badge-item img {
      width: 28px;
      height: 28px;
      object-fit: contain;
      display: block;
    }
    .badge-tooltip {
      visibility: hidden;           /* Hidden by default */
      position: absolute;
      z-index: 1000;
      background: rgba(0, 0, 0, 0.9);
      color: #f0f4ff;
      text-align: center;
      border-radius: 6px;
      padding: 6px 10px;
      font-size: 11px;
      font-weight: 600;
      white-space: nowrap;
      bottom: 130%;
      left: 50%;
      transform: translateX(-50%);
      pointer-events: none;
      opacity: 0;
      transition: opacity 0.2s ease;
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    }
    .badge-tooltip::after {
      content: '';
      position: absolute;
      top: 100%;
      left: 50%;
      margin-left: -4px;
      border: 4px solid transparent;
      border-top-color: rgba(0, 0, 0, 0.9);
    }
    .badge-item:hover .badge-tooltip {
      visibility: visible;          /* Show tooltip on hover */
      opacity: 1;
    }


    /* =============================
       BADGE POSITIONING
       - For podium and leaderboard badge placement
    ============================= */
    .podium-name-section {
      display: flex;                /* Stack name and badges vertically */
      flex-direction: column;
      align-items: center;
      width: 100%;
    }
    .podium-name {
      font-size: 12px;
      font-weight: 600;
      color: var(--text-primary);
      text-align: center;
      margin-bottom: 2px;
      line-height: 1.3;
    }

    .rank-employee-cell {
      display: flex;                /* Stack name and badges vertically */
      flex-direction: column;
      gap: 2px;
    }
    .rank-list-name {
      font-size: 13px;
      font-weight: 500;
      color: var(--text-primary);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
</style>

<div class="content active fade-in">
  <div class="dash-grid">

    <!-- PANEL 1: FILTER ENGINE -->
    <div class="card col-full">
      <div class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
        </svg>
        Filter Engine
      </div>

      <div class="filter-row">

        <!-- Date Range -->
        <div class="filter-group">
          <label class="filter-label">Date Range</label>
          <button class="filter-btn" id="dateRangeBtn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
              <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <span id="dateRangeLabel">Any Date</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </button>
          <div class="filter-dropdown" id="dateRangeDropdown">
            <div class="dropdown-section">
              <span class="dropdown-label">Custom Range</span>
              <div class="date-input-row">
                <input type="date" id="dateFrom" class="date-input" value="2022-01-01">
                <span class="date-sep">–</span>
                <input type="date" id="dateTo" class="date-input" value="2025-12-31">
              </div>
            </div>
            <div class="dropdown-section">
              <span class="dropdown-label">Quick Select</span>
              <button class="preset-btn" data-preset="today">Today</button>
              <button class="preset-btn" data-preset="thisMonth">This Month</button>
              <button class="preset-btn" data-preset="past3Months">Past 3 Months</button>
              <button class="preset-btn" data-preset="allYears">All Years</button>
            </div>
          </div>
        </div>

        <!-- KPI Category -->
        <div class="filter-group">
          <label class="filter-label">KPI Category</label>
          <button class="filter-btn" id="kpiCategoryBtn">
            <span id="kpiCategoryLabel">All Categories</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </button>
          <div class="filter-dropdown" id="kpiCategoryDropdown">
            <label class="checkbox-row">
              <input type="checkbox" id="checkAll" checked>
              <span>All Categories</span>
            </label>
            <?php foreach ($kpi_categories as $cat): ?>
              <label class="checkbox-row">
                <input type="checkbox" class="kpi-cb" value="<?= htmlspecialchars($cat['kpi_group']) ?>" checked>
                <span><?= htmlspecialchars($cat['kpi_group']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

      </div>
    </div>

    <!-- PANEL 2: PERFORMANCE RANKING (Podium Style) -->
    <div class="card col-full">
      <div class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M8 21h8M12 17v4M17 3h4v4a4 4 0 0 1-4 4M7 3H3v4a4 4 0 0 0 4 4m6 0a4 4 0 0 1-4-4V3h8v4a4 4 0 0 1-4 4z"/>
        </svg>
        Performance Ranking
      </div>

      <div id="rankingContent">

        <!-- Summary Cards -->
        <div class="rank-summary">
          <div class="rank-metric">
            <div class="rank-metric-icon" style="background:rgba(245,158,11,0.2)">🏆</div>
            <div>
              <div class="rank-metric-label">Top KPI Score</div>
              <div class="rank-metric-value"><?= number_format($top_score, 2) ?></div>
            </div>
          </div>
          <div class="rank-metric">
            <div class="rank-metric-icon" style="background:rgba(59,130,246,0.2)">📊</div>
            <div>
              <div class="rank-metric-label">Department Avg</div>
              <div class="rank-metric-value"><?= number_format($avg_score, 2) ?></div>
            </div>
          </div>
          <div class="rank-metric">
            <div class="rank-metric-icon" style="background:rgba(16,185,129,0.2)">⭐</div>
            <div>
              <div class="rank-metric-label">Top Performers</div>
              <div class="rank-metric-value"><?= $top_performers ?> Staff</div>
            </div>
          </div>
          <div class="rank-best-info">
            <div class="rank-best-label">Best Performer</div>
            <span class="rank-best-name"><?= htmlspecialchars($analytics_data[0]['name'] ?? '–') ?></span>
            <div class="rank-best-meta">
              KPI <span class="rank-best-score"><?= number_format($top_score, 2) ?> / 5.00</span>
              <span class="rank-best-rating"><?= htmlspecialchars($analytics_data[0]['rating'] ?? '') ?></span>
            </div>
          </div>
        </div>

        <!-- Podium + List -->
        <div class="rank-main">

          <!-- Podium: 2nd | 1st | 3rd -->
          <div class="podium-section">
            <?php
            // Display order: 2nd (idx 1), 1st (idx 0), 3rd (idx 2)
            $podium = [
                ['idx'=>1, 'pos'=>'2nd', 'height'=>330, 'bg'=>'linear-gradient(to bottom,#94a3b8,#64748b)'],
                ['idx'=>0, 'pos'=>'1st', 'height'=>400, 'bg'=>'linear-gradient(to bottom,#f59e0b,#d97706)'],
                ['idx'=>2, 'pos'=>'3rd', 'height'=>280,  'bg'=>'linear-gradient(to bottom,#b45309,#92400e)'],
            ];
            $icons = ['1st'=>'1st.png', '2nd'=>'2nd.png', '3rd'=>'3rd.png'];

            foreach ($podium as $slot):
                $emp = $analytics_data[$slot['idx']] ?? null;
                $name  = $emp ? htmlspecialchars($emp['name'])      : '–';
                $score = $emp ? number_format($emp['kpi_score'], 2) : '–';
                $rating = $emp ? $emp['rating'] : '';
                $avatar = $emp ? buildAvatarUrl($emp['name']) : '';
            ?>
              <div class="podium-slot">
                <img class="podium-avatar" src="<?= $avatar ?>" alt="<?= $name ?>">
                <div class="podium-name"><?= $name ?></div>
                <div class="podium-score"><?= $score ?></div>
                <div class="podium-rating"><?= $rating ?></div>
                <div class="podium-base" style="height:<?= $slot['height'] ?>px; background:<?= $slot['bg'] ?>">
                  <div class="podium-pos-label"><?= $slot['pos'] ?></div>
                  <div class="podium-icon"><img src="/sales_assistant_KPI_monitoring_system/asset/rankings/<?= $icons[$slot['pos']] ?>" alt="<?= $slot['pos'] ?>" style="width:60px;height:60px;object-fit:contain;"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Horizontal Bar List: rank 4 and below -->
          <div class="rank-list">
            <div class="rank-list-header">
              <span>#</span>
              <span></span>
              <span>Name</span>
              <span>Score</span>
              <span style="text-align:right">KPI</span>
              <span style="text-align:right">%</span>
            </div>
            <?php foreach (array_slice($analytics_data, 3) as $i => $emp):
                $rank  = $i + 4;
                $score = $emp['kpi_score'];
                $color = KPICalculator::getPerformanceColor($score);
                $pct   = round(($score / 5) * 100);
                $avatar = buildAvatarUrl($emp['name']);
            ?>
              <div class="rank-list-row">
                <span class="rank-num"><?= $rank ?></span>
                <img class="rank-avatar-sm" src="<?= $avatar ?>" alt="<?= htmlspecialchars($emp['name']) ?>">
                <span class="rank-list-name"><?= htmlspecialchars($emp['name']) ?></span>
                <div class="rank-bar-outer">
                  <div class="rank-bar-inner" style="width:<?= $pct ?>%; background:<?= $color ?>"></div>
                </div>
                <span class="rank-list-score"><?= number_format($score, 2) ?></span>
                <span class="rank-list-pct"><?= $pct ?>%</span>
              </div>
            <?php endforeach; ?>
          </div>

        </div>
      </div>
    </div>

    <!-- PANEL 3: KPI TREND OVER TIME -->
    <div class="card col-full">
      <div class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="23 6 13.5 15.5 8.5 10.5 1 17"/><polyline points="17 6 23 6 23 12"/>
        </svg>
        KPI Performance Over Time
      </div>
      <div class="chart-wrap">
        <canvas id="trendChart"></canvas>
      </div>
    </div>

    <!-- PANEL 4 & 5: DISTRIBUTION + RADAR -->
    <div class="card col-full">
      <div class="charts-row">

        <div>
          <div class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
              <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
            </svg>
            Performance Score Distribution
          </div>
          <div class="chart-wrap">
            <canvas id="distributionChart"></canvas>
          </div>
        </div>

        <div>
          <div class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
            </svg>
            KPI Category Average Scores
          </div>
          <div class="chart-wrap">
            <canvas id="categoryChart"></canvas>
          </div>
        </div>

      </div>
    </div>

  </div>
</div>

<script>

// JAVASCRIPT: DATA INITIALIZATION & UTILITIES
// Data passed from PHP
const analyticsData = <?= json_encode($analytics_data) ?>;  // All employees with KPI scores
const kpiTrendData  = <?= json_encode($kpi_trend_data) ?>;  // Historical trend data
const kpiCategories = <?= json_encode($kpi_categories) ?>;  // KPI category names

// COLOUR MAP: Assign consistent colors to each KPI category
const categoryColors = {
    'Daily Sales Operations':                '#3B82F6',
    'Customer Service Quality':              '#06B6D4',
    'Sales Target Contribution':             '#8B5CF6',
    'Training, Learning & Team Contribution':'#EC4899',
    'Inventory & Cost Control':              '#F59E0B',
    'Store Operations Support':              '#10B981'
};

// Convert KPI score (0-5) to performance rating color
// Excellent (5), Good (4), Satisfactory (3), Poor (2), Very Poor (1)
function performanceColor(score) {
    if (score >= 5) return '#10b981';
    if (score >= 4) return '#3b82f6';
    if (score >= 3) return '#f59e0b';
    if (score >= 2) return '#ef4444';
    return '#6b7280';
}

function ratingLabel(score) {
    if (score >= 5) return 'Excellent';
    if (score >= 4) return 'Good';
    if (score >= 3) return 'Satisfactory';
    if (score >= 2) return 'Poor';
    return 'Very Poor';
}

// Avatar map injected from PHP (name → local asset URL)
const AVATAR_MAP = <?= $avatar_map_json ?>;
function avatarUrl(name) {
    return AVATAR_MAP[name] ?? '/sales_assistant_KPI_monitoring_system/asset/default.jpg';
}

// ── BADGE ASSIGNMENT LOGIC ───────────────────────────────────────────────────
/***************************
Title: Implementing Achievement System in Javascript
Author: Philipp
Date: 25th March 2017
Type: Javascript
Availability: https://gamedev.stackexchange.com/questions/139136/implementing-achievement-system-in-javascript
***************************/

const badgeDefinitions = {
    gold_sales: {
        name: 'Gold Sales',
        description: 'KPI ≥ 80',
        file: 'gold_sales.png',
        condition: (score) => score >= 4.0  // 80% of 5
    },
    sliver_sales: {
        name: 'Silver Sales',
        description: 'KPI ≥ 70',
        file: 'sliver_sales.png',
        condition: (score) => score >= 3.5  // 70% of 5
    },
    bronze_sales: {
        name: 'Bronze Sales',
        description: 'KPI ≥ 60',
        file: 'bronze_sales.png',
        condition: (score) => score >= 3.0  // 60% of 5
    },
    verified: {
        name: 'Verified',
        description: 'Consistently meets monthly target',
        file: 'verified.png',
        condition: (score) => score >= 3.0
    },
    featured: {
        name: 'Featured',
        description: 'Top highlighted sales assistant of the month',
        file: 'featured.png',
        specialCondition: true  // Applied to #1 employee only
    }
};

function getBadgesForEmployee(employee, isTopRanked = false) {
    const badges = [];
    const score = employee.filtered_score || employee.kpi_score;

    // Gold, Silver, Bronze - based on KPI thresholds
    if (badgeDefinitions.gold_sales.condition(score)) {
        badges.push('gold_sales');
    } else if (badgeDefinitions.sliver_sales.condition(score)) {
        badges.push('sliver_sales');
    } else if (badgeDefinitions.bronze_sales.condition(score)) {
        badges.push('bronze_sales');
    }

    // Verified badge for consistent performers
    if (badgeDefinitions.verified.condition(score)) {
        badges.push('verified');
    }

    // Featured badge only for #1 employee
    if (isTopRanked) {
        badges.push('featured');
    }

    // Debug: Log badge assignments
    console.log(`${employee.name}: score=${score.toFixed(2)}, badges=[${badges.join(', ')}]`);

    return badges;
}

// Render HTML for badge images with tooltips
function renderBadgeHTML(badges) {
    if (!badges || badges.length === 0) return '';
    
    return `<div class="badge-row">${badges.map(badgeKey => {
        const badge = badgeDefinitions[badgeKey];
        if (!badge) return '';
        return `
            <div class="badge-item" title="${badge.name}">
                <img src="/sales_assistant_KPI_monitoring_system/asset/badges/${badge.file}" 
                     alt="${badge.name}" 
                     loading="lazy">
                <div class="badge-tooltip">${badge.name}<br>(${badge.description})</div>
            </div>`;
    }).join('')}</div>`;
}


// FILTER STATE & EVENT LISTENERS
// Get DOM references for filter controls
const dateFrom = document.getElementById('dateFrom');
const dateTo   = document.getElementById('dateTo');
const checkAll = document.getElementById('checkAll');
const kpiCbs   = document.querySelectorAll('.kpi-cb');

function selectedCategories() {
    return Array.from(kpiCbs).filter(cb => cb.checked).map(cb => cb.value);
}

function filterTrendData() {
    const from = dateFrom.value, to = dateTo.value, cats = selectedCategories();
    return kpiTrendData.filter(d =>
        d.evaluation_date >= from && d.evaluation_date <= to && cats.includes(d.kpi_group)
    );
}

/***************************
Title: JavaScript Filter Explained: How the filter Method Works and Best Practices
Author: Penligent
Date: 19th December 2025
Type: Javascript Array Method Reference
Availability: https://www.penligent.ai/hackinglabs/javascript-filter-explained-how-the-filter-method-works-and-best-practices/
***************************/

// ── DATE RANGE PICKER ─────────────────────────────────────────────────────────
const dateRangeBtn      = document.getElementById('dateRangeBtn');
const dateRangeDropdown = document.getElementById('dateRangeDropdown');
const dateRangeLabel    = document.getElementById('dateRangeLabel');

dateRangeBtn.addEventListener('click', e => {
    e.stopPropagation();
    dateRangeDropdown.classList.toggle('open');
    kpiCategoryDropdown.classList.remove('open');
});

function fmtDate(d) {
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
}

document.querySelectorAll('.preset-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const today = new Date();
        switch (btn.dataset.preset) {
            case 'today':        dateFrom.value = dateTo.value = fmtDate(today); break;
            case 'thisMonth':
                dateFrom.value = fmtDate(new Date(today.getFullYear(), today.getMonth(), 1));
                dateTo.value   = fmtDate(new Date(today.getFullYear(), today.getMonth()+1, 0)); break;
            case 'past3Months':
                dateFrom.value = fmtDate(new Date(today.getFullYear(), today.getMonth()-3, today.getDate()));
                dateTo.value   = fmtDate(today); break;
            case 'allYears':
                dateFrom.value = '2022-01-01'; dateTo.value = '2025-12-31'; break;
        }
        updateDateLabel();
        dateRangeDropdown.classList.remove('open');
        refreshAll();
    });
});

[dateFrom, dateTo].forEach(el => el.addEventListener('change', () => { updateDateLabel(); refreshAll(); }));

function updateDateLabel() {
    const f = new Date(dateFrom.value + 'T00:00:00'), t = new Date(dateTo.value + 'T00:00:00');
    const fmt = d => d.toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' });
    dateRangeLabel.textContent = dateFrom.value === dateTo.value ? fmt(f) : fmt(f) + ' – ' + fmt(t);
}

// ── KPI CATEGORY FILTER ───────────────────────────────────────────────────────
const kpiCategoryBtn      = document.getElementById('kpiCategoryBtn');
const kpiCategoryDropdown = document.getElementById('kpiCategoryDropdown');
const kpiCategoryLabel    = document.getElementById('kpiCategoryLabel');

kpiCategoryBtn.addEventListener('click', e => {
    e.stopPropagation();
    kpiCategoryDropdown.classList.toggle('open');
    dateRangeDropdown.classList.remove('open');
});

checkAll.addEventListener('change', () => {
    kpiCbs.forEach(cb => cb.checked = checkAll.checked);
    updateCategoryLabel(); refreshAll();
});
kpiCbs.forEach(cb => cb.addEventListener('change', () => {
    checkAll.checked = Array.from(kpiCbs).every(c => c.checked);
    updateCategoryLabel(); refreshAll();
}));

function updateCategoryLabel() {
    const checked = Array.from(kpiCbs).filter(cb => cb.checked);
    if (!checked.length)                   kpiCategoryLabel.textContent = 'No Categories';
    else if (checked.length === kpiCbs.length) kpiCategoryLabel.textContent = 'All Categories';
    else if (checked.length <= 2)          kpiCategoryLabel.textContent = checked.map(c => c.value).join(', ');
    else kpiCategoryLabel.textContent = checked.slice(0,2).map(c => c.value).join(', ') + ' +' + (checked.length-2) + ' more';
}

// Stop clicks inside a dropdown from bubbling up and closing it
dateRangeDropdown.addEventListener('click', e => e.stopPropagation());
kpiCategoryDropdown.addEventListener('click', e => e.stopPropagation());

document.addEventListener('click', () => {
    dateRangeDropdown.classList.remove('open');
    kpiCategoryDropdown.classList.remove('open');
});


// CHART INSTANCES & RENDERING LOGIC
// Store references to Chart.js instances for later updates/destruction
let trendChart, distributionChart, categoryChart;

/***************************
Title: Chart.js Mixed Chart Types
Author: Chartjs Contributors
Date: 13th October 2025
Type: Library & Documentation Reference
Availability: https://www.chartjs.org/docs/latest/
***************************/

// ── PANEL 2: UPDATE RANKING ───────────────────────────────────────────────────
/***************************
Title: Introduction to Data Aggregation Methods in JavaScript
Author: CodeSignal
Date: 07th April 2022
Type: Data Aggregation
Availability: https://codesignal.com/learn/courses/projection-filtering-and-aggregation-of-data-streams-in-js/lessons/introduction-to-data-aggregation-methods-in-javascript
***************************/

function updateRanking() {
    const from = dateFrom.value, to = dateTo.value, cats = selectedCategories();

    // Per-employee average from filtered trend data
    const scoreMap = {};
    kpiTrendData.forEach(d => {
        if (d.evaluation_date < from || d.evaluation_date > to || !cats.includes(d.kpi_group)) return;
        if (!scoreMap[d.employee_id]) scoreMap[d.employee_id] = [];
        scoreMap[d.employee_id].push(parseFloat(d.avg_score));
    });

    const ranked = analyticsData
        .filter(e => scoreMap[e.employee_id])
        .map(e => {
            const s = scoreMap[e.employee_id];
            return { ...e, filtered_score: s.reduce((a,v)=>a+v,0) / s.length };
        })
        .sort((a,b) => b.filtered_score - a.filtered_score);

    if (!ranked.length) {
        document.getElementById('rankingContent').innerHTML =
            '<div style="color:var(--text-secondary);padding:32px;text-align:center">No data for selected filters</div>';
        return;
    }

    // Summary metrics
    const topScore  = ranked[0].filtered_score;
    const deptAvg   = ranked.reduce((s,e)=>s+e.filtered_score,0) / ranked.length;
    const topCount  = ranked.filter(e=>e.filtered_score>=4).length;
    const topEmp    = ranked[0];

    // Summary cards HTML
    const summaryHTML = `
        <div class="rank-summary">
          <div class="rank-metric">
            <div class="rank-metric-icon" style="background:rgba(245,158,11,0.2)">🏆</div>
            <div>
              <div class="rank-metric-label">Top KPI Score</div>
              <div class="rank-metric-value">${topScore.toFixed(2)}</div>
            </div>
          </div>
          <div class="rank-metric">
            <div class="rank-metric-icon" style="background:rgba(59,130,246,0.2)">📊</div>
            <div>
              <div class="rank-metric-label">Department Avg</div>
              <div class="rank-metric-value">${deptAvg.toFixed(2)}</div>
            </div>
          </div>
          <div class="rank-metric">
            <div class="rank-metric-icon" style="background:rgba(16,185,129,0.2)">⭐</div>
            <div>
              <div class="rank-metric-label">Top Performers</div>
              <div class="rank-metric-value">${topCount} Staff</div>
            </div>
          </div>
          <div class="rank-best-info">
            <div class="rank-best-label">Best Performer</div>
            <span class="rank-best-name">${topEmp.name}</span>
            <div class="rank-best-meta">
              KPI <span class="rank-best-score">${topScore.toFixed(2)} / 5.00</span>
              <span class="rank-best-rating">${ratingLabel(topScore)}</span>
            </div>
          </div>
        </div>`;

    // Podium HTML: display order 2nd, 1st, 3rd
    const podiumSlots = [
        { data: ranked[1], pos: '2nd', height: 330, bg: 'linear-gradient(to bottom,#94a3b8,#64748b)', icon: '2nd.png' },
        { data: ranked[0], pos: '1st', height: 400, bg: 'linear-gradient(to bottom,#f59e0b,#d97706)', icon: '1st.png' },
        { data: ranked[2], pos: '3rd', height: 280, bg: 'linear-gradient(to bottom,#b45309,#92400e)', icon: '3rd.png' },
    ];

    const podiumHTML = `
        <div class="podium-section">
          ${podiumSlots.map((slot, idx) => {
            if (!slot.data) return '<div class="podium-slot"></div>';
            const s = slot.data.filtered_score;
            const badges = getBadgesForEmployee(slot.data, idx === 1);  // idx 1 is the #1 employee
            const badgesHTML = renderBadgeHTML(badges);
            return `
              <div class="podium-slot">
                <img class="podium-avatar" src="${avatarUrl(slot.data.name)}" alt="${slot.data.name}">
                <div class="podium-name-section">
                  <div class="podium-name">${slot.data.name}</div>
                  ${badgesHTML}
                </div>
                <div class="podium-score">${s.toFixed(2)}</div>
                <div class="podium-rating">${ratingLabel(s)}</div>
                <div class="podium-base" style="height:${slot.height}px;background:${slot.bg}">
                  <div class="podium-pos-label">${slot.pos}</div>
                  <div class="podium-icon"><img src="/sales_assistant_KPI_monitoring_system/asset/rankings/${slot.icon}" alt="${slot.pos}" style="width:60px;height:60px;object-fit:contain;"></div>
                </div>
              </div>`;
          }).join('')}
        </div>`;


    // Horizontal bar list: rank 4+
    const listRows = ranked.slice(3).map((emp, i) => {
        const s     = emp.filtered_score;
        const color = performanceColor(s);
        const pct   = Math.round((s / 5) * 100);
        const badges = getBadgesForEmployee(emp, false);
        const badgesHTML = renderBadgeHTML(badges);
        return `
          <div class="rank-list-row">
            <span class="rank-num">${i+4}</span>
            <img class="rank-avatar-sm" src="${avatarUrl(emp.name)}" alt="${emp.name}">
            <div class="rank-employee-cell">
              <span class="rank-list-name">${emp.name}</span>
              ${badgesHTML}
            </div>
            <div class="rank-bar-outer">
              <div class="rank-bar-inner" style="width:${pct}%;background:${color}"></div>
            </div>
            <span class="rank-list-score">${s.toFixed(2)}</span>
            <span class="rank-list-pct">${pct}%</span>
          </div>`;
    }).join('');

    const listHTML = `
        <div class="rank-list">
          <div class="rank-list-header">
            <span>#</span><span></span><span>Name</span>
            <span>Score</span>
            <span style="text-align:right">KPI</span>
            <span style="text-align:right">%</span>
          </div>
          ${listRows}
        </div>`;

    document.getElementById('rankingContent').innerHTML = `
        ${summaryHTML}
        <div class="rank-main">${podiumHTML}${listHTML}</div>`;
}

// ── PANEL 3: TREND CHART ──────────────────────────────────────────────────────
/***************************
Title: Time-Series Line Chart with Multi-Dataset Support
Author: Chartjs Contributors
Date: 13rd October 2025
Type: Library Implementation
Availability: https://www.chartjs.org/docs/latest/charts/line.html
***************************/

function renderTrendChart() {
    const cats = selectedCategories();
    const data = filterTrendData();

    const byDate = {};
    data.forEach(d => {
        if (!byDate[d.date_label]) byDate[d.date_label] = {};
        byDate[d.date_label][d.kpi_group] = parseFloat(d.avg_score);
    });
    const dates = Object.keys(byDate).sort();

    const datasets = cats.map(cat => ({
        label: cat,
        data: dates.map(date => byDate[date][cat] ?? null),
        borderColor: categoryColors[cat] || '#3B82F6',
        backgroundColor: (categoryColors[cat] || '#3B82F6') + '20',
        borderWidth: 2, tension: 0.4, fill: false,
        pointRadius: 4, pointHoverRadius: 6,
        pointBackgroundColor: categoryColors[cat] || '#3B82F6',
        pointBorderColor: '#fff', pointBorderWidth: 2
    }));

    if (trendChart) trendChart.destroy();
    trendChart = new Chart(document.getElementById('trendChart').getContext('2d'), {
        type: 'line',
        data: { labels: dates, datasets },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { color: '#fff', font: { size: 11 }, usePointStyle: true } },
                tooltip: { backgroundColor: 'rgba(0,0,0,0.8)', cornerRadius: 4 }
            },
            scales: {
                y: {
                    beginAtZero: true, max: 5,
                    ticks: { color: '#fff', stepSize: 1 },
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    title: { display: true, text: 'KPI Performance', color: '#fff', font: { size: 12 } }
                },
                x: {
                    ticks: { color: '#fff', font: { size: 10 } },
                    grid: { display: false },
                    title: { display: true, text: 'Time', color: '#fff', font: { size: 12 } }
                }
            }
        }
    });
}

// ── PANEL 4: DISTRIBUTION CHART ───────────────────────────────────────────────
/***************************
Title: Bar Chart Distribution Analysis
Author: Chartjs Contributors
Date: 13rd October 2025
Type: Library Implementation
Availability: https://www.chartjs.org/docs/latest/charts/bar.html
***************************/

function renderDistributionChart() {
    const data = filterTrendData();
    const empScores = {};
    data.forEach(d => {
        if (!empScores[d.employee_id]) empScores[d.employee_id] = [];
        empScores[d.employee_id].push(parseFloat(d.avg_score));
    });

    const counts = { 'Excellent':0, 'Good':0, 'Satisfactory':0, 'Poor':0, 'Very Poor':0 };
    Object.values(empScores).forEach(scores => {
        const avg = scores.reduce((s,v)=>s+v,0) / scores.length;
        counts[ratingLabel(avg)]++;
    });

    const labels = ['5 – Excellent','4 – Good','3 – Satisfactory','2 – Poor','1 – Very Poor'];
    const values = [counts['Excellent'], counts['Good'], counts['Satisfactory'], counts['Poor'], counts['Very Poor']];
    const colors = ['#10b981','#3b82f6','#f59e0b','#ef4444','#6b7280'];

    if (distributionChart) distributionChart.destroy();
    distributionChart = new Chart(document.getElementById('distributionChart').getContext('2d'), {
        type: 'bar',
        data: { labels, datasets: [{ label:'Employees', data:values, backgroundColor:colors, borderRadius:6, borderSkipped:false }] },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: '#fff' },
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    title: { display: true, text: 'No. of Employees', color: '#fff', font: { size: 12 } }
                },
                x: {
                    ticks: { color: '#fff', font: { size: 10 } },
                    grid: { display: false },
                    title: { display: true, text: 'Score', color: '#fff', font: { size: 12 } }
                }
            }
        }
    });
}

// ── PANEL 5: RADAR CHART ──────────────────────────────────────────────────────
/***************************
Title: Radar/Spider Chart for Multi-Dimensional Data Visualization
Author: Chartjs Contributors
Date: 13rd October 2025
Type: Library Implementation
Availability: https://www.chartjs.org/docs/latest/charts/radar.html
***************************/

function renderCategoryChart() {
    const from = dateFrom.value, to = dateTo.value, cats = selectedCategories();

    const scoreMap = {};
    kpiTrendData.forEach(d => {
        if (d.evaluation_date < from || d.evaluation_date > to || !cats.includes(d.kpi_group)) return;
        if (!scoreMap[d.kpi_group]) scoreMap[d.kpi_group] = [];
        scoreMap[d.kpi_group].push(parseFloat(d.avg_score));
    });

    const scores = cats.map(cat => {
        const arr = scoreMap[cat] || [];
        return arr.length ? arr.reduce((s,v)=>s+v,0)/arr.length : 0;
    });
    const colors = cats.map(cat => categoryColors[cat] || '#3B82F6');

    if (categoryChart) categoryChart.destroy();
    categoryChart = new Chart(document.getElementById('categoryChart').getContext('2d'), {
        type: 'radar',
        data: {
            labels: cats,
            datasets: [{
                label: 'Avg Score', data: scores,
                borderColor: '#3B82F6', backgroundColor: 'rgba(59,130,246,0.2)',
                pointBackgroundColor: colors, pointBorderColor: '#fff',
                pointBorderWidth: 2, pointRadius: 5, borderWidth: 2, fill: true
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => 'Score: ' + ctx.parsed.r.toFixed(2) } }
            },
            scales: {
                r: {
                    min: 0, max: 5,
                    ticks: { color: '#fff', stepSize: 1, backdropColor: 'transparent' },
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    pointLabels: { color: '#fff', font: { size: 11 } }
                }
            }
        }
    });
}

// ════════════════════════════════════════════════════════════════════════════
// MASTER REFRESH & INITIALIZATION
// ════════════════════════════════════════════════════════════════════════════

// Update all panels with current filter selections
function refreshAll() {
    updateRanking();               // Refresh ranking podium and leaderboard
    renderTrendChart();            // Refresh trend line chart
    renderDistributionChart();     // Refresh rating distribution bar chart
    renderCategoryChart();         // Refresh KPI category radar chart
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    updateDateLabel();             // Format and display date range in UI
    updateCategoryLabel();         // Format and display selected categories in UI
    refreshAll();                  // Draw all charts and panels
});
</script>
