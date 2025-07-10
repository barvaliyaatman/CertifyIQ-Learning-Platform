<?php
function getDiscountedPrice($original_price, $user_id) {
    global $pdo;
    
    // Get user's discount percentage
    $stmt = $pdo->prepare("SELECT discount_percentage FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    
    $discount_percentage = $result['discount_percentage'] ?? 0;
    
    if ($discount_percentage > 0) {
        $discount_amount = ($original_price * $discount_percentage) / 100;
        return round($original_price - $discount_amount, 2);
    }
    
    return $original_price;
}

function formatPrice($price) {
    return number_format($price, 2);
}
?>