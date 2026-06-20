// ============================================================
// FILE: public/assets/js/orders.js
// PURPOSE: Order history page interactivity
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // ORDER DETAILS TOGGLE (Mobile)
    // ============================================
    
    const orderHeaders = document.querySelectorAll('.order-header');
    
    orderHeaders.forEach(function(header) {
        header.addEventListener('click', function() {
            // For mobile: toggle order items visibility
            if (window.innerWidth <= 768) {
                const orderCard = this.closest('.order-card');
                const orderItems = orderCard.querySelector('.order-items');
                
                if (orderItems) {
                    const isHidden = orderItems.style.display === 'none';
                    orderItems.style.display = isHidden ? 'block' : 'none';
                }
            }
        });
    });
    
    // ============================================
    // STATUS COLOR CODING HELPER
    // ============================================
    
    function getStatusColor(status) {
        const colors = {
            'pending': '#f39c12',
            'processing': '#2C3E8F',
            'shipped': '#27ae60',
            'delivered': '#27ae60',
            'cancelled': '#e74c3c'
        };
        return colors[status] || '#6c757d';
    }
    
    // ============================================
    // PRINT ORDER DETAILS (Future feature)
    // ============================================
    
    document.querySelectorAll('.print-order').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const orderId = this.getAttribute('data-id');
            alert('Print order #' + orderId + ' (Coming soon!)');
        });
    });
    
    console.log('✅ Orders page loaded successfully!');
});