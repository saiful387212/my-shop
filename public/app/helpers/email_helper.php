<?php
// ============================================================
// FILE: app/helpers/email_helper.php
// PURPOSE: Email sending functionality
// ============================================================

if (!defined('ABSPATH')) {
    die('Direct access not allowed.');
}

/**
 * Send order confirmation email
 * 
 * @param int $orderId The order ID
 * @return bool True if email sent, false otherwise
 */
function sendOrderConfirmation($orderId) {
    try {
        // Get database connection
        $pdo = getDbConnection();
        
        if ($pdo === null) {
            error_log('Email: Database connection failed');
            return false;
        }
        
        // ============================================
        // STEP 1: Get order details
        // ============================================
        $stmt = $pdo->prepare('
            SELECT 
                o.*,
                u.email as customer_email,
                u.name as customer_name
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = :order_id
        ');
        $stmt->execute(['order_id' => $orderId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            error_log('Email: Order not found for ID: ' . $orderId);
            return false;
        }
        
        // Get customer email (from session if not in DB)
        if (empty($order['customer_email'])) {
            $order['customer_email'] = $_SESSION['user_email'] ?? 'customer@example.com';
        }
        if (empty($order['customer_name'])) {
            $order['customer_name'] = $_SESSION['user_name'] ?? 'Customer';
        }
        
        // ============================================
        // STEP 2: Get order items
        // ============================================
        $stmt = $pdo->prepare('
            SELECT * FROM order_items 
            WHERE order_id = :order_id
        ');
        $stmt->execute(['order_id' => $orderId]);
        $items = $stmt->fetchAll();
        
        // ============================================
        // STEP 3: Build email HTML
        // ============================================
        $subject = "Order Confirmation #" . $order['order_number'];
        
        // Build items table
        $itemsHtml = '';
        foreach ($items as $item) {
            $subtotal = $item['product_price'] * $item['quantity'];
            $itemsHtml .= "
                <tr>
                    <td style='padding:8px 12px; border-bottom:1px solid #eee;'>{$item['product_name']}</td>
                    <td style='padding:8px 12px; border-bottom:1px solid #eee; text-align:center;'>× {$item['quantity']}</td>
                    <td style='padding:8px 12px; border-bottom:1px solid #eee; text-align:right;'>$" . number_format($subtotal, 2) . "</td>
                </tr>
            ";
        }
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Order Confirmation</title>
        </head>
        <body style='font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5;'>
            <div style='max-width: 600px; margin: 40px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);'>
                
                <!-- Header -->
                <div style='background: #2C3E8F; padding: 30px 40px; text-align: center;'>
                    <h1 style='color: white; margin: 0; font-size: 24px;'>My Shop</h1>
                    <p style='color: rgba(255,255,255,0.8); margin: 5px 0 0; font-size: 14px;'>Order Confirmation</p>
                </div>
                
                <!-- Content -->
                <div style='padding: 30px 40px;'>
                    <h2 style='color: #1a1a2e; margin-top: 0;'>Thank you for your order!</h2>
                    <p style='color: #4a5568;'>Hi <strong>{$order['customer_name']}</strong>,</p>
                    <p style='color: #4a5568;'>Your order has been placed successfully. Here are the details:</p>
                    
                    <!-- Order Details -->
                    <div style='background: #f8fafc; border-radius: 8px; padding: 16px 20px; margin: 20px 0;'>
                        <p style='margin: 4px 0;'><strong>Order Number:</strong> #{$order['order_number']}</p>
                        <p style='margin: 4px 0;'><strong>Order Date:</strong> " . date('F j, Y \a\t g:i A', strtotime($order['created_at'])) . "</p>
                        <p style='margin: 4px 0;'><strong>Status:</strong> <span style='color: #f39c12;'>" . ucfirst($order['status']) . "</span></p>
                    </div>
                    
                    <!-- Items -->
                    <h3 style='color: #1a1a2e; margin: 20px 0 10px;'>Order Items</h3>
                    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                        <thead>
                            <tr style='background: #f8fafc;'>
                                <th style='padding: 10px 12px; text-align: left; font-size: 12px; text-transform: uppercase; color: #6c757d;'>Product</th>
                                <th style='padding: 10px 12px; text-align: center; font-size: 12px; text-transform: uppercase; color: #6c757d;'>Qty</th>
                                <th style='padding: 10px 12px; text-align: right; font-size: 12px; text-transform: uppercase; color: #6c757d;'>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            $itemsHtml
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan='2' style='padding: 12px; text-align: right; font-weight: bold;'>Total:</td>
                                <td style='padding: 12px; text-align: right; font-size: 18px; font-weight: bold; color: #2C3E8F;'>$" . number_format($order['total_amount'], 2) . "</td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <!-- Shipping Address -->
                    <h3 style='color: #1a1a2e; margin: 20px 0 10px;'>Shipping Address</h3>
                    <p style='color: #4a5568; background: #f8fafc; padding: 12px 16px; border-radius: 8px;'>
                        " . nl2br(htmlspecialchars($order['shipping_address'])) . "
                    </p>
                    
                    <!-- Next Steps -->
                    <div style='background: #e6f7ed; border-radius: 8px; padding: 16px 20px; margin: 20px 0;'>
                        <p style='margin: 0; color: #065f46;'>
                            <strong>📦 What's next?</strong><br>
                            We'll send you a shipping confirmation once your order is on its way.
                        </p>
                    </div>
                </div>
                
                <!-- Footer -->
                <div style='background: #f8fafc; padding: 20px 40px; text-align: center; border-top: 1px solid #eef2f7;'>
                    <p style='margin: 0; color: #6c757d; font-size: 12px;'>
                        This is a confirmation email. Please keep it for your records.<br>
                        &copy; " . date('Y') . " My Shop. All rights reserved.
                    </p>
                    <p style='margin: 8px 0 0; color: #6c757d; font-size: 12px;'>
                        <a href='mailto:info@myshop.com' style='color: #2C3E8F; text-decoration: none;'>info@myshop.com</a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // ============================================
        // STEP 4: Email headers
        // ============================================
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: My Shop <noreply@myshop.com>\r\n";
        $headers .= "Reply-To: info@myshop.com\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // ============================================
        // STEP 5: Send email
        // ============================================
        $to = $order['customer_email'];
        
        // Log email attempt
        error_log("Email: Sending confirmation to $to for order #{$order['order_number']}");
        
        $result = mail($to, $subject, $message, $headers);
        
        if ($result) {
            error_log("Email: Successfully sent to $to");
        } else {
            error_log("Email: Failed to send to $to");
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log('Email error: ' . $e->getMessage());
        return false;
    }
}
?>