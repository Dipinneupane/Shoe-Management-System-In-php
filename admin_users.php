<?php

include 'config.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:login.php');
}

if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   
   // Check if the user being deleted is the currently logged-in regular user (not admin)
   if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $delete_id && !isset($_SESSION['admin_id'])) {
      // Clear all session variables
      $_SESSION = array();
      // Destroy the session
      session_destroy();
      // Delete the user from the database
      mysqli_query($conn, "DELETE FROM `users` WHERE id = '$delete_id'") or die('query failed');
      // Redirect to login page
      header('location:login.php');
      exit();
   } else {
      // For admin deleting any user, or any other case
      // First delete all reviews by this user
      mysqli_query($conn, "DELETE FROM `reviews` WHERE user_id = '$delete_id'") or die('Failed to delete user reviews');
      
      // Then delete the user
      mysqli_query($conn, "DELETE FROM `users` WHERE id = '$delete_id'") or die('Failed to delete user');
      
      // If the deleted user is currently logged in (but not as admin), clear their session
      if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $delete_id) {
         // Clear all session variables
         $_SESSION = array();
         // Destroy the session
         session_destroy();
         // Set a flag to show a message about account deletion
         $_SESSION['account_deleted'] = true;
         header('location:login.php');
         exit();
      }
      
      // If admin is deleting their own account
      if(isset($_SESSION['admin_id']) && $_SESSION['admin_id'] == $delete_id) {
         // Clear session and redirect to login
         session_destroy();
         header('location:login.php');
      } else {
         // Regular deletion for other users by admin
         header('location:admin_users.php');
      }
   }
      exit();
   }


?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Users</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- custom admin css file link  -->
   <link rel="stylesheet" href="css/admin_style.css">

</head>
<body>
   
<?php include 'admin_header.php'; ?>

<section class="users">

   <h1 class="title"> User accounts </h1>

   <div class="box-container">
      <?php
         $select_users = mysqli_query($conn, "SELECT * FROM `users`") or die('query failed');
         while($fetch_users = mysqli_fetch_assoc($select_users)){
      ?>
      <div class="box">
         <p> User id : <span><?php echo $fetch_users['id']; ?></span> </p>
         <p> Username : <span><?php echo $fetch_users['name']; ?></span> </p>
         <p> Email : <span><?php echo $fetch_users['email']; ?></span> </p>
         <p> User type : <span style="color:<?php if($fetch_users['user_type'] == 'admin'){ echo 'var(--orange)'; } ?>"><?php echo $fetch_users['user_type']; ?></span> </p>
         <a href="admin_users.php?delete=<?php echo $fetch_users['id']; ?>" onclick="return confirm('delete this user?');" class="delete-btn">delete user</a>
      </div>
      <?php
         };
      ?>
   </div>

</section>









<!-- custom admin js file link  -->
<script src="js/admin_script.js"></script>

</body>
</html>