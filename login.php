<?php
session_start();
require 'db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND password = :password");
    $stmt->execute(['username' => $username, 'password' => $password]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $user['username'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login - STTI System</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }

        /* AUTO-NEXT BACKGROUND SLIDESHOW */
        .bg-slider {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
        }

        .bg-slider div {
            position: absolute;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            animation: slideShow 15s infinite;
        }

        .bg-slider div:nth-child(1) {
            background-image: url('slider/stti1.jpg');
            animation-delay: 0s;
        }

        .bg-slider div:nth-child(2) {
            background-image: url('slider/smileitlab.jpg');
            animation-delay: 5s;
        }

        .bg-slider div:nth-child(3) {
            background-image: url('slider/taytay.avif');
            animation-delay: 10s;
        }

        @keyframes slideShow {
            0% {
                opacity: 0;
                transform: scale(1.05);
            }

            10% {
                opacity: 1;
                transform: scale(1);
            }

            33% {
                opacity: 1;
                transform: scale(1);
            }

            43% {
                opacity: 0;
                transform: scale(1.05);
            }

            100% {
                opacity: 0;
            }
        }

        /* Deepened Overlay for better text contrast */
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 48, 87, 0.65);
            z-index: -1;
            backdrop-filter: blur(2px);
        }

        /* MODERN GLASSMORPHISM LOGIN CARD */
        .login-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            padding: 45px 40px;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.5);
            width: 360px;
            text-align: center;
            animation: fadeIn 0.8s ease-out forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* LOGO SECTION */
        .login-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .login-logos img {
            height: 45px;
            width: 45px;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .login-logos img:nth-child(2) {
            mix-blend-mode: multiply;
            transform: scale(1.3);
        }

        .login-logos img:nth-child(3) {
            transform: scale(1.1);
        }

        .login-card h2 {
            color: #0f3057;
            margin: 0 0 25px 0;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input {
            width: 100%;
            padding: 14px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            box-sizing: border-box;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.15);
            background: #ffffff;
        }

        /* DYNAMIC GRADIENT BUTTON */
        .btn-login {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            color: white;
            border: none;
            padding: 15px;
            width: 100%;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            font-size: 15px;
            margin-top: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.4);
        }

        .btn-login:active {
            transform: translateY(1px);
        }

        .error {
            background: #fef2f2;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
            border: 1px solid #fca5a5;
            animation: shake 0.4s ease-in-out;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            50% {
                transform: translateX(5px);
            }

            75% {
                transform: translateX(-5px);
            }
        }
    </style>
</head>

<body>

    <div class="bg-slider">
        <div></div>
        <div></div>
        <div></div>
    </div>
    <div class="overlay"></div>

    <div class="login-card">
        <div class="login-logos">
            <img src="logo/STTI_LOGO__1_-removebg-preview.png" alt="Taytay" onerror="this.src='https://via.placeholder.com/40'">
            <img src="logo/logorizal.avif" alt="STTI" onerror="this.src='https://via.placeholder.com/40'">
            <img src="logo/logo2.png" alt="Smile" onerror="this.src='https://via.placeholder.com/40'">
        </div>

        <h2>Municipal Education Office System</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Enter your username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn-login">Access System</button>
        </form>
    </div>

</body>

</html>