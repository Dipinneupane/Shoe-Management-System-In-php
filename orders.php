<?php
include 'config.php';

// Only start session if one doesn't already exist
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'];

if (!isset($user_id)) {
   header('location:login.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Orders</title>

   <!-- Font Awesome CDN Link -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- Custom CSS File Link -->
   <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'header.php'; ?>

<div class="heading">
   <h3>Your Orders</h3>
   <p><a href="home.php">Home</a> / Orders</p>
</div>

<section class="placed-orders">
   <h1 class="title">Your Order History</h1>

   <div class="box-container">
      <?php
      $order_query = mysqli_query($conn, "SELECT * FROM `orders` WHERE user_id = '$user_id' ORDER BY id DESC") or die('query failed');
      if (mysqli_num_rows($order_query) > 0) {
         while ($fetch_orders = mysqli_fetch_assoc($order_query)) {
            // Determine the payment status text
            $payment_status = ($fetch_orders['payment_status'] == 'completed') ? 'Completed' : 'Pending';
            $status_class = ($fetch_orders['payment_status'] == 'completed') ? 'status-completed' : 'status-pending';
      ?>
            <div class="order-box">
               <div class="order-header">
                  <div class="order-date">
                     <i class="fas fa-calendar-alt"></i> Ordered on: <?php echo $fetch_orders['placed_on']; ?>
                  </div>
                  <div class="order-id">
                     Order #<?php echo $fetch_orders['id']; ?>
                  </div>
                  <div class="payment-status <?php echo $status_class; ?>">
                     <i class="fas <?php echo ($fetch_orders['payment_status'] == 'completed') ? 'fa-check-circle' : 'fa-clock'; ?>"></i> 
                     <?php echo $payment_status; ?>
                  </div>
               </div>
               
               <div class="order-details">
                  <div class="order-customer-info">
                     <h4><i class="fas fa-user"></i> Customer Details</h4>
                     <p><strong>Name:</strong> <?php echo $fetch_orders['name']; ?></p>
                     <p><strong>Contact:</strong> <?php echo $fetch_orders['number']; ?></p>
                     <p><strong>Email:</strong> <?php echo $fetch_orders['email']; ?></p>
                  </div>
                  
                  <div class="order-payment-info">
                     <h4><i class="fas fa-credit-card"></i> Payment Information</h4>
                     <p><strong>Method:</strong> <?php echo $fetch_orders['method']; ?></p>
                     <p><strong>Total:</strong> <span class="price">Rs<?php echo $fetch_orders['total_price']; ?>/-</span></p>
                  </div>
               </div>
               
               <div class="order-products">
                  <h4><i class="fas fa-shopping-bag"></i> Products Ordered</h4>
                  <?php
                  // Get sizes from the order
                  $sizes = [];
                  if (!empty($fetch_orders['sizes']) && $fetch_orders['sizes'] != '{}' && $fetch_orders['sizes'] != '[]') {
                     $sizes = json_decode($fetch_orders['sizes'], true);
                     if (json_last_error() !== JSON_ERROR_NONE || !is_array($sizes)) {
                        $sizes = [];
                     }
                  }
                  
                  // Debug: Log the sizes data
                  error_log("Order #" . $fetch_orders['id'] . " - Raw sizes data: " . print_r($fetch_orders['sizes'], true));
                  error_log("Order #" . $fetch_orders['id'] . " - Decoded sizes: " . print_r($sizes, true));
                  
                  // Parse products (format: "ProductName (xQty), ...")
                  $product_entries = explode(',', $fetch_orders['total_products']);
                  
                  foreach($product_entries as $entry) {
                     $entry = trim($entry);
                     if (preg_match('/^(.*) \((\d+)\)$/', $entry, $matches)) {
                        $prod_name = trim($matches[1]);
                        $prod_qty = $matches[2];
                        $prod_query = mysqli_query($conn, "SELECT * FROM products WHERE name = '" . mysqli_real_escape_string($conn, $prod_name) . "' LIMIT 1");
                        $prod_data = ($prod_query && mysqli_num_rows($prod_query) > 0) ? mysqli_fetch_assoc($prod_query) : null;
                        
                        // Get size for this product
                        $product_size = 'N/A';
                        if (is_array($sizes)) {
                            // Try exact match first
                            if (isset($sizes[$prod_name])) {
                                $product_size = $sizes[$prod_name];
                            } else {
                                // Try case-insensitive match
                                foreach ($sizes as $key => $size) {
                                    if (strtolower($key) === strtolower($prod_name)) {
                                        $product_size = $size;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        // Debug log
                        error_log("Product: $prod_name, Size: $product_size");
                        
                        echo '<div class="order-product-detail">';
                        echo '<div class="product-main-info">';
                        echo '<span class="order-product-name"><strong>' . htmlspecialchars($prod_name) . '</strong> (x' . intval($prod_qty) . ')</span>';
                        
                        // Only show size if it's not N/A
                        if ($product_size !== 'N/A') {
                            echo '<span class="product-size-badge">Size: ' . htmlspecialchars($product_size) . '</span>';
                        }
                        
                        echo '</div>'; // Close product-main-info
                        
                        // Only show brand and type if we have the product data
                        if ($prod_data) {
                            echo '<div class="product-meta">';
                            if (!empty($prod_data['brand'])) {
                                echo '<span class="product-brand"><strong>Brand:</strong> ' . htmlspecialchars($prod_data['brand']) . '</span>';
                            }
                            if (!empty($prod_data['type'])) {
                                echo '<span class="product-type"><strong>Type:</strong> ' . htmlspecialchars($prod_data['type']) . '</span>';
                            }
                            echo '</div>'; // Close product-meta
                        }
                        echo '</div>'; // Close order-product-detail
                     } else {
                        // fallback for unexpected format
                        echo '<div class="order-product-detail">' . htmlspecialchars($entry) . '</div>';
                     }
                  }
                  ?>
               </div>
               
               <div class="order-actions">
                  <button class="btn-small view-details-btn" onclick="viewOrderDetails(<?php echo $fetch_orders['id']; ?>)">
                     <i class="fas fa-eye"></i> View Details
                  </button>
               </div>
            </div>
      <?php
         }
      } else {
         echo '<div class="empty-orders">
                  <i class="fas fa-shopping-bag fa-4x"></i>
                  <p>You haven\'t placed any orders yet!</p>
                  <a href="shop.php" class="btn">Start Shopping</a>
               </div>';
      }
      ?>
   </div>
</section>

<!-- Order Details Modal -->
<div id="orderDetailsModal" class="modal">
   <div class="modal-content">
      <div class="modal-header">
         <h2>Order #<span id="orderId"></span> Details</h2>
         <span class="close-modal">&times;</span>
      </div>
      <div class="modal-body">
         <div class="order-details-container">
            <div class="order-section">
               <h3><i class="fas fa-user"></i> Customer Information</h3>
               <div class="detail-row">
                  <span class="detail-label">Name:</span>
                  <span id="customerName" class="detail-value"></span>
               </div>
               <div class="detail-row">
                  <span class="detail-label">Contact:</span>
                  <span id="customerContact" class="detail-value"></span>
               </div>
               <div class="detail-row">
                  <span class="detail-label">Email:</span>
                  <span id="customerEmail" class="detail-value"></span>
               </div>
            </div>
            
            <div class="order-section">
               <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
               <div class="detail-row">
                  <span class="detail-label">Order Status:</span>
                  <span id="orderStatus" class="status-badge"></span>
               </div>
               <div class="detail-row">
                  <span class="detail-label">Payment Method:</span>
                  <span id="paymentMethod" class="detail-value"></span>
               </div>
               <div class="detail-row">
                  <span class="detail-label">Order Total:</span>
                  <span id="orderTotal" class="detail-value price"></span>
               </div>
               <div class="detail-row">
                  <span class="detail-label">Order Date:</span>
                  <span id="orderDate" class="detail-value"></span>
               </div>
            </div>
            
            <div class="order-section">
               <h3><i class="fas fa-box-open"></i> Order Items</h3>
               <div id="orderItems" class="order-items-list">
                  <!-- Order items will be populated here -->
               </div>
            </div>
         </div>
      </div>
   </div>
</div>

<style>
/* Order Product Styles */
.order-product-detail {
    margin-bottom: 15px;
    padding: 15px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.product-main-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.order-product-name {
    font-size: 16px;
    color: #333;
    font-weight: 600;
}

.product-meta {
    display: flex;
    gap: 15px;
    font-size: 14px;
    color: #666;
    margin-top: 5px;
}

.product-meta span {
    display: inline-flex;
    align-items: center;
}

.product-meta strong {
    margin-right: 5px;
    color: #444;
}

.product-size-badge {
    background: #e0f2fe;
    color: #0369a1;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 500;
    margin-left: 10px;
}

/* Loading and Error States */
.loading, .error, .no-items {
    text-align: center;
    padding: 20px;
    font-size: 16px;
    color: #666;
}

.loading i {
    margin-right: 8px;
    color: #3b82f6;
}

.error {
    color: #dc2626;
}

/* Modal Styles */
.modal {
   display: none;
   position: fixed;
   z-index: 1000;
   left: 0;
   top: 0;
   width: 100%;
   height: 100%;
   background-color: rgba(0,0,0,0.7);
   overflow: auto;
}

.modal-content {
   background-color: #fff;
   margin: 5% auto;
   padding: 30px;
   border-radius: 8px;
   width: 80%;
   max-width: 800px;
   position: relative;
   box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.modal-header {
   display: flex;
   justify-content: space-between;
   align-items: center;
   margin-bottom: 20px;
   padding-bottom: 15px;
   border-bottom: 1px solid #eee;
}

.modal-header h2 {
   margin: 0;
   color: #333;
   font-size: 24px;
}

.close-modal {
   font-size: 28px;
   font-weight: bold;
   cursor: pointer;
   color: #777;
}

.close-modal:hover {
   color: #333;
}

/* Order Details Styles */
.order-details-container {
   margin-top: 20px;
}

.order-section {
   margin-bottom: 30px;
   background: #f9f9f9;
   padding: 20px;
   border-radius: 8px;
}

.order-section h3 {
   margin-top: 0;
   color: #444;
   font-size: 18px;
   padding-bottom: 10px;
   border-bottom: 1px solid #eee;
   margin-bottom: 15px;
}

.detail-row {
   display: flex;
   margin-bottom: 10px;
   line-height: 1.5;
}

.detail-label {
   font-weight: 600;
   width: 150px;
   color: #666;
}

.detail-value {
   flex: 1;
   color: #333;
}

.status-badge {
   display: inline-block;
   padding: 4px 10px;
   border-radius: 12px;
   font-size: 12px;
   font-weight: 600;
   text-transform: capitalize;
}

.status-pending {
   background-color: #fff3cd;
   color: #856404;
}

.status-completed {
   background-color: #d4edda;
   color: #155724;
}

.order-items-list {
   margin-top: 15px;
}

.order-item-detail {
   display: flex;
   padding: 15px 0;
   border-bottom: 1px solid #eee;
   align-items: center;
}

.order-item-detail:last-child {
   border-bottom: none;
}

.item-image {
   width: 80px;
   height: 80px;
   object-fit: cover;
   border-radius: 4px;
   margin-right: 15px;
}

.item-details {
   flex: 1;
}

.item-name {
   font-weight: 600;
   margin-bottom: 5px;
   color: #333;
}

.item-meta {
   font-size: 13px;
   color: #666;
   margin-bottom: 5px;
}

.item-price {
   font-weight: 600;
   color: #e74c3c;
}

.quantity-badge {
   display: inline-block;
   background: #f0f0f0;
   padding: 2px 8px;
   border-radius: 10px;
   font-size: 12px;
   margin-left: 5px;
   color: #555;
}
</style>

<script>
// Get the modal
var modal = document.getElementById('orderDetailsModal');
var closeBtn = document.querySelector('.close-modal');

// When the user clicks on (x), close the modal
closeBtn.onclick = function() {
   modal.style.display = 'none';
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
   if (event.target == modal) {
      modal.style.display = 'none';
   }
}

function viewOrderDetails(orderId) {
   // Show loading state
   const orderItemsContainer = document.getElementById('orderItems');
   orderItemsContainer.innerHTML = `
      <div class="loading">
         <i class="fas fa-spinner fa-spin"></i> Loading order details...
      </div>`;
   
   // Show the modal
   const modal = document.getElementById('orderDetailsModal');
   modal.style.display = 'block';
   
   // Set order ID in the modal header immediately
   document.getElementById('orderId').textContent = orderId;
   
   console.log('Fetching order details for order ID:', orderId);
   
   // Fetch order details via AJAX
   fetch('get_order_details.php?order_id=' + orderId, {
      credentials: 'same-origin' // Include cookies in the request
   })
   .then(response => {
      console.log('Response status:', response.status);
      if (!response.ok) {
         throw new Error('Network response was not ok');
      }
      return response.json();
   })
   .then(data => {
      console.log('Response data:', data);
      
      if (data.success && data.order) {
         const order = data.order;
         
         // Update customer information
         document.getElementById('customerName').textContent = order.name || 'N/A';
         document.getElementById('customerContact').textContent = order.number || 'N/A';
         document.getElementById('customerEmail').textContent = order.email || 'N/A';
         
         // Update payment information
         const statusText = order.payment_status ? 
            order.payment_status.charAt(0).toUpperCase() + order.payment_status.slice(1) : 
            'Pending';
         
         document.getElementById('orderStatus').textContent = statusText;
         document.getElementById('orderStatus').className = 'status-badge status-' + (order.payment_status || 'pending');
         document.getElementById('paymentMethod').textContent = order.method || 'N/A';
         document.getElementById('orderTotal').textContent = 'Rs' + (order.total_price || '0') + '/-';
         document.getElementById('orderDate').textContent = order.placed_on || 'N/A';
         
         // Update order items
         orderItemsContainer.innerHTML = '';
         
         if (order.products && order.products.length > 0) {
            order.products.forEach(item => {
               console.log('Processing product:', item);
               
               const itemElement = document.createElement('div');
               itemElement.className = 'order-item-detail';
               
               // Handle missing image
               const imageSrc = item.image ? 'uploaded_img/' + item.image : 'images/default-product.jpg';
               const imageAlt = item.name || 'Product image';
               
               // Format price with 2 decimal places
               const price = parseFloat(item.price || 0).toFixed(2);
               const subtotal = (parseFloat(item.price || 0) * parseInt(item.quantity || 1)).toFixed(2);
               
               itemElement.innerHTML = `
                  <img src="${imageSrc}" alt="${imageAlt}" class="item-image" onerror="this.src='images/default-product.jpg'">
                  <div class="item-details">
                     <div class="item-name">${item.name || 'Unknown Product'}</div>
                     <div class="item-meta">
                        <span>${item.brand || 'No Brand'}</span> • 
                        <span>${item.type || 'No Type'}</span>
                        ${item.size ? '• <span class="item-size">Size: ' + item.size + '</span>' : ''}
                     </div>
                     <div class="item-price">
                        Rs${price} <span class="quantity-badge">x${item.quantity || 1}</span>
                     </div>
                  </div>
                  <div class="item-subtotal">
                     Rs${subtotal}
                  </div>
               `;
               orderItemsContainer.appendChild(itemElement);
            });
         } else {
            orderItemsContainer.innerHTML = '<div class="no-items">No products found in this order.</div>';
         }
      } else {
         const errorMsg = data.message || 'Error loading order details. Please try again.';
         orderItemsContainer.innerHTML = `<div class="error">${errorMsg}</div>`;
      }
   })
   .catch(error => {
      console.error('Error:', error);
      orderItemsContainer.innerHTML = `
         <div class="error">
            <p>An error occurred while loading order details.</p>
            <p>${error.message || 'Please try again later.'}</p>
         </div>`;
   });
}
</script>

<?php include 'footer.php'; ?>

<!-- Custom JS File Link -->
<script src="js/script.js"></script>

</body>
</html>