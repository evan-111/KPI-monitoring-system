<?php
/**
 * SAKMS - Main Dashboard Router
 * Handles page navigation and authentication
 */
require_once 'includes/auth.php';
require_once 'includes/db_config.php';
require_once 'includes/functions.php';
require_once 'includes/avatar.php';
require_once 'includes/kpi_calculator.php';

requireLogin();
checkSessionTimeout();

// Determine which page to display
$page = $_GET['page'] ?? 'dashboard';
$valid_pages = ['dashboard', 'profiles', 'analytics', 'report', 'evaluation', 'settings'];

if (!in_array($page, $valid_pages)) {
    $page = 'dashboard';
}

// Get current supervisor
$supervisor_name = getCurrentSupervisor();

$supervisor_profile = $conn->query(
    "SELECT * FROM supervisor_profile WHERE id = 1"
)->fetch_assoc();

// at_risk_notifications table is now defined in sakms.sql — no runtime bootstrap needed.

// Handle dismiss notification AJAX
if (isset($_GET['action']) && $_GET['action'] === 'dismiss_notification' && isset($_GET['notif_id'])) {
    header('Content-Type: application/json');
    $nid = (int)$_GET['notif_id'];
    $conn->query("UPDATE at_risk_notifications SET is_read = 1 WHERE id = $nid");
    echo json_encode(['ok' => true]);
    exit();
}

// Handle dismiss all AJAX
if (isset($_GET['action']) && $_GET['action'] === 'dismiss_all_notifications') {
    header('Content-Type: application/json');
    $conn->query("UPDATE at_risk_notifications SET is_read = 1");
    echo json_encode(['ok' => true]);
    exit();
}

// Fetch unread at-risk notifications
$notif_result = $conn->query("
    SELECT id, staff_id, staff_code, staff_name, kpi_score, created_at
    FROM at_risk_notifications
    WHERE is_read = 0
    ORDER BY created_at DESC
");
$notifications = [];
while ($n = $notif_result->fetch_assoc()) {
    $notifications[] = $n;
}
$notif_count = count($notifications);

// For employee detail pages
$employee_id = $_GET['emp_id'] ?? null;
$period_id = $_GET['period'] ?? 4; // Default to 2025 (period_id=4)

// Determine page title
$page_titles = [
    'dashboard' => 'Supervisor Dashboard',
    'profiles' => 'Sales Assistant Profiles',
    'analytics' => 'Analytics Dashboard',
    'report' => 'Performance & Training Report',
    'evaluation' => 'Performance Evaluation',
    'settings' => 'Settings',
];

$topbar_title = $page_titles[$page] ?? 'Dashboard';

// Supervisor avatar URL
$supervisor_avatar_url = buildAvatarUrl($supervisor_name ?? 'Emily Tan');

// Include the HTML template
include 'index.html';
