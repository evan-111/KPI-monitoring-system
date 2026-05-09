<?php
/**
 * SAKMS - Performance Evaluation Page
 * Handles both new staff registration + evaluation, and existing staff re-evaluation.
 */
//  KPI item definitions (mirrors kpi_item table in DB)
$KPI_ITEMS = [
    // Section 1 – Competency (group 1)
    'S1' => [
        ['id' => 19, 'group_id' => 1, 'code' => 'S1.1', 'desc' => 'Initiative'],
        ['id' => 20, 'group_id' => 1, 'code' => 'S1.2', 'desc' => 'Professional Conduct'],
        ['id' => 21, 'group_id' => 1, 'code' => 'S1.3', 'desc' => 'Reliability & Accountability'],
    ],
    // Section 2 – KPI Achievement groups
    'Daily Sales Operations' => [
        ['id' =>  1, 'group_id' => 2, 'code' => '1.1.1', 'desc' => 'Accurate sales transaction processing'],
        ['id' =>  2, 'group_id' => 2, 'code' => '1.1.2', 'desc' => 'Correct handling of cash, card, and e-wallet payments'],
        ['id' =>  3, 'group_id' => 2, 'code' => '1.1.3', 'desc' => 'Compliance with opening and closing procedures'],
        ['id' =>  4, 'group_id' => 2, 'code' => '1.1.4', 'desc' => 'Sales floor kept organised and presentable'],
    ],
    'Customer Service Quality' => [
        ['id' =>  5, 'group_id' => 3, 'code' => '1.2.1', 'desc' => 'Accurate information provided to customers'],
        ['id' =>  6, 'group_id' => 3, 'code' => '1.2.2', 'desc' => 'Complaints handled professionally and escalated appropriately'],
        ['id' =>  7, 'group_id' => 3, 'code' => '1.2.3', 'desc' => 'Compliance with service standards'],
    ],
    'Sales Target Contribution' => [
        ['id' =>  8, 'group_id' => 4, 'code' => '2.1.1', 'desc' => 'Achievement of assigned sales targets'],
        ['id' =>  9, 'group_id' => 4, 'code' => '2.1.2', 'desc' => 'Participation in sales campaigns and promotions'],
    ],
    'Training, Learning & Team Contribution' => [
        ['id' => 10, 'group_id' => 5, 'code' => '3.1.1', 'desc' => 'Attendance at required training programmes'],
        ['id' => 11, 'group_id' => 5, 'code' => '3.1.2', 'desc' => 'Applies learning to daily sales work'],
        ['id' => 12, 'group_id' => 5, 'code' => '3.1.3', 'desc' => 'Supports team operations during peak periods'],
        ['id' => 13, 'group_id' => 5, 'code' => '3.1.4', 'desc' => 'Participates in team activities or briefings'],
    ],
    'Inventory & Cost Control' => [
        ['id' => 14, 'group_id' => 6, 'code' => '4.1.1', 'desc' => 'Proper inventory handling'],
        ['id' => 15, 'group_id' => 6, 'code' => '4.1.2', 'desc' => 'Minimisation of stock loss or damage'],
    ],
    'Store Operations Support' => [
        ['id' => 16, 'group_id' => 7, 'code' => '4.2.1', 'desc' => 'Stock receiving and shelving support'],
        ['id' => 17, 'group_id' => 7, 'code' => '4.2.2', 'desc' => 'Promotion and display setup support'],
        ['id' => 18, 'group_id' => 7, 'code' => '4.2.3', 'desc' => 'Compliance with SOP and safety rules'],
    ],
];

// Section 2 group weights 
$GROUP_WEIGHTS = [
    'Daily Sales Operations'                  => 0.15,
    'Customer Service Quality'                => 0.15,
    'Sales Target Contribution'               => 0.15,
    'Training, Learning & Team Contribution'  => 0.10,
    'Inventory & Cost Control'                => 0.05,
    'Store Operations Support'                => 0.15,
];

// Section 1 item weights
$S1_WEIGHTS = [
    'Initiative'                   => 0.05,
    'Professional Conduct'         => 0.10,
    'Reliability & Accountability' => 0.10,
];

// Map S1 desc → item_id (used by the evaluation form to look up weight by item)
$S1_DESC_TO_ID = [
    'Initiative'                   => 19,
    'Professional Conduct'         => 20,
    'Reliability & Accountability' => 21,
];

// Reverse mapping of item_id into desc name
$S1_ID_TO_DESC = array_flip($S1_DESC_TO_ID);


//  Load latest saved weights from weight_config table
//  (overrides hardcoded defaults above if a saved config exists)

$tbl_check = $conn->query("SHOW TABLES LIKE 'weight_config'");
$wc = ($tbl_check && $tbl_check->num_rows > 0)
    ? $conn->query("SELECT s1_weights_json, s2_weights_json FROM weight_config ORDER BY config_id DESC LIMIT 1")
    : false;
if ($wc && $wc->num_rows > 0) {
    $wc_row = $wc->fetch_assoc();
    $db_s1  = json_decode($wc_row['s1_weights_json'], true);
    $db_s2  = json_decode($wc_row['s2_weights_json'], true);
    if ($db_s1) {
        foreach ($db_s1 as $name => $w) {
            if (isset($S1_WEIGHTS[$name])) $S1_WEIGHTS[$name] = (float)$w;
        }
    }
    if ($db_s2) {
        foreach ($db_s2 as $gname => $gw) {
            if (isset($GROUP_WEIGHTS[$gname])) $GROUP_WEIGHTS[$gname] = (float)$gw;
        }
    }
}

// ─────────────────────────────────────────────────────────
//  Fetch periods and existing staff for dropdowns
// ─────────────────────────────────────────────────────────
$periods_result = $conn->query("SELECT period_id, year FROM evaluation_period ORDER BY year DESC");
$periods = [];
while ($p = $periods_result->fetch_assoc()) {
    $periods[] = $p;
}

// Compute next staff code by finding the highest SA### number
$sc_result = $conn->query("SELECT staff_code FROM staff WHERE staff_code REGEXP '^SA[0-9]+$'");
$max_staff_num = 0;
while ($sc_row = $sc_result->fetch_assoc()) {
    $num = (int)substr($sc_row['staff_code'], 2);
    if ($num > $max_staff_num) $max_staff_num = $num;
}
$next_staff_code = 'SA' . str_pad($max_staff_num + 1, 3, '0', STR_PAD_LEFT);

$staff_result = $conn->query("SELECT staff_id, staff_code, full_name, role FROM staff WHERE status = 'Active' ORDER BY staff_code ASC");
$all_staff = [];
while ($s = $staff_result->fetch_assoc()) {
    $all_staff[] = $s;
}

// ─────────────────────────────────────────────────────────
//  Handle POST – save evaluation
// ─────────────────────────────────────────────────────────
$success_msg = '';
$error_msg   = '';
$prefill     = []; // scores to prefill when editing existing data

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_evaluation'])) {

    $mode          = $_POST['mode']      ?? 'existing';
    $eval_date     = $_POST['eval_date'] ?? '';          // YYYY-MM-DD from date picker
    $sup_comments  = trim($_POST['supervisor_comments'] ?? '');
    $training_rec  = trim($_POST['training_recommendations'] ?? '');

    // Derive period_id from the year of the selected date
    $period_id     = 0;
    $date_recorded = '';
    if ($eval_date !== '') {
        $eval_year = (int)date('Y', strtotime($eval_date));
        $date_recorded = $eval_date;

        // Look up existing period
        $yr_row = $conn->prepare("SELECT period_id FROM evaluation_period WHERE year = ?");
        $yr_row->bind_param("i", $eval_year);
        $yr_row->execute();
        $yr_data = $yr_row->get_result()->fetch_assoc();

        if ($yr_data) {
            // Period already exists
            $period_id = (int)$yr_data['period_id'];
        } else {
            // Auto-create a new evaluation period for this year
            $y_start = $eval_year . '-01-01';
            $y_end   = $eval_year . '-12-31';
            $ins_period = $conn->prepare("INSERT INTO evaluation_period (year, start_date, end_date) VALUES (?, ?, ?)");
            $ins_period->bind_param("iss", $eval_year, $y_start, $y_end);
            $ins_period->execute();
            $period_id = (int)$conn->insert_id;
        }
    }

    // 1. Resolve staff_id 
    $staff_id = 0;

    if ($mode === 'new') {
        $new_name  = trim($_POST['new_name']  ?? '');
        $new_code  = trim($_POST['new_code']  ?? '');
        $new_role  = trim($_POST['new_role']  ?? 'Sales Assistant');

        if ($new_name === '' || $new_code === '') {
            $error_msg = 'Please enter both staff code and full name for the new staff member.';
        } else {
            // Check code uniqueness
            $chk = $conn->prepare("SELECT staff_id FROM staff WHERE staff_code = ?");
            $chk->bind_param("s", $new_code);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $error_msg = "Staff code '$new_code' already exists. Please use a unique code.";
            } else {
                $ins = $conn->prepare("INSERT INTO staff (staff_code, full_name, role, status) VALUES (?, ?, ?, 'Active')");
                $ins->bind_param("sss", $new_code, $new_name, $new_role);
                $ins->execute();
                $staff_id = $conn->insert_id;
            }
        }
    } else {
        $staff_id = (int)($_POST['staff_id'] ?? 0);
        if ($staff_id === 0) $error_msg = 'Please select a staff member.';
    }

    if ($error_msg === '' && ($staff_id === 0 || $period_id === 0)) {
        $error_msg = 'Invalid staff or evaluation date. Please check your selection and try again.';
    }

    // date_recorded already set above from $eval_date
    if ($error_msg === '' && $date_recorded === '') {
        $error_msg = 'Please select an evaluation date.';
    }

    //  3. Save kpi_score rows
    if ($error_msg === '') {
        $all_item_ids = [];
        foreach ($KPI_ITEMS as $items) {
            foreach ($items as $item) {
                $all_item_ids[] = $item['id'];
            }
        }

        $supervisor_profile_id = 1; // single-supervisor system
        $upsert = $conn->prepare("
            INSERT INTO kpi_score (staff_id, kpi_item_id, period_id, score, date_recorded, evaluated_by)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE score = VALUES(score), date_recorded = VALUES(date_recorded), evaluated_by = VALUES(evaluated_by)
        ");

        foreach ($all_item_ids as $item_id) {
            $score = (int)($_POST['score_' . $item_id] ?? 3);
            $score = max(1, min(5, $score)); 
            $upsert->bind_param("iiiisi", $staff_id, $item_id, $period_id, $score, $date_recorded, $supervisor_profile_id);
            $upsert->execute();
        }

        // ── 4a. Generate AI training recommendation ───────
        require_once __DIR__ . '/../includes/gemini.php';
        require_once __DIR__ . '/../includes/config.php';

        // Build score map: KPI description => score
        $kpiScoresForAI = [];
        foreach ($KPI_ITEMS as $items) {
            foreach ($items as $item) {
                $kpiScoresForAI[$item['desc']] = max(1, min(5, (int)($_POST['score_' . $item['id']] ?? 3)));
            }
        }

        // Fetch past recommendations from DB as style examples
        $exRes = $conn->query("SELECT training_recommendations FROM supervisor_feedback WHERE training_recommendations IS NOT NULL AND training_recommendations != '' ORDER BY feedback_id DESC LIMIT 10");
        $exampleRecs = [];
        while ($ex = $exRes->fetch_assoc()) {
            $exampleRecs[] = $ex['training_recommendations'];
        }

        // Calculate KPI score for context
        $kpiForAI      = KPICalculator::calculateKPI($conn, $staff_id, $period_id);
        $training_rec  = generateTrainingRecommendation(
            $kpiScoresForAI,
            round((float)$kpiForAI['overall'], 2),
            $kpiForAI['rating'],
            $exampleRecs,
            GEMINI_API_KEY
        );

        // ── 4. Save supervisor_feedback ──────────────────
        // Check if a row already exists for this staff + period
        $chk_sf = $conn->prepare("SELECT feedback_id FROM supervisor_feedback WHERE staff_id = ? AND period_id = ? ORDER BY feedback_id DESC LIMIT 1");
        $chk_sf->bind_param("ii", $staff_id, $period_id);
        $chk_sf->execute();
        $existing_sf = $chk_sf->get_result()->fetch_assoc();

        if ($existing_sf) {
            // Update existing row
            $sf = $conn->prepare("UPDATE supervisor_feedback SET supervisor_name = ?, supervisor_comments = ?, training_recommendations = ?, supervisor_id = ? WHERE feedback_id = ?");
            $sf->bind_param("sssii", $_SESSION['supervisor_name'], $sup_comments, $training_rec, $supervisor_profile_id, $existing_sf['feedback_id']);
        } else {
            // Insert new row — supervisor_id links to supervisor_profile
            $sf = $conn->prepare("INSERT INTO supervisor_feedback (staff_id, period_id, supervisor_name, supervisor_comments, training_recommendations, supervisor_id) VALUES (?, ?, ?, ?, ?, ?)");
            $sf->bind_param("iisssi", $staff_id, $period_id, $_SESSION['supervisor_name'], $sup_comments, $training_rec, $supervisor_profile_id);
        }
        $sf->execute();

        // ── 5. Recalculate & save performance_summary ────
        // Pass current saved weights so the score reflects the active weight_config
        $weight_overrides = [
            's1' => $S1_WEIGHTS,    // ['Initiative' => 0.05, ...]
            's2' => $GROUP_WEIGHTS, // ['Daily Sales Operations' => 0.15, ...]
        ];
        $kpi = KPICalculator::calculateKPI($conn, $staff_id, $period_id, $weight_overrides);

        $grade_label = $kpi['rating'];
        // Map rating to interpretation_id
        $interp_map = ['Excellent' => 1, 'Good' => 2, 'Satisfactory' => 3, 'Poor' => 4, 'Very Poor' => 5];
        $interp_id  = $interp_map[$grade_label] ?? 3;

        $s1 = $kpi['section1'];
        $s2 = $kpi['section2'];
        $fs = $kpi['overall'];

        // Determine which weight_config was active at evaluation time
        $cfg_used_id = null;
        $cfg_row = $conn->query("SELECT config_id FROM weight_config ORDER BY config_id DESC LIMIT 1");
        if ($cfg_row && $cfg_row->num_rows > 0) {
            $cfg_used_id = (int)$cfg_row->fetch_assoc()['config_id'];
        }

        $ps = $conn->prepare("
            INSERT INTO performance_summary (staff_id, period_id, section1_score, section2_score, final_score, grade_label, interpretation_id, config_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                section1_score    = VALUES(section1_score),
                section2_score    = VALUES(section2_score),
                final_score       = VALUES(final_score),
                grade_label       = VALUES(grade_label),
                interpretation_id = VALUES(interpretation_id),
                config_id         = VALUES(config_id)
        ");
        $ps->bind_param("iidddsii", $staff_id, $period_id, $s1, $s2, $fs, $grade_label, $interp_id, $cfg_used_id);
        $ps->execute();

        $success_msg = "Evaluation saved successfully! Final Score: <strong>" . number_format($fs, 4) . "</strong> – $grade_label"
            . "<br><span style='color:#a78bfa;'> AI Training Recommendation:</span> " . htmlspecialchars($training_rec);

        // ── 6. Fire at-risk notification if score < 3.0 ──
        if ($fs < 3.0) {
            if ($mode === 'new') {
                $notif_code = $new_code;
                $notif_name = $new_name;
            } else {
                $srow = $conn->prepare("SELECT staff_code, full_name FROM staff WHERE staff_id = ?");
                $srow->bind_param("i", $staff_id);
                $srow->execute();
                $sdata = $srow->get_result()->fetch_assoc();
                $notif_code = $sdata['staff_code'] ?? '';
                $notif_name = $sdata['full_name']  ?? '';
            }

            // Remove any previous unread notification for this staff+period to avoid duplicates
            $conn->query("DELETE FROM at_risk_notifications WHERE staff_id = $staff_id AND period_id = $period_id AND is_read = 0");

            $ins_notif = $conn->prepare("
                INSERT INTO at_risk_notifications (staff_id, staff_code, staff_name, kpi_score, period_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $ins_notif->bind_param("issdi", $staff_id, $notif_code, $notif_name, $fs, $period_id);
            $ins_notif->execute();
        }
    }
}

//  Prefill existing scores when user picks a staff + period
//  (via AJAX GET request: ?action=load_scores&staff_id=X&period_id=Y)
if (isset($_GET['action']) && $_GET['action'] === 'load_scores') {
    header('Content-Type: application/json');
    $sid = (int)($_GET['staff_id']  ?? 0);
    $pid = (int)($_GET['period_id'] ?? 0);

    $rows = [];
    if ($sid && $pid) {
        $q = $conn->prepare("
            SELECT ks.kpi_item_id, ks.score,
                   sf.supervisor_comments, sf.training_recommendations
            FROM kpi_score ks
            LEFT JOIN supervisor_feedback sf ON sf.staff_id = ks.staff_id AND sf.period_id = ks.period_id
            WHERE ks.staff_id = ? AND ks.period_id = ?
        ");
        $q->bind_param("ii", $sid, $pid);
        $q->execute();
        $res = $q->get_result();
        $sup_c = ''; $train = '';
        while ($r = $res->fetch_assoc()) {
            $rows[$r['kpi_item_id']] = (int)$r['score'];
            $sup_c  = $r['supervisor_comments']     ?? '';
            $train  = $r['training_recommendations'] ?? '';
        }
        echo json_encode(['scores' => $rows, 'supervisor_comments' => $sup_c, 'training_recommendations' => $train]);
    } else {
        echo json_encode(['scores' => [], 'supervisor_comments' => '', 'training_recommendations' => '']);
    }
    exit();
}


//  Save weight config
//  (via AJAX POST request: ?action=save_weights)
if (isset($_GET['action']) && $_GET['action'] === 'save_weights') {
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['ok' => false, 'error' => 'POST required']);
        exit();
    }

    $s1_raw = $_POST['s1_json'] ?? '';
    $s2_raw = $_POST['s2_json'] ?? '';

    if ($s1_raw === '' || $s2_raw === '') {
        echo json_encode(['ok' => false, 'error' => 'Missing s1_json or s2_json']);
        exit();
    }

    $new_s1 = json_decode($s1_raw, true);
    $new_s2 = json_decode($s2_raw, true);

    if (!is_array($new_s1) || !is_array($new_s2) || count($new_s1) === 0 || count($new_s2) === 0) {
        echo json_encode(['ok' => false, 'error' => 'Invalid JSON payload']);
        exit();
    }

    // Append a new history row — NEVER delete old rows.
    // Deleting would trigger ON DELETE SET NULL cascade and wipe config_id
    // references in performance_summary for previously evaluated staff.
    $max_row = $conn->query("SELECT COALESCE(MAX(config_id),0)+1 AS nxt FROM weight_config")->fetch_assoc();
    $conn->query("ALTER TABLE weight_config AUTO_INCREMENT = " . (int)$max_row['nxt']);

    $stmt = $conn->prepare("INSERT INTO weight_config (s1_weights_json, s2_weights_json) VALUES (?, ?)");
    $stmt->bind_param("ss", $s1_raw, $s2_raw);

    if ($stmt->execute()) {
        echo json_encode(['ok' => true, 'config_id' => $conn->insert_id]);
    } else {
        echo json_encode(['ok' => false, 'error' => $conn->error]);
    }
    exit();
}
?>

<div class="content active fade-in">

<div class="dash-grid">

<?php if ($success_msg): ?>
<div class="col-full" style="background: rgba(34,197,94,0.12); border:1px solid #22c55e; border-radius:10px; padding:14px 18px; color:#22c55e; font-size:13px;">
   <?php echo $success_msg; ?>
</div>
<?php endif; ?>

<?php if ($error_msg): ?>
<div class="col-full" style="background: rgba(239,68,68,0.12); border:1px solid #ef4444; border-radius:10px; padding:14px 18px; color:#ef4444; font-size:13px;">
   <?php echo safe($error_msg); ?>
</div>
<?php endif; ?>


   <!--  STEP 1 — Mode & Period Selector-->
<div class="card col-full">
  <div class="card-title">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
    </svg>
    Performance Evaluation Setup
    <button type="button" id="change-weight-btn"
      style="margin-left:auto; padding:6px 14px; background:transparent; border:1px solid var(--border); border-radius:8px; color:var(--text-secondary); font-size:11px; font-weight:600; cursor:pointer; transition:.2s;">
       Change Weight
    </button>
  </div>

  <!-- Mode Toggle -->
  <div style="display:flex; gap:10px; margin-top:16px;">
    <button type="button" id="btn-existing"
      style="flex:1; padding:10px; border-radius:8px; border:2px solid var(--accent); background:var(--accent); color:#fff; font-size:12px; font-weight:600; cursor:pointer; transition:.2s;">
       Evaluate Existing Staff
    </button>
    <button type="button" id="btn-new"
      style="flex:1; padding:10px; border-radius:8px; border:2px solid var(--border); background:transparent; color:var(--text-secondary); font-size:12px; font-weight:600; cursor:pointer; transition:.2s;">
       Register New Staff & Evaluate
    </button>
  </div>

  <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:20px;">

    <!-- Existing Staff Dropdown -->
    <div id="existing-staff-wrap">
      <label style="font-size:11px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px;">Select Staff Member</label>
      <select id="sel-staff"
        style="width:100%; padding:10px 12px; background:var(--bg-input,#141c2b); border:1px solid var(--border); border-radius:8px; color:var(--text-primary); font-size:13px;">
        <option value="">— Choose staff —</option>
        <?php foreach ($all_staff as $s): ?>
          <option value="<?php echo $s['staff_id']; ?>">
            <?php echo safe($s['staff_code'] . ' - ' . $s['full_name'] . ' (' . $s['role'] . ')'); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- New Staff Fields -->
    <div id="new-staff-wrap" style="display:none; grid-column:1/3;">
      <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
        <div>
          <label style="font-size:11px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px;">
            Staff Code
            <span style="color:var(--text-muted); font-weight:400; margin-left:4px;">(auto-generated)</span>
          </label>
          <input type="text" id="new-code" readonly
            value="<?php echo safe($next_staff_code); ?>"
            style="width:100%; padding:10px 12px; background:var(--bg-input,#141c2b); border:1px solid var(--accent); border-radius:8px; color:var(--accent); font-size:13px; font-weight:600; cursor:default;"/>
        </div>
        <div>
          <label style="font-size:11px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px;">Full Name *</label>
          <input type="text" id="new-name" placeholder="e.g. Ahmad Zulkifli"
            style="width:100%; padding:10px 12px; background:var(--bg-input,#141c2b); border:1px solid var(--border); border-radius:8px; color:var(--text-primary); font-size:13px;"/>
        </div>
        <div>
          <label style="font-size:11px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px;">Role</label>
          <input type="text" id="new-role" value="Sales Assistant"
            style="width:100%; padding:10px 12px; background:var(--bg-input,#141c2b); border:1px solid var(--border); border-radius:8px; color:var(--text-primary); font-size:13px;"/>
        </div>
      </div>
    </div>

    <!-- Date Picker -->
    <div>
      <label style="font-size:11px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px;">Evaluation Date</label>
      <input type="date" id="sel-date"
        style="width:100%; padding:10px 12px; background:var(--bg-input,#141c2b); border:1px solid var(--border); border-radius:8px; color:var(--text-primary); font-size:13px; cursor:pointer; color-scheme:dark;"/>
      <div id="date-year-hint" style="font-size:11px; color:var(--text-muted); margin-top:5px; display:none;">
         Evaluation period: <strong id="date-year-label" style="color:var(--accent);"></strong>
      </div>
      <div id="date-year-warn" style="font-size:11px; color:#ef4444; margin-top:5px; display:none;">
         No evaluation period found for the selected year. A new period will be created automatically.
      </div>
    </div>
  </div>

  <div id="load-hint" style="margin-top:12px; font-size:11px; color:var(--text-muted); display:none;">
     Existing scores for this period have been loaded into the form below.
  </div>
</div>

<!--EVALUATION FORM -->
<div class="card col-full" id="eval-form-card">
  <div class="card-title">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
    </svg>
    KPI Evaluation Form
    <span id="staff-badge" style="margin-left:auto; font-size:11px; color:var(--accent); font-weight:600;"></span>
  </div>

  <form method="POST" id="evaluation-form">
    <input type="hidden" name="submit_evaluation" value="1"/>
    <input type="hidden" name="mode"      id="f-mode"      value="existing"/>
    <input type="hidden" name="staff_id"  id="f-staff-id"  value=""/>
    <input type="hidden" name="eval_date" id="f-eval-date" value=""/>
    <input type="hidden" name="new_name"  id="f-new-name"  value=""/>
    <input type="hidden" name="new_code"  id="f-new-code"  value=""/>
    <input type="hidden" name="new_role"  id="f-new-role"  value=""/>

    <!-- Score Guide -->
    <div style="display:flex; gap:8px; flex-wrap:wrap; margin:16px 0 20px;">
      <?php
        $guide = [1=>'Very Poor',2=>'Poor',3=>'Satisfactory',4=>'Good',5=>'Excellent'];
        $gcol  = [1=>'#7f1d1d',2=>'#ef4444',3=>'#f59e0b',4=>'#3b82f6',5=>'#22c55e'];
        foreach ($guide as $n => $lbl):
      ?>
        <div style="background:<?php echo $gcol[$n]; ?>22; border:1px solid <?php echo $gcol[$n]; ?>; border-radius:6px; padding:4px 10px; font-size:11px; color:<?php echo $gcol[$n]; ?>; font-weight:600;">
          <?php echo $n; ?> – <?php echo $lbl; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- SECTION 1 -->
    <div style="background:rgba(59,130,246,0.06); border:1px solid rgba(59,130,246,0.2); border-radius:10px; padding:16px; margin-bottom:20px;">
      <div style="font-size:13px; font-weight:700; color:#3b82f6; margin-bottom:14px;">
        SECTION 1 – Core Competencies
        <span style="font-size:11px; font-weight:400; color:var(--text-muted); margin-left:8px;">25% of total score</span>
      </div>

      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr style="border-bottom:1px solid var(--border);">
            <th style="text-align:left;  padding:8px 6px; font-size:11px; color:var(--text-muted); font-weight:600; text-transform:uppercase;">Competency</th>
            <th style="text-align:center; padding:8px 6px; font-size:11px; color:var(--text-muted); font-weight:600; text-transform:uppercase; width:80px;">Weight</th>
            <th style="text-align:center; padding:8px 6px; font-size:11px; color:var(--text-muted); font-weight:600; text-transform:uppercase; width:140px;">Score (1–5)</th>
            <th style="text-align:center; padding:8px 6px; font-size:11px; color:var(--text-muted); font-weight:600; text-transform:uppercase; width:110px;">Weighted Score</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($KPI_ITEMS['S1'] as $item):
            $w = $S1_WEIGHTS[$item['desc']];
          ?>
          <tr style="border-bottom:1px solid var(--border);" class="score-row" data-item-id="<?php echo $item['id']; ?>" data-weight="<?php echo $w; ?>" data-section="1">
            <td style="padding:10px 6px; font-size:12px; color:var(--text-primary);"><?php echo safe($item['desc']); ?></td>
            <td style="padding:10px 6px; text-align:center; font-size:12px; color:var(--text-secondary);"><?php echo $w; ?></td>
            <td style="padding:10px 6px; text-align:center;">
              <div style="display:flex; justify-content:center; gap:5px;" class="star-group" data-item-id="<?php echo $item['id']; ?>">
                <?php for ($n = 1; $n <= 5; $n++): ?>
                  <button type="button" class="star-btn" data-val="<?php echo $n; ?>"
                    style="width:28px; height:28px; border-radius:6px; border:1px solid var(--border); background:transparent; color:var(--text-secondary); font-size:12px; font-weight:600; cursor:pointer; transition:.15s;">
                    <?php echo $n; ?>
                  </button>
                <?php endfor; ?>
              </div>
              <input type="hidden" name="score_<?php echo $item['id']; ?>" id="score-<?php echo $item['id']; ?>" value=""/>
            </td>
            <td style="padding:10px 6px; text-align:center;">
              <span id="ws-<?php echo $item['id']; ?>" style="font-size:12px; font-weight:700; color:#3b82f6;">
                -
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
          <tr style="background:rgba(59,130,246,0.08);">
            <td colspan="3" style="padding:10px 6px; font-size:12px; font-weight:700; color:var(--text-primary);">Section 1 Total</td>
            <td style="padding:10px 6px; text-align:center; font-size:13px; font-weight:700; color:#3b82f6;" id="s1-total">0.75</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- SECTION 2-->
    <div style="background:rgba(6,182,212,0.06); border:1px solid rgba(6,182,212,0.2); border-radius:10px; padding:16px; margin-bottom:20px;">
      <div style="font-size:13px; font-weight:700; color:#06b6d4; margin-bottom:14px;">
        SECTION 2 - KPI Achievement
        <span style="font-size:11px; font-weight:400; color:var(--text-muted); margin-left:8px;">75% of total score</span>
      </div>

      <?php
        $s2_group_colors = [
          'Daily Sales Operations'                  => '#3b82f6',
          'Customer Service Quality'                => '#06b6d4',
          'Sales Target Contribution'               => '#8b5cf6',
          'Training, Learning & Team Contribution'  => '#ec4899',
          'Inventory & Cost Control'                => '#f59e0b',
          'Store Operations Support'                => '#10b981',
        ];
        foreach ($KPI_ITEMS as $group_name => $items):
          if ($group_name === 'S1') continue;
          $gw     = $GROUP_WEIGHTS[$group_name];
          $gcolor = $s2_group_colors[$group_name] ?? '#3b82f6';
          $gid    = $items[0]['group_id'];
          $n_items = count($items);
          // Per-item weight = group_weight / number of items in group
          $item_w = round($gw / $n_items, 6);
      ?>
      <div style="border-left:3px solid <?php echo $gcolor; ?>; padding-left:12px; margin-bottom:20px;">

        <!-- Group header row -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
          <div style="font-size:12px; font-weight:700; color:<?php echo $gcolor; ?>;"><?php echo safe($group_name); ?></div>
          <div style="font-size:11px; color:var(--text-muted);">
            Group Weight: <strong style="color:<?php echo $gcolor; ?>"><?php echo ($gw * 100); ?>%</strong>
            &nbsp;|&nbsp; Group Total = <strong id="gws-<?php echo $gid; ?>" style="color:<?php echo $gcolor; ?>">–</strong>
          </div>
        </div>

        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr style="border-bottom:1px solid var(--border);">
              <th style="text-align:left;   padding:8px 6px; font-size:11px; color:var(--text-muted); font-weight:600; text-transform:uppercase;">KPI Item</th>
              <th style="text-align:center; padding:8px 6px; font-size:11px; color:var(--text-muted); font-weight:600; text-transform:uppercase; width:80px;">Weight</th>
              <th style="text-align:center; padding:8px 6px; font-size:11px; color:var(--text-muted); font-weight:600; text-transform:uppercase; width:140px;">Score (1–5)</th>
              <th style="text-align:center; padding:8px 6px; font-size:11px; color:var(--text-muted); font-weight:600; text-transform:uppercase; width:110px;">Weighted Score</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
            <tr style="border-bottom:1px solid var(--border);" class="score-row"
                data-item-id="<?php echo $item['id']; ?>"
                data-group-id="<?php echo $gid; ?>"
                data-group-weight="<?php echo $gw; ?>"
                data-item-weight="<?php echo $item_w; ?>"
                data-section="2">
              <td style="padding:10px 6px; font-size:12px; color:var(--text-primary);"><?php echo safe($item['desc']); ?></td>
              <td style="padding:10px 6px; text-align:center; font-size:12px; color:var(--text-secondary);"><?php echo $item_w; ?></td>
              <td style="padding:10px 6px; text-align:center;">
                <div style="display:flex; justify-content:center; gap:5px;" class="star-group" data-item-id="<?php echo $item['id']; ?>">
                  <?php for ($n = 1; $n <= 5; $n++): ?>
                    <button type="button" class="star-btn" data-val="<?php echo $n; ?>"
                      style="width:28px; height:28px; border-radius:6px; border:1px solid var(--border); background:transparent; color:var(--text-secondary); font-size:12px; font-weight:600; cursor:pointer; transition:.15s;">
                      <?php echo $n; ?>
                    </button>
                  <?php endfor; ?>
                </div>
                <input type="hidden" name="score_<?php echo $item['id']; ?>" id="score-<?php echo $item['id']; ?>" value=""/>
              </td>
              <td style="padding:10px 6px; text-align:center;">
                <span id="ws-<?php echo $item['id']; ?>" style="font-size:12px; font-weight:700; color:<?php echo $gcolor; ?>;">–</span>
              </td>
            </tr>
            <?php endforeach; ?>

            <!-- Group subtotal row -->
            <tr style="background:<?php echo $gcolor; ?>11;">
              <td colspan="3" style="padding:10px 6px; font-size:12px; font-weight:700; color:var(--text-primary);">
                <?php echo safe($group_name); ?> Total
              </td>
              <td style="padding:10px 6px; text-align:center; font-size:13px; font-weight:700; color:<?php echo $gcolor; ?>;" id="gws-total-<?php echo $gid; ?>">–</td>
            </tr>
          </tbody>
        </table>
      </div>
      <?php endforeach; ?>

      <!-- Section 2 grand total -->
      <table style="width:100%; border-collapse:collapse; margin-top:4px;">
        <tr style="background:rgba(6,182,212,0.12);">
          <td style="padding:10px 6px; font-size:12px; font-weight:700; color:var(--text-primary);">Section 2 Total</td>
          <td style="padding:10px 6px; text-align:right; font-size:13px; font-weight:700; color:#06b6d4;" id="s2-total">–</td>
        </tr>
      </table>
    </div>

    <!-- FINAL SCORE -->
    <div style="background:rgba(139,92,246,0.08); border:1px solid rgba(139,92,246,0.25); border-radius:10px; padding:16px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between;">
      <div>
        <div style="font-size:13px; font-weight:700; color:var(--text-primary);">FINAL PERFORMANCE SCORE</div>
        <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">Section 1 + Section 2</div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:32px; font-weight:800; color:#8b5cf6;" id="final-score">–</div>
        <div id="final-rating" style="margin-top:4px; font-size:12px; font-weight:700; padding:4px 12px; border-radius:6px; display:inline-block; background:#8b5cf622; color:#8b5cf6;">–</div>
      </div>
    </div>

    <!-- COMMENTS & DEVELOPMENT -->
    <div style="background:rgba(245,158,11,0.06); border:1px solid rgba(245,158,11,0.2); border-radius:10px; padding:16px; margin-bottom:20px;">
      <div style="font-size:13px; font-weight:700; color:#f59e0b; margin-bottom:14px;">
        Comments &amp; Development Plan
      </div>
      <div style="margin-bottom:14px;">
        <label style="font-size:11px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px;">Supervisor Comments</label>
        <textarea name="supervisor_comments" id="t-sup-comments" rows="4" placeholder="Enter performance comments, observations, strengths…"
          style="width:100%; padding:10px 12px; background:var(--bg-input,#141c2b); border:1px solid var(--border); border-radius:8px; color:var(--text-primary); font-size:12px; resize:vertical; line-height:1.6;"></textarea>
      </div>
      <div style="margin-bottom:4px;">
        <label style="font-size:11px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px;">
          Training Recommendation
          <span style="background:rgba(139,92,246,0.18); color:#a78bfa; font-size:10px; font-weight:700; padding:2px 8px; border-radius:6px; border:1px solid rgba(139,92,246,0.4); margin-left:6px;">
             AI Generated
          </span>
        </label>
        <div style="position:relative;">
          <div id="ai-rec-display"
            style="width:100%; min-height:56px; padding:10px 12px; background:rgba(139,92,246,0.06); border:1px solid rgba(139,92,246,0.3); border-radius:8px; color:var(--text-secondary); font-size:12px; line-height:1.6; font-style:italic;">
            Will be auto-generated by Gemini AI after you submit the evaluation.
          </div>
        </div>
        <div style="font-size:10px; color:var(--text-muted); margin-top:5px;">
          Generated automatically by Gemini AI based on KPI scores and historical training data. Stored to database on save.
        </div>
      </div>
      <input type="hidden" name="training_recommendations" id="t-training" value=""/>
    </div>

    <!-- SUBMIT -->
    <button type="button" id="submit-btn"
      style="width:100%; padding:14px; background:linear-gradient(135deg,#8b5cf6,#6d28d9); border:none; border-radius:10px; color:#fff; font-size:14px; font-weight:700; cursor:pointer; transition:.2s; letter-spacing:.3px;">
       Save Evaluation to Database
    </button>
  </form>
</div>

<!--CHANGE WEIGHT MODAL-->
<div id="weight-modal-overlay"
  style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:1000; align-items:center; justify-content:center;">
</div>

<div id="weight-modal"
  style="display:none; position:fixed; top:60px; left:50%; transform:translateX(-50%);
         width:560px; max-width:95vw; max-height:88vh; overflow-y:auto;
         background:var(--bg-sidebar); border:1px solid var(--border); border-radius:16px;
         box-shadow:0 24px 64px rgba(0,0,0,0.5); z-index:1001; padding:24px;">

  <!-- Modal Header -->
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <div>
      <div style="font-size:15px; font-weight:700; color:var(--text-primary);">⚖ Change KPI Weights</div>
      <div style="font-size:11px; color:var(--text-muted); margin-top:3px;">Enter values as percentages. All weights must add up to exactly 100%.</div>
    </div>
    <button type="button" id="weight-modal-close"
      style="background:transparent; border:none; color:var(--text-muted); font-size:20px; cursor:pointer; line-height:1;">✕</button>
  </div>

  <!-- Running Total -->
  <div style="background:var(--bg-input,#141c2b); border-radius:10px; padding:12px 16px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
    <span style="font-size:12px; color:var(--text-secondary);">Total Weight</span>
    <span id="wm-total" style="font-size:18px; font-weight:800; color:#22c55e;">100%</span>
  </div>

  <!-- Error message -->
  <div id="wm-error" style="display:none; background:rgba(239,68,68,0.12); border:1px solid #ef4444; border-radius:8px; padding:10px 14px; color:#ef4444; font-size:12px; margin-bottom:16px;"></div>

  <!-- Section 1 -->
  <div style="background:rgba(59,130,246,0.06); border:1px solid rgba(59,130,246,0.2); border-radius:10px; padding:14px; margin-bottom:14px;">
    <div style="font-size:12px; font-weight:700; color:#3b82f6; margin-bottom:12px;">SECTION 1 – Core Competencies</div>
    <div style="display:flex; flex-direction:column; gap:8px;">
      <?php foreach ($KPI_ITEMS['S1'] as $item): ?>
      <div style="display:flex; justify-content:space-between; align-items:center;">
        <label style="font-size:12px; color:var(--text-primary); flex:1;"><?php echo safe($item['desc']); ?></label>
        <div style="display:flex; align-items:center; gap:6px; width:110px;">
          <input type="number" class="wm-input" id="wm-<?php echo $item['id']; ?>"
            data-type="s1" data-id="<?php echo $item['id']; ?>" data-name="<?php echo htmlspecialchars($item['desc'], ENT_QUOTES); ?>"
            value="<?php echo $S1_WEIGHTS[$item['desc']] * 100; ?>"
            min="0" max="100" step="0.01"
            style="width:70px; padding:6px 8px; background:var(--bg-input,#141c2b); border:1px solid var(--border); border-radius:6px; color:var(--text-primary); font-size:12px; text-align:right;"/>
          <span style="font-size:12px; color:var(--text-muted);">%</span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Section 2 -->
  <div style="background:rgba(6,182,212,0.06); border:1px solid rgba(6,182,212,0.2); border-radius:10px; padding:14px; margin-bottom:20px;">
    <div style="font-size:12px; font-weight:700; color:#06b6d4; margin-bottom:12px;">SECTION 2 – KPI Achievement Groups</div>
    <div style="display:flex; flex-direction:column; gap:8px;">
      <?php foreach ($KPI_ITEMS as $group_name => $items):
        if ($group_name === 'S1') continue;
        $gid = $items[0]['group_id'];
      ?>
      <div style="display:flex; justify-content:space-between; align-items:center;">
        <label style="font-size:12px; color:var(--text-primary); flex:1;"><?php echo safe($group_name); ?></label>
        <div style="display:flex; align-items:center; gap:6px; width:110px;">
          <input type="number" class="wm-input" id="wm-g<?php echo $gid; ?>"
            data-type="s2" data-gid="<?php echo $gid; ?>"
            value="<?php echo $GROUP_WEIGHTS[$group_name] * 100; ?>"
            min="0" max="100" step="0.01"
            style="width:70px; padding:6px 8px; background:var(--bg-input,#141c2b); border:1px solid var(--border); border-radius:6px; color:var(--text-primary); font-size:12px; text-align:right;"/>
          <span style="font-size:12px; color:var(--text-muted);">%</span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Action Buttons -->
  <div style="display:flex; gap:10px;">
    <button type="button"
      style="flex:1; padding:11px; background:transparent; border:1px solid var(--border); border-radius:8px; color:var(--text-secondary); font-size:13px; font-weight:600; cursor:pointer; transition:.2s;">
      Cancel
    </button>
    <button type="button"
      style="flex:2; padding:11px; background:linear-gradient(135deg,#3b82f6,#2563eb); border:none; border-radius:8px; color:#fff; font-size:13px; font-weight:700; cursor:pointer; transition:.2s;">
      ✓ Apply Weight Changes
    </button>
  </div>
</div>

</div><!-- .dash-grid -->
</div><!-- .content -->

<style>
  .star-btn:hover { background: rgba(59,130,246,0.2) !important; border-color: #3b82f6 !important; color: #fff !important; }
  .star-btn.active { color: #fff !important; font-weight: 700 !important; }
  #change-weight-btn:hover { border-color: var(--accent); color: var(--accent); }
  textarea { box-sizing: border-box; }
</style>

<script>

//  State
let currentMode     = 'existing';
let currentStaffId  = '';
let currentPeriodId = '';

// Year → period_id map (built from DB evaluation_period table)
const yearToPeriod = <?php
  $map = [];
  foreach ($periods as $p) { $map[(int)$p['year']] = (int)$p['period_id']; }
  echo json_encode($map);
?>;

// Auto-generated next staff code
const nextStaffCode = <?php echo json_encode($next_staff_code); ?>;

// Section 1 weights keyed by descriptive name (e.g. "Initiative" => 0.05)
const s1Weights = <?php echo json_encode($S1_WEIGHTS); ?>;

// Map: item_id → desc name, and desc name → item_id
const s1IdToName = <?php echo json_encode($S1_ID_TO_DESC); ?>;
const s1NameToId = <?php echo json_encode($S1_DESC_TO_ID); ?>;

// Section 2: group_id -> { weight, item_ids[] }
const s2Groups = <?php
  $out = [];
  foreach ($KPI_ITEMS as $gname => $items) {
    if ($gname === 'S1') continue;
    $gid = $items[0]['group_id'];
    $out[$gid] = ['weight' => $GROUP_WEIGHTS[$gname], 'item_ids' => array_column($items, 'id')];
  }
  echo json_encode($out);
?>;

const ratingLabels = ['Very Poor','Very Poor','Poor','Satisfactory','Good','Excellent'];
const ratingColors = ['','#7f1d1d','#ef4444','#f59e0b','#3b82f6','#22c55e'];


//  Mode toggle
function setMode(mode) {
  currentMode = mode;
  document.getElementById('f-mode').value = mode;

  const btnEx  = document.getElementById('btn-existing');
  const btnNew = document.getElementById('btn-new');
  const exWrap = document.getElementById('existing-staff-wrap');
  const newWrap= document.getElementById('new-staff-wrap');

  if (mode === 'existing') {
    btnEx.style.background  = 'var(--accent)';
    btnEx.style.borderColor = 'var(--accent)';
    btnEx.style.color       = '#fff';
    btnNew.style.background = 'transparent';
    btnNew.style.borderColor= 'var(--border)';
    btnNew.style.color      = 'var(--text-secondary)';
    exWrap.style.display    = 'block';
    newWrap.style.display   = 'none';
  } else {
    btnNew.style.background  = '#22c55e';
    btnNew.style.borderColor = '#22c55e';
    btnNew.style.color       = '#fff';
    btnEx.style.background   = 'transparent';
    btnEx.style.borderColor  = 'var(--border)';
    btnEx.style.color        = 'var(--text-secondary)';
    newWrap.style.display    = 'block';
    exWrap.style.display     = 'none';
    document.getElementById('new-code').value = nextStaffCode;
    document.getElementById('staff-badge').textContent = 'New Staff — ' + nextStaffCode;
    clearScores();
  }
}


//  Staff / Period change handlers
function onStaffChange() {
  currentStaffId = document.getElementById('sel-staff').value;
  const sel      = document.getElementById('sel-staff');
  const label    = sel.options[sel.selectedIndex]?.text || '';
  document.getElementById('staff-badge').textContent = currentStaffId ? label : '';
  tryLoadScores();
}

function onDateChange(dateStr) {
  const hint  = document.getElementById('date-year-hint');
  const warn  = document.getElementById('date-year-warn');
  const label = document.getElementById('date-year-label');

  if (!dateStr) {
    hint.style.display = 'none';
    warn.style.display = 'none';
    currentPeriodId = '';
    return;
  }

  const year  = parseInt(dateStr.substring(0, 4));
  const match = yearToPeriod[year];

  if (match) {
    currentPeriodId = match;
    label.textContent = `Year ${year} (Period ${match})`;
    hint.style.display = 'block';
    warn.style.display = 'none';
  } else {
    // Allow new years — the PHP will auto-create the period
    currentPeriodId = year; // non-zero truthy value so submit is not blocked
    label.textContent = `Year ${year} (New period will be created)`;
    hint.style.display = 'block';
    warn.style.display = 'none';
  }

  tryLoadScores();
}

function tryLoadScores() {
  if (currentMode === 'existing' && currentStaffId && currentPeriodId) {
    fetch(`index.php?page=evaluation&action=load_scores&staff_id=${currentStaffId}&period_id=${currentPeriodId}`)
      .then(r => r.json())
      .then(data => {
        const scores = data.scores;
        if (Object.keys(scores).length === 0) {
          clearScores();
          document.getElementById('load-hint').style.display = 'none';
        } else {
          for (const [itemId, score] of Object.entries(scores)) {
            setScore(parseInt(itemId), parseInt(score));
          }
          document.getElementById('load-hint').style.display = 'block';
          document.getElementById('t-sup-comments').value = data.supervisor_comments || '';
          document.getElementById('t-training').value     = data.training_recommendations || '';
          const existingRec = data.training_recommendations || '';
          const display = document.getElementById('ai-rec-display');
          if (existingRec) {
            display.style.fontStyle = 'normal';
            display.style.color = 'var(--text-primary)';
            display.textContent = existingRec;
          } else {
            display.style.fontStyle = 'italic';
            display.style.color = 'var(--text-secondary)';
            display.textContent = 'Will be auto-generated by Gemini AI after you submit the evaluation.';
          }
        }
      });
  }
}


//  Score setting & live calculation
function setScore(itemId, val) {
  document.getElementById(`score-${itemId}`).value = val;

  // Clear red highlight on this row
  const inp = document.getElementById(`score-${itemId}`);
  const row = inp?.closest('tr');
  if (row) { row.style.background = ''; row.style.outline = ''; }

  // If all scores are now filled, clear the error box too
  const anyMissing = Array.from(document.querySelectorAll('[id^="score-"]')).some(i => !i.value);
  if (!anyMissing) clearScoreError();

  // Update star buttons
  const group = document.querySelector(`.star-group[data-item-id="${itemId}"]`);
  if (group) {
    group.querySelectorAll('.star-btn').forEach(btn => {
      const n = parseInt(btn.dataset.val);
      if (n <= val) {
        btn.style.background   = ratingColors[val];
        btn.style.borderColor  = ratingColors[val];
        btn.style.color        = '#fff';
        btn.classList.add('active');
      } else {
        btn.style.background   = 'transparent';
        btn.style.borderColor  = 'var(--border)';
        btn.style.color        = 'var(--text-secondary)';
        btn.classList.remove('active');
      }
    });
  }

  recalculate();
}

function clearScores() {
  document.querySelectorAll('[id^="score-"]').forEach(inp => {
    inp.value = '';
    const itemId = parseInt(inp.id.replace('score-', ''));
    const group = document.querySelector(`.star-group[data-item-id="${itemId}"]`);
    if (group) {
      group.querySelectorAll('.star-btn').forEach(btn => {
        btn.style.background  = 'transparent';
        btn.style.borderColor = 'var(--border)';
        btn.style.color       = 'var(--text-secondary)';
        btn.classList.remove('active');
      });
    }
  });
  document.getElementById('t-sup-comments').value = '';
  document.getElementById('t-training').value = '';
  const display = document.getElementById('ai-rec-display');
  display.style.fontStyle = 'italic';
  display.style.color = 'var(--text-secondary)';
  display.textContent = 'Will be auto-generated by Gemini AI after you submit the evaluation.';
  recalculate();
}

function getScore(itemId) {
  const val = document.getElementById(`score-${itemId}`)?.value;
  return val ? parseInt(val) : 0;
}

function recalculate() {
  const anyScored = Array.from(document.querySelectorAll('[id^="score-"]')).some(i => i.value !== '');

  // Section 1 — use live weights (name-keyed; resolve item_id via s1NameToId)
  let s1 = 0;
  for (const [name, weight] of Object.entries(liveS1Weights)) {
    const id = s1NameToId[name];
    if (id === undefined) continue;
    const sc = getScore(id);
    const ws = sc * weight;
    s1 += ws;
    const wsEl = document.getElementById(`ws-${id}`);
    if (wsEl) wsEl.textContent = sc > 0 ? ws.toFixed(4) : '–';
  }
  const s1El = document.getElementById('s1-total');
  if (s1El) s1El.textContent = anyScored ? s1.toFixed(4) : '–';

  // Section 2 — use live weights
  let s2 = 0;
  for (const [gidStr, gdata] of Object.entries(liveS2Groups)) {
    const gid    = parseInt(gidStr);
    const ids    = gdata.item_ids;
    const weight = gdata.weight;
    const itemW  = weight / ids.length;
    const wsEl   = document.getElementById(`gws-${gid}`);
    const gTotEl = document.getElementById(`gws-total-${gid}`);
    const hasAny = ids.some(id => getScore(id) > 0);

    if (!hasAny) {
      if (wsEl)   wsEl.textContent   = '–';
      if (gTotEl) gTotEl.textContent = '–';
      ids.forEach(id => {
        const el = document.getElementById(`ws-${id}`);
        if (el) el.textContent = '–';
      });
      continue;
    }

    let groupTotal = 0;
    ids.forEach(id => {
      const sc = getScore(id);
      const ws = sc * itemW;
      groupTotal += ws;
      const el = document.getElementById(`ws-${id}`);
      if (el) el.textContent = sc > 0 ? ws.toFixed(4) : '–';
    });

    s2 += groupTotal;

    const avg = ids.reduce((sum, id) => sum + getScore(id), 0) / ids.length;
    if (wsEl)   wsEl.textContent   = `${avg.toFixed(2)} × ${weight.toFixed(4)} = ${groupTotal.toFixed(4)}`;
    if (gTotEl) gTotEl.textContent = groupTotal.toFixed(4);
  }

  const s2El = document.getElementById('s2-total');
  if (s2El) s2El.textContent = anyScored ? s2.toFixed(4) : '–';

  const finalEl  = document.getElementById('final-score');
  const ratingEl = document.getElementById('final-rating');
  if (!anyScored) {
    if (finalEl)  { finalEl.textContent = '–'; finalEl.style.color = 'var(--text-muted)'; }
    if (ratingEl) { ratingEl.textContent = '–'; ratingEl.style.background = 'transparent'; ratingEl.style.color = 'var(--text-muted)'; }
    return;
  }
  const final = s1 + s2;
  if (finalEl) finalEl.textContent = final.toFixed(4);

  let rating = 'Very Poor', rcolor = '#7f1d1d';
  if      (final >= 5.00) { rating = 'Excellent';   rcolor = '#22c55e'; }
  else if (final >= 4.00) { rating = 'Good';         rcolor = '#3b82f6'; }
  else if (final >= 3.00) { rating = 'Satisfactory'; rcolor = '#f59e0b'; }
  else if (final >= 2.00) { rating = 'Poor';         rcolor = '#ef4444'; }

  if (ratingEl) {
    ratingEl.textContent        = rating;
    ratingEl.style.background   = rcolor + '22';
    ratingEl.style.color        = rcolor;
    ratingEl.style.borderRadius = '6px';
    ratingEl.style.padding      = '4px 12px';
    ratingEl.style.display      = 'inline-block';
  }
  if (finalEl) finalEl.style.color = rcolor;
}


//  Form submission
function showScoreError(count) {
  let errBox = document.getElementById('submit-score-error');
  if (!errBox) {
    errBox = document.createElement('div');
    errBox.id = 'submit-score-error';
    errBox.style.cssText = 'background:rgba(239,68,68,0.12); border:1px solid #ef4444; border-radius:8px; padding:12px 16px; color:#ef4444; font-size:12px; margin-bottom:12px; line-height:1.6; transition:opacity .2s;';
    const submitBtn = document.getElementById('submit-btn');
    submitBtn.parentNode.insertBefore(errBox, submitBtn);
  }
  errBox.innerHTML = ` <strong>Please score all ${count} remaining item(s) before submitting.</strong><br>Rows missing a score are highlighted in red below.`;
  // Shake animation to show something happened without scrolling away
  errBox.animate([
    { transform:'translateX(0)' }, { transform:'translateX(-6px)' },
    { transform:'translateX(6px)' }, { transform:'translateX(-4px)' },
    { transform:'translateX(4px)' }, { transform:'translateX(0)' }
  ], { duration: 320, easing: 'ease-in-out' });
}

function clearScoreError() {
  const errBox = document.getElementById('submit-score-error');
  if (errBox) errBox.remove();
  document.querySelectorAll('[id^="score-"]').forEach(inp => {
    const row = inp.closest('tr');
    if (row) { row.style.background = ''; row.style.outline = ''; }
  });
}

function submitEvaluation() {
  const evalDate = document.getElementById('sel-date').value;
  if (!evalDate) { alert('Please select an evaluation date.'); return; }
  if (!currentPeriodId) { alert('No evaluation period found. A new period will be created automatically.'); return; }

  if (currentMode === 'existing') {
    const staffId = document.getElementById('sel-staff').value;
    if (!staffId) { alert('Please select a staff member.'); return; }
    document.getElementById('f-staff-id').value = staffId;
  } else {
    const code = document.getElementById('new-code').value.trim();
    const name = document.getElementById('new-name').value.trim();
    const role = document.getElementById('new-role').value.trim();
    if (!name) { alert('Please enter the full name.'); return; }
    if (!code) { alert('Staff code could not be generated. Please refresh and try again.'); return; }
    document.getElementById('f-new-code').value = code;
    document.getElementById('f-new-name').value = name;
    document.getElementById('f-new-role').value = role || 'Sales Assistant';
  }

  // Validate: every score must be filled 
  let missingCount = 0;
  document.querySelectorAll('[id^="score-"]').forEach(inp => {
    const row = inp.closest('tr');
    if (!inp.value || inp.value === '') {
      missingCount++;
      if (row) { row.style.background = 'rgba(239,68,68,0.08)'; row.style.outline = '1px solid rgba(239,68,68,0.4)'; }
    } else {
      if (row) { row.style.background = ''; row.style.outline = ''; }
    }
  });

  if (missingCount > 0) {
    showScoreError(missingCount);
    return;
  }

  // All good — clear error state and submit
  clearScoreError();
  document.getElementById('f-eval-date').value = evalDate;
  if (!confirm('Save this evaluation to the database?')) return;
  document.getElementById('evaluation-form').submit();
}


//  Live weights (changeble copies – PHP defaults as starting point)
let liveS1Weights = JSON.parse(JSON.stringify(s1Weights));   // deep copy
let liveS2Groups  = JSON.parse(JSON.stringify(s2Groups));    // deep copy

//  Weight Modal
function openWeightModal() {
  // Populate inputs with current live weights
  document.querySelectorAll('.wm-input').forEach(inp => {
    if (inp.dataset.type === 's1') {
      const name = inp.dataset.name;
      inp.value = (liveS1Weights[name] * 100).toFixed(2);
    } else {
      const gid = parseInt(inp.dataset.gid);
      inp.value = (liveS2Groups[gid].weight * 100).toFixed(2);
    }
  });
  updateWeightTotal();
  document.getElementById('wm-error').style.display = 'none';
  document.getElementById('weight-modal-overlay').style.display = 'block';
  document.getElementById('weight-modal').style.display = 'block';
}

function closeWeightModal() {
  document.getElementById('weight-modal-overlay').style.display = 'none';
  document.getElementById('weight-modal').style.display = 'none';
}

function updateWeightTotal() {
  let total = 0;
  document.querySelectorAll('.wm-input').forEach(inp => {
    total += parseFloat(inp.value) || 0;
  });
  const totalEl = document.getElementById('wm-total');
  totalEl.textContent = total.toFixed(2) + '%';
  const diff = Math.abs(total - 100);
  totalEl.style.color = diff < 0.01 ? '#22c55e' : '#ef4444';
}

function applyWeightChanges() {
  // Validate sum = 100
  let total = 0;
  document.querySelectorAll('.wm-input').forEach(inp => {
    total += parseFloat(inp.value) || 0;
  });

  const errEl = document.getElementById('wm-error');
  if (Math.abs(total - 100) >= 0.01) {
    errEl.textContent = ` Weights total ${total.toFixed(2)}% but must equal exactly 100%. Please adjust your values.`;
    errEl.style.display = 'block';
    return;
  }
  errEl.style.display = 'none';

  // Apply to live weights
  const s1Obj = {}, s2Obj = {};
  document.querySelectorAll('.wm-input').forEach(inp => {
    const pct = parseFloat(inp.value) || 0;
    const dec = pct / 100;
    if (inp.dataset.type === 's1') {
      const name = inp.dataset.name;
      liveS1Weights[name] = dec;
      s1Obj[name] = dec;
    } else {
      const gid = parseInt(inp.dataset.gid);
      liveS2Groups[gid].weight = dec;
    }
  });

  // Build s2Obj for saving (group_name → weight)
  for (const [gidStr, gdata] of Object.entries(liveS2Groups)) {
    const nameEl = document.querySelector(`.score-row[data-group-id="${gidStr}"]`);
    // Fallback: find group name via DOM or just use gid
    const gid = parseInt(gidStr);
    document.querySelectorAll(`.score-row[data-group-id="${gid}"]`).forEach(r => {
      const groupName = r.closest('[style*="border-left"]')?.querySelector('[style*="font-weight:700"]')?.textContent?.trim();
      if (groupName) s2Obj[groupName] = gdata.weight;
    });
  }

  // Persist to DB via save_weights AJAX
  const fd = new FormData();
  fd.append('s1_json', JSON.stringify(s1Obj));
  fd.append('s2_json', JSON.stringify(s2Obj));
  fetch('index.php?page=evaluation&action=save_weights', { method: 'POST', body: fd })
    .then(r => r.json())
    .catch(() => {});

  // Update displayed weight cells in Section 1
  document.querySelectorAll('.score-row[data-section="1"]').forEach(row => {
    const id   = parseInt(row.dataset.itemId);
    const name = s1IdToName[id];
    const wTd  = row.querySelector('td:nth-child(2)');
    if (wTd && name) wTd.textContent = liveS1Weights[name].toFixed(4).replace(/\.?0+$/, '') || liveS1Weights[name];
  });

  // Update displayed weight + per-item weight cells in Section 2
  for (const [gidStr, gdata] of Object.entries(liveS2Groups)) {
    const gid    = parseInt(gidStr);
    const ids    = gdata.item_ids;
    const newW   = gdata.weight;
    const itemW  = newW / ids.length;

    ids.forEach(id => {
      const row  = document.querySelector(`.score-row[data-item-id="${id}"]`);
      if (row) {
        // Update data attribute
        row.dataset.itemWeight = itemW;
        // Update displayed weight cell
        const wTd = row.querySelector('td:nth-child(2)');
        if (wTd) wTd.textContent = parseFloat(itemW.toFixed(6));
      }
    });

    // Update group header % label
    const headerW = document.getElementById(`gws-${gid}`);
    if (headerW) {
      const parent = headerW.closest('[style*="border-left"]') || headerW.parentElement;
      // Update the "Group Weight: X%" text
      const gwEl = headerW.closest('div')?.querySelector('strong');
    }
  }

  // Recalculate with new weights
  recalculate();
  closeWeightModal();
}

</script>

</div>  <!-- closes dash-grid -->
</div>  <!-- closes .content -->

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Add button click handlers
  const btnExisting = document.getElementById('btn-existing');
  const btnNew = document.getElementById('btn-new');
  if (btnExisting) btnExisting.addEventListener('click', () => setMode('existing'));
  if (btnNew) btnNew.addEventListener('click', () => setMode('new'));
  
  // Add score button click handlers
  document.querySelectorAll('.star-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const itemId = parseInt(this.parentElement.dataset.itemId);
      const val = parseInt(this.dataset.val);
      setScore(itemId, val);
    });
  });
  
  // Add submit button handler
  const submitBtn = document.getElementById('submit-btn');
  if (submitBtn) {
    submitBtn.addEventListener('click', (e) => {
      e.preventDefault();
      submitEvaluation();
    });
  }
  
  // Add staff and date change handlers
  const selStaff = document.getElementById('sel-staff');
  if (selStaff) {
    selStaff.addEventListener('change', () => onStaffChange());
  }
  
  const selDate = document.getElementById('sel-date');
  if (selDate) {
    selDate.addEventListener('change', () => onDateChange(selDate.value));
  }
  
  // Add weight modal handlers
  const changeWeightBtn = document.getElementById('change-weight-btn');
  if (changeWeightBtn) {
    changeWeightBtn.addEventListener('click', openWeightModal);
  }
  
  const weightModalOverlay = document.getElementById('weight-modal-overlay');
  if (weightModalOverlay) {
    weightModalOverlay.addEventListener('click', closeWeightModal);
  }

  const weightModalClose = document.getElementById('weight-modal-close');
  if (weightModalClose) {
    weightModalClose.addEventListener('click', closeWeightModal);
  }
  
  // Add weight input change handlers
  document.querySelectorAll('.wm-input').forEach(input => {
    input.addEventListener('input', updateWeightTotal);
  });
  
  // Add modal action button handlers
  const modalButtons = document.querySelectorAll('#weight-modal > div:last-child button');
  if (modalButtons.length >= 2) {
    modalButtons[0].addEventListener('click', closeWeightModal);
    modalButtons[1].addEventListener('click', applyWeightChanges);
  }
  
  // Restrict date picker to today or earlier
  const today = new Date();
  const yyyy  = today.getFullYear();
  const mm    = String(today.getMonth() + 1).padStart(2, '0');
  const dd    = String(today.getDate()).padStart(2, '0');
  document.getElementById('sel-date').max = `${yyyy}-${mm}-${dd}`;

  clearScores();
  recalculate();
});
</script>
