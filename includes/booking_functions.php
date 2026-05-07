<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function isLoggedIn(): bool
{
    return isset($_SESSION['userID']) && (int) $_SESSION['userID'] > 0;
}

function requireLogin(string $redirect = 'login.php'): void
{
    if (!isLoggedIn()) {
        $_SESSION['auth_message'] = 'Please login first to continue.';
        header('Location: ' . $redirect);
        exit;
    }
}

function getCurrentUserId(): int
{
    return (int) ($_SESSION['userID'] ?? 0);
}

function getCurrentUsername(): string
{
    return (string) ($_SESSION['username'] ?? '');
}

function getFlashMessage(string $key): string
{
    if (!isset($_SESSION[$key])) {
        return '';
    }

    $message = (string) $_SESSION[$key];
    unset($_SESSION[$key]);
    return $message;
}

function getRooms(mysqli $conn): array
{
    $rooms = [];
    $result = mysqli_query($conn, "SELECT roomID, roomName, description, price_per_night, max_guests, stock, image_path FROM rooms WHERE is_active = 1 ORDER BY roomID ASC");

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rooms[] = $row;
        }
        mysqli_free_result($result);
    }

    return $rooms;
}

function getRoomById(mysqli $conn, int $roomID): ?array
{
    $stmt = mysqli_prepare($conn, "SELECT roomID, roomName, description, price_per_night, max_guests, stock, image_path FROM rooms WHERE roomID = ? AND is_active = 1 LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $roomID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $room = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $room ?: null;
}

function validateDates(string $checkin, string $checkout): array
{
    $errors = [];
    $today = date('Y-m-d');

    if ($checkin === '' || $checkout === '') {
        $errors[] = 'Check-in and check-out dates are required.';
        return $errors;
    }

    if ($checkin < $today) {
        $errors[] = 'Check-in date cannot be before today.';
    }

    if ($checkout <= $checkin) {
        $errors[] = 'Check-out date must be later than check-in date.';
    }

    return $errors;
}

function calculateNights(string $checkin, string $checkout): int
{
    $start = new DateTime($checkin);
    $end = new DateTime($checkout);
    return (int) $start->diff($end)->days;
}

function calculateTotalPrice(float $pricePerNight, int $rooms, int $nights): float
{
    return $pricePerNight * $rooms * $nights;
}

function getBookedRoomCount(mysqli $conn, int $roomID, string $checkin, string $checkout, ?int $excludeBookingID = null): int
{
    $sql = "SELECT COALESCE(SUM(number_of_rooms), 0) AS booked_rooms
            FROM bookings
            WHERE roomID = ?
              AND booking_status <> 'Cancelled'
              AND checkin_date < ?
              AND checkout_date > ?";

    if ($excludeBookingID !== null) {
        $sql .= " AND bookingID <> ?";
    }

    $stmt = mysqli_prepare($conn, $sql);

    if ($excludeBookingID !== null) {
        mysqli_stmt_bind_param($stmt, 'issi', $roomID, $checkout, $checkin, $excludeBookingID);
    } else {
        mysqli_stmt_bind_param($stmt, 'iss', $roomID, $checkout, $checkin);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return (int) ($row['booked_rooms'] ?? 0);
}

function checkAvailability(mysqli $conn, int $roomID, string $checkin, string $checkout, int $requestedRooms, ?int $excludeBookingID = null): array
{
    $room = getRoomById($conn, $roomID);

    if (!$room) {
        return [
            'roomID' => $roomID,
            'roomName' => 'Unknown Room',
            'total_stock' => 0,
            'already_booked' => 0,
            'available_rooms' => 0,
            'requested_rooms' => $requestedRooms,
            'is_available' => false,
        ];
    }

    $totalStock = (int) $room['stock'];
    $alreadyBooked = getBookedRoomCount($conn, $roomID, $checkin, $checkout, $excludeBookingID);
    $availableRooms = max(0, $totalStock - $alreadyBooked);

    return [
        'roomID' => $roomID,
        'roomName' => (string) $room['roomName'],
        'total_stock' => $totalStock,
        'already_booked' => $alreadyBooked,
        'available_rooms' => $availableRooms,
        'requested_rooms' => $requestedRooms,
        'is_available' => $requestedRooms <= $availableRooms,
    ];
}

function getBookingsForUser(mysqli $conn, int $userID, string $status = ''): array
{
    $sql = "SELECT b.bookingID, b.userID, b.roomID, b.checkin_date, b.checkout_date,
                   b.number_of_rooms, b.adults, b.children, b.special_requests,
                   b.total_price, b.booking_status, b.created_at, b.updated_at,
                   r.roomName, r.price_per_night,
                   u.username, u.email
            FROM bookings b
            INNER JOIN rooms r ON b.roomID = r.roomID
            INNER JOIN users u ON b.userID = u.userID
            WHERE b.userID = ?";

    if ($status !== '') {
        $sql .= " AND b.booking_status = ?";
    }

    $sql .= " ORDER BY b.created_at DESC, b.bookingID DESC";

    $stmt = mysqli_prepare($conn, $sql);

    if ($status !== '') {
        mysqli_stmt_bind_param($stmt, 'is', $userID, $status);
    } else {
        mysqli_stmt_bind_param($stmt, 'i', $userID);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $bookings = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $bookings[] = $row;
        }
    }

    mysqli_stmt_close($stmt);
    return $bookings;
}

function getBookingById(mysqli $conn, int $bookingID, int $userID): ?array
{
    $sql = "SELECT b.bookingID, b.userID, b.roomID, b.checkin_date, b.checkout_date,
                   b.number_of_rooms, b.adults, b.children, b.special_requests,
                   b.total_price, b.booking_status, b.created_at, b.updated_at,
                   r.roomName, r.price_per_night, r.max_guests, r.stock
            FROM bookings b
            INNER JOIN rooms r ON b.roomID = r.roomID
            WHERE b.bookingID = ? AND b.userID = ?
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $bookingID, $userID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $booking = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $booking ?: null;
}

function getDisplayStatus(array $booking): string
{
    if (($booking['booking_status'] ?? '') === 'Cancelled') {
        return 'Cancelled';
    }

    if (($booking['checkout_date'] ?? '') < date('Y-m-d')) {
        return 'Completed';
    }

    return 'Confirmed';
}

function getBookingSummary(array $bookings): array
{
    $summary = ['total' => count($bookings), 'confirmed' => 0, 'cancelled' => 0, 'completed' => 0];

    foreach ($bookings as $booking) {
        $status = getDisplayStatus($booking);
        if ($status === 'Confirmed') {
            $summary['confirmed']++;
        } elseif ($status === 'Cancelled') {
            $summary['cancelled']++;
        } else {
            $summary['completed']++;
        }
    }

    return $summary;
}
