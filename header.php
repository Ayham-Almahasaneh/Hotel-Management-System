<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/booking_functions.php';
$loggedIn = isLoggedIn();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>

  <meta charset="UTF-8">
  <title>Ottawa Albus Hotel</title>
  <link rel="stylesheet" href="assets/style.css">
  <link rel="stylesheet" href="assets/booking.css">
</head>
<body>
<nav class="navbar">
  <div class="nav-left">
    <a href="index.php">Home</a>
    <a href="rooms.php">Rooms</a>
    <a href="gallery.php">Gallery</a>
    <!-- <a href="availability.php">Availability</a> -->
    <a href="my_bookings.php">My Bookings</a>
    <?php if ($loggedIn): ?>
      <a href="logout.php">Logout</a>
    <?php else: ?>
      <a href="login.php">Login</a>
      <a href="register.php">Register</a>
    <?php endif; ?>
  </div>

  <div class="nav-center">Albus</div>

  <div class="nav-right nav-user">
    <?php if ($loggedIn): ?>
      <span class="welcome-text">Hi, <?php echo e(getCurrentUsername()); ?></span>
    <?php endif; ?>
    <a href="book.php" class="book-btn">Book Now</a>
  </div>
</nav>
