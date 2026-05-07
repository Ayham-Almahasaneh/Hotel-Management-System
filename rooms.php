<?php
include 'header.php';
$rooms = getRooms($conn);
?>

<h2 class="page-title">Our Rooms</h2>

<div class="rooms-section">
  <div class="room-container">
    <?php foreach ($rooms as $room): ?>
      <div class="room-card">
        <img src="<?php echo e((string) $room['image_path']); ?>" alt="<?php echo e((string) $room['roomName']); ?>">
        <h3><?php echo e((string) $room['roomName']); ?></h3>
        <p><?php echo e((string) $room['description']); ?></p>
        <p>$<?php echo number_format((float) $room['price_per_night'], 2); ?> per night</p>
        <a href="book.php?roomID=<?php echo (int) $room['roomID']; ?>" class="btn">Book Now</a>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php include 'footer.php'; ?>
