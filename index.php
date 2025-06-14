<?php
require_once 'config/config.php';

$database = new Database();
$db = $database->getConnection();


$query = "SELECT * FROM fields WHERE status = 'active' ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Booking Lapangan Futsal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2E8B57;
            --secondary-color: #228B22;
            --accent-color: #32CD32;
            --dark-green: #006400;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><defs><pattern id="grass" patternUnits="userSpaceOnUse" width="100" height="100"><rect width="100" height="100" fill="%23228B22"/><path d="M0,50 Q25,30 50,50 T100,50" stroke="%2332CD32" stroke-width="2" fill="none"/></pattern></defs><rect width="1200" height="600" fill="url(%23grass)"/></svg>');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, var(--dark-green), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .field-card {
            position: relative;
            overflow: hidden;
        }
        
        .field-card img {
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .field-card:hover img {
            transform: scale(1.05);
        }
        
        .price-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--accent-color);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .soccer-icon {
            color: var(--accent-color);
            font-size: 3rem;
            margin-bottom: 20px;
        }
        
        .feature-box {
            text-align: center;
            padding: 30px 20px;
            background: white;
            border-radius: 15px;
            margin-bottom: 30px;
            transition: transform 0.3s ease;
        }
        
        .feature-box:hover {
            transform: translateY(-5px);
        }
        
        .footer {
            background: var(--dark-green);
            color: white;
            padding: 40px 0;
            margin-top: 80px;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-futbol text-success me-2"></i>
                <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#fields">Lapangan</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <?php if (isUser()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="booking.php">Booking</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="my-bookings.php">Riwayat Booking</a>
                            </li>
                        <?php endif; ?>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin/">Admin Panel</a>
                            </li>
                        <?php endif; ?>
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>


    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <i class="fas fa-futbol soccer-icon"></i>
                    <h1 class="display-4 fw-bold mb-4">Selamat Datang di <?php echo SITE_NAME; ?></h1>
                    <p class="lead mb-5">Platform booking lapangan futsal terbaik dengan fasilitas lengkap dan harga terjangkau. Wujudkan passion sepak bola Anda bersama kami!</p>
                    <?php if (!isLoggedIn()): ?>
                        <a href="register.php" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                        </a>
                        <a href="login.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    <?php elseif (isUser()): ?>
                        <a href="booking.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-calendar-plus me-2"></i>Booking Sekarang
                        </a>
                    <?php elseif (isAdmin()): ?>
                        <a href="admin/" class="btn btn-primary btn-lg">
                            <i class="fas fa-tachometer-alt me-2"></i>Admin Panel
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>


    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="feature-box">
                        <i class="fas fa-clock text-success" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">Booking 24/7</h4>
                        <p>Sistem booking online yang tersedia 24 jam untuk kemudahan Anda</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <i class="fas fa-shield-alt text-success" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">Pembayaran Aman</h4>
                        <p>Sistem pembayaran yang aman dan terpercaya dengan berbagai metode</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <i class="fas fa-star text-success" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">Fasilitas Terbaik</h4>
                        <p>Lapangan berkualitas tinggi dengan fasilitas lengkap dan modern</p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <section id="fields" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-success">Lapangan Tersedia</h2>
                <p class="lead">Pilih lapangan futsal terbaik sesuai kebutuhan Anda</p>
            </div>
            <div class="row">
                <?php foreach ($fields as $field): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card field-card">
                            <div class="position-relative">
                                <img src="<?php echo $field['image_path'] ?: '/placeholder.svg?height=200&width=400'; ?>" 
                                     class="card-img-top" alt="<?php echo htmlspecialchars($field['name']); ?>">
                                <div class="price-badge">
                                    <?php echo formatCurrency($field['price_per_hour']); ?>/jam
                                </div>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title fw-bold"><?php echo htmlspecialchars($field['name']); ?></h5>
                                <p class="card-text text-muted"><?php echo htmlspecialchars($field['description']); ?></p>
                                <?php if (isUser()): ?>
                                    <a href="booking.php?field_id=<?php echo $field['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-calendar-plus me-2"></i>Book Sekarang
                                    </a>
                                <?php elseif (!isLoggedIn()): ?>
                                    <a href="login.php" class="btn btn-primary">
                                        <i class="fas fa-sign-in-alt me-2"></i>Login untuk Book
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>Admin tidak dapat booking
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>


    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-futbol me-2"></i><?php echo SITE_NAME; ?></h5>
                    <p>Platform booking lapangan futsal terpercaya dengan fasilitas terbaik.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>&copy; 2024 <?php echo SITE_NAME; ?>. All rights reserved.</p>
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
