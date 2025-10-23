<?php
include 'config.php';

// Only start session if one doesn't already exist
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
   header('location:login.php');
   exit();
}

$user_id = $_SESSION['user_id'];

// Handle review submission
if(isset($_POST['submit_review'])){
   $product_id = $_POST['product_id'];
   $rating = $_POST['rating'];
   $review_text = mysqli_real_escape_string($conn, $_POST['review_text']);
   
   // Check if user has already reviewed this product
   $check_review = mysqli_query($conn, "SELECT * FROM `reviews` WHERE user_id = '$user_id' AND product_id = '$product_id'") or die('query failed');
   
   if(mysqli_num_rows($check_review) > 0){
      // Update existing review - automatically approved
      mysqli_query($conn, "UPDATE `reviews` SET rating = '$rating', review_text = '$review_text', status = 'approved', created_at = CURRENT_TIMESTAMP WHERE user_id = '$user_id' AND product_id = '$product_id'") or die('query failed');
      $message[] = 'Your review has been updated successfully!';
   } else {
      // Add new review - automatically approved
      mysqli_query($conn, "INSERT INTO `reviews` (product_id, user_id, rating, review_text, status) VALUES ('$product_id', '$user_id', '$rating', '$review_text', 'approved')") or die('query failed');
      $message[] = 'Thank you for your review! It is now visible to other users.';
   }
   
   // Redirect back to product page
   header('location:product.php?id='.$product_id);
   exit();
}

// Get reviews for a specific product
function getProductReviews($product_id) {
   global $conn;
   $reviews = [];
   
   $select_reviews = mysqli_query($conn, "SELECT r.*, u.name as user_name FROM `reviews` r 
                                         JOIN `users` u ON r.user_id = u.id 
                                         WHERE r.product_id = '$product_id' 
                                         ORDER BY r.created_at DESC") or die('query failed');
   
   if(mysqli_num_rows($select_reviews) > 0){
      while($fetch_review = mysqli_fetch_assoc($select_reviews)){
         $reviews[] = $fetch_review;
      }
   }
   
   return $reviews;
}

// Get average rating for a product
function getAverageRating($product_id) {
   global $conn;
   
   $select_avg = mysqli_query($conn, "SELECT AVG(rating) as average_rating FROM `reviews` WHERE product_id = '$product_id' AND status = 'approved'") or die('query failed');
   $fetch_avg = mysqli_fetch_assoc($select_avg);
   
   return $fetch_avg['average_rating'] ? round($fetch_avg['average_rating'], 1) : 0;
}

// Get rating count for a product
function getRatingCount($product_id) {
   global $conn;
   
   $select_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM `reviews` WHERE product_id = '$product_id' AND status = 'approved'") or die('query failed');
   $fetch_count = mysqli_fetch_assoc($select_count);
   
   return $fetch_count['count'];
}

// Check if user has purchased the product
function hasUserPurchasedProduct($user_id, $product_id) {
   global $conn;
   
   $product_name = '';
   $select_product = mysqli_query($conn, "SELECT name FROM `products` WHERE id = '$product_id'") or die('query failed');
   if(mysqli_num_rows($select_product) > 0){
      $fetch_product = mysqli_fetch_assoc($select_product);
      $product_name = $fetch_product['name'];
   }
   
   $select_orders = mysqli_query($conn, "SELECT * FROM `orders` WHERE user_id = '$user_id' AND total_products LIKE '%$product_name%'") or die('query failed');
   
   return mysqli_num_rows($select_orders) > 0;
}

// Display review form
function displayReviewForm($product_id) {
   global $user_id, $conn;
   
   // Check if user has already reviewed this product
   $check_review = mysqli_query($conn, "SELECT * FROM `reviews` WHERE user_id = '$user_id' AND product_id = '$product_id'") or die('query failed');
   $existing_review = mysqli_fetch_assoc($check_review);
   
   $form_title = mysqli_num_rows($check_review) > 0 ? 'Update Your Review' : 'Write a Review';
   $submit_text = mysqli_num_rows($check_review) > 0 ? 'Update Review' : 'Submit Review';
   $existing_rating = mysqli_num_rows($check_review) > 0 ? $existing_review['rating'] : 0;
   $existing_text = mysqli_num_rows($check_review) > 0 ? $existing_review['review_text'] : '';
   
   // Allow all users to review products, regardless of purchase history
   if(mysqli_num_rows($check_review) > 0) {
      echo '<div class="review-notice">You can update your existing review below.</div>';
   } else {
      echo '<div class="review-notice">Share your thoughts about this product.</div>';
   }
   
   ?>
   <style>
      /* Improved, accessible star rating styles for the write-review form */
      .review-form .rating-select { margin-bottom: 14px; }
      .review-form .rating-stars { display: inline-flex; flex-direction: row-reverse; gap: 6px; align-items: center; }
      .review-form .rating-stars input { display: none; }
      .review-form .rating-stars label { font-size: 28px; color: #ccc; cursor: pointer; line-height: 1; user-select: none; transition: color .15s ease; }
      .review-form .rating-stars label:hover,
      .review-form .rating-stars label:hover ~ label { color: #ffc107; }
      .review-form .rating-stars input:checked ~ label { color: #ffc107; }
      .review-form .rating-value { margin-top: 6px; color: #555; font-size: .95rem; }
      .review-form .rating-hint { color: #888; font-size: .85rem; margin-top: 4px; }
    </style>

   <div class="review-form">
      <h3><?php echo htmlspecialchars($form_title, ENT_QUOTES, 'UTF-8'); ?></h3>
      <form action="" method="post">
         <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">
          
         <div class="rating-select">
            <p>Your Rating: <span id="rating-value-display" aria-live="polite"><?php echo ($existing_rating > 0 ? htmlspecialchars($existing_rating).' stars' : 'Not rated yet'); ?></span></p>
            <div class="rating-stars" role="radiogroup" aria-label="Your rating out of 5 stars">
              <input type="radio" id="star5" name="rating" value="5" <?php echo ($existing_rating == 5 ? 'checked' : ''); ?> required>
              <label for="star5" title="5 stars">&#9733;</label>
              
              <input type="radio" id="star4" name="rating" value="4" <?php echo ($existing_rating == 4 ? 'checked' : ''); ?> required>
              <label for="star4" title="4 stars">&#9733;</label>
              
              <input type="radio" id="star3" name="rating" value="3" <?php echo ($existing_rating == 3 ? 'checked' : ''); ?> required>
              <label for="star3" title="3 stars">&#9733;</label>
              
              <input type="radio" id="star2" name="rating" value="2" <?php echo ($existing_rating == 2 ? 'checked' : ''); ?> required>
              <label for="star2" title="2 stars">&#9733;</label>
              
              <input type="radio" id="star1" name="rating" value="1" <?php echo ($existing_rating == 1 ? 'checked' : ''); ?> required>
              <label for="star1" title="1 star">&#9733;</label>
              
              <div class="rating-value" id="rating-value"><?php echo ($existing_rating > 0 ? htmlspecialchars($existing_rating).' stars' : 'Not rated yet'); ?></div>
              <div class="rating-hint">Click on a star to rate this product</div>
            </div>
            
            <textarea name="review_text" placeholder="Share your experience with this product..." required><?php echo htmlspecialchars($existing_text, ENT_QUOTES, 'UTF-8'); ?></textarea>
            
            <button type="submit" name="submit_review" class="btn"><?php echo htmlspecialchars($submit_text, ENT_QUOTES, 'UTF-8'); ?></button>
         </form>
      </div>
   </div>
   <script src="js/rating.js"></script>
   <script>
     // Lightweight enhancement in case rating.js doesn't handle live display
     (function(){
       var stars = document.querySelectorAll('.review-form .rating-stars input[name="rating"]');
       var display = document.getElementById('rating-value-display');
       if (!stars.length || !display) return;
       function setText(val){ display.textContent = val ? (val + ' stars') : 'Not rated yet'; }
       stars.forEach(function(radio){
         radio.addEventListener('change', function(){ setText(this.value); });
       });
     })();
   </script>
   <?php
   }

// Display reviews list
// Get most reviewed products
function getMostReviewedProducts($limit = 4) {
    global $conn;
    
    $query = "SELECT p.*, COUNT(r.id) as review_count, AVG(r.rating) as avg_rating 
              FROM products p 
              LEFT JOIN reviews r ON p.id = r.product_id 
              WHERE r.status = 'approved'
              GROUP BY p.id 
              HAVING review_count > 0
              ORDER BY review_count DESC, avg_rating DESC 
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

function displayReviews($product_id) {
   $reviews = getProductReviews($product_id);
   $avg_rating = getAverageRating($product_id);
   $rating_count = getRatingCount($product_id);
   
   echo '
   <div class="reviews-container">
      <div class="reviews-summary">
         <div class="average-rating">
            <span class="rating-value">'.$avg_rating.'</span>
            <div class="stars">';
            
            for($i = 1; $i <= 5; $i++){
               if($i <= $avg_rating){
                  echo '<i class="fas fa-star"></i>';
               } elseif($i - 0.5 <= $avg_rating){
                  echo '<i class="fas fa-star-half-alt"></i>';
               } else {
                  echo '<i class="far fa-star"></i>';
               }
            }
            
   echo '   </div>
            <span class="rating-count">Based on '.$rating_count.' reviews</span>
         </div>
      </div>
      
      <div class="reviews-list">';
      
      if(count($reviews) > 0){
         foreach($reviews as $review){
            echo '
            <div class="review-item">
               <div class="review-header">
                  <div class="reviewer-info">
                     <span class="reviewer-name">'.$review['user_name'].'</span>
                     <span class="review-date">'.date('F j, Y', strtotime($review['created_at'])).'</span>
                  </div>
                  <div class="review-rating">';
                  
                  for($i = 1; $i <= 5; $i++){
                     if($i <= $review['rating']){
                        echo '<i class="fas fa-star"></i>';
                     } else {
                        echo '<i class="far fa-star"></i>';
                     }
                  }
                  
            echo '</div>
               </div>
               <div class="review-content">
                  <p>'.$review['review_text'].'</p>
               </div>
            </div>';
         }
      } else {
         echo '<div class="no-reviews">No reviews yet. Be the first to review this product!</div>';
      }
      
   echo '</div>
   </div>';
}
?>
