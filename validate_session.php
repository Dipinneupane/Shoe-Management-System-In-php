<?php
// This file should be included at the top of every protected page

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Skip validation for admin users
if (isset($_SESSION['admin_id'])) {
    return;
}

// If regular user is logged in, verify the account still exists
if (isset($_SESSION['user_id'])) {
    include 'config.php';
    $user_id = $_SESSION['user_id'];
    $check_user = mysqli_query($conn, "SELECT id FROM `users` WHERE id = '$user_id'") or die('query failed');
    
    // If user doesn't exist in database, destroy session and redirect to login
    if (mysqli_num_rows($check_user) == 0) {
        // Clear all session variables
        $_SESSION = array();
        // Destroy the session
        session_destroy();
        // Set a flag to show account was deleted
        session_start(); // Start a new session for the message
        $_SESSION['account_deleted'] = true;
        // Redirect to login
        header('location:login.php');
        exit();
    }
}
?>
