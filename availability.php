<?php
include 'header.php';
$rooms = getRooms($conn);
$errors = [];
$availability = null;

$roomID = isset($_GET['roomID']) ? (int) $_GET['roomID'] : ((isset($rooms[0]['roomID'])) ? (int) $rooms[0]['roomID'] : 0);
$checkin = trim($_GET['checkin'] ?? '');
$checkout = trim($_GET['checkout'] ?? '');
$requestedRooms = isset($_GET['rooms']) ? max(1, (int) $_GET['rooms']) : 1;
$adults = isset($_GET['adults']) ? max(1, (int) $_GET['adults']) : 1;
$children = isset($_GET['children']) ? max(0, (int) $_GET['children']) : 0;

if (isset($_GET['checkin']) || isset($_GET['checkout'])) {
    if (!getRoomById($conn, $roomID)) {
        $errors[] = 'Please select a valid room.';
    }

    $errors = array_merge($errors, validateDates($checkin, $checkout));

    if (empty($errors)) {
        $availability = checkAvailability($conn, $roomID, $checkin, $checkout, $requestedRooms);
    }
}

//include 'header.php';
?>

<section class="content-section" style="margin-top: 90px;">
    <div class="container">
        <div class="card">
            <h2>Check Room Availability</h2>
            <p class="muted">Review room stock before creating a booking.</p>

            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endforeach; ?>

            <form method="get" action="availability.php" class="form-grid">
                <div class="form-group">
                    <label for="roomID">Room</label>
                    <select name="roomID" id="roomID" required>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo (int) $room['roomID']; ?>" <?php echo $roomID === (int) $room['roomID'] ? 'selected' : ''; ?>>
                                <?php echo e((string) $room['roomName']); ?> - $<?php echo number_format((float) $room['price_per_night'], 2); ?>/night
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="checkin">Check-in Date</label>
                    <input type="date" name="checkin" id="checkin" value="<?php echo e($checkin); ?>" required>
                </div>

                <div class="form-group">
                    <label for="checkout">Check-out Date</label>
                    <input type="date" name="checkout" id="checkout" value="<?php echo e($checkout); ?>" required>
                </div>

                <div class="form-group">
                    <label for="rooms">Number of Rooms</label>
                    <select name="rooms" id="rooms">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $requestedRooms === $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="adults">Adults</label>
                    <input type="number" name="adults" id="adults" min="1" max="10" value="<?php echo $adults; ?>">
                </div>

                <div class="form-group">
                    <label for="children">Children</label>
                    <input type="number" name="children" id="children" min="0" max="10" value="<?php echo $children; ?>">
                </div>

                <div class="form-actions">
                    <button type="submit" class="primary-btn">Check Availability</button>
                   <!-- <a href="book.php" class="secondary-btn">Go to Booking Form</a> -->
                </div>
            </form>

            <?php if ($availability !== null): ?>
                <div class="result-box">
                    <h3>Availability Result</h3>
                    <ul>
                        <li><strong>Room:</strong> <?php echo e((string) $availability['roomName']); ?></li>
                        <li><strong>Total Room Stock:</strong> <?php echo e((string) $availability['total_stock']); ?></li>
                        <li><strong>Already Booked:</strong> <?php echo e((string) $availability['already_booked']); ?></li>
                        <li><strong>Available Rooms:</strong> <?php echo e((string) $availability['available_rooms']); ?></li>
                        <li><strong>Requested Rooms:</strong> <?php echo e((string) $availability['requested_rooms']); ?></li>
                    </ul>

                    <?php if ($availability['is_available']): ?>
                        <div class="alert alert-success">Great news. The requested room quantity is available for the selected dates.</div>
                        <a class="primary-btn" href="book.php?roomID=<?php echo (int) $roomID; ?>&checkin=<?php echo urlencode($checkin); ?>&checkout=<?php echo urlencode($checkout); ?>&rooms=<?php echo $requestedRooms; ?>&adults=<?php echo $adults; ?>&children=<?php echo $children; ?>">Continue to Booking</a>
                    <?php else: ?>
                        <div class="alert alert-error">Sorry, the requested room quantity is not available for the selected dates.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
