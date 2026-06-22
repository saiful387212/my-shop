<?php
// ============================================================
// FILE: public/submit-review.php
// PURPOSE: Handle review submission
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Review.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Please login to submit a review.';
    header('Location: login.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
    exit;
}

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$review = isset($_POST['review']) ? trim($_POST['review']) : '';
$pros = isset($_POST['pros']) ? trim($_POST['pros']) : '';
$cons = isset($_POST['cons']) ? trim($_POST['cons']) : '';
$isVerified = isset($_POST['is_verified_purchase']) ? 1 : 0;

// Validate
$errors = [];

if ($productId <= 0) {
    $errors[] = 'Invalid product.';
}

if ($rating < 1 || $rating > 5) {
    $errors[] = 'Please select a rating.';
}

if (empty($review)) {
    $errors[] = 'Please write your review.';
} elseif (strlen($review) < 10) {
    $errors[] = 'Review must be at least 10 characters.';
}

// Check if user already reviewed this product
$reviewModel = new Review();
if ($reviewModel->hasUserReviewed($_SESSION['user_id'], $productId)) {
    $errors[] = 'You have already reviewed this product.';
}

// Handle image uploads
$uploadedImages = [];
if (!empty($_FILES['images']) && isset($_FILES['images']['name'][0]) && !empty($_FILES['images']['name'][0])) {
    $totalFiles = count($_FILES['images']['name']);
    
    if ($totalFiles > 5) {
        $errors[] = 'You can upload a maximum of 5 images.';
    } else {
        $uploadDir = ABSPATH . 'public/uploads/reviews/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        for ($i = 0; $i < $totalFiles; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $fileExt = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (!in_array($fileExt, $allowedExtensions)) {
                    $errors[] = 'Invalid image format. Allowed: JPG, PNG, GIF, WEBP.';
                    continue;
                }
                
                if ($_FILES['images']['size'][$i] > 5242880) {
                    $errors[] = 'Image size cannot exceed 5MB.';
                    continue;
                }
                
                $newFileName = 'review_' . time() . '_' . uniqid() . '.' . $fileExt;
                
                if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $uploadDir . $newFileName)) {
                    $uploadedImages[] = $newFileName;
                }
            }
        }
    }
}

// If there are errors, redirect back
if (!empty($errors)) {
    $_SESSION['review_errors'] = $errors;
    $_SESSION['review_data'] = $_POST;
    header('Location: product_details.php?id=' . $productId . '#reviews');
    exit;
}

// Save review
$reviewData = [
    'product_id' => $productId,
    'user_id' => $_SESSION['user_id'],
    'rating' => $rating,
    'title' => $title,
    'review' => $review,
    'pros' => $pros,
    'cons' => $cons,
    'is_verified_purchase' => $isVerified,
    'images' => $uploadedImages
];

$result = $reviewModel->create($reviewData);

if ($result) {
    $_SESSION['success_message'] = '✅ Your review has been submitted successfully!';
} else {
    $_SESSION['error_message'] = '❌ Failed to submit review. Please try again.';
}

header('Location: product_details.php?id=' . $productId . '#reviews');
exit;
?>