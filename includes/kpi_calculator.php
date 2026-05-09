<?php
/**
 * SAKMS - KPI Calculator
 * Implements the two-section KPI calculation model
 */

class KPICalculator {
    
    /**
     * Calculate overall KPI score for an employee in a given period.
     *
     * @param mysqli $conn           Database connection
     * @param int    $employee_id    Employee ID
     * @param int    $period_id      Evaluation period ID
     * @param array  $weight_overrides  Optional. Overrides DB weights with saved weight_config values.
     *                               Format: [
     *                                 's1' => ['Initiative' => 0.05, ...],   // name → decimal
     *                                 's2' => ['Daily Sales Operations' => 0.15, ...]  // group_name → decimal
     *                               ]
     * @return array ['section1' => float, 'section2' => float, 'overall' => float, 'rating' => string]
     */
    public static function calculateKPI($conn, $employee_id, $period_id, $weight_overrides = []) {

        // Get all KPI scores for this employee in this period
        // JOIN chain: kpi_score → kpi_item → kpi_group → kpi_section
        $query = "
            SELECT
                ki.kpi_code,
                ki.description      AS item_desc,
                ksec.section_name   AS section,
                kg.group_name       AS kpi_group,
                kg.weight_percentage AS group_weight,
                ks.score
            FROM kpi_score ks
            JOIN kpi_item    ki   ON ks.kpi_item_id   = ki.kpi_item_id
            JOIN kpi_group   kg   ON ki.kpi_group_id  = kg.kpi_group_id
            JOIN kpi_section ksec ON kg.section_id    = ksec.section_id
            WHERE ks.staff_id = ? AND ks.period_id = ?
            ORDER BY ksec.section_id, kg.kpi_group_id, ki.kpi_item_id
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $employee_id, $period_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $s1_overrides = $weight_overrides['s1'] ?? [];  // name → decimal weight
        $s2_overrides = $weight_overrides['s2'] ?? [];  // group_name → decimal weight

        // Collect scores grouped by section → group
        $groups = [];
        while ($row = $result->fetch_assoc()) {
            $section      = $row['section'];   // 'Core Competencies' or 'KPI Achievement'
            $group        = $row['kpi_group'];
            $item_desc    = $row['item_desc'];
            $group_weight = (float) $row['group_weight'];

            // Use override weight if provided, otherwise fall back to DB value
            if ($section === 'Core Competencies' && isset($s1_overrides[$item_desc])) {
                // S1: each item has its own weight override (already a decimal)
                $resolved_weight = (float)$s1_overrides[$item_desc];
            } elseif ($section !== 'Core Competencies' && isset($s2_overrides[$group])) {
                // S2: group-level weight override (already a decimal)
                $resolved_weight = (float)$s2_overrides[$group];
            } else {
                $resolved_weight = $group_weight / 100;
            }

            if (!isset($groups[$section][$group])) {
                $groups[$section][$group] = [
                    'scores'          => [],
                    'weight'          => $resolved_weight,
                    'item_weights'    => [],   // S1 only: per-item weight by desc
                    'is_s1_per_item'  => ($section === 'Core Competencies' && !empty($s1_overrides)),
                ];
            }
            $groups[$section][$group]['scores'][]               = (int) $row['score'];
            $groups[$section][$group]['item_weights'][$item_desc] = $resolved_weight;
        }

        // ======================================
        // SECTION 1: Core Competencies (25%)
        // Formula: AVG(items in group) × group_weight (0.25)
        // ======================================
        $section1_total     = 0;
        $section1_breakdown = [];

        // ======================================
        // SECTION 2: KPI Achievement (75%)
        // Formula: SUM( AVG(items in group) × group_weight ) across all groups
        // ======================================
        $section2_total     = 0;
        $section2_breakdown = [];

        foreach ($groups as $section => $section_groups) {
            foreach ($section_groups as $group_name => $group_data) {

                if ($group_data['is_s1_per_item'] && !empty($group_data['item_weights'])) {
                    // S1 with per-item overrides: sum each item's (score × item_weight)
                    $weighted = 0;
                    $scores   = $group_data['scores'];
                    $iw       = array_values($group_data['item_weights']);
                    foreach ($scores as $i => $sc) {
                        $weighted += $sc * ($iw[$i] ?? 0);
                    }
                    $avg = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
                } else {
                    // Standard: AVG(scores) × group_weight
                    $avg      = count($group_data['scores']) > 0
                                    ? array_sum($group_data['scores']) / count($group_data['scores'])
                                    : 0;
                    $weighted = $avg * $group_data['weight'];
                }

                $breakdown_entry = [
                    'avg_score'     => round($avg, 2),
                    'weight'        => $group_data['weight'],
                    'weighted_score'=> round($weighted, 2)
                ];

                if ($section === 'Core Competencies') {
                    $section1_total                += $weighted;
                    $section1_breakdown[$group_name] = $breakdown_entry;
                } else {
                    $section2_total                += $weighted;
                    $section2_breakdown[$group_name] = $breakdown_entry;
                }
            }
        }

        // ======================================
        // FINAL SCORE (Section 1 + Section 2)
        // ======================================
        $final_score = round($section1_total + $section2_total, 2);
        $final_score = max(1, min(5, $final_score));

        $rating = self::getRatingLabel($final_score);

        return [
            'section1'           => round($section1_total, 2),
            'section2'           => round($section2_total, 2),
            'overall'            => $final_score,
            'rating'             => $rating,
            'section1_breakdown' => $section1_breakdown,
            'section2_breakdown' => $section2_breakdown
        ];
    }
    
    public static function getRatingLabel($score) {
        // Round DOWN to nearest integer (floor)
        $rating = floor($score);
        $rating = max(1, min(5, $rating)); // Ensure within 1-5 range
        
        switch ($rating) {
            case 5:
                return 'Excellent';
            case 4:
                return 'Good';
            case 3:
                return 'Satisfactory';
            case 2:
                return 'Poor';
            case 1:
            default:
                return 'Very Poor';
        }
    }
    
    /**
     * Get color coding for performance visualization (based on rating, not raw score)
     */
    public static function getPerformanceColor($score) {
        $rating = floor(max(1, min(5, $score)));
        switch ($rating) {
            case 5: return '#22c55e';  // Green - Excellent
            case 4: return '#3b82f6';  // Blue - Good
            case 3: return '#f59e0b';  // Amber - Satisfactory
            case 2: return '#ef4444';  // Red - Poor
            case 1:
            default: return '#7f1d1d'; // Dark Red - Very Poor
        }
    }
    
    /**
     * Get CSS class for performance badge
     */
    public static function getPerformanceClass($score) {
        if ($score == 5) return 'badge-excellent';
        if ($score >= 4) return 'badge-good';
        if ($score >= 3) return 'badge-satisfactory';
        if ($score >= 2) return 'badge-poor';
        return 'badge-very-poor';
    }
    
    /**
     * Classify employee based on KPI score
     */
    public static function classifyPerformance($score) {
        if ($score == 5) return 'Top Performer';
        if ($score >= 4) return 'Good Performer';
        if ($score >= 3) return 'Average Performer';
        if ($score >= 2) return 'At-Risk';
        return 'Critical Risk';
    }
    
    /**
     * Calculate KPI for multiple employees
     */
    public static function calculateBatchKPI($conn, $employee_ids, $period_id) {
        $results = [];
        foreach ($employee_ids as $emp_id) {
            $results[$emp_id] = self::calculateKPI($conn, $emp_id, $period_id);
        }
        return $results;
    }
    
    /**
     * Get KPI trend for an employee (multiple periods)
     * Returns: array['year'] => ['overall' => float, 'rating' => string, ...]
     */
    public static function getKPITrend($conn, $employee_id, $start_year = 2022, $end_year = 2025) {
        $query = "
            SELECT
                ep.year,
                ep.period_id
            FROM evaluation_period ep
            WHERE ep.year BETWEEN ? AND ?
            ORDER BY ep.year ASC
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $start_year, $end_year);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $trend = [];
        while ($row = $result->fetch_assoc()) {
            $trend[$row['year']] = self::calculateKPI($conn, $employee_id, $row['period_id']);
        }
        
        return $trend;
    }
}
