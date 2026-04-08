<?php
session_start();
require 'db.php';
require_once 'lang.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');

    if (empty($username) || empty($password) || empty($confirm)) {
        $error = 'Vui lòng điền đầy đủ các trường bắt buộc!';
    } elseif (strlen($username) < 4) {
        $error = 'Tên đăng nhập phải có ít nhất 4 ký tự!';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
    } elseif ($password !== $confirm) {
        $error = t('pass_mismatch');
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $error = t('user_exists');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (username, password, email, phone, role) VALUES (?, ?, ?, ?, 'user')")
                ->execute([$username, $hash, $email, $phone]);
            header("Location: login.php?registered=1");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('register_title'); ?> - Royal Hotel</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>
<body>
    <div class="lang-top">
        <a href="?lang=vi" class="<?php echo $lang=='vi'?'active':''; ?>">VI</a>
        <span>|</span>
        <a href="?lang=en" class="<?php echo $lang=='en'?'active':''; ?>">EN</a>
    </div>

    <div class="auth-page">
        <div class="auth-card" style="max-width:500px;">
            <div class="auth-brand">
                <i class="fa-solid fa-hotel"></i>
                <h1>Royal Hotel</h1>
                <p><?php echo t('register_subtitle'); ?></p>
            </div>

            <?php if ($error): ?>
                <div class="auth-alert error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" id="registerForm">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label><?php echo t('username'); ?> *</label>
                        <div class="input-group">
                            <i class="fa-solid fa-user input-icon"></i>
                            <input type="text" name="username" class="form-control" placeholder="username123" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                   required minlength="4">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?php echo t('phone'); ?></label>
                        <div class="input-group">
                            <i class="fa-solid fa-phone input-icon"></i>
                            <input type="tel" name="phone" class="form-control" placeholder="0912345678"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo t('email'); ?></label>
                    <div class="input-group">
                        <i class="fa-solid fa-envelope input-icon"></i>
                        <input type="email" name="email" class="form-control" placeholder="email@example.com"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo t('password'); ?> *</label>
                    <div class="input-group">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" name="password" id="regPass" class="form-control" 
                               placeholder="Ít nhất 6 ký tự" required minlength="6">
                        <i class="fa-solid fa-eye" id="toggleRegPass" 
                           style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:rgba(255,255,255,0.3);font-size:0.85rem;" 
                           onclick="togglePassword('regPass','toggleRegPass')"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo t('confirm_password'); ?> *</label>
                    <div class="input-group">
                        <i class="fa-solid fa-shield-halved input-icon"></i>
                        <input type="password" name="confirm_password" id="confirmPass" class="form-control" 
                               placeholder="Nhập lại mật khẩu" required>
                    </div>
                </div>

                <!-- Password strength indicator -->
                <div id="strengthBar" style="height:4px;background:var(--border);border-radius:2px;margin:-10px 0 16px;overflow:hidden;">
                    <div id="strengthFill" style="height:100%;width:0;transition:all 0.3s ease;border-radius:2px;"></div>
                </div>

                <button type="submit" class="btn btn-primary w-100" style="padding:13px;font-size:0.95rem;border-radius:10px;">
                    <i class="fa-solid fa-user-plus"></i> <?php echo t('register_btn'); ?>
                </button>
            </form>

            <div class="auth-footer">
                <p><?php echo t('have_account'); ?> <a href="login.php"><?php echo t('login_now'); ?></a></p>
                <p style="margin-top:12px;"><a href="index.php" style="color:rgba(255,255,255,0.4);font-size:0.82rem;"><?php echo t('back_home'); ?></a></p>
            </div>
        </div>
    </div>

    <script>
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        input.type = input.type === 'password' ? 'text' : 'password';
        icon.className = input.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
        icon.style.cssText = icon.getAttribute('style') || '';
    }

    // Password strength
    document.getElementById('regPass').addEventListener('input', function() {
        const val = this.value;
        const fill = document.getElementById('strengthFill');
        let strength = 0;
        if(val.length >= 6) strength++;
        if(val.length >= 10) strength++;
        if(/[A-Z]/.test(val)) strength++;
        if(/[0-9]/.test(val)) strength++;
        if(/[^A-Za-z0-9]/.test(val)) strength++;
        const pct = (strength/5)*100;
        const colors = ['#EF4444','#F97316','#EAB308','#22C55E','#10B981'];
        fill.style.width = pct + '%';
        fill.style.background = colors[Math.min(strength-1,4)] || '#EF4444';
    });
    </script>
</body>
</html>