<?php
include 'config.php';

// Only start session if one doesn't already exist
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
   header('location:login.php');
}

if (isset($_POST['update_order'])) {
   $order_update_id = $_POST['order_id'];
   if (isset($_POST['update_payment'])) {
      $update_payment = $_POST['update_payment'];
      mysqli_query($conn, "UPDATE `orders` SET payment_status = '$update_payment' WHERE id = '$order_update_id'") or die('query failed');
      $message[] = 'Payment status has been updated!';
   } else {
      $message[] = 'Payment status not provided!';
   }
}

if (isset($_GET['delete'])) {
   $delete_id = $_GET['delete'];
   mysqli_query($conn, "DELETE FROM `orders` WHERE id = '$delete_id'") or die('query failed');
   header('location:admin_orders.php');
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

   <!-- Custom Admin CSS File Link -->
   <link rel="stylesheet" href="css/admin_style.css">
   <link rel="stylesheet" href="css/admin_orders.css">
   <style>
      .product-image-container {
         position: relative;
         display: inline-block;
         margin-right: 10px;
         margin-bottom: 10px;
      }
      .product-size {
         position: absolute;
         bottom: 0;
         left: 0;
         right: 0;
         background: rgba(0,0,0,0.7);
         color: #fff;
         font-size: 11px;
         text-align: center;
         padding: 2px 0;
      }
      .product-quantity {
         position: absolute;
         top: -8px;
         right: -8px;
         background: var(--red);
         color: #fff;
         border-radius: 50%;
         width: 20px;
         height: 20px;
         display: flex;
         align-items: center;
         justify-content: center;
         font-size: 11px;
         font-weight: bold;
      }
      .order-items-count {
         font-size: 12px;
         color: #666;
         margin-top: 3px;
      }
   </style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<section class="admin-section orders-section">
   <div class="section-header">
      <h1 class="title">Order Management</h1>
      <div class="section-actions">
         <div class="search-container">
            <input type="text" id="orderSearch" placeholder="Search orders..." onkeyup="searchOrders()">
            <i class="fas fa-search"></i>
         </div>
         <div class="filter-container">
            <select id="statusFilter" onchange="filterOrders()">
               <option value="all">All Orders</option>
               <option value="pending">Pending</option>
               <option value="completed">Completed</option>
            </select>
         </div>
      </div>
   </div>

   <div class="orders-container">
      <div class="orders-header">
         <div class="order-id">Order ID</div>
         <div class="order-products">Products</div>
         <div class="order-customer">Customer</div>
         <div class="order-amount">Amount</div>
         <div class="order-status">Status</div>
         <div class="order-actions">Actions</div>
      </div>

      <?php
      $select_orders = mysqli_query($conn, "SELECT * FROM `orders` ORDER BY id DESC") or die('query failed');
      if (mysqli_num_rows($select_orders) > 0) {
         while ($fetch_orders = mysqli_fetch_assoc($select_orders)) {
            $status_class = $fetch_orders['payment_status'] == 'completed' ? 'status-completed' : 'status-pending';
      ?>
            <div class="order-item" data-status="<?php echo $fetch_orders['payment_status']; ?>">
               <div class="order-id">
                  <div class="id-number">#<?php echo $fetch_orders['id']; ?></div>
                  <div class="order-date"><?php echo $fetch_orders['placed_on']; ?></div>
               </div>
               <div class="order-products">
                  <?php
                  // Parse the product string to extract product names and quantities
                  $products_str = $fetch_orders['total_products'];
                  $product_items = explode(', ', $products_str);
                  
                  // Get sizes from the order
                  $sizes = [];
                  if (!empty($fetch_orders['sizes']) && $fetch_orders['sizes'] != '{}') {
                     $sizes = json_decode($fetch_orders['sizes'], true);
                     if (json_last_error() !== JSON_ERROR_NONE || !is_array($sizes)) {
                        $sizes = [];
                     }
                  }
                  
                  // Display up to 3 product images with sizes
                  $count = 0;
                  echo '<div class="product-images">';
                  foreach($product_items as $item) {
                     // Extract product name and quantity info
                     if (preg_match('/(.*?)\s*\((\d+)\)/', $item, $matches)) {
                        $product_name = trim($matches[1]);
                        $quantity = $matches[2];
                        
                        // Get product details from database
                        $get_product = mysqli_query($conn, "SELECT * FROM `products` WHERE name = '$product_name' LIMIT 1");
                        
                        if(mysqli_num_rows($get_product) > 0) {
                           $product = mysqli_fetch_assoc($get_product);
                           echo '<div class="product-image-container">';
                           echo '<img src="uploaded_img/' . $product['image'] . '" alt="' . htmlspecialchars($product_name) . '">';
                           
                           // Display size if available for this product
                           $displayed_size = '';
                           if (isset($sizes[$product_name])) {
                              $displayed_size = $sizes[$product_name];
                           } elseif (isset($product['size'])) {
                              $displayed_size = $product['size'];
                           }
                           
                           if (!empty($displayed_size)) {
                              echo '<div class="product-size">Size: ' . htmlspecialchars($displayed_size) . '</div>';
                           }
                           
                           echo '<div class="product-quantity">x' . $quantity . '</div>';
                           echo '</div>';
                           $count++;
                        }
                     }
                     
                     if($count >= 3) break; // Show max 3 images
                  }
                  
                  // If there are more products than shown
                  $total_products = count($product_items);
                  if($total_products > 3) {
                     echo '<div class="more-products">+' . ($total_products - 3) . ' more</div>';
                  }
                  
                  echo '</div>';
                  ?>
               </div>
               <div class="order-customer">
                  <div class="customer-name"><?php echo $fetch_orders['name']; ?></div>
                  <div class="customer-contact"><?php echo $fetch_orders['email']; ?></div>
               </div>
               <div class="order-amount">
                  Rs<?php echo number_format($fetch_orders['total_price']); ?>/-
                  <div class="order-items-count">
                     <?php echo count($product_items); ?> item(s)
                  </div>
               </div>
               <div class="order-status">
                  <span class="status-badge <?php echo $status_class; ?>">
                     <?php echo ucfirst($fetch_orders['payment_status']); ?>
                  </span>
               </div>
               <div class="order-actions">
                  <form action="" method="post" class="status-form">
                     <input type="hidden" name="order_id" value="<?php echo $fetch_orders['id']; ?>">
                     <select name="update_payment" class="status-select">
                        <option value="" selected disabled>Change Status</option>
                        <option value="pending" <?php if($fetch_orders['payment_status'] == 'pending') echo 'selected'; ?>>Pending</option>
                        <option value="completed" <?php if($fetch_orders['payment_status'] == 'completed') echo 'selected'; ?>>Completed</option>
                     </select>
                     <button type="submit" name="update_order" class="action-btn update-btn">
                        <i class="fas fa-check-circle"></i> Save
                     </button>
                  </form>
                  
                  <a href="admin_orders.php?delete=<?php echo $fetch_orders['id']; ?>" onclick="return confirm('Are you sure you want to delete this order? This action cannot be undone.');" class="action-btn delete-btn">
                     <i class="fas fa-trash-alt"></i> Delete
                  </a>
               </div>
            </div>
      <?php
         }
      } else {
         echo '<div class="empty-state">
                  <div class="empty-icon"><i class="fas fa-shopping-cart"></i></div>
                  <h3>No Orders Found</h3>
                  <p>There are no orders placed yet.</p>
               </div>';
      }
      ?>
   </div>

   <!-- Order Details Modal -->
   <div id="orderDetailsModal" class="modal">
      <div class="modal-content">
         <div class="modal-header">
            <h2>Order Details</h2>
            <span class="close">&times;</span>
         </div>
         <div class="modal-body" id="orderDetailsContent">
            <!-- Order details will be loaded here via JavaScript -->
         </div>
      </div>
   </div>
</section>

<!-- Custom Admin JS File Link -->
<script src="js/admin_script.js"></script>

</body>
</html>