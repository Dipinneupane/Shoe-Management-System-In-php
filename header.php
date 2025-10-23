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

<header class="header">
   <div class="header-1">
      <div class="container">
         <div class="top-bar">
            <div class="top-bar-left">
               <p><i class="fas fa-map-marker-alt"></i> Kathmandu, Nepal</p>
               <p><i class="fas fa-phone"></i> +977 9863517314</p>
            </div>
            <div class="top-bar-right">
               <?php if(isset($_SESSION['user_id'])): ?>
                  <p>Welcome, <span><?php echo $_SESSION['user_name']; ?></span></p>
               <?php else: ?>
                  <p><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a> | <a href="register.php"><i class="fas fa-user-plus"></i> Register</a></p>
               <?php endif; ?>
            </div>
         </div>
      </div>
   </div>

   <div class="header-2">
      <div class="container">
         <div class="main-header">
            <a href="home.php" class="logo">
               <i class="fas fa-shoe-prints"></i> Happy Fit
            </a>

            <nav class="navbar">
               <a href="home.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'home.php') ? 'active' : ''; ?>">Home</a>
               <a href="shop.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'shop.php') ? 'active' : ''; ?>">Shop</a>

               <a href="about.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active' : ''; ?>">About</a>
               <a href="contact.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'active' : ''; ?>">Contact</a>
               <a href="orders.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'orders.php') ? 'active' : ''; ?>">Orders</a>
            </nav>

            <div class="header-actions">
               <div class="action-item">
                  <a href="search_page.php" class="search-btn">
                     <i class="fas fa-search"></i>
                  </a>
               </div>
               
               <div class="action-item">
                  <div id="user-btn" class="user-btn">
                     <i class="fas fa-user"></i>
                  </div>
                  <div class="user-box">
                     <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="user-info">
                           <p><i class="fas fa-user-circle"></i> <?php echo $_SESSION['user_name']; ?></p>
                           <p><i class="fas fa-envelope"></i> <?php echo $_SESSION['user_email']; ?></p>
                           <a href="logout.php" class="delete-btn">Logout <i class="fas fa-sign-out-alt"></i></a>
                        </div>
                     <?php else: ?>
                        <div class="user-actions">
                           <a href="login.php" class="btn">Login</a>
                           <a href="register.php" class="option-btn">Register</a>
                        </div>
                     <?php endif; ?>
                  </div>
               </div>
               
               <div class="action-item">
                  <?php
                     if(isset($user_id)){
                        $select_cart_number = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'") or die('query failed');
                        $cart_rows_number = mysqli_num_rows($select_cart_number); 
                     } else {
                        $cart_rows_number = 0;
                     }
                  ?>
                  <a href="cart.php" class="cart-btn"> 
                     <i class="fas fa-shopping-cart"></i> 
                     <span class="cart-count"><?php echo $cart_rows_number; ?></span> 
                  </a>
               </div>
               
               <div class="action-item mobile-only">
                  <div id="menu-btn" class="menu-btn">
                     <i class="fas fa-bars"></i>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</header>