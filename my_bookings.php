<?php
declare(strict_types=1);
include 'header.php';

// Ensure user is logged in
requireLogin();

// Get current user ID from session
$userID = getCurrentUserId();

// Get flash messages
$successMessage = getFlashMessage('booking_success');
$errorMessage = getFlashMessage('booking_error');

// Get optional filter from URL
$statusFilter = trim($_GET['status'] ?? '');

// Handle booking cancellation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {

    // Get booking ID from form
    $bookingID = (int) ($_POST['booking_id'] ?? 0);

    // Fetch booking data for validation
    $booking = getBookingById($conn, $bookingID, $userID);

    if (!$booking) {
        $_SESSION['booking_error'] = 'Booking not found.';
    
    } elseif (($booking['booking_status'] ?? '') === 'Cancelled') {
        $_SESSION['booking_error'] = 'This booking is already cancelled.';
    
    } elseif (($booking['checkin_date'] ?? '') < date('Y-m-d')) {
        $_SESSION['booking_error'] = 'Past or active bookings cannot be cancelled.';
    
    } else {

        // Prepare SQL query to update booking status
        $stmt = $conn->prepare("UPDATE bookings 
                                SET booking_status = 'Cancelled', updated_at = CURRENT_TIMESTAMP 
                                WHERE bookingID = ? AND userID = ?");

        // Bind parameters (integers)
        $stmt->bind_param("ii", $bookingID, $userID);

        // Execute update
        $stmt->execute();

        // Close statement
        $stmt->close();

        // Set success message
        $_SESSION['booking_success'] = 'Booking #' . $bookingID . ' was cancelled successfully.';
    }

    // Redirect to refresh page
    header('Location: my_bookings.php');
    exit;
}

// Fetch bookings for current user (with optional filter)
$bookings = getBookingsForUser($conn, $userID, $statusFilter);

// Generate summary statistics
$summary = getBookingSummary($bookings);

// Include header layout
//include 'header.php';
?>

<section class="content-section">
    <div class="container">
        <div class="card">
            <h2>Booking History</h2>
            <p class="muted">View, update, and cancel your bookings from one page.</p>

            <?php if ($successMessage !== ''): ?>
                <!-- Display success message -->
                <div class="alert alert-success"><?php echo e($successMessage); ?></div>
            <?php endif; ?>

            <?php if ($errorMessage !== ''): ?>
                <!-- Display error message -->
                <div class="alert alert-error"><?php echo e($errorMessage); ?></div>
            <?php endif; ?>

            <!-- Booking summary -->
            <div class="summary-grid">
                <div class="summary-card"><strong><?php echo $summary['total']; ?></strong><span>Total</span></div>
                <div class="summary-card"><strong><?php echo $summary['confirmed']; ?></strong><span>Confirmed</span></div>
                <div class="summary-card"><strong><?php echo $summary['completed']; ?></strong><span>Completed</span></div>
                <div class="summary-card"><strong><?php echo $summary['cancelled']; ?></strong><span>Cancelled</span></div>
            </div>

            <!-- Filter form -->
            <form method="get" action="my_bookings.php" class="filter-form">
                <select name="status">
                    <option value="" <?php echo $statusFilter === '' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="Confirmed" <?php echo $statusFilter === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="Cancelled" <?php echo $statusFilter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button type="submit" class="primary-btn">Filter</button>
                <a href="book.php" class="secondary-btn">Create Booking</a>
            </form>

            <?php if (empty($bookings)): ?>
                <!-- No bookings message -->
                <div class="empty-state">
                    <h3>No bookings found</h3>
                    <p>You do not have any bookings yet for the selected filter.</p>
                </div>
            <?php else: ?>

                <!-- Booking table -->
                <div class="table-wrapper">
                    <table class="booking-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>User ID</th>
                                <th>Room ID</th>
                                <th>Room</th>
                                <th>Stay</th>
                                <th>Rooms / Guests</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php foreach ($bookings as $booking): ?>
                                <?php $displayStatus = getDisplayStatus($booking); ?>

                                <tr>
                                    <td>#<?php echo (int) $booking['bookingID']; ?></td>
                                    <td><?php echo (int) $booking['userID']; ?></td>
                                    <td><?php echo (int) $booking['roomID']; ?></td>

                                    <td><?php echo e((string) $booking['roomName']); ?></td>

                                    <td>
                                        <?php echo e((string) $booking['checkin_date']); ?> to<br>
                                        <?php echo e((string) $booking['checkout_date']); ?><br>
                                        <span class="muted">
                                            <?php echo calculateNights(
                                                (string) $booking['checkin_date'],
                                                (string) $booking['checkout_date']
                                            ); ?> night(s)
                                        </span>
                                    </td>

                                    <td>
                                        <?php echo (int) $booking['number_of_rooms']; ?> room(s)<br>
                                        <span class="muted">
                                            <?php echo (int) $booking['adults']; ?> adult(s), 
                                            <?php echo (int) $booking['children']; ?> child(ren)
                                        </span>
                                    </td>

                                    <td>$<?php echo number_format((float) $booking['total_price'], 2); ?></td>

                                    <td>
                                        <span class="badge badge-<?php echo strtolower($displayStatus); ?>">
                                            <?php echo e($displayStatus); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if (($booking['booking_status'] ?? '') !== 'Cancelled' && ($booking['checkin_date'] ?? '') >= date('Y-m-d')): ?>

                                            <!-- Edit button -->
                                            <a href="edit_booking.php?id=<?php echo (int) $booking['bookingID']; ?>" class="secondary-btn">Edit</a>

                                            <!-- Cancel form -->
                                            <form method="post" action="my_bookings.php" class="inline-form" 
                                                  onsubmit="return confirm('Are you sure you want to cancel this booking?');">

                                                <input type="hidden" name="booking_id" value="<?php echo (int) $booking['bookingID']; ?>">
                                                <input type="hidden" name="action" value="cancel">

                                                <button type="submit" class="danger-btn">Cancel</button>
                                            </form>

                                        <?php else: ?>
                                            <span class="muted">No actions</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>

            <?php endif; ?>
        </div>
    </div>
</section>

<?php 
// Include footer layout
include 'footer.php'; 
?>