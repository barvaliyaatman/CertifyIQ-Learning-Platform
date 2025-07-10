<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/discount_helper.php';

// Ensure user is logged in
require_login();

// Get course ID from POST or GET
$course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : (isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0);

// Validate course exists and get details
// After getting course details
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    header("Location: courses.php");
    exit();
}

// Calculate prices
$original_price = $course['price'];
$discounted_price = getDiscountedPrice($original_price, $_SESSION['user_id']);

// Check if already enrolled
$stmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
$stmt->execute([$_SESSION['user_id'], $course_id]);
if ($stmt->fetch()) {
    header("Location: course.php?id=" . $course_id);
    exit();
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payment'])) {
    $course_id = $_POST['course_id'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'credit_card';
    
    if (empty($course_id)) {
        $error = "Course ID is required";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Get course details
            $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
            $stmt->execute([$course_id]);
            $course = $stmt->fetch();
            
            if (!$course) {
                throw new Exception("Course not found");
            }
            
            // Calculate discounted price
            require_once '../includes/discount_helper.php';
            $original_price = $course['price'];
            $discounted_price = getDiscountedPrice($original_price, $_SESSION['user_id']);
            
            if ($payment_method === 'paypal') {
                // Handle PayPal payment
                $transaction_id = 'PAYPAL_' . uniqid();
                
                // Create payment record for PayPal
                $stmt = $pdo->prepare("
                    INSERT INTO payments (
                        user_id, course_id, amount, original_amount, discount_applied,
                        payment_method, payment_date, status, transaction_id
                    ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['user_id'], 
                    $course_id, 
                    $discounted_price,
                    $original_price,
                    ($original_price - $discounted_price),
                    'paypal',
                    'pending',
                    $transaction_id
                ]);
                
                $payment_id = $pdo->lastInsertId();
                
                // Create enrollment
                $stmt = $pdo->prepare("
                    INSERT INTO enrollments (
                        user_id, course_id, enrollment_date, payment_id
                    ) VALUES (?, ?, NOW(), ?)
                ");
                $stmt->execute([
                    $_SESSION['user_id'], 
                    $course_id,
                    $payment_id
                ]);
                
                $pdo->commit();
                
                // Redirect to PayPal (you would integrate with PayPal API here)
                $_SESSION['success'] = "PayPal payment initiated! Transaction ID: " . $transaction_id;
                header("Location: course.php?id=" . $course_id);
                exit();
                
            } else {
                // Handle credit card payment (existing logic)
                $card_number = str_replace(' ', '', $_POST['card_number']);
                $expiry = $_POST['expiry'];
                $cvv = $_POST['cvv'];
                
                // Card validation - accept any valid card number
                if (preg_match('/^[0-9]{16}$/', $card_number) && 
                    preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $expiry) && 
                    preg_match('/^[0-9]{3,4}$/', $cvv)) {
                    
                    // Generate unique transaction ID
                    $transaction_id = 'TEST_' . uniqid();

                    // Create payment record
                    // After the initial requires, add:
                    require_once '../includes/discount_helper.php';
                    
                    // After getting course details, calculate discounted price
                    $original_price = $course['price'];
                    $discounted_price = getDiscountedPrice($original_price, $_SESSION['user_id']);
                    
                    // Update the payment record creation
                    $stmt = $pdo->prepare("
                        INSERT INTO payments (
                            user_id, course_id, amount, original_amount, discount_applied,
                            payment_method, payment_date, status, transaction_id
                        ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)
                    ");
                    $stmt->execute([
                        $_SESSION['user_id'], 
                        $course_id, 
                        $discounted_price,
                        $original_price,
                        ($original_price - $discounted_price),
                        'credit_card',
                        'completed',
                        $transaction_id
                    ]);
                    
                    $payment_id = $pdo->lastInsertId();

                    // Create enrollment
                    $stmt = $pdo->prepare("
                        INSERT INTO enrollments (
                            user_id, course_id, enrollment_date, payment_id
                        ) VALUES (?, ?, NOW(), ?)
                    ");
                    $stmt->execute([
                        $_SESSION['user_id'], 
                        $course_id,
                        $payment_id
                    ]);

                    // Commit transaction
                    $pdo->commit();

                    // Set success message and redirect
                    $_SESSION['success'] = "Payment successful! You are now enrolled in the course.";
                    header("Location: course.php?id=" . $course_id);
                    exit();
                } else {
                    throw new Exception("Invalid card details. Please check your card number, expiry date, and CVV.");
                }
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
            error_log("Payment Error: " . $e->getMessage());
        }
    }
}

$page_title = "Payment - " . $course['title'];
include '../includes/header.php';

// Get payment method from POST
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
?>

<div class="payment-container">
    <div class="payment-card">
        <h2>Course Payment</h2>
        <div class="course-summary">
            <img src="<?php echo !empty($course['image_url']) ? '../' . ltrim($course['image_url'], '/') : '../assets/images/default-course.jpg'; ?>" 
                 alt="<?php echo htmlspecialchars($course['title']); ?>">
            
            <div class="course-info">
                <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                <?php if ($discounted_price < $original_price): ?>
                    <p class="price">
                        <span class="original-price">$<?php echo number_format($original_price, 2); ?></span>
                        <span class="discounted-price">$<?php echo number_format($discounted_price, 2); ?></span>
                        <span class="discount-badge">
                            <?php echo round((($original_price - $discounted_price) / $original_price) * 100); ?>% OFF
                        </span>
                    </p>
                <?php else: ?>
                    <p class="price">$<?php echo number_format($original_price, 2); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="payment-methods">
            <h3>Select Payment Method</h3>
            <div class="payment-method-options">
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="credit_card" checked>
                    <span class="payment-icon"><i class="fas fa-credit-card"></i></span>
                    <span>Credit Card</span>
                </label>
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="paypal">
                    <span class="payment-icon"><i class="fab fa-paypal"></i></span>
                    <span>PayPal</span>
                </label>
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="bank_transfer">
                    <span class="payment-icon"><i class="fas fa-university"></i></span>
                    <span>Bank Transfer</span>
                </label>
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="crypto">
                    <span class="payment-icon"><i class="fab fa-bitcoin"></i></span>
                    <span>Cryptocurrency</span>
                </label>
            </div>
        </div>

        <form action="payment.php" method="POST" class="payment-form" id="creditCardForm">
            <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
            <input type="hidden" name="payment_method" value="credit_card">
            
            <div class="form-group">
                <label>Card Number</label>
                <input type="text" name="card_number" required pattern="[0-9]{16}" placeholder="1234 5678 9012 3456">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Expiry Date</label>
                    <input type="text" name="expiry" required pattern="(0[1-9]|1[0-2])\/[0-9]{2}" placeholder="MM/YY">
                </div>
                <div class="form-group">
                    <label>CVV</label>
                    <input type="text" name="cvv" required pattern="[0-9]{3,4}" placeholder="123">
                </div>
            </div>

            <div class="form-group">
                <label>Cardholder Name</label>
                <input type="text" name="cardholder_name" required placeholder="John Doe">
            </div>

            <div class="form-group text-center">
                <button type="submit" name="process_payment" class="btn payment-btn">
                    Pay $<?php echo number_format($discounted_price, 2); ?>
                </button>
            </div>
            
            
            <style>
                .text-center {
                    text-align: center;
                }

                .payment-btn {
                    width: auto;
                    min-width: 200px;
                    padding: 12px 30px;
                    margin: 0 auto;
                    display: inline-block;
                }
            </style>
        </form>

        <div id="paypalForm" class="payment-form" style="display: none;">
            <form action="payment.php" method="POST">
                <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                <input type="hidden" name="payment_method" value="paypal">
                <div class="payment-info">
                    <p>You will be redirected to PayPal to complete your payment.</p>
                    <div class="form-group text-center">
                        <button type="submit" name="process_payment" class="btn payment-btn paypal-btn">
                            <i class="fab fa-paypal"></i> Pay with PayPal - $<?php echo number_format($discounted_price, 2); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div id="bankTransferForm" class="payment-form" style="display: none;">
            <div class="bank-details">
                <h4>Bank Account Details</h4>
                <p><strong>Bank Name:</strong> Example Bank</p>
                <p><strong>Account Name:</strong> LMS Education</p>
                <p><strong>Account Number:</strong> 1234567890</p>
                <p><strong>Sort Code:</strong> 12-34-56</p>
                <p><strong>Reference:</strong> COURSE-<?php echo $course_id; ?></p>
                <div class="alert alert-info">
                    Please include the reference number in your transfer description.
                </div>
            </div>
        </div>

        <div id="cryptoForm" class="payment-form" style="display: none;">
            <div class="crypto-details">
                <h4>Cryptocurrency Payment</h4>
                <div class="crypto-options">
                    <button type="button" class="crypto-btn" data-crypto="btc">
                        <i class="fab fa-bitcoin"></i> Bitcoin
                    </button>
                    <button type="button" class="crypto-btn" data-crypto="eth">
                        <i class="fab fa-ethereum"></i> Ethereum
                    </button>
                </div>
                <div class="wallet-address">
                    <p>Send exact amount to:</p>
                    <code class="wallet-code">1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa</code>
                    <button class="copy-btn" data-clipboard-target=".wallet-code">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const forms = {
        credit_card: document.getElementById('creditCardForm'),
        paypal: document.getElementById('paypalForm'),
        bank_transfer: document.getElementById('bankTransferForm'),
        crypto: document.getElementById('cryptoForm')
    };

    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            // Hide all forms
            Object.values(forms).forEach(form => form.style.display = 'none');
            // Show selected form
            forms[this.value].style.display = 'block';
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>

<style>
    .payment-container {
        max-width: 900px;
        margin: 30px auto;
        padding: 20px;
    }

    .payment-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 25px;
    }

    .course-summary {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }

    .course-summary img {
        width: 200px;
        height: 120px;
        object-fit: cover;
        border-radius: 8px;
    }

    .course-info h3 {
        margin: 0 0 15px 0;
        color: #333;
    }

    .price {
        font-size: 1.2em;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .original-price {
        text-decoration: line-through;
        color: #999;
        font-size: 0.9em;
    }

    .discounted-price {
        color: #28a745;
        font-weight: bold;
    }

    .discount-badge {
        background: #dc3545;
        color: white;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 0.8em;
    }

    .payment-methods {
        margin: 30px 0;
    }

    .payment-method-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin: 20px 0;
    }

    .payment-option {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .payment-option:hover {
        border-color: #007bff;
        background: #f8f9fa;
    }

    .payment-icon {
        font-size: 24px;
        margin-bottom: 10px;
        color: #007bff;
    }

    .payment-form {
        margin-top: 30px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #555;
        font-weight: 500;
    }

    .form-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .payment-btn {
        width: 15%;
        padding: 12px;
        background: #007bff;
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    .payment-btn:hover {
        background: #0056b3;
    }

    .bank-details, .crypto-details {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
    }

    .wallet-address {
        background: #e9ecef;
        padding: 15px;
        border-radius: 5px;
        margin-top: 15px;
    }

    .wallet-code {
        display: block;
        padding: 10px;
        background: #fff;
        border-radius: 4px;
        margin: 10px 0;
        font-family: monospace;
    }

    .copy-btn {
        background: #6c757d;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }

    .copy-btn:hover {
        background: #5a6268;
    }

    .alert {
        padding: 15px;
        margin: 20px 0;
        border-radius: 5px;
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert-info {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
</style>