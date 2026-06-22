<?php
// ============================================================
// FILE: public/product_details.php
// PURPOSE: Product details page with reviews
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cart_functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Shop.php';

session_start();

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    header('Location: products.php');
    exit;
}

// Get product details
$productModel = new Product();
$product = $productModel->getProductById($productId);

if (!$product) {
    header('Location: products.php');
    exit;
}

// Get shop details
$shopModel = new Shop();
$shop = $shopModel->getById($product['shop_id']);

// Get reviews
$reviewModel = new Review();
$reviews = $reviewModel->getProductReviews($productId, 10, 0, $_GET['sort'] ?? 'newest');
$ratingSummary = $reviewModel->getRatingSummary($productId);
$userReview = null;

// Check if user already reviewed
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
if ($isLoggedIn) {
    $userReview = $reviewModel->getUserReview($_SESSION['user_id'], $productId);
}

// Check if user has purchased this product (for verified badge)
$hasPurchased = false;
if ($isLoggedIn) {
    try {
        $pdo = getDbConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                WHERE o.user_id = :user_id 
                AND oi.product_id = :product_id 
                AND o.status = 'completed'
            ");
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':product_id' => $productId
            ]);
            $result = $stmt->fetch();
            $hasPurchased = $result['count'] > 0;
        }
    } catch (Exception $e) {
        error_log('Check purchase error: ' . $e->getMessage());
    }
}

$pageTitle = $product['name'] . ' - DU Marketplace';
$cartCount = getCartTotalItems();

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/reviews.css">

<div class="product-details-page">
    <div class="container">
        
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php">Home</a> &gt;
            <a href="products.php">Products</a> &gt;
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </div>
        
        <div class="product-details">
            <!-- Product Info -->
            <div class="product-info-section">
                <div class="product-gallery">
                    <img src="<?php echo getProductImageUrl($product['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                
                <div class="product-meta">
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <!-- Rating Summary -->
                    <?php if ($ratingSummary && $ratingSummary['total_reviews'] > 0): ?>
                        <div class="product-rating-summary">
                            <div class="rating-stars">
                                <?php echo renderStars($ratingSummary['avg_rating']); ?>
                                <span class="rating-number"><?php echo $ratingSummary['avg_rating']; ?></span>
                            </div>
                            <span class="rating-count"><?php echo $ratingSummary['total_reviews']; ?> reviews</span>
                        </div>
                    <?php else: ?>
                        <div class="product-rating-summary">
                            <span class="no-reviews">No reviews yet</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="product-price">৳<?php echo number_format($product['price'], 2); ?></div>
                    
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </div>
                    
                    <!-- Add to Cart -->
                    <button class="btn btn-primary add-to-cart" 
                            data-id="<?php echo $product['id']; ?>"
                            data-name="<?php echo htmlspecialchars($product['name']); ?>"
                            data-price="<?php echo $product['price']; ?>">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                    
                    <!-- Write Review Button -->
                    <?php if ($isLoggedIn && !$userReview): ?>
                        <button class="btn btn-outline" onclick="showReviewForm()">
                            <i class="fas fa-star"></i> Write a Review
                        </button>
                    <?php elseif ($isLoggedIn && $userReview): ?>
                        <a href="edit-review.php?id=<?php echo $userReview['id']; ?>" class="btn btn-outline">
                            <i class="fas fa-edit"></i> Edit Your Review
                        </a>
                    <?php else: ?>
                        <a href="login.php?redirect=<?php echo urlencode('product_details.php?id=' . $productId); ?>" class="btn btn-outline">
                            <i class="fas fa-sign-in-alt"></i> Login to Review
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Reviews Section -->
            <div class="reviews-section" id="reviews">
                <h2>Customer Reviews</h2>
                
                <!-- Rating Breakdown -->
                <?php if ($ratingSummary && $ratingSummary['total_reviews'] > 0): ?>
                    <div class="rating-breakdown">
                        <div class="rating-overall">
                            <div class="big-rating"><?php echo $ratingSummary['avg_rating']; ?></div>
                            <div class="rating-stars-large">
                                <?php echo renderStars($ratingSummary['avg_rating']); ?>
                            </div>
                            <span><?php echo $ratingSummary['total_reviews']; ?> reviews</span>
                        </div>
                        
                        <div class="rating-bars">
                            <div class="rating-bar">
                                <span>5★</span>
                                <div class="bar"><div style="width:<?php echo $ratingSummary['five_star_percent']; ?>%"></div></div>
                                <span><?php echo $ratingSummary['five_star']; ?></span>
                            </div>
                            <div class="rating-bar">
                                <span>4★</span>
                                <div class="bar"><div style="width:<?php echo $ratingSummary['four_star_percent']; ?>%"></div></div>
                                <span><?php echo $ratingSummary['four_star']; ?></span>
                            </div>
                            <div class="rating-bar">
                                <span>3★</span>
                                <div class="bar"><div style="width:<?php echo $ratingSummary['three_star_percent']; ?>%"></div></div>
                                <span><?php echo $ratingSummary['three_star']; ?></span>
                            </div>
                            <div class="rating-bar">
                                <span>2★</span>
                                <div class="bar"><div style="width:<?php echo $ratingSummary['two_star_percent']; ?>%"></div></div>
                                <span><?php echo $ratingSummary['two_star']; ?></span>
                            </div>
                            <div class="rating-bar">
                                <span>1★</span>
                                <div class="bar"><div style="width:<?php echo $ratingSummary['one_star_percent']; ?>%"></div></div>
                                <span><?php echo $ratingSummary['one_star']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Sort Options -->
                <div class="review-sort">
                    <span>Sort by:</span>
                    <select onchange="window.location.href='?id=<?php echo $productId; ?>&sort=' + this.value">
                        <option value="newest" <?php echo ($_GET['sort'] ?? 'newest') == 'newest' ? 'selected' : ''; ?>>Newest</option>
                        <option value="highest" <?php echo ($_GET['sort'] ?? '') == 'highest' ? 'selected' : ''; ?>>Highest Rating</option>
                        <option value="lowest" <?php echo ($_GET['sort'] ?? '') == 'lowest' ? 'selected' : ''; ?>>Lowest Rating</option>
                        <option value="helpful" <?php echo ($_GET['sort'] ?? '') == 'helpful' ? 'selected' : ''; ?>>Most Helpful</option>
                    </select>
                </div>
                
                <!-- Review List -->
                <?php if (!empty($reviews)): ?>
                    <div class="review-list">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item" id="review-<?php echo $review['id']; ?>">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <div class="reviewer-avatar">
                                            <?php 
                                            $avatar = !empty($review['user_avatar']) ? $review['user_avatar'] : 'assets/images/default-avatar.png';
                                            ?>
                                            <img src="<?php echo SITE_URL . $avatar; ?>" alt="<?php echo htmlspecialchars($review['user_name']); ?>">
                                        </div>
                                        <div>
                                            <div class="reviewer-name"><?php echo htmlspecialchars($review['user_name']); ?></div>
                                            <div class="review-stars">
                                                <?php echo renderStars($review['rating']); ?>
                                            </div>
                                            <div class="review-date">
                                                <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($review['is_verified_purchase']): ?>
                                        <span class="verified-badge">
                                            <i class="fas fa-check-circle"></i> Verified Purchase
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($review['title'])): ?>
                                    <h4 class="review-title"><?php echo htmlspecialchars($review['title']); ?></h4>
                                <?php endif; ?>
                                
                                <div class="review-content">
                                    <?php echo nl2br(htmlspecialchars($review['review'])); ?>
                                </div>
                                
                                <?php if (!empty($review['pros']) || !empty($review['cons'])): ?>
                                    <div class="review-pros-cons">
                                        <?php if (!empty($review['pros'])): ?>
                                            <div class="pros">
                                                <strong>👍 Pros:</strong>
                                                <?php echo nl2br(htmlspecialchars($review['pros'])); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($review['cons'])): ?>
                                            <div class="cons">
                                                <strong>👎 Cons:</strong>
                                                <?php echo nl2br(htmlspecialchars($review['cons'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($review['images'])): ?>
                                    <div class="review-images">
                                        <?php foreach ($review['images'] as $image): ?>
                                            <img src="<?php echo SITE_URL . 'uploads/reviews/' . $image; ?>" 
                                                 alt="Review image" 
                                                 onclick="openImageModal(this.src)">
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="review-actions">
                                    <button class="btn-helpful" onclick="markHelpful(<?php echo $review['id']; ?>)">
                                        <i class="fas fa-thumbs-up"></i>
                                        Helpful (<span id="helpful-count-<?php echo $review['id']; ?>">
                                            <?php echo $review['helpful_count']; ?>
                                        </span>)
                                    </button>
                                    
                                    <?php if ($isLoggedIn && $_SESSION['user_id'] == $review['user_id']): ?>
                                        <a href="edit-review.php?id=<?php echo $review['id']; ?>" class="btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <button class="btn-delete" onclick="deleteReview(<?php echo $review['id']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-reviews-message">
                        <i class="fas fa-star"></i>
                        <h3>No reviews yet</h3>
                        <p>Be the first to review this product!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Review Form Modal -->
<div class="modal" id="reviewModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Write a Review</h2>
            <button class="modal-close" onclick="closeReviewForm()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="reviewForm" action="submit-review.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                
                <!-- Rating -->
                <div class="form-group">
                    <label>Your Rating <span class="required">*</span></label>
                    <div class="star-rating">
                        <input type="radio" name="rating" id="star5" value="5">
                        <label for="star5" title="5 stars">★</label>
                        <input type="radio" name="rating" id="star4" value="4">
                        <label for="star4" title="4 stars">★</label>
                        <input type="radio" name="rating" id="star3" value="3">
                        <label for="star3" title="3 stars">★</label>
                        <input type="radio" name="rating" id="star2" value="2">
                        <label for="star2" title="2 stars">★</label>
                        <input type="radio" name="rating" id="star1" value="1">
                        <label for="star1" title="1 star">★</label>
                    </div>
                    <span class="rating-text" id="ratingText">Select rating</span>
                </div>
                
                <!-- Review Title -->
                <div class="form-group">
                    <label for="reviewTitle">Review Title</label>
                    <input type="text" id="reviewTitle" name="title" class="form-control" 
                           placeholder="Summarize your experience">
                </div>
                
                <!-- Review Content -->
                <div class="form-group">
                    <label for="reviewContent">Your Review <span class="required">*</span></label>
                    <textarea id="reviewContent" name="review" class="form-control" rows="5" 
                              placeholder="What did you like or dislike about this product?" required></textarea>
                </div>
                
                <!-- Pros -->
                <div class="form-group">
                    <label for="reviewPros">What are the pros?</label>
                    <textarea id="reviewPros" name="pros" class="form-control" rows="2" 
                              placeholder="What did you like?"></textarea>
                </div>
                
                <!-- Cons -->
                <div class="form-group">
                    <label for="reviewCons">What are the cons?</label>
                    <textarea id="reviewCons" name="cons" class="form-control" rows="2" 
                              placeholder="What could be improved?"></textarea>
                </div>
                
                <!-- Images -->
                <div class="form-group">
                    <label for="reviewImages">Upload Images (Optional)</label>
                    <input type="file" id="reviewImages" name="images[]" class="form-control" 
                           accept="image/*" multiple>
                    <small class="form-hint">You can upload up to 5 images</small>
                    <div id="imagePreview" class="image-preview"></div>
                </div>
                
                <!-- Verified Purchase -->
                <?php if ($hasPurchased): ?>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_verified_purchase" value="1" checked>
                            This review is for a verified purchase
                        </label>
                    </div>
                <?php endif; ?>
                
                <button type="submit" class="btn btn-primary">Submit Review</button>
            </form>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div class="modal" id="imageModal" onclick="closeImageModal()">
    <div class="modal-content image-modal-content">
        <img id="modalImage" src="" alt="Review image">
    </div>
</div>

<script src="<?php echo SITE_URL; ?>assets/js/reviews.js"></script>

<?php require_once ABSPATH . 'app/views/frontend/layout/footer.php'; ?>