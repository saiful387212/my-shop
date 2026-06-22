<?php
// ============================================================
// FILE: public/mark-helpful.php
// PURPOSE: Mark a review as helpful
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Review.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit;
}

$reviewId = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;

if ($reviewId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid review ID.']);
    exit;
}

$reviewModel = new Review();
$result = $reviewModel->markHelpful($reviewId, $_SESSION['user_id']);

header('Content-Type: application/json');
echo json_encode($result);
exit;
?>