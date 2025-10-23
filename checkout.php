<?php
include 'config.php';
// Khalti removed

// Only start session if one doesn't already exist
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'];

if (!isset($user_id)) {
   header('location:login.php');
}

// Fetch logged-in user's name and email for validation and prefilling
$logged_user = null;
$user_res = mysqli_query($conn, "SELECT name, email FROM `users` WHERE id = '" . mysqli_real_escape_string($conn, $user_id) . "' LIMIT 1");
if ($user_res && mysqli_num_rows($user_res) === 1) {
    $logged_user = mysqli_fetch_assoc($user_res);
}

// Prefill helpers
$prefill_name = isset($_POST['name']) ? $_POST['name'] : ($logged_user['name'] ?? '');
$prefill_email = isset($_POST['email']) ? $_POST['email'] : ($logged_user['email'] ?? '');
$prefill_number = isset($_POST['number']) ? $_POST['number'] : '';

// Khalti environment and keys (inline config)
// TODO: Replace with your actual keys or set via environment variables
if (!defined('KHALTI_PRODUCTION_MODE')) { define('KHALTI_PRODUCTION_MODE', false); }
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '');
$isLocalHost = in_array($host, ['localhost', '127.0.0.1'], true);
$isPrivateIp = filter_var($host, FILTER_VALIDATE_IP) && (strpos($host, '192.168.') === 0 || strpos($host, '10.') === 0 || preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $host));
$is_local_env = $isLocalHost || $isPrivateIp;
$EFFECTIVE_PRODUCTION_MODE = (KHALTI_PRODUCTION_MODE && !$is_local_env);

if (!defined('KHALTI_PUBLIC_KEY')) {
    $envPub = getenv('KHALTI_PUBLIC_KEY');
    define('KHALTI_PUBLIC_KEY', $envPub !== false ? $envPub : '57d5b5bdb156448595506984ff43269b');
}
if (!defined('KHALTI_SECRET_KEY')) {
    $envSec = getenv('KHALTI_SECRET_KEY');
    define('KHALTI_SECRET_KEY', $envSec !== false ? $envSec : '9ce8887af0d44587b07f94a33cd6b021'); // keep secret server-side
}
if (!defined('KHALTI_API_ENDPOINT')) {
    // Per docs: Production https://khalti.com/api/v2/ ; Sandbox https://dev.khalti.com/api/v2/
    define('KHALTI_API_ENDPOINT', $EFFECTIVE_PRODUCTION_MODE ? 'https://khalti.com/api/v2/' : 'https://dev.khalti.com/api/v2/');
}

// Helper to build absolute URLs
function current_base_url() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $scheme . '://' . $host . ($scriptDir ? $scriptDir : '');
}

// KPG-2: Initiate ePayment (server-side) and redirect to payment_url
if (isset($_POST['khalti_epayment']) && $_POST['khalti_epayment'] === '1') {
    header('Content-Type: application/json');
    if (!KHALTI_SECRET_KEY) {
        echo json_encode(['success' => false, 'message' => 'Khalti secret key not configured on server.']);
        exit;
    }
    // Basic validations
    if (!isset($_POST['name'], $_POST['number'], $_POST['email'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    $name   = mysqli_real_escape_string($conn, $_POST['name']);
    $number = mysqli_real_escape_string($conn, $_POST['number']);
    $email  = mysqli_real_escape_string($conn, $_POST['email']);

    // Recompute amount securely from server-side cart
    $cart_total = 0; // in rupees
    $cart_products = [];
    $product_sizes = [];

    $cart_query = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'");
    if (!$cart_query) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch cart.']);
        exit;
    }
    while ($cart_item = mysqli_fetch_assoc($cart_query)) {
        $product_name = $cart_item['name'];
        $cart_products[] = $product_name . ' (' . $cart_item['quantity'] . ')';
        if (!empty($cart_item['size'])) {
            $product_sizes[$product_name] = $cart_item['size'];
        }
        $sub_total = ($cart_item['price'] * $cart_item['quantity']);
        $cart_total += $sub_total;
    }

    if ($cart_total <= 0) {
        echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
        exit;
    }
    $server_amount_paisa = (int)($cart_total * 100);

    // Persist expected data for lookup validation after callback
    $_SESSION['khalti_epayment'] = [
        'expected_amount' => $server_amount_paisa,
        'user_id' => $user_id,
        'cart_products' => $cart_products,
        'product_sizes' => $product_sizes,
        'name' => $name,
        'number' => $number,
        'email' => $email,
    ];

    $purchase_order_id = 'ORD' . $user_id . '_' . time();
    $return_url = current_base_url() . '/checkout.php?khalti_return=1';
    $website_url = current_base_url() . '/';

    $payload = [
        'return_url' => $return_url,
        'website_url' => $website_url,
        'amount' => $server_amount_paisa,
        'purchase_order_id' => $purchase_order_id,
        'purchase_order_name' => 'Shoes Order',
        'customer_info' => [
            'name' => $name,
            'email' => $email,
            'phone' => $number,
        ],
    ];

    $init_url = rtrim(KHALTI_API_ENDPOINT, '/') . '/epayment/initiate/';
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $init_url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Key ' . KHALTI_SECRET_KEY,
            'Content-Type: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($response === false || $http_code !== 200) {
        echo json_encode(['success' => false, 'message' => 'Khalti initiate failed', 'http_code' => $http_code, 'detail' => ($response ?: $curl_err)]);
        exit;
    }
    $resp = json_decode($response, true);
    if (!is_array($resp) || empty($resp['payment_url'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid initiate response.']);
        exit;
    }
    // Redirect browser to Khalti payment page
    echo json_encode(['redirect' => $resp['payment_url']]);
    exit;
}

// KPG-2: Return handler -> lookup payment using pidx and place order
if (isset($_GET['khalti_return'])) {
    $pidx = isset($_GET['pidx']) ? trim($_GET['pidx']) : '';
    if ($pidx === '') {
        $message[] = 'Missing payment reference (pidx).';
    } else {
        // Lookup
        $lookup_url = rtrim(KHALTI_API_ENDPOINT, '/') . '/epayment/lookup/';
        $payload = [ 'pidx' => $pidx ];
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $lookup_url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Key ' . KHALTI_SECRET_KEY,
                'Content-Type: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        if ($response === false || $http_code !== 200) {
            $message[] = 'Failed to confirm payment. ' . ($response ?: $curl_err);
        } else {
            $resp = json_decode($response, true);
            if (!is_array($resp) || empty($resp['status'])) {
                $message[] = 'Invalid lookup response.';
            } else if (strtolower($resp['status']) !== 'completed') {
                // Pending, Expired, User canceled, etc.
                $message[] = 'Payment not completed. Status: ' . $resp['status'];
            } else {
                // Validate amount vs session expectation
                $expected = isset($_SESSION['khalti_epayment']['expected_amount']) ? (int)$_SESSION['khalti_epayment']['expected_amount'] : 0;
                $total_amount = isset($resp['total_amount']) ? (int)$resp['total_amount'] : 0;
                if ($expected <= 0 || $total_amount !== $expected) {
                    $message[] = 'Amount mismatch during verification.';
                } else {
                    // Place order
                    $name   = mysqli_real_escape_string($conn, $_SESSION['khalti_epayment']['name'] ?? '');
                    $number = mysqli_real_escape_string($conn, $_SESSION['khalti_epayment']['number'] ?? '');
                    $email  = mysqli_real_escape_string($conn, $_SESSION['khalti_epayment']['email'] ?? '');

                    // Rebuild cart details and compute total in rupees from DB to be safe
                    $cart_total = 0; $cart_products = []; $product_sizes = [];
                    $cart_query = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'");
                    if ($cart_query) {
                        while ($cart_item = mysqli_fetch_assoc($cart_query)) {
                            $cart_products[] = $cart_item['name'] . ' (' . (int)$cart_item['quantity'] . ')';
                            $line_total = ((float)$cart_item['price']) * ((int)$cart_item['quantity']);
                            $cart_total += $line_total;
                            if (!empty($cart_item['size'])) {
                                $product_sizes[$cart_item['name']] = $cart_item['size'];
                            }
                        }
                    }

                    // Ensure sizes column exists
                    $check_column = mysqli_query($conn, "SHOW COLUMNS FROM `orders` LIKE 'sizes'");
                    if ($check_column && mysqli_num_rows($check_column) == 0) {
                        mysqli_query($conn, "ALTER TABLE `orders` ADD `sizes` TEXT NULL AFTER `total_products`");
                    }

                    $method = 'khalti';
                    $placed_on = date('d-M-Y');
                    $total_products = implode(', ', $cart_products);
                    $sizes_json = !empty($product_sizes) ? json_encode($product_sizes) : '{}';

                    mysqli_begin_transaction($conn);
                    try {
                        $insert_order = mysqli_query($conn, "INSERT INTO `orders`(user_id, name, number, email, method, total_products, sizes, total_price, placed_on) 
                                           VALUES('$user_id', '$name', '$number', '$email', '$method', '$total_products', '$sizes_json', '$cart_total', '$placed_on')");
                        if (!$insert_order) { throw new Exception('Failed to create order: ' . mysqli_error($conn)); }

                        // Update inventory
                        $cart_query2 = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'");
                        if (!$cart_query2) { throw new Exception('Failed to re-fetch cart: ' . mysqli_error($conn)); }
                        while ($cart_item = mysqli_fetch_assoc($cart_query2)) {
                            $product_name = $cart_item['name'];
                            $quantity = (int)$cart_item['quantity'];
                            $product_query = mysqli_query($conn, "SELECT id, quantity FROM `products` WHERE name = '$product_name' LIMIT 1 FOR UPDATE");
                            if (!$product_query) { throw new Exception('Failed to fetch product: ' . mysqli_error($conn)); }
                            $product = mysqli_fetch_assoc($product_query);
                            if (!$product) { throw new Exception('Product not found: ' . $product_name); }
                            if ((int)$product['quantity'] < $quantity) { throw new Exception('Insufficient stock for product: ' . $product_name); }
                            $pid = (int)$product['id'];
                            $upd = mysqli_query($conn, "UPDATE `products` SET quantity = quantity - $quantity WHERE id = '$pid'");
                            if (!$upd) { throw new Exception('Failed to update stock: ' . mysqli_error($conn)); }
                        }

                        // Clear cart
                        $delete_cart = mysqli_query($conn, "DELETE FROM `cart` WHERE user_id = '$user_id'");
                        if (!$delete_cart) { throw new Exception('Failed to clear cart: ' . mysqli_error($conn)); }

                        mysqli_commit($conn);
                        unset($_SESSION['khalti_epayment']);
                        header('Location: orders.php');
                        exit;
                    } catch (Exception $ex) {
                        mysqli_rollback($conn);
                        $message[] = 'Order placement failed: ' . $ex->getMessage();
                    }
                }
            }
        }
    }
    // Fall-through shows $message on checkout page
}

if (isset($_POST['order_btn'])) {
   $name = mysqli_real_escape_string($conn, $_POST['name']);
   $number = $_POST['number'];
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $method = 'cod'; // Only Cash on Delivery
   $placed_on = date('d-M-Y');

   // Validations: phone, email format, and match logged-in user's name/email
   $validation_errors = [];
   if (strlen($number) != 10) {
      $validation_errors[] = 'Phone number must be 10 digits long!';
   }
   if (!ctype_digit($number)) {
      $validation_errors[] = 'Phone number must contain only digits.';
   }
   if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $validation_errors[] = 'Please enter a valid email address.';
   }
   if (is_array($logged_user)) {
      if (isset($logged_user['name']) && strcasecmp(trim($name), trim($logged_user['name'])) !== 0) {
         $validation_errors[] = 'Name does not match your account.';
      }
      if (isset($logged_user['email']) && strcasecmp(trim($email), trim($logged_user['email'])) !== 0) {
         $validation_errors[] = 'Email does not match your account.';
      }
   }

   if (!empty($validation_errors)) {
      foreach ($validation_errors as $err) { $message[] = $err; }
   } else {
      $cart_total = 0;
      $cart_products = [];
      $product_sizes = [];

      $cart_query = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'") or die('query failed');
      if (mysqli_num_rows($cart_query) > 0) {
         while ($cart_item = mysqli_fetch_assoc($cart_query)) {
            $product_name = $cart_item['name'];
            $cart_products[] = $product_name . ' (' . $cart_item['quantity'] . ')';
            
            // Store size information if available
            if (!empty($cart_item['size'])) {
               $product_sizes[$product_name] = $cart_item['size'];
            }
            
            $sub_total = ($cart_item['price'] * $cart_item['quantity']);
            $cart_total += $sub_total;
         }
      }

      $total_products = implode(', ', $cart_products);
      $sizes_json = !empty($product_sizes) ? json_encode($product_sizes) : '{}';

      $order_query = mysqli_query($conn, "SELECT * FROM `orders` WHERE name = '$name' AND number = '$number' AND email = '$email' AND method = '$method' AND total_products = '$total_products' AND total_price = '$cart_total'") or die('query failed');

      if ($cart_total == 0) {
         $message[] = 'Your cart is empty!';
      } else {
         if (mysqli_num_rows($order_query) > 0) {
            $message[] = 'Order already placed!';
         } else {
            // eSewa removed: proceed with normal COD order placement only

            // Check if sizes column exists, if not add it
            $check_column = mysqli_query($conn, "SHOW COLUMNS FROM `orders` LIKE 'sizes'");
            if(mysqli_num_rows($check_column) == 0) {
               mysqli_query($conn, "ALTER TABLE `orders` ADD `sizes` TEXT NULL AFTER `total_products`") or die('query failed');
            }
            
            // Start transaction to ensure data consistency
            mysqli_begin_transaction($conn);
            
            try {
                // Insert the order
                $insert_order = mysqli_query($conn, "INSERT INTO `orders`(user_id, name, number, email, method, total_products, sizes, total_price, placed_on) 
                                   VALUES('$user_id', '$name', '$number', '$email', '$method', '$total_products', '$sizes_json', '$cart_total', '$placed_on')");
                
                if (!$insert_order) {
                    throw new Exception('Failed to create order: ' . mysqli_error($conn));
                }
                
                // Update product quantities
                $cart_query = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'");
                
                if (!$cart_query) {
                    throw new Exception('Failed to fetch cart items: ' . mysqli_error($conn));
                }
                
                while ($cart_item = mysqli_fetch_assoc($cart_query)) {
                    $product_name = $cart_item['name'];
                    $quantity = $cart_item['quantity'];
                    
                    // First, get the product ID and check current stock
                    $product_query = mysqli_query($conn, "SELECT id, quantity FROM `products` WHERE name = '$product_name' LIMIT 1 FOR UPDATE");
                    if (!$product_query) {
                        throw new Exception('Failed to fetch product details: ' . mysqli_error($conn));
                    }
                    
                    $product = mysqli_fetch_assoc($product_query);
                    if (!$product) {
                        throw new Exception('Product not found: ' . $product_name);
                    }
                    
                    $product_id = $product['id'];
                    
                    // Check if there's enough stock
                    if ($product['quantity'] < $quantity) {
                        throw new Exception('Insufficient stock for product: ' . $product_name);
                    }
                    
                    // Update product quantity in the database
                    $update_query = "UPDATE `products` SET quantity = quantity - $quantity WHERE id = '$product_id'";
                    $result = mysqli_query($conn, $update_query);
                    
                    if (!$result) {
                        throw new Exception('Failed to update product quantity: ' . mysqli_error($conn));
                    }
                }
                
                // Purchase information is now tracked in the orders table
                // No need for separate user_purchases table
                
                // Clear the cart
                $delete_cart = mysqli_query($conn, "DELETE FROM `cart` WHERE user_id = '$user_id'");
                if (!$delete_cart) {
                    throw new Exception('Failed to clear cart: ' . mysqli_error($conn));
                }
                
                // Commit the transaction
                mysqli_commit($conn);
                $message[] = 'Order placed successfully!';
                // Don't redirect, stay on the same page to show success message
                
            } catch (Exception $e) {
                // Rollback the transaction in case of any error
                mysqli_rollback($conn);
                $message[] = 'Error: ' . $e->getMessage();
                // Set a flag to prevent further processing
                $error_occurred = true;
            }
         }
      }
   }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Checkout</title>

   <!-- Font Awesome CDN Link -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- Custom CSS File Link -->
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/checkout.css">
   <style>
   /* Checkout form enhancements */
   .checkout form#checkout-form {
       max-width: 700px;
       margin: 20px auto 40px;
       background: #fff;
       border-radius: 8px;
       padding: 20px;
       box-shadow: 0 4px 14px rgba(0,0,0,0.08);
    }
    .checkout form#checkout-form input[type="text"],
    .checkout form#checkout-form input[type="email"],
    .checkout form#checkout-form input[type="tel"] {
       width: 100%;
       padding: 12px 14px;
       border: 1px solid #e0e0e0;
       border-radius: 6px;
       margin: 8px 0 14px;
       font-size: 15px;
    }
    .checkout form#checkout-form input:focus {
       outline: none;
       border-color: #27ae60;
       box-shadow: 0 0 0 3px rgba(39,174,96,.15);
    }
    .checkout .btn {
       background: #27ae60;
       border-radius: 6px;
    }
    .checkout .btn:hover { background: #2ecc71; }

    /* Message styling */
    .order-success-message .message {
        margin: 10px auto;
        max-width: 900px;
        padding: 12px 16px;
        border-radius: 6px;
        border: 1px solid #e6e6e6;
        background: #fff;
    }
    .order-success-message .message.error { border-color: #ffd1d1; background: #fff5f5; }
    .order-success-message .message.success { border-color: #ccebd7; background: #f3fbf7; }
   /* Success message styling */
   .order-success-message {
       max-width: 1200px;
       margin: 20px auto;
       padding: 0 20px;
   }
   
   .order-success-message .message {
       background-color: #d4edda;
       color: #155724;
       border: 1px solid #c3e6cb;
       padding: 15px;
       margin-bottom: 20px;
       border-radius: 5px;
       text-align: center;
       font-size: 16px;
       box-shadow: 0 2px 5px rgba(0,0,0,0.1);
   }
   
   .order-success-message .message span {
       font-weight: 500;
   }
   
   /* Hide the checkout form if order was successful */
   .checkout-form {
       <?php if (isset($_POST['order_btn']) && !empty($isSuccess) && $isSuccess): ?>display: none;<?php endif; ?>
   }
   
   .order-success-actions {
       text-align: center;
       margin: 30px 0;
       padding: 20px;
       background: #f9f9f9;
       border-radius: 5px;
       max-width: 800px;
       margin: 30px auto;
   }
   
   .order-success-actions h3 {
       color: #27ae60;
       margin-bottom: 15px;
   }
   
   .order-success-actions p {
       margin-bottom: 20px;
       color: #555;
   }
   
   .action-buttons {
       margin-top: 20px;
   }
   
   .action-buttons .btn {
       display: inline-block;
       padding: 12px 30px;
       cursor: pointer;
       font-size: 16px;
       color: #fff;
       border: none;
       border-radius: 5px;
       text-transform: capitalize;
       margin: 0 10px;
       text-decoration: none;
       transition: all 0.3s ease;
   }
   
   .action-buttons .btn-primary {
       background: #27ae60;
   }
   
   .action-buttons .btn-primary:hover {
       background: #2ecc71;
   }
   
   .action-buttons .btn:last-child {
       background: #666;
   }
   
   .action-buttons .btn:last-child:hover {
       background: #777;
   }
   </style>
</head>
<body>

<?php include 'header.php'; ?>

<?php
// Initialize message array if not set
if (!isset($message)) {
    $message = [];
} elseif (is_string($message)) {
    $message = [$message];
}

// Display success/error messages if any
if (isset($_POST['order_btn']) && !empty($message)) {
    $isSuccess = false;
    
    echo '<div class="order-success-message">';
    foreach ((array)$message as $msg) {
        if (is_string($msg) && stripos($msg, 'successfully') !== false) {
            $isSuccess = true;
        }
        echo '<div class="message"><span>'.htmlspecialchars($msg).'</span></div>';
    }
    echo '</div>';
    
    // Show success actions if order was successful
    if ($isSuccess) {
        echo '<div class="order-success-actions">';
        echo '<h3>Thank you for your order!</h3>';
        echo '<p>Your order has been placed successfully. You will receive an order confirmation shortly.</p>';
        echo '<div class="action-buttons">';
        echo '<a href="home.php" class="btn btn-primary">Continue Shopping</a>';
        echo '<a href="orders.php" class="btn" style="background: #666;">View Orders</a>';
        echo '</div>';
        echo '</div>';
    }
}
?>

<div class="heading">
   <h3>Checkout</h3>
   <p><a href="home.php">Home</a> / Checkout</p>
</div>

<section class="display-order">
   <h3>Order Summary</h3>
   <div class="order-items">
      <?php
      $grand_total = 0;
      $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'") or die('query failed');
      if (mysqli_num_rows($select_cart) > 0) {
         while ($fetch_cart = mysqli_fetch_assoc($select_cart)) {
            $total_price = ($fetch_cart['price'] * $fetch_cart['quantity']);
            $grand_total += $total_price;
      ?>
            <div class="order-item">
               <div class="item-image">
                  <img src="uploaded_img/<?php echo $fetch_cart['image']; ?>" alt="<?php echo $fetch_cart['name']; ?>">
               </div>
               <div class="item-info">
                  <div class="item-name"><?php echo $fetch_cart['name']; ?></div>
                  <?php if(isset($fetch_cart['size'])): ?>
                  <div class="item-size">Size: <?php echo $fetch_cart['size']; ?></div>
                  <?php endif; ?>
                  <div class="item-details">
                     <span class="item-price">Rs<?php echo $fetch_cart['price']; ?>/-</span>
                     <span class="item-quantity">x <?php echo $fetch_cart['quantity']; ?></span>
                     <span class="item-total">Rs<?php echo $total_price; ?>/-</span>
                  </div>
               </div>
            </div>
      <?php
         }
      } else {
         echo '<p class="empty">Your cart is empty!</p>';
      }
      ?>
   </div>
   
   <div class="order-summary">
      <div class="summary-item">
         <span>Subtotal:</span>
         <span>Rs<?php echo $grand_total; ?>/-</span>
      </div>
      <div class="summary-item">
         <span>Shipping:</span>
         <span>Free</span>
      </div>
      <div class="summary-item grand-total">
         <span>Grand Total:</span>
         <span>Rs<?php echo $grand_total; ?>/-</span>
      </div>
   </div>
</section>

<section class="checkout">
   <form id="checkout-form" action="" method="post" novalidate>
      <input type="text" name="name" id="name" required placeholder="Enter your name" value="<?php echo htmlspecialchars($prefill_name); ?>">
      <input type="tel" name="number" id="number" required placeholder="Enter your phone number" pattern="^[0-9]{10}$" maxlength="10" value="<?php echo htmlspecialchars($prefill_number); ?>">
      <input type="email" name="email" id="email" required placeholder="Enter your email" value="<?php echo htmlspecialchars($prefill_email); ?>">
       <!-- Other form fields as needed -->
       <button type="submit" name="order_btn" class="btn">Place Order (COD)</button>
       <button type="button" id="khalti-button" class="btn" style="background:#5d2dfd; margin-left:8px;">Pay with Khalti</button>
  </form>
</section>

<?php include 'footer.php'; ?>

 <!-- Custom JS File Link -->
 <script src="js/script.js"></script>
 <script>
 // Submit form to initiate KPG-2 and redirect via JSON response
 (function(){
   var btn = document.getElementById('khalti-button');
   var form = document.getElementById('checkout-form');
   if (!btn || !form) return;
   btn.addEventListener('click', function(){
     // basic validation (client-side)
     var name = document.getElementById('name').value.trim();
     var number = document.getElementById('number').value.trim();
     var email = document.getElementById('email').value.trim();
     if (!name || !number || !email) { alert('Please fill name, phone, and email first.'); return; }
     if (number.length !== 10 || !/^\d{10}$/.test(number)) { alert('Phone number must be 10 digits.'); return; }
     var fd = new FormData(form);
     fd.append('khalti_epayment', '1');
     fetch('', { method: 'POST', body: fd })
       .then(function(res){ return res.json(); })
       .then(function(json){
         if (json && json.redirect) {
           window.location.href = json.redirect;
         } else {
           alert((json && json.message) ? json.message : 'Failed to initiate payment.');
         }
       })
       .catch(function(err){ alert('Network error: ' + err); });
   });
 })();
 </script>

<!-- jQuery (for autocomplete and UI helpers) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Toggle mobile menu
document.querySelector('.mobile-menu-btn')?.addEventListener('click', function() {
    document.querySelector('.nav-menu')?.classList.toggle('active');
});

// Toggle Cash on Delivery form
document.getElementById('cod-btn')?.addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('cod-form')?.classList.toggle('active');
});

//

// Autocomplete functionality with images
function showAutocompleteResults(data) {
    const resultsDiv = $("#autocompleteResults");
    resultsDiv.empty();
    
    if (Array.isArray(data) && data.length > 0) {
        data.forEach(function(item) {
            const imgSrc = item.image || 'img/placeholder.jpg';
            const div = $(`
                <div class="autocomplete-item">
                    <img src="${imgSrc}" alt="${item.name}" class="autocomplete-img">
                    <span>${item.name}</span>
                </div>
            `);
            div.on('click', function() {
                window.location.href = 'product.php?id=' + item.id;
            });
            resultsDiv.append(div);
        });
    }
}

// Autocomplete AJAX
$(document).on('input', '#searchInput', function() {
    const query = $(this).val();
    if (query && query.length > 0) {
        $.ajax({
            type: 'GET',
            url: 'search_autocomplete.php',
            data: { query: query },
            dataType: 'json',
            success: function(data) {
                console.log('Autocomplete response:', data);
                showAutocompleteResults(data);
            },
            error: function(xhr, status, error) {
                console.error('Autocomplete AJAX error:', xhr.status, error, xhr.responseText);
            }
        });
    } else {
        $("#autocompleteResults").empty();
    }
});

// Close autocomplete when clicking outside
$(document).on('click', function(e) {
    if (!$(e.target).closest('.search-bar').length) {
        $('#autocompleteResults').empty();
    }
});

// Submit search form when pressing enter
$(document).on('keypress', '#searchInput', function(e) {
    if (e.which == 13) { // Enter key
        $('#searchForm').submit();
    }
});

// Sorting functionality
$(document).on('change', '#sortSelect', function() {
    const selectedSort = $(this).val();
    const params = new URLSearchParams(window.location.search);
    params.set('sort', selectedSort);
    window.location.href = 'shop.php?' + params.toString();
});
</script>

 </body>
 </html>