// ============================================================
// REVIEWS JAVASCRIPT
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    // Star rating display
    const ratingInputs = document.querySelectorAll('.star-rating input[type="radio"]');
    const ratingText = document.getElementById('ratingText');
    
    if (ratingInputs.length > 0 && ratingText) {
        const ratingLabels = {
            5: 'Excellent! ⭐⭐⭐⭐⭐',
            4: 'Very Good ⭐⭐⭐⭐',
            3: 'Good ⭐⭐⭐',
            2: 'Fair ⭐⭐',
            1: 'Poor ⭐'
        };
        
        ratingInputs.forEach(input => {
            input.addEventListener('change', function() {
                ratingText.textContent = ratingLabels[this.value] || 'Select rating';
            });
        });
    }
    
    // Image preview for review form
    const imageInput = document.getElementById('reviewImages');
    const previewContainer = document.getElementById('imagePreview');
    
    if (imageInput && previewContainer) {
        imageInput.addEventListener('change', function() {
            previewContainer.innerHTML = '';
            const files = Array.from(this.files);
            
            // Limit to 5 images
            if (files.length > 5) {
                alert('You can upload a maximum of 5 images.');
                this.value = '';
                return;
            }
            
            files.forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        previewContainer.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
    }
});

// Show review form modal
function showReviewForm() {
    const modal = document.getElementById('reviewModal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

// Close review form modal
function closeReviewForm() {
    const modal = document.getElementById('reviewModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal on outside click
document.addEventListener('click', function(e) {
    const modal = document.getElementById('reviewModal');
    if (modal && modal.classList.contains('active')) {
        if (e.target === modal) {
            closeReviewForm();
        }
    }
});

// Mark review as helpful
function markHelpful(reviewId) {
    const button = event.currentTarget;
    const countSpan = document.getElementById('helpful-count-' + reviewId);
    const currentCount = parseInt(countSpan.textContent);
    
    fetch('mark-helpful.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'review_id=' + reviewId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.action === 'added') {
                countSpan.textContent = currentCount + 1;
                button.classList.add('active');
                button.querySelector('i').className = 'fas fa-thumbs-up';
            } else if (data.action === 'removed') {
                countSpan.textContent = currentCount - 1;
                button.classList.remove('active');
            }
        } else {
            if (data.message === 'Please login first.') {
                window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
            } else {
                alert(data.message);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to mark review as helpful. Please try again.');
    });
}

// Delete review
function deleteReview(reviewId) {
    if (!confirm('Are you sure you want to delete this review? This action cannot be undone.')) {
        return;
    }
    
    fetch('delete-review.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'review_id=' + reviewId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const reviewElement = document.getElementById('review-' + reviewId);
            if (reviewElement) {
                reviewElement.style.opacity = '0';
                setTimeout(() => {
                    reviewElement.remove();
                    
                    // Check if there are no reviews left
                    const reviewList = document.querySelector('.review-list');
                    if (reviewList && reviewList.children.length === 0) {
                        location.reload(); // Reload to show "No reviews" message
                    }
                }, 300);
            }
            alert('Review deleted successfully!');
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to delete review. Please try again.');
    });
}

// Open image modal
function openImageModal(src) {
    const modal = document.getElementById('imageModal');
    const img = document.getElementById('modalImage');
    if (modal && img) {
        img.src = src;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

// Close image modal
function closeImageModal() {
    const modal = document.getElementById('imageModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close image modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImageModal();
        closeReviewForm();
    }
});

// Helper function to render stars
function renderStars(rating) {
    let html = '';
    const fullStars = Math.floor(rating);
    const hasHalf = rating - fullStars >= 0.5;
    
    for (let i = 0; i < fullStars; i++) {
        html += '<span class="star">★</span>';
    }
    
    if (hasHalf) {
        html += '<span class="star">★</span>';
        // Half star would use special styling, but we're keeping it simple
    }
    
    const emptyStars = 5 - fullStars - (hasHalf ? 1 : 0);
    for (let i = 0; i < emptyStars; i++) {
        html += '<span class="star empty">★</span>';
    }
    
    return html;
}