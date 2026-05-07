<?php

include 'header.php';

$rooms = getRooms($conn);
?>

<header class="hero">
  <div class="overlay">

    <h1>Luxury Stay in Ottawa</h1>
    <p>Experience comfort and elegance</p>

    <div class="booking-box">
      <form action="availability.php" method="get">
        <div class="booking-row">
          <input type="date" name="checkin" required>
          <input type="date" name="checkout" required>
          <button type="submit">Check Availability</button>
        </div>

        <div class="booking-row">
          <select name="roomID" required>
            <?php foreach ($rooms as $room): ?>
              <option value="<?php echo (int) $room['roomID']; ?>"><?php echo e($room['roomName']); ?></option>
            <?php endforeach; ?>
          </select>

          <select name="rooms">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <option value="<?php echo $i; ?>"><?php echo $i; ?> Room<?php echo $i > 1 ? 's' : ''; ?></option>
            <?php endfor; ?>
          </select>

          <select name="adults">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <option value="<?php echo $i; ?>"><?php echo $i; ?> Adult<?php echo $i > 1 ? 's' : ''; ?></option>
            <?php endfor; ?>
          </select>

          <select name="children">
            <?php for ($i = 0; $i <= 5; $i++): ?>
              <option value="<?php echo $i; ?>"><?php echo $i; ?> Child<?php echo $i !== 1 ? 'ren' : ''; ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </form>
    </div>

  </div>
</header>

<section class="about">
  <h2>Welcome to Ottawa Albus Hotel</h2>
  <p>
    Discover premium rooms, exceptional service, and a relaxing environment in the heart of Ottawa.
  </p>
</section>

<section class="features">
  <div class="feature-box">
    <h3>Employee Service</h3>
    <p>- Available 24/7</p>
  </div>

  <div class="feature-box">
    <h3>Gym</h3>
    <p>- Open 24/7</p>
  </div>

  <div class="feature-box">
    <h3>Swimming Pool</h3>
    <p>- Open 8AM - 7PM</p>
  </div>

  <div class="feature-box">
    <h3>Free Wi-Fi</h3>
    <p>- Unlimited connections</p>
  </div>
</section>

<?php include 'footer.php'; ?>
