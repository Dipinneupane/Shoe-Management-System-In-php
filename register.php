<?php
include 'config.php';

if (isset($_POST['submit'])) {
   $name = mysqli_real_escape_string($conn, $_POST['name']);
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $pass = mysqli_real_escape_string($conn, md5($_POST['password']));
   $cpass = mysqli_real_escape_string($conn, md5($_POST['cpassword']));
   $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);

   // Server-side validation
   $errors = [];

   // Validate name (must contain at least one letter, can contain numbers)
   if (!preg_match('/[a-zA-Z]/', $name)) {
      $errors[] = 'Username must contain at least one letter';
   }

   // Validate email (must end with @gmail.com)
   if (!preg_match('/@gmail\.com$/', $email)) {
      $errors[] = 'Email must be a Gmail address (@gmail.com)';
   }
   
   // Check if email already exists
   $check_email = mysqli_query($conn, "SELECT * FROM `users` WHERE email = '$email'") or die('query failed');
   if (mysqli_num_rows($check_email) > 0) {
      $errors[] = 'Email already registered! Please use a different email address or login.';
   }

   // If there are validation errors, show them and stop further execution
   if (!empty($errors)) {
      $message = $errors;
   } else {
      // Check if the user is trying to register as admin
      if ($user_type == 'admin') {
         $select_admin = mysqli_query($conn, "SELECT * FROM `users` WHERE user_type = 'admin'") or die('query failed');
         if (mysqli_num_rows($select_admin) > 0) {
            $message[] = 'Only one admin can be registered!';
         } else {
            // Proceed with admin registration
            if ($pass != $cpass) {
               $message[] = 'Confirm password not matched!';
            } else {
               mysqli_query($conn, "INSERT INTO `users`(name, email, password, user_type) VALUES('$name', '$email', '$pass', '$user_type')") or die('query failed');
               $message[] = 'Registered successfully!';
               header('location:login.php');
            }
         }
      } else {
         // Proceed with regular user registration
         if ($pass != $cpass) {
            $message[] = 'Confirm password not matched!';
         } else {
            mysqli_query($conn, "INSERT INTO `users`(name, email, password, user_type) VALUES('$name', '$email', '$pass', '$user_type')") or die('query failed');
            $message[] = 'Registered successfully!';
            header('location:login.php');
         }
      }
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Register</title>

   <!-- Font Awesome CDN Link -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- Custom CSS File Link -->
   <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php
if (isset($message)) {
   foreach ($message as $message) {
      echo '
      <div class="message">
         <span>'.$message.'</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}
?>

<div class="form-container">
   <form action="" method="post" onsubmit="return validateForm()">
      <h3>Register now</h3>
      <input type="text" name="name" id="name" placeholder="Enter your name (no numbers)" required class="box" pattern="[A-Za-z\s]+" title="Name should not contain numbers">
      <input type="email" name="email" id="email" placeholder="Enter your Gmail address" required class="box" pattern="[a-zA-Z0-9._%+-]+@gmail\.com$" title="Please enter a valid Gmail address">
      <input type="password" name="password" placeholder="Enter your password" required class="box">
      <input type="password" name="cpassword" placeholder="Confirm your password" required class="box">
      <select name="user_type" class="box">
         <option value="user">User</option>
         <option value="admin">Admin</option>
      </select>
      <input type="submit" name="submit" value="Register now" class="btn">
      <p>Already have an account? <a href="login.php">Login now</a></p>
   </form>
</div>

<script>
// Function to check if username exists
async function checkUsername(username) {
   try {
      const response = await fetch('check_availability.php', {
         method: 'POST',
         headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
         },
         body: 'type=username&value=' + encodeURIComponent(username)
      });
      const data = await response.json();
      return data.available === false; // returns true if username exists
   } catch (error) {
      console.error('Error checking username:', error);
      return false;
   }
}

// Function to check if email exists
async function checkEmail(email) {
   try {
      const response = await fetch('check_availability.php', {
         method: 'POST',
         headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
         },
         body: 'type=email&value=' + encodeURIComponent(email)
      });
      const data = await response.json();
      return data.available === false; // returns true if email exists
   } catch (error) {
      console.error('Error checking email:', error);
      return false;
   }
}

async function validateForm() {
   // Client-side validation
   const name = document.getElementById('name').value.trim();
   const email = document.getElementById('email').value.trim();
   const emailPattern = /@gmail\.com$/;
   const namePattern = /^[A-Za-z\s]+$/;
   
   if (!namePattern.test(name)) {
      alert('Name should not contain numbers');
      return false;
   }
   
   if (!emailPattern.test(email)) {
      alert('Please enter a valid Gmail address (ending with @gmail.com)');
      return false;
   }
   
   // Check if username already exists
   const usernameExists = await checkUsername(name);
   if (usernameExists) {
      alert('This username is already taken. Please choose a different one.');
      return false;
   }
   
   // Check if email already exists
   const emailExists = await checkEmail(email);
   if (emailExists) {
      alert('This email is already registered. Please use a different email or login.');
      return false;
   }
   
   return true;
}
</script>

</body>
</html>