<?php
include 'config.php';
include 'reviews.php';
include 'recommendations.php';


// Only start session if one doesn't already exist
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simple debug logger to avoid syntax issues with closures
if (!function_exists('ac_debug_log')) {
   function ac_debug_log($msg) {
      $file = __DIR__ . DIRECTORY_SEPARATOR . 'add_to_cart_debug.log';
      @file_put_contents($file, '['.date('Y-m-d H:i:s').'] '.$msg.PHP_EOL, FILE_APPEND);
   }
}

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

if(isset($_GET['id'])){
   $product_id = $_GET['id'];
} else {
   header('location:shop.php');
   exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_to_cart']) || isset($_POST['product_name']))){
   // Debug log
   ac_debug_log('HIT add_to_cart with POST=' . json_encode($_POST));
   if(!isset($_SESSION['user_id'])){
      header('location:login.php');
      exit();
   }

   $product_name = $_POST['product_name'];
   $product_price = (float)$_POST['product_price'];
   $product_image = $_POST['product_image'];
   $product_quantity = (int)$_POST['product_quantity'];
   $product_size = isset($_POST['product_size']) ? $_POST['product_size'] : '';

   // Server-side sanity checks
   if ($product_quantity < 1) { $product_quantity = 1; }

   // Ensure cart table has `size` column (runtime migration for compatibility)
   $check_cart_size = mysqli_query($conn, "SHOW COLUMNS FROM `cart` LIKE 'size'");
   if ($check_cart_size && mysqli_num_rows($check_cart_size) == 0) {
      // Add size column after quantity
      ac_debug_log('Applying ALTER TABLE cart ADD size');
      mysqli_query($conn, "ALTER TABLE `cart` ADD `size` VARCHAR(50) NULL AFTER `quantity`");
   }

   // Insert using prepared statement (with size)
   $insert_ok = false;
   $stmt = mysqli_prepare($conn, "INSERT INTO `cart` (user_id, name, price, quantity, size, image) VALUES (?, ?, ?, ?, ?, ?)");
   if ($stmt) {
      mysqli_stmt_bind_param($stmt, 'isdiss', $user_id, $product_name, $product_price, $product_quantity, $product_size, $product_image);
      if (mysqli_stmt_execute($stmt)) {
         $insert_ok = true;
         ac_debug_log(sprintf('INSERT with size OK user_id=%d name=%s qty=%d size=%s', $user_id, $product_name, $product_quantity, (string)$product_size));
      } else {
         $err = mysqli_stmt_error($stmt);
         ac_debug_log('INSERT with size FAILED: ' . $err);
      }
      mysqli_stmt_close($stmt);
   } else {
      $err = mysqli_error($conn);
      ac_debug_log('Prepare with size FAILED: ' . $err);
   }

   // Fallback: insert without size column if needed
   if (!$insert_ok) {
      $stmt2 = mysqli_prepare($conn, "INSERT INTO `cart` (user_id, name, price, quantity, image) VALUES (?, ?, ?, ?, ?)");
      if ($stmt2) {
         mysqli_stmt_bind_param($stmt2, 'isdis', $user_id, $product_name, $product_price, $product_quantity, $product_image);
         if (mysqli_stmt_execute($stmt2)) {
            $insert_ok = true;
            ac_debug_log(sprintf('INSERT without size OK user_id=%d name=%s qty=%d', $user_id, $product_name, $product_quantity));
         } else {
            $err2 = mysqli_stmt_error($stmt2);
            ac_debug_log('INSERT without size FAILED: ' . $err2);
         }
         mysqli_stmt_close($stmt2);
      }
   }

   if ($insert_ok) {
      ac_debug_log('REDIRECT to cart.php');
      header('Location: cart.php');
      exit();
   } else {
      $message[] = 'Failed to add to cart.' . (isset($err) ? ' Err: '.htmlspecialchars($err) : '') . (isset($err2) ? ' Fallback err: '.htmlspecialchars($err2) : '');
      ac_debug_log('FINAL FAIL add_to_cart');
   }
}

// Get product details
$select_product = mysqli_query($conn, "SELECT * FROM `products` WHERE id = '$product_id'") or die('query failed');
if(mysqli_num_rows($select_product) <= 0){
   header('location:shop.php');
   exit();
}
$fetch_product = mysqli_fetch_assoc($select_product);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?php echo $fetch_product['name']; ?> | Shoes</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/product.css">
   <link rel="stylesheet" href="css/notifications.css">
   <link rel="stylesheet" href="css/product-rating.css">

</head>
<body>
   
<?php include 'header.php'; ?>


<?php
// Display success/error messages if any
if (isset($message) && is_array($message) && count($message) > 0) {
   echo '<div class="message-container" style="max-width:1200px;margin:15px auto;">';
   foreach ($message as $msg) {
      echo '<div class="message" style="background:#f1f8e9;border:1px solid #c5e1a5;color:#33691e;padding:10px 14px;border-radius:4px;margin-bottom:8px;">'
         . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') .
      '</div>';
   }
   echo '</div>';
}
?>


<section class="product-details">
   <div class="product-container">
      <div class="product-image-container">
         <img src="uploaded_img/<?php echo $fetch_product['image']; ?>" alt="<?php echo $fetch_product['name']; ?>" class="product-image">
      </div>
      
      <div class="product-info">
         <h1 class="product-title"><?php echo $fetch_product['name']; ?></h1>
         
         <div class="product-rating">
            <?php 
               $avg_rating = getAverageRating($product_id);
               $rating_count = getRatingCount($product_id);
               $avg_rating_rounded = round($avg_rating * 2) / 2; // Round to nearest 0.5
            ?>
            <div class="rating-display" data-rating="<?php echo $avg_rating; ?>">
               <div class="rating-stars">
                  <input type="radio" id="rating-5" name="rating-display" value="5" <?php echo $avg_rating_rounded == 5 ? 'checked' : ''; ?> disabled>
                  <label for="rating-5" title="5 stars"><i class="fas fa-star"></i></label>
                  
                  <input type="radio" id="rating-4" name="rating-display" value="4" <?php echo $avg_rating_rounded == 4 ? 'checked' : ''; ?> disabled>
                  <label for="rating-4" title="4 stars"><i class="fas fa-star"></i></label>
                  
                  <input type="radio" id="rating-3" name="rating-display" value="3" <?php echo $avg_rating_rounded == 3 ? 'checked' : ''; ?> disabled>
                  <label for="rating-3" title="3 stars"><i class="fas fa-star"></i></label>
                  
                  <input type="radio" id="rating-2" name="rating-display" value="2" <?php echo $avg_rating_rounded == 2 ? 'checked' : ''; ?> disabled>
                  <label for="rating-2" title="2 stars"><i class="fas fa-star"></i></label>
                  
                  <input type="radio" id="rating-1" name="rating-display" value="1" <?php echo $avg_rating_rounded == 1 ? 'checked' : ''; ?> disabled>
                  <label for="rating-1" title="1 star"><i class="fas fa-star"></i></label>
               </div>
               <span class="rating-value"><?php echo number_format($avg_rating, 1); ?></span>
               <span class="rating-count">(<?php echo $rating_count; ?> reviews)</span>
            </div>
         </div>
         
         <div class="product-price">
            <span class="price">Rs<?php echo $fetch_product['price']; ?>/-</span>
         </div>
         
         <?php if(isset($fetch_product['description']) && !empty($fetch_product['description'])): ?>
         <div class="product-description">
            <h3>Description</h3>
            <p><?php echo $fetch_product['description']; ?></p>
         </div>
         <?php endif; ?>
         
         <form action="product.php?id=<?php echo urlencode($product_id); ?>" method="post" class="add-to-cart-form" id="addToCartForm" aria-label="Add this product to your cart">
             <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
             <input type="hidden" name="product_name" value="<?php echo $fetch_product['name']; ?>">
             <input type="hidden" name="product_price" value="<?php echo $fetch_product['price']; ?>">
             <input type="hidden" name="product_image" value="<?php echo $fetch_product['image']; ?>">
            
            <?php
            // Get sizes from the product's sizes field (comma-separated)
            $sizes = [];
            if (!empty($fetch_product['sizes'])) {
                $sizes = array_map('trim', explode(',', $fetch_product['sizes']));
                $sizes = array_filter($sizes); // Remove any empty values
                sort($sizes, SORT_NUMERIC); // Sort sizes numerically
            }
            
            if (!empty($sizes)): ?>
            <div class="product-size-container">
               <h3>Select Size</h3>
               <div class="size-selector">
                  <?php foreach($sizes as $size): ?>
                     <div class="size-option">
                        <input type="radio" id="size-<?php echo htmlspecialchars($size); ?>" 
                               name="product_size" 
                               value="<?php echo htmlspecialchars($size); ?>" 
                               required>
                        <label for="size-<?php echo htmlspecialchars($size); ?>">
                           <?php echo htmlspecialchars($size); ?>
                        </label>
                     </div>
                  <?php endforeach; ?>
               </div>
            </div>
            <?php else: ?>
               <div class="no-sizes-message">
                  <p>No size information available for this product.</p>
               </div>
            <?php endif; ?>
            
            <div class="product-quantity">
               <h3>Quantity</h3>
               <div class="quantity-selector">
                  <button type="button" class="quantity-btn minus-btn"><i class="fas fa-minus"></i></button>
                  <input type="number" name="product_quantity" class="quantity-input" value="1" min="1" 
                         max="<?php echo $fetch_product['quantity']; ?>" 
                         data-max-quantity="<?php echo $fetch_product['quantity']; ?>">
                  <button type="button" class="quantity-btn plus-btn"><i class="fas fa-plus"></i></button>
               </div>
               <div class="stock-availability">
                  <?php if($fetch_product['quantity'] > 0): ?>
                     <span class="in-stock"><?php echo $fetch_product['quantity']; ?> available in stock</span>
                  <?php else: ?>
                     <span class="out-of-stock">Out of stock</span>
                  <?php endif; ?>
               </div>
            </div>
            
            <style>
               .stock-availability {
                  margin-top: 5px;
                  font-size: 0.9em;
               }
               .in-stock {
                  color: #28a745;
               }
               .out-of-stock {
                  color: #dc3545;
                  font-weight: bold;
               }
            </style>
            
            <div class="product-actions">
               <input type="hidden" name="add_to_cart" value="1">
               <input 
                  type="submit" 
                  name="add_to_cart_btn" 
                  value="add to cart" 
                  class="btn"
                  aria-label="Add <?php echo htmlspecialchars($fetch_product['name']); ?> to cart"
                  <?php echo ($fetch_product['quantity'] <= 0) ? 'disabled aria-disabled="true"' : '';?>
               >
            </div>
            <div class="sr-status" aria-live="polite" aria-atomic="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">Status messages will appear here.</div>
          </form>
      </div>
   </div>
   
   <div class="product-tabs">
      <div class="tabs-content">
         <div class="tab-panel active" id="reviews-panel">
            <?php 
               if(isset($_SESSION['user_id'])){
                  displayReviewForm($product_id);
               } else {
                  echo '<div class="login-notice">Please <a href="login.php">login</a> to write a review.</div>';
               }
               
               displayReviews($product_id);
            ?>
         </div>
         

      </div>
   </div>
</section>

<?php 
// Content-Based Recommendations for the viewed product
if (isset($product_id) && is_numeric($product_id)) {
    $cb_recs = getContentBasedRecommendations($conn, (int)$product_id, 8);
    if (!empty($cb_recs)) {
        echo '<section class="products">';
        echo '<h1 class="title">You May Also <span>Like</span></h1>';
        echo '<div class="box-container">';
        // Build a quantity map for recommended product IDs
        $qtyMap = [];
        $recIds = [];
        foreach ($cb_recs as $rp) { $recIds[] = (int)$rp['id']; }
        if (!empty($recIds)) {
            $idList = implode(',', array_unique($recIds));
            $qres = mysqli_query($conn, "SELECT id, quantity FROM `products` WHERE id IN ($idList)");
            if ($qres) {
                while ($row = mysqli_fetch_assoc($qres)) {
                    $qtyMap[(int)$row['id']] = (int)$row['quantity'];
                }
            }
        }
        foreach ($cb_recs as $p) {
            echo '<div class="box">';
            echo '<div class="image-container">';
            echo '<a href="product.php?id=' . (int)$p['id'] . '">';
            echo '<img src="uploaded_img/' . htmlspecialchars($p['image']) . '" alt="' . htmlspecialchars($p['name']) . '">';
            echo '</a>';
            echo '</div>';
            echo '<div class="meta">';
            // Rating row above name (user-friendly design)
            if (isset($p['avg_rating'])) {
                $avg = round((float)$p['avg_rating'], 1);
                $stars = (int)round((float)$p['avg_rating']);
                $count = isset($p['rating_count']) ? (int)$p['rating_count'] : null;
                echo '<div class="rating" aria-label="Rated ' . htmlspecialchars(number_format($avg,1)) . ' out of 5">'
                   . '<span class="stars">' . str_repeat('★', $stars) . str_repeat('☆', 5 - $stars) . '</span>'
                   . '<span class="value">' . htmlspecialchars(number_format($avg,1)) . '</span>'
                   . ($count !== null ? '<span class="count">(' . $count . ')</span>' : '')
                   . '</div>';
            } else {
                echo '<div class="rating no-rating" aria-label="No ratings yet">'
                   . '<span class="stars">' . str_repeat('☆', 5) . '</span>'
                   . '<span class="count">No ratings yet</span>'
                   . '</div>';
            }
            echo '<div class="name">' . htmlspecialchars($p['name']) . '</div>';
            echo '<div class="price">Rs' . number_format((float)$p['price'], 2) . '/-</div>';
            $pid = (int)$p['id'];
            $q = isset($qtyMap[$pid]) ? (int)$qtyMap[$pid] : (isset($p['quantity']) ? (int)$p['quantity'] : 0);
            if ($q > 0) {
                echo '<div class="stock in-stock">' . $q . ' in stock</div>';
            } else {
                echo '<div class="stock out-of-stock">Out of stock</div>';
            }
            echo '</div>'; // .meta
            echo '</div>';
        }
        echo '</div>';
        echo '</section>';
    }
}
?>

<?php include 'footer.php'; ?>

<script src="js/script.js"></script>
<script src="js/product.js"></script>

<style>
/* Ensure recommendation/product sections render as a responsive grid */
.products .box-container {
   display: grid;
   grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
   gap: 20px;
   align-items: stretch;
}

/* Normalize card layout */
.products .box {
   display: flex;
   flex-direction: column;
   gap: 8px;
   align-items: stretch;
   position: static;
   overflow: hidden;
}

/* Prevent image-induced overlap by constraining images */
.products .box img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    display: block;
}

.products .box a { display: block; }
.products .box::after { content: ""; display: block; clear: both; }
.products .box .name {
    display: block;
    line-height: 1.3;
}

/* Rating overlay standardized via css/style.css; remove local overrides */

/* Space out price and stock labels to avoid overlap */
.products .box .price {
   margin-top: 4px;
   line-height: 1.3;
   display: block;
   position: static !important;
   float: none !important;
   clear: both;
   width: 100%;
}

.products .box .stock {
   display: block;
   margin-top: 0;
   font-size: 0.95rem;
   font-weight: 600;
   position: static !important;
   float: none !important;
   clear: both;
   width: 100%;
}

.products .box .stock.in-stock { color: #2e7d32; }
.products .box .stock.out-of-stock { color: #c62828; }

/* Recommended Products Styles */
.recommended-products {
   margin-top: 40px;
   padding: 20px 0;
   background: #f9f9f9;
   border-radius: 8px;
}

.recommended-products h3 {
   text-align: center;
   margin-bottom: 20px;
   color: #333;
   font-size: 24px;
   position: relative;
   padding-bottom: 10px;
}

.recommended-products h3:after {
   content: '';
   position: absolute;
   bottom: 0;
   left: 50%;
   transform: translateX(-50%);
   width: 80px;
   height: 3px;
   background: #ff6b6b;
}

.products-container {
   display: flex;
   flex-wrap: wrap;
   justify-content: center;
   gap: 20px;
   padding: 0 20px;
}

.product-card {
   background: white;
   border-radius: 8px;
   overflow: hidden;
   box-shadow: 0 2px 10px rgba(0,0,0,0.1);
   transition: transform 0.3s ease, box-shadow 0.3s ease;
   width: 220px;
   margin-bottom: 20px;
}

.product-card:hover {
   transform: translateY(-5px);
   box-shadow: 0 5px 15px rgba(0,0,0,0.15);
}

.product-card img {
   width: 100%;
   height: 200px;
   object-fit: cover;
   border-bottom: 1px solid #eee;
}

.product-card h4 {
   padding: 12px 15px 5px;
   margin: 0;
   font-size: 15px;
   color: #333;
   white-space: nowrap;
   overflow: hidden;
   text-overflow: ellipsis;
}

.product-card .price {
   padding: 0 15px 10px;
   color: #ff6b6b;
   font-weight: bold;
   font-size: 16px;
}

.product-card .rating {
   padding: 0 15px 15px;
   color: #ffc107;
   font-size: 14px;
}

.product-card a {
   text-decoration: none;
   color: inherit;
}

/* Responsive adjustments */
@media (max-width: 768px) {
   .products-container {
      gap: 15px;
      padding: 0 10px;
   }
   
   .product-card {
      width: calc(50% - 20px);
   }
}

@media (max-width: 480px) {
   .product-card {
      width: 100%;
   }
}
</style>

<script>
   document.addEventListener('DOMContentLoaded', function() {
      const quantityInput = document.querySelector('.quantity-input');
      const minusBtn = document.querySelector('.minus-btn');
      const plusBtn = document.querySelector('.plus-btn');
      const maxQuantity = parseInt(quantityInput.dataset.maxQuantity);
      const addToCartForm = document.getElementById('addToCartForm');
      const addToCartBtn  = document.querySelector('input[name="add_to_cart_btn"], input[name="add_to_cart"]') || document.querySelector('.add-to-cart-form input[type="submit"]');
      const statusRegion  = document.querySelector('.sr-status');
      
      // Update quantity when clicking minus button
      minusBtn.addEventListener('click', function() {
         let currentValue = parseInt(quantityInput.value) || 1;
         if (currentValue > 1) {
            quantityInput.value = currentValue - 1;
         }
      });
      
      // Update quantity when clicking plus button
      plusBtn.addEventListener('click', function() {
         let currentValue = parseInt(quantityInput.value) || 1;
         if (currentValue < maxQuantity) {
            quantityInput.value = currentValue + 1;
         } else {
            // Show a message when trying to exceed max quantity
            alert('You cannot add more than ' + maxQuantity + ' items to your cart.');
         }
      });
      
      // Validate input on change
      quantityInput.addEventListener('change', function() {
         let value = parseInt(this.value) || 1;
         
         if (value < 1) {
            this.value = 1;
         } else if (value > maxQuantity) {
            this.value = maxQuantity;
            alert('You cannot add more than ' + maxQuantity + ' items to your cart.');
         }
      });
      
      // Enhance Add to Cart UX: validate, prevent double submit, add feedback
      if (addToCartForm) {
         addToCartForm.addEventListener('submit', function(e) {
            // out of stock guard
            if (maxQuantity <= 0) {
               e.preventDefault();
               alert('This product is currently out of stock.');
               return false;
            }

            // size required guard (if size inputs exist)
            const sizeInputs = addToCartForm.querySelectorAll('input[name="product_size"]');
            if (sizeInputs && sizeInputs.length > 0) {
               const anyChecked = Array.from(sizeInputs).some(r => r.checked);
               if (!anyChecked) {
                  e.preventDefault();
                  alert('Please select a size before adding to cart.');
                  sizeInputs[0].focus();
                  return false;
               }
            }

            // quantity guard
            const selectedQuantity = parseInt(quantityInput.value) || 1;
            if (selectedQuantity > maxQuantity) {
               e.preventDefault();
               alert('The requested quantity exceeds available stock.');
               return false;
            }

            // disable button to prevent double submit and provide feedback
            if (addToCartBtn) {
               addToCartBtn.disabled = true;
               addToCartBtn.classList.add('loading');
               addToCartBtn.value = 'adding...';
               if (statusRegion) statusRegion.textContent = 'Adding item to your cart';
            }
            return true;
         });
      }
   });
</script>
   
</body>
</html>
