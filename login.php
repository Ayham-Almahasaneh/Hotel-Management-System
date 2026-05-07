<?php
declare(strict_types=1);
include 'header.php';
// Initialize error message
$showError = '';

// Get flash message (e.g., after registration)
$authMessage = getFlashMessage('auth_message');

// Redirect user if already logged in
if (isLoggedIn()) {
    header('Location: my_bookings.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get and clean user input
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate required fields
    if ($username === '' || $password === '') {
        $showError = 'Username and password are required.';
    
    } else {

        // Prepare SQL query to fetch user by username
        $stmt = $conn->prepare("SELECT userID, username, email, password FROM users WHERE username = ? LIMIT 1");

        // Bind input parameter (string)
        $stmt->bind_param("s", $username);

        // Execute query
        $stmt->execute();

        // Get result set
        $result = $stmt->get_result();

        // Fetch user data as associative array
        $user = $result ? $result->fetch_assoc() : null;

        // Close statement
        $stmt->close();

        // Verify password using hashed password
        if ($user && password_verify($password, (string) $user['password'])) {

            // Store user data in session
            $_SESSION['userID'] = (int) $user['userID'];
            $_SESSION['username'] = (string) $user['username'];
            $_SESSION['email'] = (string) $user['email'];

            // Store welcome message
            $_SESSION['booking_success'] = 'Welcome back, ' . $user['username'] . '.';

            // Redirect to user dashboard
            header('Location: my_bookings.php');
            exit;
        }

        // Show error if login fails
        $showError = 'Invalid username or password.';
    }
}

// Include header layout
//include 'header.php';
?>

<div class="container auth-page">
    <div class="card auth-card">
        <h1>Login</h1>

        <?php if ($authMessage !== ''): ?>
            <!-- Display success message -->
            <div class="alert alert-success"><?php echo e($authMessage); ?></div>
        <?php endif; ?>

        <?php if ($showError !== ''): ?>
            <!-- Display error message -->
            <div class="alert alert-error"><?php echo e($showError); ?></div>
        <?php endif; ?>

        <!-- Login form -->
        <form method="POST" action="login.php" class="auth-form">
            
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <input type="submit" value="Login" class="primary-btn">
        </form>
    </div>
</div>

<?php 
// Include footer layout
include 'footer.php'; 
?>