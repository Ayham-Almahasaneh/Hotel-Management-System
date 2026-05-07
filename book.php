<?php
declare(strict_types=1);
include 'header.php';
// Ensure user is logged in
requireLogin();

// Fetch available rooms from database
$rooms = getRooms($conn);

// Retrieve validation errors from previous submission (if any)
$errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);

// Get authentication message (if exists)
$authMessage = getFlashMessage('auth_message');

// Determine selected room (from URL or default to first room)
$selectedRoomID = isset($_GET['roomID'])
    ? (int) $_GET['roomID']
    : ((isset($rooms[0]['roomID'])) ? (int) $rooms[0]['roomID'] : 0);

// Fetch selected room details
$selectedRoom = getRoomById($conn, $selectedRoomID);

// Initialize form data (used to repopulate fields)
$formData = [
    'roomID' => $selectedRoomID,
    'checkin' => trim($_GET['checkin'] ?? ''),
    'checkout' => trim($_GET['checkout'] ?? ''),
    'rooms' => trim($_GET['rooms'] ?? '1'),
    'adults' => trim($_GET['adults'] ?? '1'),
    'children' => trim($_GET['children'] ?? '0'),
    'special_requests' => '',
];

// Include header layout
//include 'header.php';
?>

<section class="content-section">
    <div class="container">
        <div class="card">
            <h2>Create Booking</h2>
            <p class="muted">Fill in the booking form below to reserve your room.</p>

            <?php if ($authMessage !== ''): ?>
                <!-- Display success message -->
                <div class="alert alert-success"><?php echo e($authMessage); ?></div>
            <?php endif; ?>

            <?php foreach ($errors as $error): ?>
                <!-- Display validation errors -->
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endforeach; ?>

            <!-- Booking form -->
            <form method="post" action="process_booking.php">
                <div class="form-grid">

                    <!-- Display user ID (readonly) -->
                    <div class="form-group">
                        <label for="userID">User ID</label>
                        <input type="text" id="userID" value="<?php echo (int) getCurrentUserId(); ?>" readonly>
                    </div>

                    <!-- Room selection -->
                    <div class="form-group">
                        <label for="roomID">Room</label>
                        <select id="roomID" name="roomID" required>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo (int) $room['roomID']; ?>"
                                    <?php echo (int) $formData['roomID'] === (int) $room['roomID'] ? 'selected' : ''; ?>>

                                    <?php echo e((string) $room['roomName']); ?> 
                                    - $<?php echo number_format((float) $room['price_per_night'], 2); ?>/night
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Check-in date -->
                    <div class="form-group">
                        <label for="checkin">Check-in Date</label>
                        <input type="date" id="checkin" name="checkin"
                               value="<?php echo e((string) $formData['checkin']); ?>" required>
                    </div>

                    <!-- Check-out date -->
                    <div class="form-group">
                        <label for="checkout">Check-out Date</label>
                        <input type="date" id="checkout" name="checkout"
                               value="<?php echo e((string) $formData['checkout']); ?>" required>
                    </div>

                    <!-- Number of rooms -->
                    <div class="form-group">
                        <label for="rooms">Number of Rooms</label>
                        <select id="rooms" name="rooms">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>"
                                    <?php echo (int) $formData['rooms'] === $i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- Adults -->
                    <div class="form-group">
                        <label for="adults">Adults</label>
                        <input type="number" id="adults" name="adults"
                               min="1" max="10"
                               value="<?php echo e((string) $formData['adults']); ?>" required>
                    </div>

                    <!-- Children -->
                    <div class="form-group">
                        <label for="children">Children</label>
                        <input type="number" id="children" name="children"
                               min="0" max="10"
                               value="<?php echo e((string) $formData['children']); ?>">
                    </div>

                    <!-- Special requests -->
                    <div class="form-group form-group-full">
                        <label for="special_requests">Special Requests</label>
                        <textarea id="special_requests" name="special_requests" rows="4"
                                  placeholder="Optional notes for your stay"><?php echo e((string) $formData['special_requests']); ?></textarea>
                    </div>

                </div>

                <!-- Form actions -->
                <div class="form-actions">
                    <button type="submit" class="primary-btn">Create Booking</button>
                    <a href="my_bookings.php" class="secondary-btn">View Booking History</a>
                </div>
            </form>

            <?php if ($selectedRoom): ?>
                <!-- Display selected room information -->
                <div class="note-box">
                    <strong>Selected Room:</strong> <?php echo e((string) $selectedRoom['roomName']); ?> |
                    <strong>Price:</strong> $<?php echo number_format((float) $selectedRoom['price_per_night'], 2); ?> per night |
                    <strong>Max Guests per Room:</strong> <?php echo (int) $selectedRoom['max_guests']; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php 
// Include footer layout
include 'footer.php'; 
?>