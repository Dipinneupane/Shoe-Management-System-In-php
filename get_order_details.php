<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'config.php';

header('Content-Type: application/json');

// Check if order_id is provided
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

$order_id = (int)$_GET['order_id'];
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Debug logging (remove in production)
error_log("Fetching order details - Order ID: $order_id, User ID: $user_id");

// Fetch order details
$order_query = mysqli_query($conn, "SELECT * FROM `orders` WHERE id = '$order_id' AND user_id = '$user_id'");

if (mysqli_num_rows($order_query) === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

$order = mysqli_fetch_assoc($order_query);

// Parse products from the order
$products = [];
$product_entries = explode(',', $order['total_products']);
$sizes = [];

// Debug: Log the raw sizes data
error_log("Raw sizes data: " . print_r($order['sizes'], true));

// Get sizes if available
if (!empty($order['sizes']) && $order['sizes'] != '{}' && $order['sizes'] != '[]') {
    $sizes = json_decode($order['sizes'], true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($sizes)) {
        error_log("Error decoding sizes JSON: " . json_last_error_msg());
        $sizes = [];
    } else {
        error_log("Decoded sizes: " . print_r($sizes, true));
    }
} else {
    error_log("No sizes data found or empty");
}

error_log("Processing product entries: " . print_r($product_entries, true));

foreach ($product_entries as $entry) {
    $entry = trim($entry);
    if (preg_match('/^(.*) \((\d+)\)$/', $entry, $matches)) {
        $product_name = trim($matches[1]);
        $quantity = (int)$matches[2];
        
        error_log("Processing product: $product_name, Quantity: $quantity");
        
        // Get product details
        $product_query = mysqli_query($conn, "SELECT * FROM `products` WHERE name = '" . mysqli_real_escape_string($conn, $product_name) . "'");
        $product = $product_query ? mysqli_fetch_assoc($product_query) : null;
        
        // Find the size for this product
        $product_size = null;
        if (is_array($sizes)) {
            // Try exact match first
            if (isset($sizes[$product_name])) {
                $product_size = $sizes[$product_name];
            } else {
                // Try case-insensitive match
                foreach ($sizes as $key => $size) {
                    if (strtolower($key) === strtolower($product_name)) {
                        $product_size = $size;
                        break;
                    }
                }
            }
        }
        
        error_log("Product: $product_name, Found size: " . ($product_size ?? 'N/A'));
        
        if ($product) {
            $product_data = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'brand' => $product['brand'] ?? 'Unknown',
                'type' => $product['type'] ?? 'Unknown',
                'quantity' => $quantity,
                'size' => $product_size
            ];
            $products[] = $product_data;
            error_log("Added product with data: " . print_r($product_data, true));
        } else {
            // If product not found, include basic info
            $product_data = [
                'name' => $product_name,
                'price' => 0,
                'image' => '',
                'brand' => 'Unknown',
                'type' => 'Unknown',
                'quantity' => $quantity,
                'size' => $product_size
            ];
            $products[] = $product_data;
            error_log("Added unknown product with data: " . print_r($product_data, true));
        }
    } else {
        error_log("Could not parse product entry: $entry");
    }
}

// Add products to the order data
$order['products'] = $products;

// Debug: Log the final order data
error_log("Final order data: " . print_r(['success' => true, 'order' => $order], true));

// Return the response
$response = ['success' => true, 'order' => $order];
echo json_encode($response);
?>
