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

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Admin panel</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- custom admin css file link  -->
   <link rel="stylesheet" href="css/admin_style.css">

</head>
<body>
   
<?php include 'admin_header.php'; ?>

<!-- admin dashboard section starts  -->

<section class="dashboard">
   <div class="dashboard-header">
      <h1 class="title">Admin Dashboard</h1>
      <div class="date-time">
         <p><i class="fas fa-calendar-alt"></i> <?php echo date('l, F j, Y'); ?></p>
      </div>
   </div>

   <div class="stats-overview">
      <div class="stats-row">
         <div class="stat-card revenue">
            <?php
               $total_revenue = 0;
               $select_all = mysqli_query($conn, "SELECT total_price FROM `orders`") or die('query failed');
               if(mysqli_num_rows($select_all) > 0){
                  while($fetch_all = mysqli_fetch_assoc($select_all)){
                     $total_revenue += $fetch_all['total_price'];
                  };
               };
            ?>
            <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
            <div class="stat-details">
               <h3>Rs<?php echo $total_revenue; ?>/-</h3>
               <p>Total Revenue</p>
            </div>
         </div>

         <div class="stat-card pending">
            <?php
               $total_pendings = 0;
               $select_pending = mysqli_query($conn, "SELECT total_price FROM `orders` WHERE payment_status = 'pending'") or die('query failed');
               if(mysqli_num_rows($select_pending) > 0){
                  while($fetch_pendings = mysqli_fetch_assoc($select_pending)){
                     $total_pendings += $fetch_pendings['total_price'];
                  };
               };
            ?>
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-details">
               <h3>Rs<?php echo $total_pendings; ?>/-</h3>
               <p>Pending Payments</p>
            </div>
         </div>

         <div class="stat-card completed">
            <?php
               $total_completed = 0;
               $select_completed = mysqli_query($conn, "SELECT total_price FROM `orders` WHERE payment_status = 'completed'") or die('query failed');
               if(mysqli_num_rows($select_completed) > 0){
                  while($fetch_completed = mysqli_fetch_assoc($select_completed)){
                     $total_completed += $fetch_completed['total_price'];
                  };
               };
            ?>
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-details">
               <h3>Rs<?php echo $total_completed; ?>/-</h3>
               <p>Completed Payments</p>
            </div>
         </div>
      </div>
   </div>

   <div class="dashboard-grid">
      <div class="dashboard-card out-of-stock">
         <?php
            $select_out_of_stock = mysqli_query($conn, "SELECT COUNT(*) as count FROM `products` WHERE quantity <= 0") or die('query failed');
            $out_of_stock = mysqli_fetch_assoc($select_out_of_stock)['count'];
         ?>
         <h3><?php echo $out_of_stock; ?></h3>
         <p>Out of Stock Products</p>
         <i class="fas fa-exclamation-triangle"></i>
         <a href="admin_products.php?filter=out_of_stock" class="btn">View All</a>
      </div>

      <div class="dashboard-card reviews pending-reviews">
         <?php
            $select_pending_reviews = mysqli_query($conn, "SELECT COUNT(*) as count FROM `reviews` WHERE status = 'pending'") or die('query failed');
            $pending_reviews = mysqli_fetch_assoc($select_pending_reviews)['count'];
         ?>
         <div class="card-header">
            <h3><i class="fas fa-star"></i> Pending Reviews</h3>
            <span class="count"><?php echo $pending_reviews; ?></span>
         </div>
         <div class="card-content">
            <?php if($pending_reviews > 0): ?>
               <p>You have <?php echo $pending_reviews; ?> review(s) waiting for approval.</p>
               <a href="admin_reviews.php" class="btn">Manage Reviews</a>
            <?php else: ?>
               <p>No pending reviews at this time.</p>
            <?php endif; ?>
         </div>
      </div>
      
      <div class="dashboard-card orders">
         <?php 
            $select_orders = mysqli_query($conn, "SELECT * FROM `orders`") or die('query failed');
            $number_of_orders = mysqli_num_rows($select_orders);
         ?>
         <div class="card-icon"><i class="fas fa-shopping-bag"></i></div>
         <div class="card-content">
            <h3><?php echo $number_of_orders; ?></h3>
            <p>Total Orders</p>
         </div>
         <a href="admin_orders.php" class="card-link">View All <i class="fas fa-arrow-right"></i></a>
      </div>

      <div class="dashboard-card products">
         <?php 
            $select_products = mysqli_query($conn, "SELECT * FROM `products`") or die('query failed');
            $number_of_products = mysqli_num_rows($select_products);
         ?>
         <div class="card-icon"><i class="fas fa-box"></i></div>
         <div class="card-content">
            <h3><?php echo $number_of_products; ?></h3>
            <p>Total Products</p>
         </div>
         <a href="admin_products.php" class="card-link">Manage <i class="fas fa-arrow-right"></i></a>
      </div>

      <div class="dashboard-card users">
         <?php 
            $select_users = mysqli_query($conn, "SELECT * FROM `users` WHERE user_type = 'user'") or die('query failed');
            $number_of_users = mysqli_num_rows($select_users);
         ?>
         <div class="card-icon"><i class="fas fa-users"></i></div>
         <div class="card-content">
            <h3><?php echo $number_of_users; ?></h3>
            <p>Registered Users</p>
         </div>
         <a href="admin_users.php" class="card-link">View All <i class="fas fa-arrow-right"></i></a>
      </div>

      <div class="dashboard-card admins">
         <?php 
            $select_admins = mysqli_query($conn, "SELECT * FROM `users` WHERE user_type = 'admin'") or die('query failed');
            $number_of_admins = mysqli_num_rows($select_admins);
         ?>
         <div class="card-icon"><i class="fas fa-user-shield"></i></div>
         <div class="card-content">
            <h3><?php echo $number_of_admins; ?></h3>
            <p>Admin Users</p>
         </div>
      </div>

      <div class="dashboard-card accounts">
         <?php 
            $select_account = mysqli_query($conn, "SELECT * FROM `users`") or die('query failed');
            $number_of_account = mysqli_num_rows($select_account);
         ?>
         <div class="card-icon"><i class="fas fa-user-circle"></i></div>
         <div class="card-content">
            <h3><?php echo $number_of_account; ?></h3>
            <p>Total Accounts</p>
         </div>
      </div>

      <div class="dashboard-card messages">
         <?php 
            $select_messages = mysqli_query($conn, "SELECT * FROM `message`") or die('query failed');
            $number_of_messages = mysqli_num_rows($select_messages);
         ?>
         <div class="card-icon"><i class="fas fa-envelope"></i></div>
         <div class="card-content">
            <h3><?php echo $number_of_messages; ?></h3>
            <p>New Messages</p>
         </div>
         <a href="admin_contacts.php" class="card-link">View All <i class="fas fa-arrow-right"></i></a>
      </div>
   </div>
</section>

<!-- admin dashboard section ends -->








<!-- custom admin js file link  -->
<script src="js/admin_script.js"></script>

</body>
</html>