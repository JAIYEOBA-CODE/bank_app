<?php
require_once 'includes/auth.php';

// Handle registration
$registerMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($password !== $confirmPassword) {
        $registerMessage = '<div class="alert alert-danger">Passwords do not match</div>';
    } else {
        $result = registerUser($conn, $name, $email, $password);
        $registerMessage = '<div class="alert alert-' . ($result['success'] ? 'success' : 'danger') . '">' . $result['message'] . '</div>';
        if ($result['success']) {
            // Auto login after registration
            loginUser($conn, $email, $password);
            header('Location: dashboard.php');
            exit();
        }
    }
}

// Handle login
$loginMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $result = loginUser($conn, $email, $password);
    $loginMessage = '<div class="alert alert-' . ($result['success'] ? 'success' : 'danger') . '">' . $result['message'] . '</div>';
    if ($result['success']) {
        header('Location: dashboard.php');
        exit();
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FedBank - Digital Wallet System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="#home">
                <i class="bi bi-bank"></i> FedBank
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#login">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#register">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4 animate-fade-in">Welcome to FedBank Digital Wallet</h1>
                    <p class="lead mb-4 animate-slide-up">Experience seamless digital banking with Nigeria's most
                        trusted financial platform. Manage your money, send payments, and track your budget all in one
                        place.</p>
                    <div class="d-flex gap-3 animate-slide-up">
                        <a href="#register" class="btn btn-primary btn-lg">Get Started</a>
                        <a href="#features" class="btn btn-outline-primary btn-lg">Learn More</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image animate-fade-in">
                        <div class="card shadow-lg border-0">
                            <div class="card-body p-5">
                                <div class="text-center mb-4">
                                    <i class="bi bi-wallet2 display-1 text-primary"></i>
                                </div>
                                <h3 class="text-center mb-4">Your Digital Wallet</h3>
                                <p class="text-center text-muted">Secure, Fast, and Reliable</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">Why Choose FedBank?</h2>
                <p class="lead text-muted">Everything you need for modern digital banking</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card card h-100 border-0 shadow-sm animate-slide-up">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="bi bi-cash-coin display-4 text-primary"></i>
                            </div>
                            <h4 class="fw-bold">Virtual Deposits</h4>
                            <p class="text-muted">Add money to your wallet instantly with our virtual deposit system. No
                                payment gateway needed.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card card h-100 border-0 shadow-sm animate-slide-up">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="bi bi-send display-4 text-primary"></i>
                            </div>
                            <h4 class="fw-bold">Send Money</h4>
                            <p class="text-muted">Transfer funds to friends and family instantly. Just enter their email
                                or account number.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card card h-100 border-0 shadow-sm animate-slide-up">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="bi bi-envelope-paper display-4 text-primary"></i>
                            </div>
                            <h4 class="fw-bold">Request Money</h4>
                            <p class="text-muted">Need to collect payment? Send a money request to anyone with just
                                their email address.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card card h-100 border-0 shadow-sm animate-slide-up">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="bi bi-clock-history display-4 text-primary"></i>
                            </div>
                            <h4 class="fw-bold">Transaction History</h4>
                            <p class="text-muted">View all your transactions in one place. Filter by type and track your
                                spending patterns.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card card h-100 border-0 shadow-sm animate-slide-up">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="bi bi-piggy-bank display-4 text-primary"></i>
                            </div>
                            <h4 class="fw-bold">Budget Tracking</h4>
                            <p class="text-muted">Set monthly spending targets and track your progress. Stay on top of
                                your finances.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card card h-100 border-0 shadow-sm animate-slide-up">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="bi bi-shield-check display-4 text-primary"></i>
                            </div>
                            <h4 class="fw-bold">Secure & Safe</h4>
                            <p class="text-muted">Your data is protected with industry-standard encryption. Bank with
                                confidence.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Login Section -->
    <section id="login" class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card shadow-lg border-0 animate-slide-up">
                        <div class="card-body p-5">
                            <h2 class="text-center mb-4 fw-bold">Login to Your Account</h2>
                            <?php echo $loginMessage; ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="login_email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="login_email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="login_password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="login_password" name="password"
                                        required>
                                </div>
                                <button type="submit" name="login" class="btn btn-primary w-100 btn-lg">Login</button>
                            </form>
                            <p class="text-center mt-3">
                                Don't have an account? <a href="#register" class="text-primary">Register here</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Registration Section -->
    <section id="register" class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card shadow-lg border-0 animate-slide-up">
                        <div class="card-body p-5">
                            <h2 class="text-center mb-4 fw-bold">Create Your Account</h2>
                            <?php echo $registerMessage; ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required
                                        minlength="6">
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password"
                                        name="confirm_password" required minlength="6">
                                </div>
                                <button type="submit" name="register"
                                    class="btn btn-primary w-100 btn-lg">Register</button>
                            </form>
                            <p class="text-center mt-3">
                                Already have an account? <a href="#login" class="text-primary">Login here</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="fw-bold mb-3">FedBank</h5>
                    <p>Your trusted partner in digital banking. Serving Nigerians with secure and innovative financial
                        solutions.</p>
                </div>
                <div class="col-md-6">
                    <h5 class="fw-bold mb-3">Contact Us</h5>
                    <p><i class="bi bi-telephone"></i> +234 800 FEDBANK (3332265)</p>
                    <p><i class="bi bi-envelope"></i> support@fedbank.ng</p>
                    <p><i class="bi bi-geo-alt"></i> 123 Banking Street, Lagos, Nigeria</p>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p>&copy; 2024 FedBank. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>

</html>