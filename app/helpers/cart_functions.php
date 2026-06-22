<?php
// ============================================================
// FILE: app/helpers/cart_functions.php
// ============================================================

if (!defined('ABSPATH')) {
    die('Direct access not allowed.');
}

function getCartItems() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
}

function saveCartItems($cart) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['cart'] = $cart;
}

function getCartTotalItems() {
    $cart = getCartItems();
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['quantity'];
    }
    return $total;
}

function getCartSubtotal() {
    $cart = getCartItems();
    $subtotal = 0;
    foreach ($cart as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    return $subtotal;
}

function addToCart($productId, $productName, $productPrice, $productImage = null) {
    $cart = getCartItems();
    
    if (isset($cart[$productId])) {
        $cart[$productId]['quantity'] += 1;
    } else {
        $cart[$productId] = [
            'id' => $productId,
            'name' => $productName,
            'price' => $productPrice,
            'quantity' => 1,
            'image' => $productImage
        ];
    }
    
    saveCartItems($cart);
    return true;
}

function removeFromCart($productId) {
    $cart = getCartItems();
    if (isset($cart[$productId])) {
        unset($cart[$productId]);
        saveCartItems($cart);
        return true;
    }
    return false;
}

function updateCartQuantity($productId, $quantity) {
    $cart = getCartItems();
    if (isset($cart[$productId])) {
        if ($quantity <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId]['quantity'] = $quantity;
        }
        saveCartItems($cart);
        return true;
    }
    return false;
}

function clearCart() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['cart'] = [];
    return true;
}

function getItemSubtotal($productId) {
    $cart = getCartItems();
    if (isset($cart[$productId])) {
        return $cart[$productId]['price'] * $cart[$productId]['quantity'];
    }
    return 0;
}
?>