<<<<<<< HEAD
<?php
require_once __DIR__ . '/init.php';

$app = new App();
$auth = $app->getAuth();

$error = '';
$success = '';

// Wenn bereits eingeloggt, weiterleiten
if($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Login-Formular verarbeiten
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    // CSRF-Token prüfen (wenn implementiert)
    // if(!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
    //     $error = 'CSRF-Token ungültig';
    // } else {
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $rememberMe = isset($_POST['remember_me']) && $_POST['remember_me'] === '1';
    
    if(empty($username) || empty($password)) {
        $error = 'Bitte alle Felder ausfüllen';
    } else {
        if($auth->login($username, $password, $rememberMe)) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Falscher Username oder Passwort';
        }
    }
    // }
}

// Registrierung verarbeiten (unverändert)
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['reg_username']);
    $email = trim($_POST['reg_email']);
    $password = $_POST['reg_password'];
    $passwordRepeat = $_POST['reg_password_repeat'];
    
    $result = $auth->register($username, $email, $password, $passwordRepeat);
    
    if($result['success']) {
        $success = $result['message'] . ' - Du kannst dich jetzt einloggen.';
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
        }
        
        .login-box {
            background: #252525;
            border: 2px solid #444;
            border-radius: 10px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        
        .login-box h2 {
            color: #3498db;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2em;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #fff;
            font-weight: bold;
        }
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px;
            background: #333;
            border: 1px solid #555;
            border-radius: 5px;
            color: #fff;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        /* Remember Me Checkbox */
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 10px;
            cursor: pointer;
            accent-color: #3498db;
        }
        
        .remember-me label {
            color: #bdc3c7;
            cursor: pointer;
            user-select: none;
            font-weight: normal;
            margin: 0;
        }
        
        .remember-me-info {
            font-size: 0.85em;
            color: #95a5a6;
            margin-top: 5px;
            padding-left: 28px;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: #3498db;
            border: none;
            border-radius: 5px;
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #95a5a6;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .error {
            background: #e74c3c;
            color: #fff;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success {
            background: #2ecc71;
            color: #fff;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid #444;
        }
        
        .tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            background: #333;
            border: none;
            color: #fff;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .tab.active {
            background: #3498db;
            border-bottom: 3px solid #2980b9;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>🎮 <?php echo SITE_NAME; ?></h2>
            
            <?php if($error): ?>
                <div class="error"><?php echo e($error); ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="success"><?php echo e($success); ?></div>
            <?php endif; ?>
            
            <div class="tabs">
                <button class="tab active" onclick="showTab('login')">Login</button>
                <button class="tab" onclick="showTab('register')">Registrieren</button>
            </div>
            
            <!-- Login Tab -->
            <div id="login-tab" class="tab-content active">
                <form method="POST">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label>Passwort</label>
                        <input type="password" name="password" required>
                    </div>
                    
                    <div class="remember-me">
                        <input type="checkbox" name="remember_me" id="remember_me" value="1">
                        <label for="remember_me">
                            🔒 Angemeldet bleiben (30 Tage)
                        </label>
                    </div>
                    <div class="remember-me-info">
                        ⚠️ Nur auf vertrauenswürdigen Geräten aktivieren
                    </div>
                    
                    <button type="submit" name="login" class="btn">Einloggen</button>
                </form>
                
                <p style="text-align: center; margin-top: 20px; color: #95a5a6;">
                    Test-Account: <strong>TestSpieler</strong> / <strong>test123</strong>
                </p>
            </div>
            
            <!-- Register Tab -->
            <div id="register-tab" class="tab-content">
                <form method="POST">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="reg_username" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="reg_email" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Passwort</label>
                        <input type="password" name="reg_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Passwort wiederholen</label>
                        <input type="password" name="reg_password_repeat" required>
                    </div>
                    
                    <button type="submit" name="register" class="btn btn-secondary">Registrieren</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Alle Tabs und Contents verstecken
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Aktiven Tab zeigen
            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        }
    </script>
</body>
=======
<?php
require_once __DIR__ . '/init.php';

$app = new App();
$auth = $app->getAuth();

$error = '';
$success = '';

// Wenn bereits eingeloggt, weiterleiten
if($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Login-Formular verarbeiten
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if(empty($username) || empty($password)) {
        $error = 'Bitte alle Felder ausfüllen';
    } else {
        if($auth->login($username, $password)) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Falscher Username oder Passwort';
        }
    }
}

// Registrierung verarbeiten
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['reg_username']);
    $email = trim($_POST['reg_email']);
    $password = $_POST['reg_password'];
    $passwordRepeat = $_POST['reg_password_repeat'];
    
    $result = $auth->register($username, $email, $password, $passwordRepeat);
    
    if($result['success']) {
        $success = $result['message'] . ' - Du kannst dich jetzt einloggen.';
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
        }
        
        .login-box {
            background: #252525;
            border: 2px solid #444;
            border-radius: 10px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        
        .login-box h2 {
            color: #3498db;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2em;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #fff;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            background: #333;
            border: 1px solid #555;
            border-radius: 5px;
            color: #fff;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: #3498db;
            border: none;
            border-radius: 5px;
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #95a5a6;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .error {
            background: #e74c3c;
            color: #fff;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success {
            background: #2ecc71;
            color: #fff;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid #444;
        }
        
        .tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            background: #333;
            border: none;
            color: #fff;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .tab.active {
            background: #3498db;
            border-bottom: 3px solid #2980b9;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>🎮 <?php echo SITE_NAME; ?></h2>
            
            <?php if($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="tabs">
                <button class="tab active" onclick="showTab('login')">Login</button>
                <button class="tab" onclick="showTab('register')">Registrieren</button>
            </div>
            
            <!-- Login Tab -->
            <div id="login-tab" class="tab-content active">
                <form method="POST">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label>Passwort</label>
                        <input type="password" name="password" required>
                    </div>
                    
                    <button type="submit" name="login" class="btn">Einloggen</button>
                </form>
                
                <p style="text-align: center; margin-top: 20px; color: #95a5a6;">
                    Test-Account: <strong>TestSpieler</strong> / <strong>test123</strong>
                </p>
            </div>
            
            <!-- Register Tab -->
            <div id="register-tab" class="tab-content">
                <form method="POST">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="reg_username" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="reg_email" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Passwort</label>
                        <input type="password" name="reg_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Passwort wiederholen</label>
                        <input type="password" name="reg_password_repeat" required>
                    </div>
                    
                    <button type="submit" name="register" class="btn btn-secondary">Registrieren</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Alle Tabs und Contents verstecken
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Aktiven Tab zeigen
            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        }
    </script>
</body>
>>>>>>> 971ab47689bd561bd08c6e4d77cea7f516414d66
</html>