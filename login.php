<?php
session_start();
require 'db.php';
require_once 'lang.php';

$error = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';

// Chống lỗ hổng Open Redirect (Chỉ cho phép chuyển hướng nội bộ)
if (parse_url($redirect, PHP_URL_HOST) !== null) {
    $redirect = 'index.php';
}

if (isset($_SESSION['user_id'])) {
    header("Location: $redirect");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];

    if (empty($user) || empty($pass)) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$user]);
        $userData = $stmt->fetch();

        if ($userData && password_verify($pass, $userData['password'])) {
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['username'] = $userData['username'];
            $_SESSION['role'] = $userData['role'];
            header("Location: $redirect");
            exit();
        } else {
            $error = t('wrong_pass');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('login_title'); ?> - Royal Hotel</title>
    <meta name="description" content="Đăng nhập vào tài khoản Royal Hotel của bạn">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>
<body>
    <div class="lang-top">
        <a href="?lang=vi<?php echo isset($_GET['redirect']) ? '&redirect='.urlencode($redirect) : ''; ?>" class="<?php echo $lang=='vi'?'active':''; ?>">VI</a>
        <span>|</span>
        <a href="?lang=en<?php echo isset($_GET['redirect']) ? '&redirect='.urlencode($redirect) : ''; ?>" class="<?php echo $lang=='en'?'active':''; ?>">EN</a>
    </div>

    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-brand">
                <i class="fa-solid fa-hotel"></i>
                <h1>Royal Hotel</h1>
                <p><?php echo t('login_subtitle'); ?></p>
            </div>

            <?php if ($error): ?>
                <div class="auth-alert error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['registered'])): ?>
                <div class="auth-alert success"><i class="fa-solid fa-circle-check"></i> <?php echo t('register_success'); ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                <div class="form-group">
                    <label><?php echo t('username'); ?></label>
                    <div class="input-group">
                        <i class="fa-solid fa-user input-icon"></i>
                        <input type="text" name="username" class="form-control" 
                               placeholder="<?php echo t('username'); ?>" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                               required autocomplete="username">
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo t('password'); ?></label>
                    <div class="input-group">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" name="password" id="loginPass" class="form-control" 
                               placeholder="••••••••" required autocomplete="current-password">
                        <i class="fa-solid fa-eye" id="togglePass" 
                           style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:rgba(255,255,255,0.3);font-size:0.85rem;" 
                           onclick="togglePassword('loginPass','togglePass')"></i>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100" style="padding:13px;font-size:0.95rem;margin-top:8px;border-radius:10px;">
                    <i class="fa-solid fa-right-to-bracket"></i> <?php echo t('login_btn'); ?>
                </button>
            </form>

            <div class="auth-footer">
                <p><?php echo t('no_account'); ?> <a href="register.php"><?php echo t('register_now'); ?></a></p>
                <p style="margin-top:12px;"><a href="index.php" style="color:rgba(255,255,255,0.4);font-size:0.82rem;"><?php echo t('back_home'); ?></a></p>
            </div>
        </div>
    </div>

    <script>
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if(input.type === 'password') {
            input.type = 'text';
            icon.className = 'fa-solid fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fa-solid fa-eye';
        }
    }
    </script>
</body>
</html>