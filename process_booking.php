<?php
declare(strict_types=1);

// Include database connection (provides $conn)
require_once __DIR__ . '/includes/db_connect.php';

// Include helper functions (validation, booking logic, auth, etc.)
require_once __DIR__ . '/includes/booking_functions.php';

// Ensure user is logged in
requireLogin();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: book.php');
    exit;
}

// Get current user ID
$userID = getCurrentUserId();

// Retrieve and sanitize form inputs
$roomID = (int) ($_POST['roomID'] ?? 0);
$checkin = trim($_POST['checkin'] ?? '');
$checkout = trim($_POST['checkout'] ?? '');
$numberOfRooms = max(1, (int) ($_POST['rooms'] ?? 1));
$adults = max(1, (int) ($_POST['adults'] ?? 1));
$children = max(0, (int) ($_POST['children'] ?? 0));
$specialRequests = trim($_POST['special_requests'] ?? '');

// Validate dates using helper function
$errors = validateDates($checkin, $checkout);

// Fetch room details from database
$room = getRoomById($conn, $roomID);

// Validate selected room
if (!$room) {
    $errors[] = 'Please select a valid room.';
}

// Validate capacity (guests vs room capacity)
if ($room) {
    $capacity = (int) $room['max_guests'] * $numberOfRooms;
    if (($adults + $children) > $capacity) {
        $errors[] = 'The selected room quantity cannot accommodate all guests.';
    }
}

// Check availability if no previous errors
if (empty($errors)) {
    $availability = checkAvailability($conn, $roomID, $checkin, $checkout, $numberOfRooms);
    if (!$availability['is_available']) {
        $errors[] = 'The requested room quantity is not available for the selected dates.';
    }
}

// If there are validation errors, store them and redirect back
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;

    header('Location: book.php?roomID=' . $roomID .
        '&checkin=' . urlencode($checkin) .
        '&checkout=' . urlencode($checkout) .
        '&rooms=' . $numberOfRooms .
        '&adults=' . $adults .
        '&children=' . $children);

    exit;
}

// Calculate number of nights
$nights = calculateNights($checkin, $checkout);

// Calculate total booking price
$totalPrice = calculateTotalPrice((float) $room['price_per_night'], $numberOfRooms, $nights);

// Set booking status
$bookingStatus = 'Confirmed';

// Prepare SQL INSERT query
$stmt = $conn->prepare("
    INSERT INTO bookings 
    (userID, roomID, checkin_date, checkout_date, number_of_rooms, adults, children, special_requests, total_price, booking_status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

// Bind parameters (types: i=int, s=string, d=double)
$stmt->bind_param(
    "iissiiisds",
    $userID,
    $roomID,
    $checkin,
    $checkout,
    $numberOfRooms,
    $adults,
    $children,
    $specialRequests,
    $totalPrice,
    $bookingStatus
);

// Execute query
$ok = $stmt->execute();

// Get inserted booking ID
$newID = $conn->insert_id;

// Close statement
$stmt->close();

// If booking was successful
if ($ok) {
    $_SESSION['booking_success'] = 'Booking created successfully. Your booking ID is #' . $newID . '.';
    header('Location: my_bookings.php');
    exit;
}

// If something went wrong
$_SESSION['form_errors'] = ['Booking could not be saved. Please try again.'];
header('Location: book.php?roomID=' . $roomID);
exit;