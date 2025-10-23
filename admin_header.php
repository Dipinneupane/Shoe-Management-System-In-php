<?php
// Include session validation
require_once 'validate_session.php';

if(isset($message)){
   foreach($message as $message){
      echo '
      <div class="message">
         <span>'.$message.'</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}
?>

<header class="header admin-header">
   <div class="admin-header-container">
      <a href="admin_page.php" class="logo">
         <i class="fas fa-shield-alt"></i> Admin<span>Panel</span>
      </a>

      <nav class="navbar">
         <a href="admin_page.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
         <a href="admin_products.php"><i class="fas fa-box"></i> Products</a>
         <a href="admin_orders.php"><i class="fas fa-shopping-bag"></i> Orders</a>
         <a href="admin_reviews.php"><i class="fas fa-star"></i> Reviews</a>
         <a href="admin_users.php"><i class="fas fa-users"></i> Users</a>
         <a href="admin_contacts.php"><i class="fas fa-envelope"></i> Messages</a>
      </nav>

      <div class="admin-controls">
         <div id="menu-btn" class="fas fa-bars"></div>
         <div id="user-btn" class="fas fa-user-shield"></div>
      </div>

      <div class="account-box">
         <div class="admin-profile">
            <div class="admin-avatar">
               <i class="fas fa-user-circle"></i>
            </div>
            <div class="admin-info">
               <p class="admin-name"><?php echo $_SESSION['admin_name']; ?></p>
               <p class="admin-email"><?php echo $_SESSION['admin_email']; ?></p>
            </div>
         </div>
         <div class="admin-actions">
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
         </div>
      </div>
   </div>
</header>