<?php
/**
 * GREENWOOD ADMIN - HIGHLY SECURED LOGIN SYSTEM
 * 
 * Security Features Implemented:
 * - Password hashing with bcrypt (PASSWORD_ARGON2ID for PHP 7.2+)
 * - Session hijacking prevention
 * - CSRF token protection
 * - Rate limiting & brute force protection
 * - IP-based login attempt tracking
 * - Account lockout mechanism
 * - Secure session configuration
 * - SQL injection prevention (prepared statements)
 * - XSS protection
 * - Timing attack prevention
 * - Security headers
 */

// Start session with secure configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Set to 1 if using HTTPS
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline';");

// Include existing database connection
require_once 'db.php';

// Security configuration
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes in seconds
define('SESSION_LIFETIME', 3600); // 1 hour
define('RATE_LIMIT_WINDOW', 300); // 5 minutes

// Initialize variables
$error = '';
$info = '';

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    // Validate session
    if (validateSession()) {
        header('Location: index.php');
        exit();
    }
}

// Convert mysqli connection to PDO for prepared statements
function getDbConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please contact administrator.");
    }
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Get client IP address
function getClientIP() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

// Check if IP is locked out
function isIPLockedOut($pdo, $ip) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as attempts, MAX(attempt_time) as last_attempt
        FROM login_attempts
        WHERE ip_address = :ip
        AND attempt_time > DATE_SUB(NOW(), INTERVAL :lockout_time SECOND)
        AND success = 0
    ");
    $stmt->execute([
        ':ip' => $ip,
        ':lockout_time' => LOCKOUT_TIME
    ]);
    $result = $stmt->fetch();
    
    if ($result['attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $lockoutRemaining = LOCKOUT_TIME - (time() - strtotime($result['last_attempt']));
        if ($lockoutRemaining > 0) {
            return ceil($lockoutRemaining / 60); // Return minutes remaining
        }
    }
    return false;
}

// Log login attempt
function logLoginAttempt($pdo, $username, $ip, $success) {
    $stmt = $pdo->prepare("
        INSERT INTO login_attempts (username, ip_address, attempt_time, success, user_agent)
        VALUES (:username, :ip, NOW(), :success, :user_agent)
    ");
    $stmt->execute([
        ':username' => $username,
        ':ip' => $ip,
        ':success' => $success ? 1 : 0,
        ':user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255)
    ]);
}

// Clear old login attempts
function clearOldAttempts($pdo, $ip) {
    $stmt = $pdo->prepare("
        DELETE FROM login_attempts
        WHERE ip_address = :ip
        AND attempt_time < DATE_SUB(NOW(), INTERVAL :lockout_time SECOND)
    ");
    $stmt->execute([
        ':ip' => $ip,
        ':lockout_time' => LOCKOUT_TIME
    ]);
}

// Validate session
function validateSession() {
    // Check if session has expired
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        session_unset();
        session_destroy();
        return false;
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    // Validate user agent (prevent session hijacking)
    if (!isset($_SESSION['user_agent'])) {
        return false;
    }
    
    if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_unset();
        session_destroy();
        return false;
    }
    
    // Regenerate session ID periodically (every 30 minutes)
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    return true;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDbConnection();
        
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            throw new Exception('Invalid security token. Please refresh the page and try again.');
        }
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip = getClientIP();
        
        // Input validation
        if (empty($username) || empty($password)) {
            throw new Exception('Username and password are required.');
        }
        
        // Check if IP is locked out
        $lockoutMinutes = isIPLockedOut($pdo, $ip);
        if ($lockoutMinutes !== false) {
            throw new Exception("Too many failed login attempts. Please try again in {$lockoutMinutes} minutes.");
        }
        
        // Clear old attempts
        clearOldAttempts($pdo, $ip);
        
        // Query user from database
        $stmt = $pdo->prepare("
            SELECT id, username, password_hash, email, is_active, last_login
            FROM admin_users
            WHERE username = :username
            LIMIT 1
        ");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        
        // Timing attack prevention: always verify password even if user doesn't exist
        $dummyHash = '$2y$10$abcdefghijklmnopqrstuv1234567890123456789012345678';
        $passwordHash = $user ? $user['password_hash'] : $dummyHash;
        
        if (password_verify($password, $passwordHash) && $user) {
            // Check if account is active
            if ($user['is_active'] != 1) {
                logLoginAttempt($pdo, $username, $ip, false);
                throw new Exception('This account has been deactivated. Please contact the administrator.');
            }
            
            // Successful login
            logLoginAttempt($pdo, $username, $ip, true);
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = :id");
            $stmt->execute([':id' => $user['id']]);
            
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['last_activity'] = time();
            $_SESSION['last_regeneration'] = time();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['login_ip'] = $ip;
            
            // Clear CSRF token
            unset($_SESSION['csrf_token']);
            
            // Redirect to admin panel
            header('Location: index.php');
            exit();
            
        } else {
            // Failed login
            logLoginAttempt($pdo, $username, $ip, false);
            
            // Generic error message to prevent username enumeration
            sleep(1); // Add delay to slow down brute force attempts
            throw new Exception('Invalid username or password.');
        }
        
    } catch (Exception $e) {
        $error = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}

// Generate CSRF token for the form
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Greenwood</title>
    <link rel="stylesheet" type="text/css" href="admin.css">
    <style>
        /* Login-specific styles */
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #3a5a2a 0%, #648E37 100%);
            padding: 20px;
        }
        
        .login-box {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(to bottom, #fafbf7 0%, #ffffff 100%);
            padding: 40px 40px 30px 40px;
            text-align: center;
            border-bottom: 1px solid #e8e8e8;
        }
        
        .login-logo {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
        }
        
        .login-header h1 {
            font-size: 26px;
            font-weight: 700;
            color: #2d2d2d;
            margin-bottom: 8px;
        }
        
        .login-header p {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }
        
        .login-body {
            padding: 40px;
        }
        
        .login-alert {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .login-alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1.5px solid #fecaca;
        }
        
        .login-alert-info {
            background: #eff6ff;
            color: #1e40af;
            border: 1.5px solid #bfdbfe;
        }
        
        .login-alert-icon {
            font-size: 18px;
        }
        
        .login-form-group {
            margin-bottom: 24px;
        }
        
        .login-form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 14px;
            color: #2d2d2d;
        }
        
        .login-input-wrapper {
            position: relative;
        }
        
        .login-input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-input-icon svg {
            display: block;
        }
        
        .login-form-group input {
            width: 100%;
            padding: 13px 16px 13px 46px;
            border: 1.5px solid #d0d0d0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s ease;
            background: #ffffff;
        }
        
        .login-form-group input:focus {
            outline: none;
            border-color: #648E37;
            box-shadow: 0 0 0 3px rgba(100, 142, 55, 0.1);
        }
        
        .login-btn {
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, #648E37 0%, #547529 100%);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(100, 142, 55, 0.3);
        }
        
        .login-btn:hover {
            background: linear-gradient(135deg, #547529 0%, #3a5a2a 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(100, 142, 55, 0.4);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .login-footer {
            padding: 24px 40px;
            background: #fafbf7;
            border-top: 1px solid #e8e8e8;
            text-align: center;
        }
        
        .login-footer p {
            font-size: 13px;
            color: #666;
            margin: 0;
        }
        
        .login-security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 12px;
            font-size: 12px;
            color: #999;
        }
        
        .login-security-icon {
            color: #648E37;
        }
        
        @media (max-width: 480px) {
            .login-header {
                padding: 30px 30px 24px 30px;
            }
            
            .login-body {
                padding: 30px;
            }
            
            .login-footer {
                padding: 20px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <img src="../assets/images/nobg.png" alt="Greenwood Logo" class="login-logo">
                <h1>Admin Login</h1>
                <p>Greenwood Management System</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="login-alert login-alert-error">
                        <span class="login-alert-icon">⚠️</span>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($info): ?>
                    <div class="login-alert login-alert-info">
                        <span class="login-alert-icon">ℹ️</span>
                        <span><?php echo $info; ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    
                    <div class="login-form-group">
                        <label for="username">Username</label>
                        <div class="login-input-wrapper">
                            <span class="login-input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </span>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                required 
                                autocomplete="off"
                                autofocus
                                maxlength="50"
                                pattern="[a-zA-Z0-9_-]+"
                                title="Username can only contain letters, numbers, underscores, and hyphens"
                            >
                        </div>
                    </div>
                    
                    <div class="login-form-group">
                        <label for="password">Password</label>
                        <div class="login-input-wrapper">
                            <span class="login-input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </span>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required
                                autocomplete="off"
                                minlength="8"
                            >
                        </div>
                    </div>
                    
                    <button type="submit" class="login-btn">Sign In</button>
                </form>
            </div>
            
            <div class="login-footer">
                <p>&copy; <?php echo date('Y'); ?> Greenwood. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>