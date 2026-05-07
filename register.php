<?php
declare(strict_types=1);
include 'header.php';
// Initialize messages
$showAlert = '';
$showError = '';

// Redirect user if already logged in
if (isLoggedIn()) {
    header('Location: my_bookings.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get and clean user input
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate required fields
    if ($username === '' || $email === '' || $password === '') {
        $showError = 'Username, email, and password are required.';
    
    // Validate email format
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $showError = 'Please enter a valid email address.';
    
    } else {

        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT userID FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();

        // Get result set
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;

        // Close statement after use
        $stmt->close();

        if ($exists) {
            $showError = 'Username or email is already in use.';
        
        } else {

            // Hash password securely before storing
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user into database
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashedPassword);

            // Execute insertion
            $ok = $stmt->execute();

            // Close statement
            $stmt->close();

            if ($ok) {
                // Store success message in session
                $_SESSION['auth_message'] = 'Your account was created successfully. Please login.';
                
                // Redirect to login page
                header('Location: login.php');
                exit;
            }

            // Fallback error message
            $showError = 'Registration failed. Please try again.';
        }
    }
}

// Include header layout
//include 'header.php';
?>

<div class="container auth-page">
    <div class="card auth-card">
        <h1>Register</h1>

        <?php if ($showAlert !== ''): ?>
            <!-- Display success message -->
            <div class="alert alert-success"><?php echo e($showAlert); ?></div>
        <?php endif; ?>

        <?php if ($showError !== ''): ?>
            <!-- Display error message -->
            <div class="alert alert-error"><?php echo e($showError); ?></div>
        <?php endif; ?>

        <!-- Registration form -->
        <form method="POST" action="register.php" class="auth-form">
            
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <input type="submit" value="Register" class="primary-btn">
        </form>
    </div>
</div>

<?php 
// Include footer layout
include 'footer.php'; 
?>
