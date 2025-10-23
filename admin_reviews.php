<?php

include 'config.php';

// Only start session if one doesn't already exist
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:login.php');
}

// Reviews are now auto-approved, but we keep this for backward compatibility
if(isset($_GET['approve'])){
   $review_id = $_GET['approve'];
   mysqli_query($conn, "UPDATE `reviews` SET status = 'approved' WHERE id = '$review_id'") or die('query failed');
   $message[] = 'Review has been approved!';
}

if(isset($_GET['delete'])){
   $review_id = $_GET['delete'];
   mysqli_query($conn, "DELETE FROM `reviews` WHERE id = '$review_id'") or die('query failed');
   $message[] = 'Review has been deleted!';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Review Management</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- custom admin css file link  -->
   <link rel="stylesheet" href="css/admin_style.css">
   <!-- page-specific css for reviews page -->
   <link rel="stylesheet" href="css/admin_reviews.css">

</head>
<body>
   
<?php include 'admin_header.php'; ?>

<section class="admin-section reviews-section">
   <div class="section-header">
      <h1 class="title">Review Management</h1>
      <div class="section-actions">
         <div class="search-container">
            <input type="text" id="reviewSearch" placeholder="Search reviews..." onkeyup="searchReviews()">
            <button type="button"><i class="fas fa-search"></i></button>
         </div>
         <div class="filter-container">
            <select id="statusFilter" onchange="filterReviews()">
               <option value="all">All Reviews</option>
               <option value="approved">Approved</option>
            </select>
         </div>
      </div>
   </div>

   <div class="reviews-container">
      <div class="reviews-grid" id="reviewsGrid">
         <?php
            $select_reviews = mysqli_query($conn, "SELECT r.*, p.name as product_name, p.image as product_image, u.name as user_name 
                                                 FROM `reviews` r 
                                                 JOIN `products` p ON r.product_id = p.id 
                                                 JOIN `users` u ON r.user_id = u.id 
                                                 ORDER BY r.created_at DESC") or die('query failed');
            if(mysqli_num_rows($select_reviews) > 0){
               while($fetch_review = mysqli_fetch_assoc($select_reviews)){
         ?>
         <div class="review-card" data-status="<?php echo $fetch_review['status']; ?>">
            <div class="review-header">
               <div class="product-info">
                  <img src="uploaded_img/<?php echo $fetch_review['product_image']; ?>" alt="<?php echo $fetch_review['product_name']; ?>">
                  <div>
                     <h3><?php echo $fetch_review['product_name']; ?></h3>
                     <p class="review-date"><?php echo date('M d, Y', strtotime($fetch_review['created_at'])); ?></p>
                  </div>
               </div>
               <div class="review-status <?php echo $fetch_review['status']; ?>">
                  <?php echo ucfirst($fetch_review['status']); ?>
               </div>
            </div>
            <div class="review-content">
               <div class="reviewer-info">
                  <p class="reviewer-name"><i class="fas fa-user"></i> <?php echo $fetch_review['user_name']; ?></p>
                  <div class="rating">
                     <?php
                        for($i = 1; $i <= 5; $i++){
                           if($i <= $fetch_review['rating']){
                              echo '<i class="fas fa-star"></i>';
                           } else {
                              echo '<i class="far fa-star"></i>';
                           }
                        }
                     ?>
                  </div>
               </div>
               <p class="review-text"><?php echo $fetch_review['review_text']; ?></p>
            </div>
            <div class="review-actions">
                  <a href="admin_reviews.php?delete=<?php echo $fetch_review['id']; ?>" class="btn delete-btn" onclick="return confirm('Delete this review?');">Delete</a>
               </div>
         </div>
         <?php
               }
            } else {
               echo '<p class="empty">No reviews found!</p>';
            }
         ?>
      </div>
   </div>
</section>

<script>
   function searchReviews() {
      var input, filter, grid, cards, title, i, txtValue;
      input = document.getElementById("reviewSearch");
      filter = input.value.toUpperCase();
      grid = document.getElementById("reviewsGrid");
      cards = grid.getElementsByClassName("review-card");
      
      for (i = 0; i < cards.length; i++) {
         title = cards[i].getElementsByTagName("h3")[0];
         txtValue = title.textContent || title.innerText;
         if (txtValue.toUpperCase().indexOf(filter) > -1) {
            cards[i].style.display = "";
         } else {
            cards[i].style.display = "none";
         }
      }
   }
   
   function filterReviews() {
      var select, filter, grid, cards, i;
      select = document.getElementById("statusFilter");
      filter = select.value;
      grid = document.getElementById("reviewsGrid");
      cards = grid.getElementsByClassName("review-card");
      
      for (i = 0; i < cards.length; i++) {
         if (filter === "all" || cards[i].getAttribute("data-status") === filter) {
            cards[i].style.display = "";
         } else {
            cards[i].style.display = "none";
         }
      }
   }
</script>

</body>
</html>
