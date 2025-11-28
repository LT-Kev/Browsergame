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
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>🎮 <?php echo SITE_NAME; ?></h2>

            <?php if($error): ?>
                <div class="login-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="login-success"><?php echo e($success); ?></div>
            <?php endif; ?>

            <div class="login-tabs">
                <button class="login-tab active" onclick="showLoginTab(event,'login')">Login</button>
                <button class="login-tab" onclick="showLoginTab(event,'register')">Registrieren</button>
            </div>

            <!-- Login Tab -->
            <div id="login-tab" class="login-tab-content active">
                <form method="POST">
                    <div class="login-form-group">
                        <label>Username</label>
                        <input type="text" name="username" required autofocus>
                    </div>

                    <div class="login-form-group">
                        <label>Passwort</label>
                        <input type="password" name="password" required>
                    </div>

                    <div class="login-remember-me">
                        <input type="checkbox" name="remember_me" id="remember_me" value="1">
                        <label for="remember_me">🔒 Angemeldet bleiben (30 Tage)</label>
                        <div class="login-remember-info">⚠️ Nur auf vertrauenswürdigen Geräten aktivieren</div>
                    </div>

                    <button type="submit" name="login" class="login-btn">Einloggen</button>
                </form>
                
                <p style="text-align:center; margin-top:20px; color:#95a5a6;">
                    Test-Account: <strong>TestSpieler</strong> / <strong>test123</strong>
                </p>
            </div>

            <!-- Register Tab -->
            <div id="register-tab" class="login-tab-content">
                <form method="POST">
                    <div class="login-form-group">
                        <label>Username</label>
                        <input type="text" name="reg_username" required>
                    </div>

                    <div class="login-form-group">
                        <label>Email</label>
                        <input type="email" name="reg_email" required>
                    </div>

                    <div class="login-form-group">
                        <label>Passwort</label>
                        <input type="password" name="reg_password" required>
                    </div>

                    <div class="login-form-group">
                        <label>Passwort wiederholen</label>
                        <input type="password" name="reg_password_repeat" required>
                    </div>

                    <button type="submit" name="register" class="login-btn login-btn-secondary">Registrieren</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showLoginTab(evt, tabName) {
            const tabs = document.querySelectorAll('.login-tab');
            const contents = document.querySelectorAll('.login-tab-content');

            tabs.forEach(tab => tab.classList.remove('active'));
            contents.forEach(content => content.classList.remove('active'));

            evt.currentTarget.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        }
    </script>
</body>
</html>
