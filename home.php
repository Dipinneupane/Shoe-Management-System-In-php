<?php

include 'config.php';
include 'reviews.php'; // Include reviews functionality
include 'recommendations.php';

// Only start session if one doesn't already exist
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:login.php');
}

if(isset($_POST['add_to_cart'])){

   $product_name = $_POST['product_name'];
   $product_price = $_POST['product_price'];
   $product_image = $_POST['product_image'];
   $product_quantity = $_POST['product_quantity'];

   $check_cart_numbers = mysqli_query($conn, "SELECT * FROM `cart` WHERE name = '$product_name' AND user_id = '$user_id'") or die('query failed');

   if(mysqli_num_rows($check_cart_numbers) > 0){
      $message[] = 'already added to cart!';
   }else{
      mysqli_query($conn, "INSERT INTO `cart`(user_id, name, price, quantity, image) VALUES('$user_id', '$product_name', '$product_price', '$product_quantity', '$product_image')") or die('query failed');
      $message[] = 'product added to cart!';
   }

}

?>

<!-- Collaborative Recommendations Section moved below latest products -->

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Home</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- custom css file link  -->
   <style>
   
   
   /* Ensure product cards have consistent height */
   .box {
       display: flex;
       flex-direction: column;
       height: 100%;
   }

   /* Ensure recommendation/product sections render as a responsive grid */
   .products .box-container {
       display: grid;
       grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
       gap: 20px;
       align-items: stretch;
   }

   /* Prevent image-induced overlap by constraining images */
   .products .box img {
       width: 100%;
       height: 220px;
       object-fit: cover;
       display: block;
   }
   
   .image-container {
       flex-grow: 1;
       display: flex;
       flex-direction: column;
       position: relative; /* enable overlays inside image container */
   }
   
   .content {
       margin-top: auto;
       position: relative;
   }
   
   /* Rating overlay standardized via css/style.css */
   
   /* Recommendation badge styles */
   .recommendation-badge {
       position: absolute;
       top: -25px;
       left: 10px;
       background: #27ae60;
       color: white;
       padding: 3px 10px;
       border-radius: 12px;
       font-size: 12px;
       font-weight: bold;
       box-shadow: 0 2px 5px rgba(0,0,0,0.2);
       z-index: 1;
   }
   
   .box {
       position: relative;
       overflow: visible;
   }
   
   /* Purchase History Styles */
   .purchase-history {
       max-width: 1200px;
       margin: 40px auto;
       padding: 0 15px;
   }
   
   .purchase-history .heading {
       text-align: center;
       font-size: 2.5rem;
       color: #333;
       margin-bottom: 30px;
   }
   
   .purchase-history .heading span {
       color: #27ae60;
   }
   
   .preferences {
       background: #f9f9f9;
       border-radius: 10px;
       padding: 25px;
       margin-bottom: 30px;
       box-shadow: 0 2px 15px rgba(0,0,0,0.1);
   }
   
   .preference-category {
       margin-bottom: 20px;
   }
   
   .preference-category:last-child {
       margin-bottom: 0;
   }
   
   .preference-category h3 {
       font-size: 1.2rem;
       color: #555;
       margin-bottom: 15px;
       font-weight: 600;
   }
   
   .tags {
       display: flex;
       flex-wrap: wrap;
       gap: 10px;
   }
   
   .tag {
       background: #27ae60;
       color: white;
       padding: 8px 18px;
       border-radius: 25px;
       font-size: 0.9rem;
       display: inline-block;
       box-shadow: 0 2px 5px rgba(0,0,0,0.1);
       transition: all 0.3s ease;
   }
   
   .tag:hover {
       background: #219653;
       transform: translateY(-2px);
   }
   
   /* Testimonials: constrain product image size and align layout */
   .testimonials .testimonial-content {
       display: flex;
       align-items: center;
       gap: 16px;
   }
   .testimonials .testimonial-product-image {
       flex: 0 0 auto;
   }
   .testimonials .testimonial-product-image img {
       width: 110px;
       height: 110px;
       object-fit: cover;
       display: block;
       border-radius: 8px;
   }
   
   /* Responsive adjustments */
   @media (max-width: 768px) {
       .purchase-history .heading {
           font-size: 2rem;
       }
       
       .preferences {
           padding: 15px;
       }
       
       .tag {
           padding: 6px 14px;
           font-size: 0.85rem;
       }
   }
   
   /* Your existing styles */
   
   /* Ensure CF images are fully visible (no crop) */
   .cf-products .image-container {
       min-height: 260px;
       display: flex;
       align-items: center;
       justify-content: center;
       background: #fff;
   }
   .cf-products .box img {
       object-fit: contain;
       height: auto;          /* override global 220px height */
       max-height: 260px;     /* keep consistent card size */
       width: 100%;
       background: #fff; /* avoid black bars */
   }
 </style>
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/size_finder_promo.css">
   <link rel="stylesheet" href="css/testimonials.css">
   <link rel="stylesheet" href="css/product-rating.css">

</head>
<body>
   
<?php include 'header.php'; ?>



<section class="home">
   <div class="container">
      <div class="content">
         <h3>Step into <span>Comfort</span> & Style with Our Premium Collection</h3>
         <p>Discover our exclusive range of high-quality footwear designed for every occasion. From casual sneakers to elegant formal shoes, find your perfect fit with us.</p>
         <div class="home-btns">
            <a href="shop.php" class="btn">Shop Now</a>
            <a href="about.php" class="white-btn">Learn More</a>
         </div>
      </div>
      <div class="image-container">
         <img src="images/hero-shoes.png" alt="Featured Shoes" onerror="this.src='images/about-img.jpg'">
      </div>
   </div>

</section>

<!-- Collaborative Filtering (Recommended For You) placed before Latest Products -->
<section class="products cf-products">
  <h1 class="title">Recommended <span>For You</span></h1>
  <?php if (isset($user_id) && !empty($user_id)): ?>
    <?php $cf = getCollaborativeRecommendationsByUser($conn, (int)$user_id, 8); ?>
    <?php if (!empty($cf)): ?>
      <div class="box-container">
        <?php foreach ($cf as $p): ?>
          <div class="box">
            <div class="image-container">
              <a href="product.php?id=<?= (int)$p['id'] ?>">
                <img class="image" src="uploaded_img/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" onerror="this.onerror=null;this.src='images/about-img.jpg';">
              </a>
              <span class="recommendation-badge">Recommended</span>
            </div>
            <div class="price">Rs<?= htmlspecialchars($p['price']) ?>/-</div>
            <?php
              // Rating row similar to latest products
              $avg = 0.0; $cnt = 0;
              $ratRes = mysqli_query($conn, "SELECT COALESCE(AVG(rating),0) AS avg_rating, COUNT(*) AS rating_count FROM `reviews` WHERE product_id='".(int)$p['id']."'");
              if ($ratRes && $rr = mysqli_fetch_assoc($ratRes)) { $avg = (float)$rr['avg_rating']; $cnt = (int)$rr['rating_count']; }
              if ($cnt > 0) {
                  $rating = (int)round($avg);
                  echo '<div class="rating" aria-label="Rated ' . $rating . ' out of 5">' . str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) . ' <span class="rating-count">(' . $cnt . ')</span></div>';
              } else {
                  echo '<div class="rating no-rating" aria-label="No ratings yet">' . str_repeat('☆', 5) . '</div>';
              }
            ?>
            <div class="name"><?= htmlspecialchars($p['name']) ?></div>
            <?php
              // Live stock label for this recommended product
              $qty = 0;
              $qres = mysqli_query($conn, "SELECT quantity FROM `products` WHERE id = '".(int)$p['id']."' LIMIT 1");
              if ($qres && $row = mysqli_fetch_assoc($qres)) { $qty = (int)$row['quantity']; }
              if ($qty > 0) {
                  echo '<div class="stock in-stock">' . $qty . ' in stock</div>';
              } else {
                  echo '<div class="stock out-of-stock">Out of stock</div>';
              }
            ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="empty" style="text-align:center;">No personalized recommendations yet.</p>
    <?php endif; ?>
  <?php else: ?>
    <p class="empty" style="text-align:center;">Login to see personalized recommendations.</p>
  <?php endif; ?>
</section>

<?php /* Latest products moved below CF */ ?>

<section class="products">

   <h1 class="title">latest products</h1>

   <div class="box-container">

      <?php  

      // Get latest products with their average ratings
      $products = [];
      $result = mysqli_query($conn, 
          "SELECT p.*, 
                  COALESCE(AVG(r.rating), 0) as avg_rating,
                  COUNT(r.id) as rating_count
           FROM products p
           LEFT JOIN reviews r ON p.id = r.product_id
           GROUP BY p.id
           ORDER BY avg_rating DESC, rating_count DESC, p.id DESC
           LIMIT 6") or die('query failed');
      
      while ($row = mysqli_fetch_assoc($result)) {
          $products[] = $row;
      }
      // Show recommended size for user if set
      $user_size = isset($_SESSION['foot_length']) ? $_SESSION['foot_length'] : null;
      foreach ($products as $fetch_products) {
           // Make the entire product card clickable
           echo '<a href="product.php?id=' . urlencode($fetch_products['id']) . '" class="box" style="display:block;text-decoration:none;color:inherit;">';
           echo '<div class="image-container">';
           echo '<img class="image" src="uploaded_img/' . htmlspecialchars($fetch_products['image']) . '" alt="' . htmlspecialchars($fetch_products['name']) . '">';
           echo '<div class="price">Rs' . htmlspecialchars($fetch_products['price']) . '/-</div>';
           
           if ((int)$fetch_products['quantity'] > 0) {
               echo '<div class="stock in-stock">' . (int)$fetch_products['quantity'] . ' in stock</div>';
           } else {
               echo '<div class="stock out-of-stock">Out of stock</div>';
           }
           echo '</div>';
           echo '<div class="content">';
           // Rating row above name
           if (!empty($fetch_products['rating_count'])) {
               $rating = (int)round($fetch_products['avg_rating']);
               echo '<div class="rating" aria-label="Rated ' . $rating . ' out of 5">' . str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) . '</div>';
           } else {
               echo '<div class="rating no-rating" aria-label="No ratings yet">' . str_repeat('☆', 5) . '</div>';
           }
           echo '<div class="name">' . htmlspecialchars($fetch_products['name']) . '</div>';
           // Show recommended size if user size is set
          if ($user_size) {
              $rec = getSizeRecommendation($fetch_products['id'], $user_size); // Uses updated algorithm supporting half sizes and new fit logic
              $fit_type_label = '';
              if ($rec['fit_type'] === 'perfect') {
                  $fit_type_label = '<span style="color:#1a7a4e;font-weight:bold;">Perfect Fit</span>';
              } elseif ($rec['fit_type'] === 'tight') {
                  $fit_type_label = '<span style="color:#d97706;font-weight:bold;">Tight Fit</span>';
              } elseif ($rec['fit_type'] === 'loose') {
                  $fit_type_label = '<span style="color:#2563eb;font-weight:bold;">Loose Fit</span>';
              } elseif ($rec['fit_type'] === 'tight (not perfect)') {
                  $fit_type_label = '<span style="color:#d97706;font-weight:bold;">Tight (not perfect)</span>';
              } elseif ($rec['fit_type'] === 'loose (bigger than your size)') {
                  $fit_type_label = '<span style="color:#2563eb;font-weight:bold;">Loose (bigger than your size)</span>';
              } else {
                  $fit_type_label = '<span style="color:#555;">' . htmlspecialchars($rec['fit_type']) . '</span>';
              }
              echo '<div class="recommended-size" style="margin-bottom:5px;">Recommended size for you: <strong>' . htmlspecialchars($rec['recommended_size']) . '</strong> ' . $fit_type_label . '</div>';
          }
          // ... rest of product details ...

          echo '</div>';
      }
      ?>

   </div>

   <div class="load-more" style="margin-top: 2rem; text-align:center">
      <a href="shop.php" class="option-btn">Load more</a>
   </div>

</section>

<!-- Purchase-based (order history) -->
<section class="products">
  <h1 class="title">Recommendations <span>From Your Purchases</span></h1>
  <?php if (isset($user_id) && !empty($user_id)): ?>
    <?php $purch = getPurchaseBasedRecommendationsByUser($conn, (int)$user_id, 8); ?>
    <?php if (!empty($purch)): ?>
      <div class="box-container">
        <?php foreach ($purch as $p): ?>
          <div class="box">
            <div class="image-container">
              <a href="product.php?id=<?= (int)$p['id'] ?>">
                <img src="uploaded_img/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
              </a>
              <?php if (isset($p['avg_rating'])): ?>
                <?php $rating = (int)round($p['avg_rating']); ?>
                <div class="rating" aria-label="Rated <?= $rating ?> out of 5"><?= str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) ?></div>
              <?php endif; ?>
            </div>
            <div class="name"><?= htmlspecialchars($p['name']) ?></div>
              <?php
              // Fetch live stock for this recommended product
              $qty = 0;
              $qres = mysqli_query($conn, "SELECT quantity FROM `products` WHERE id = '".(int)$p['id']."' LIMIT 1");
              if ($qres && $row = mysqli_fetch_assoc($qres)) { $qty = (int)$row['quantity']; }
              if ($qty > 0) {
                  echo '<div class="stock in-stock">' . $qty . ' in stock</div>';
              } else {
                  echo '<div class="stock out-of-stock">Out of stock</div>';
              }
            ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="empty" style="text-align:center;">No purchase-based recommendations yet.</p>
    <?php endif; ?>
  <?php else: ?>
    <p class="empty" style="text-align:center;">Please log in to see recommendations.</p>
  <?php endif; ?>
</section>
<?php

?>
<!-- User's Purchase History and Preferences -->
<?php
if(isset($user_id)) {
    // Get user's purchase history
    $purchaseHistoryQuery = "SELECT DISTINCT p.type, p.brand, COUNT(*) as purchase_count 
                           FROM orders o
                           JOIN cart c ON o.user_id = c.user_id
                           JOIN products p ON c.name = p.name
                           WHERE o.user_id = '$user_id'
                           GROUP BY p.type, p.brand
                           ORDER BY purchase_count DESC";
    
    $purchaseHistory = mysqli_query($conn, $purchaseHistoryQuery);
    
    if (mysqli_num_rows($purchaseHistory) > 0) {
        echo '<section class="purchase-history">';
        echo '<h2 class="heading">Your <span>Purchase History</span></h2>';
        echo '<div class="preferences">';
        
        $types = [];
        $brands = [];
        
        while ($row = mysqli_fetch_assoc($purchaseHistory)) {
            if (!in_array($row['type'], $types)) {
                $types[] = $row['type'];
            }
            if (!in_array($row['brand'], $brands)) {
                $brands[] = $row['brand'];
            }
        }
        
        if (!empty($types)) {
            echo '<div class="preference-category">';
            echo '<h3>Your Preferred Types:</h3>';
            echo '<div class="tags">';
            foreach ($types as $type) {
                echo '<span class="tag">' . htmlspecialchars($type) . '</span>';
            }
            echo '</div></div>';
        }
        
        if (!empty($brands)) {
            echo '<div class="preference-category">';
            echo '<h3>Your Preferred Brands:</h3>';
            echo '<div class="tags">';
            foreach ($brands as $brand) {
                echo '<span class="tag">' . htmlspecialchars($brand) . '</span>';
            }
            echo '</div></div>';
        }
        
        echo '</div>'; // .preferences
        echo '</section>'; // .purchase-history
    }
}
?>

 

<!-- Most Reviewed Products Section -->
<section class="products">
   <h1 class="title">Most <span>Reviewed</span> Products</h1>
   <div class="box-container">
      <?php
      // Get most reviewed products
      $most_reviewed = getMostReviewedProducts(4);
      
      if(count($most_reviewed) > 0) {
         foreach($most_reviewed as $product) {
            echo '<a href="product.php?id=' . urlencode($product['id']) . '" class="box" style="display:block;text-decoration:none;color:inherit;">';
            echo '<div class="image-container">';
            echo '<img class="image" src="uploaded_img/' . htmlspecialchars($product['image']) . '" alt="' . htmlspecialchars($product['name']) . '">';
            echo '<div class="price">Rs' . number_format($product['price']) . '/-</div>';
            
            // Display review stars
            echo '<div class="product-rating">';
            $avg_rating = round($product['avg_rating'], 1);
            for($i = 1; $i <= 5; $i++) {
               if($i <= $avg_rating) {
                  echo '<i class="fas fa-star"></i>';
               } elseif($i - 0.5 <= $avg_rating) {
                  echo '<i class="fas fa-star-half-alt"></i>';
               } else {
                  echo '<i class="far fa-star"></i>';
               }
            }
            echo ' <span class="rating-count">(' . $product['review_count'] . ')</span>';
            echo '</div>';
            
            echo '</div>';
            echo '<div class="name">' . htmlspecialchars($product['name']) . '</div>';
            echo '</a>';
         }
      } else {
         echo '<p class="empty">No reviewed products yet. Be the first to review!</p>';
      }
      ?>
   </div>
</section>

<section class="about">
   <div class="flex">
      <div class="image">
         <img src="images/about-img.jpg" alt="">
      </div>
      <div class="content">
         <h3>About Happy Fit</h3>
         <p>At our Online Shoes Shop, we believe that the perfect pair of shoes can transform your style and confidence. We offer a wide range of high-quality footwear, from trendy sneakers to elegant formal shoes, ensuring comfort and durability for every step you take.</p>
         <div class="about-features">
            <div class="feature-item">
               <div class="feature-icon">
                  <i class="fas fa-check"></i>
               </div>
               <div class="feature-text">
                  <h4>Premium Quality</h4>
                  <p>All our products are made with the finest materials for durability and comfort.</p>
               </div>
            </div>
            <div class="feature-item">
               <div class="feature-icon">
                  <i class="fas fa-truck"></i>
               </div>
               <div class="feature-text">
                  <h4>Fast Delivery</h4>
                  <p>We ensure quick and reliable delivery to your doorstep.</p>
               </div>
            </div>
         </div>
         <a href="about.php" class="btn">Learn More About Us</a>
      </div>
   </div>
</section>

<section class="home-contact">
   <div class="content">
      <h3>Have Any Questions?</h3>
      <p>We're here to help with any inquiries about our products, orders, or services. Our customer support team is ready to assist you with prompt and helpful responses.</p>
      <a href="contact.php" class="contact-btn">Contact Us Now</a>
   </div>
</section>

<!-- testimonials section starts -->
<section class="testimonials">
   <h1 class="title">What Our <span>Customers</span> Say</h1>
   
   <div class="testimonial-slider">
      <div class="testimonial-container" id="testimonialContainer">
         <?php
            // Get top rated reviews that are approved
            $select_testimonials = mysqli_query($conn, "SELECT r.*, u.name as user_name, p.name as product_name, p.image as product_image, p.id as product_id 
                                                   FROM `reviews` r 
                                                   JOIN `users` u ON r.user_id = u.id 
                                                   JOIN `products` p ON r.product_id = p.id 
                                                   WHERE r.status = 'approved' AND r.rating >= 4 
                                                   ORDER BY r.created_at DESC LIMIT 5") or die('query failed');
            
            if(mysqli_num_rows($select_testimonials) > 0){
               $testimonial_count = 0;
               while($fetch_testimonial = mysqli_fetch_assoc($select_testimonials)){
                  $testimonial_count++;
         ?>
         <div class="testimonial-card">
            <div class="testimonial-content">
               <div class="testimonial-product-image">
                  <a href="product.php?id=<?php echo $fetch_testimonial['product_id']; ?>">
                     <img src="uploaded_img/<?php echo $fetch_testimonial['product_image']; ?>" alt="<?php echo $fetch_testimonial['product_name']; ?>">
                  </a>
               </div>
               <div class="testimonial-details">
                  <div class="testimonial-text">
                     <?php echo $fetch_testimonial['review_text']; ?>
                  </div>
                  <div class="testimonial-rating">
                     <?php
                        for($i = 1; $i <= 5; $i++){
                           if($i <= $fetch_testimonial['rating']){
                              echo '<i class="fas fa-star"></i>';
                           } else {
                              echo '<i class="far fa-star"></i>';
                           }
                        }
                     ?>
                  </div>
                  <div class="testimonial-author">
                     <div class="testimonial-name"><?php echo $fetch_testimonial['user_name']; ?></div>
                     <div class="testimonial-product">on <a href="product.php?id=<?php echo $fetch_testimonial['product_id']; ?>"><?php echo $fetch_testimonial['product_name']; ?></a></div>
                  </div>
               </div>
            </div>
         </div>
         <?php
               }
            } else {
               echo '<div class="testimonial-card"><div class="testimonial-content"><p>No testimonials yet.</p></div></div>';
               $testimonial_count = 1;
            }
         ?>
      </div>
      
      <?php if($testimonial_count > 1): ?>
      <div class="testimonial-arrow prev" onclick="moveTestimonial(-1)"><i class="fas fa-chevron-left"></i></div>
      <div class="testimonial-arrow next" onclick="moveTestimonial(1)"><i class="fas fa-chevron-right"></i></div>
      
      <div class="testimonial-controls">
         <?php for($i = 0; $i < $testimonial_count; $i++): ?>
            <div class="testimonial-dot <?php echo ($i === 0) ? 'active' : ''; ?>" onclick="goToTestimonial(<?php echo $i; ?>)"></div>
         <?php endfor; ?>
      </div>
      <?php endif; ?>
   </div>
</section>
<!-- testimonials section ends -->

<?php include 'footer.php'; ?>

<!-- custom js file link  -->
<script src="js/script.js"></script>

<script>
// Testimonial slider functionality
let currentTestimonial = 0;
const testimonialContainer = document.getElementById('testimonialContainer');
const testimonialCards = document.querySelectorAll('.testimonial-card');
const dots = document.querySelectorAll('.testimonial-dot');
const totalTestimonials = testimonialCards.length;

function updateTestimonialPosition() {
    testimonialContainer.style.transform = `translateX(-${currentTestimonial * 100}%)`;
    
    // Update active dot
    dots.forEach((dot, index) => {
        if (index === currentTestimonial) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });
}

function moveTestimonial(direction) {
    currentTestimonial += direction;
    
    // Handle wrapping
    if (currentTestimonial < 0) {
        currentTestimonial = totalTestimonials - 1;
    } else if (currentTestimonial >= totalTestimonials) {
        currentTestimonial = 0;
    }
    
    updateTestimonialPosition();
}

function goToTestimonial(index) {
    currentTestimonial = index;
    updateTestimonialPosition();
}

// Auto-rotate testimonials every 5 seconds
let testimonialInterval = setInterval(() => moveTestimonial(1), 5000);

// Pause auto-rotation when hovering over testimonials
const testimonialSlider = document.querySelector('.testimonial-slider');
if (testimonialSlider) {
    testimonialSlider.addEventListener('mouseenter', () => {
        clearInterval(testimonialInterval);
    });
    
    testimonialSlider.addEventListener('mouseleave', () => {
        testimonialInterval = setInterval(() => moveTestimonial(1), 5000);
    });
}
</script>

<button type="button" id="khalti-pay-btn" style="display:none;">Pay with Khalti</button>
<script src="https://khalti.com/static/khalti-checkout.js"></script>
<script>
// Show Khalti button only if Khalti is selected
document.querySelectorAll('input[name="payment_method"]').forEach(function(el) {
  el.addEventListener('change', function() {
    document.getElementById('khalti-pay-btn').style.display = (this.value === 'khalti') ? 'inline-block' : 'none';
  });
});

var khaltiConfig = {
  "publicKey": "<?= $khalti_public_key ?>", // from khalti-config.php
  "productIdentity": "ShoesOrder",
  "productName": "Shoes",
  "productUrl": "<?= $_SERVER['REQUEST_URI'] ?>",
  "eventHandler": {
      onSuccess (payload) {
          // Send payload.token to server for verification and order placement
          var xhr = new XMLHttpRequest();
          xhr.open("POST", "khalti-verify.php", true);
          xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
          xhr.onreadystatechange = function() {
              if (xhr.readyState === 4 && xhr.status === 200) {
                  // On success, redirect to orders.php
                  window.location.href = "orders.php";
              }
          };
          xhr.send("token=" + payload.token + "&amount=" + payload.amount);
      },
      onError (error) {
          alert("Khalti Payment Error: " + error.message);
      },
      onClose () {
          // Optional: handle modal close
      }
  }
};

var checkout = new KhaltiCheckout(khaltiConfig);
document.getElementById("khalti-pay-btn").onclick = function () {
  // Amount in paisa (e.g., Rs 100 = 10000 paisa)
  var amount = <?= $total_amount * 100 ?>;
  checkout.show({amount: amount});
};
</script>

</body>
</html>
