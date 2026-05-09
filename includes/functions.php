<?php
/**
 * SAKMS - Utility Functions
 */

require_once 'db_config.php';
require_once 'kpi_calculator.php';

/**
 * Get all employees with their latest KPI scores
 */
function getAllEmployeesSummary($conn, $period_id = 4) {
    $query = "
        SELECT
            s.staff_id AS employee_id,
            s.full_name AS name,
            s.staff_code AS staff_id,
            s.role AS department,
            s.role,
            s.status,
            COUNT(DISTINCT ks.kpi_item_id) as kpi_count,
            AVG(ks.score) as avg_score
        FROM staff s
        LEFT JOIN kpi_score ks ON s.staff_id = ks.staff_id AND ks.period_id = ?
        WHERE s.status = 'Active'
        GROUP BY s.staff_id
        ORDER BY avg_score DESC, s.full_name ASC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $period_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Get single employee profile
 */
function getEmployeeProfile($conn, $employee_id) {
    $query = "
        SELECT
            s.staff_id AS employee_id,
            s.staff_code AS staff_id,
            s.full_name AS name,
            s.role AS department,
            s.role,
            s.status,
            COUNT(DISTINCT ks.kpi_item_id) as total_kpis
        FROM staff s
        LEFT JOIN kpi_score ks ON s.staff_id = ks.staff_id
        WHERE s.staff_id = ?
        GROUP BY s.staff_id
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get supervisor comments for employee
 */
function getSupervisorComments($conn, $employee_id, $period_id) {
    $query = "
        SELECT
            sf.*,
            ep.year
        FROM supervisor_feedback sf
        JOIN evaluation_period ep ON sf.period_id = ep.period_id
        WHERE sf.staff_id = ? AND sf.period_id = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $employee_id, $period_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get top performers (KPI >= 4.5)
 */
function getTopPerformers($conn, $period_id = 4, $limit = 10) {
    $employees = getAllEmployeesSummary($conn, $period_id);
    $top_performers = [];
    
    while ($emp = $employees->fetch_assoc()) {
        $kpi = KPICalculator::calculateKPI($conn, $emp['employee_id'], $period_id);
        if ($kpi['overall'] >= 4.5) {
            $emp['kpi_score'] = $kpi['overall'];
            $emp['rating'] = $kpi['rating'];
            $top_performers[] = $emp;
        }
    }
    
    return array_slice($top_performers, 0, $limit);
}

/**
 * Get at-risk employees (KPI < 3.0)
 */
function getAtRiskEmployees($conn, $period_id = 4) {
    $employees = getAllEmployeesSummary($conn, $period_id);
    $at_risk = [];
    
    while ($emp = $employees->fetch_assoc()) {
        $kpi = KPICalculator::calculateKPI($conn, $emp['employee_id'], $period_id);
        if ($kpi['overall'] < 3.0) {
            $emp['kpi_score'] = $kpi['overall'];
            $emp['rating'] = $kpi['rating'];
            $emp['status'] = KPICalculator::classifyPerformance($kpi['overall']);
            $at_risk[] = $emp;
        }
    }
    
    return $at_risk;
}

/**
 * Get average KPI by role (grouped as department)
 */
function getAverageByDepartment($conn, $period_id = 4) {
    $query = "
        SELECT
            s.role AS department,
            COUNT(DISTINCT s.staff_id) as emp_count,
            AVG(ks.score) as avg_score
        FROM staff s
        LEFT JOIN kpi_score ks ON s.staff_id = ks.staff_id AND ks.period_id = ?
        WHERE s.status = 'Active'
        GROUP BY s.role
        ORDER BY avg_score DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $period_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Get performance distribution
 */
function getPerformanceDistribution($conn, $period_id = 4) {
    $distribution = [
        'Excellent' => 0,
        'Good' => 0,
        'Satisfactory' => 0,
        'Poor' => 0,
        'Very Poor' => 0
    ];
    
    $employees = getAllEmployeesSummary($conn, $period_id);
    
    while ($emp = $employees->fetch_assoc()) {
        $kpi = KPICalculator::calculateKPI($conn, $emp['employee_id'], $period_id);
        $rating = $kpi['rating'];
        if (isset($distribution[$rating])) {
            $distribution[$rating]++;
        }
    }
    
    return $distribution;
}

/**
 * Get KPI scores by group
 */
function getKPIGroupScores($conn, $employee_id, $period_id = 4) {
    $query = "
        SELECT
            kg.group_name AS kpi_group,
            AVG(ks.score) as avg_score,
            COUNT(ks.score) as count,
            MIN(ks.score) as min_score,
            MAX(ks.score) as max_score
        FROM kpi_score ks
        JOIN kpi_item ki ON ks.kpi_item_id = ki.kpi_item_id
        JOIN kpi_group kg ON ki.kpi_group_id = kg.kpi_group_id
        WHERE ks.staff_id = ? AND ks.period_id = ?
        GROUP BY kg.kpi_group_id, kg.group_name
        ORDER BY avg_score DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $employee_id, $period_id);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Predict performance risk (compares current period to previous period)
 */
function predictPerformanceRisk($conn, $employee_id, $period_id = 4) {
    // Get all periods ordered by year
    $all_periods = $conn->query("SELECT period_id, year FROM evaluation_period ORDER BY year ASC")->fetch_all(MYSQLI_ASSOC);
    
    // If less than 2 periods, can't calculate risk
    if (count($all_periods) < 2) {
        return ['risk_level' => 'Low', 'trend' => 'Insufficient Data', 'change' => 0];
    }
    
    // Find current and previous period indices
    $curr_idx = -1;
    foreach ($all_periods as $i => $p) {
        if ((int)$p['period_id'] === (int)$period_id) {
            $curr_idx = $i;
            break;
        }
    }
    
    // If no previous period, can't calculate risk
    if ($curr_idx <= 0) {
        return ['risk_level' => 'Low', 'trend' => 'Insufficient Data', 'change' => 0];
    }
    
    // Calculate KPI for current and previous periods only
    $curr_kpi = KPICalculator::calculateKPI($conn, $employee_id, (int)$all_periods[$curr_idx]['period_id']);
    $prev_kpi = KPICalculator::calculateKPI($conn, $employee_id, (int)$all_periods[$curr_idx - 1]['period_id']);
    
    $latest_score = $curr_kpi['overall'];
    $previous_score = $prev_kpi['overall'];
    $change = round($latest_score - $previous_score, 2);
    
    // Risk assessment based on current score and trend
    if ($latest_score < 2.5) {
        return ['risk_level' => 'Critical', 'trend' => 'Declining', 'change' => $change];
    } elseif ($latest_score < 3.0 && $change < -0.5) {
        return ['risk_level' => 'High', 'trend' => 'Declining', 'change' => $change];
    } elseif ($change < -1.0) {
        return ['risk_level' => 'Medium', 'trend' => 'Declining', 'change' => $change];
    } elseif ($change > 1.0) {
        return ['risk_level' => 'Low', 'trend' => 'Improving', 'change' => $change];
    }
    
    return ['risk_level' => 'Low', 'trend' => 'Stable', 'change' => $change];
}

/**
 * Get training recommendations
 */
function getTrainingRecommendations($conn, $employee_id, $period_id) {
    $query = "
        SELECT training_recommendations
        FROM supervisor_feedback
        WHERE staff_id = ? AND period_id = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $employee_id, $period_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return $result['training_recommendations'] ?? 'No recommendations available';
}

/**
 * Format number as percentage
 */
function formatPercent($value, $decimals = 1) {
    return number_format($value * 100, $decimals) . '%';
}

/**
 * Format score with color
 */
function formatScoreWithColor($score) {
    $rating = KPICalculator::getRatingLabel($score);
    $color = KPICalculator::getPerformanceColor($score);
    return "<span style='color: $color; font-weight: 600;'>$score ($rating)</span>";
}

/**
 * Safe output (prevent XSS)
 */
function safe($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
