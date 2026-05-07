<?php
declare(strict_types=1);

include 'header.php';
// Ensure user is logged in
requireLogin();

// Get current user ID
$userID = getCurrentUserId();

// Get booking ID from GET or POST
$bookingID = (int) ($_GET['id'] ?? $_POST['bookingID'] ?? 0);

// Fetch booking data for this user
$booking = getBookingById($conn, $bookingID, $userID);

// Fetch available rooms
$rooms = getRooms($conn);

// Initialize variables
$errors = [];
$successMessage = '';

// If booking not found → redirect
if (!$booking) {
    $_SESSION['booking_error'] = 'Booking not found.';
    header('Location: my_bookings.php');
    exit;
}

// Prevent editing cancelled or past bookings
if (($booking['booking_status'] ?? '') === 'Cancelled' || ($booking['checkin_date'] ?? '') < date('Y-m-d')) {
    $_SESSION['booking_error'] = 'This booking cannot be edited.';
    header('Location: my_bookings.php');
    exit;
}

// Prepare form data for display
$formData = [
    'bookingID' => $bookingID,
    'roomID' => (int) $booking['roomID'],
    'checkin' => (string) $booking['checkin_date'],
    'checkout' => (string) $booking['checkout_date'],
    'rooms' => (string) $booking['number_of_rooms'],
    'adults' => (string) $booking['adults'],
    'children' => (string) $booking['children'],
    'special_requests' => (string) ($booking['special_requests'] ?? ''),
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Update form data from POST
    $formData['roomID'] = (int) ($_POST['roomID'] ?? 0);
    $formData['checkin'] = trim($_POST['checkin'] ?? '');
    $formData['checkout'] = trim($_POST['checkout'] ?? '');
    $formData['rooms'] = (string) max(1, (int) ($_POST['rooms'] ?? 1));
    $formData['adults'] = (string) max(1, (int) ($_POST['adults'] ?? 1));
    $formData['children'] = (string) max(0, (int) ($_POST['children'] ?? 0));
    $formData['special_requests'] = trim($_POST['special_requests'] ?? '');

    // Fetch selected room
    $room = getRoomById($conn, (int) $formData['roomID']);

    // Validate dates
    $errors = validateDates($formData['checkin'], $formData['checkout']);

    // Validate room existence
    if (!$room) {
        $errors[] = 'Please select a valid room.';
    }

    // Validate capacity
    if ($room) {
        $capacity = (int) $room['max_guests'] * (int) $formData['rooms'];
        if (((int) $formData['adults'] + (int) $formData['children']) > $capacity) {
            $errors[] = 'The selected room quantity cannot accommodate all guests.';
        }
    }

    // Check availability (exclude current booking)
    if (empty($errors)) {
        $availability = checkAvailability(
            $conn,
            (int) $formData['roomID'],
            $formData['checkin'],
            $formData['checkout'],
            (int) $formData['rooms'],
            $bookingID
        );

        if (!$availability['is_available']) {
            $errors[] = 'The requested room quantity is not available for the selected dates.';
        }
    }

    // If no errors → update booking
    if (empty($errors)) {

        // Calculate nights and total price
        $nights = calculateNights($formData['checkin'], $formData['checkout']);
        $totalPrice = calculateTotalPrice((float) $room['price_per_night'], (int) $formData['rooms'], $nights);

        // Prepare UPDATE query
        $stmt = $conn->prepare("
            UPDATE bookings 
            SET roomID = ?, checkin_date = ?, checkout_date = ?, number_of_rooms = ?, 
                adults = ?, children = ?, special_requests = ?, total_price = ?, 
                updated_at = CURRENT_TIMESTAMP 
            WHERE bookingID = ? AND userID = ?
        ");

        // Bind parameters
        $stmt->bind_param(
            "issiiisdii",
            $formData['roomID'],
            $formData['checkin'],
            $formData['checkout'],
            $formData['rooms'],
            $formData['adults'],
            $formData['children'],
            $formData['special_requests'],
            $totalPrice,
            $bookingID,
            $userID
        );

        // Execute update
        $ok = $stmt->execute();

        // Close statement
        $stmt->close();

        // If success → redirect
        if ($ok) {
            $_SESSION['booking_success'] = 'Booking #' . $bookingID . ' was updated successfully.';
            header('Location: my_bookings.php');
            exit;
        }

        // If failed
        $errors[] = 'Booking could not be updated. Please try again.';
    }
}

// Include header
//include 'header.php';
?>s