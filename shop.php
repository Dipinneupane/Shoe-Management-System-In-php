<?php
include 'config.php';
include 'reviews.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if(!isset($user_id)){
   header('location:login.php');
   exit();
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

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Shop</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/product-rating.css">
</head>
<body>
   
<?php include 'header.php'; ?>

<div class="heading">
   <h3>Our shop</h3>
   <p><a href="home.php">Home</a> / Shop</p>
</div>

<section class="products">
   <h1 class="title">Latest products</h1>
   <div class="box-container">
      <?php  
      // Get products with their average ratings and filter by search if needed
      $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
      $where = [];
      
      if (!empty($search)) {
          $where[] = "(p.name LIKE '%$search%' OR p.brand LIKE '%$search%' OR p.type LIKE '%$search%')";
      }
      
      $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
      
      $select_products = mysqli_query($conn, 
          "SELECT p.*, 
                  COALESCE(AVG(r.rating), 0) as avg_rating,
                  COUNT(r.id) as rating_count
           FROM products p
           LEFT JOIN reviews r ON p.id = r.product_id
           $whereClause
           GROUP BY p.id
           ORDER BY avg_rating DESC, rating_count DESC, p.id DESC") or die('query failed');
             
        // If user size is set and $product_ids exists, skip products already shown above
        if (isset($user_size) && isset($product_ids) && is_array($product_ids) && count($product_ids) > 0) {
            $shown_ids = array_map('intval', $product_ids);
        } else {
            $shown_ids = [];
        }
        
        if(mysqli_num_rows($select_products) > 0){
            while($fetch_products = mysqli_fetch_assoc($select_products)){
                if (in_array((int)$fetch_products['id'], $shown_ids)) continue; // Skip products already shown in size section
      ?>
      <div class="box">
         <div class="image-container">
            <a href="product.php?id=<?php echo $fetch_products['id']; ?>">
               <img class="image" src="uploaded_img/<?php echo $fetch_products['image']; ?>" alt="<?php echo $fetch_products['name']; ?>">
            </a>
         </div>
         <div class="content">
            <?php 
                $hasRatings = isset($fetch_products['rating_count']) && (int)$fetch_products['rating_count'] > 0;
                if ($hasRatings) {
                    $avg = round((float)$fetch_products['avg_rating'], 1);
                    $stars = (int)round((float)$fetch_products['avg_rating']);
                    $count = (int)$fetch_products['rating_count'];
                    echo '<div class="rating" aria-label="Rated ' . htmlspecialchars(number_format($avg,1)) . ' out of 5">'
                       . '<span class="stars">' . str_repeat('★', $stars) . str_repeat('☆', 5 - $stars) . '</span>'
                       . '<span class="value">' . htmlspecialchars(number_format($avg,1)) . '</span>'
                       . '<span class="count">(' . $count . ')</span>'
                       . '</div>';
                } else {
                    echo '<div class="rating no-rating" aria-label="No ratings yet">'
                       . '<span class="stars">' . str_repeat('☆', 5) . '</span>'
                       . '<span class="count">No ratings yet</span>'
                       . '</div>';
                }
            ?>
            <a href="product.php?id=<?php echo $fetch_products['id']; ?>" class="name"><?php echo $fetch_products['name']; ?></a>
            <div class="price">Rs<?php echo $fetch_products['price']; ?>/-</div>
            <?php if(isset($fetch_products['quantity']) && $fetch_products['quantity'] > 0): ?>
               <div class="stock in-stock">In Stock</div>
            <?php else: ?>
               <div class="stock out-of-stock">Out of Stock</div>
            <?php endif; ?>
            <div class="product-brand-type">
               <span class="product-brand"><strong>Brand:</strong> <a href="shop.php?brand=<?php echo urlencode($fetch_products['brand']); ?>" class="brand-link"><?php echo htmlspecialchars($fetch_products['brand']); ?></a></span> | 
               <span class="product-type"><strong>Type:</strong> <a href="shop.php?type=<?php echo urlencode($fetch_products['type']); ?>" class="type-link"><?php echo htmlspecialchars($fetch_products['type']); ?></a></span>
            </div>
         </div>
      </div>
      <?php
         }
      } else {
         echo '<p class="empty">No products added yet!</p>';
      }
      ?>
   </div>
</section>

<?php
?>

<?php include 'footer.php'; ?>

<script src="js/script.js"></script>

    font-size: 14px;
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

</body>
</html>
