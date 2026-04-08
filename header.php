<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'lang.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Royal Hotel - <?php echo t('home'); ?></title>
    <meta name="description" content="Royal Hotel - Khách sạn 5 sao với dịch vụ đẳng cấp, phòng sang trọng và không gian nghỉ dưỡng tuyệt vời.">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>
<body class="bg-gray">
    <nav class="navbar" id="mainNav">
        <div class="logo">
            <a href="index.php"><i class="fa-solid fa-hotel"></i> Royal Hotel</a>
        </div>
        <ul class="nav-links" id="navLinks">
            <li><a href="index.php" class="active"><?php echo t('home'); ?></a></li>
            <li><a href="index.php#rooms"><?php echo t('rooms'); ?></a></li>
            <li><a href="index.php#services"><?php echo t('services'); ?></a></li>
            <li><a href="index.php#reviews"><?php echo t('reviews'); ?></a></li>
            <li><a href="#footer"><?php echo t('contact'); ?></a></li>
            <li>
                <div class="lang-switcher">
                    <a href="<?php echo switchLangUrl('vi'); ?>" class="<?php echo $lang=='vi'?'active':''; ?>">VI</a>
                    <span>|</span>
                    <a href="<?php echo switchLangUrl('en'); ?>" class="<?php echo $lang=='en'?'active':''; ?>">EN</a>
                </div>
            </li>
        </ul>
        <div class="nav-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="nav-user-info"><?php echo t('hello'); ?>, <b><?php echo htmlspecialchars($_SESSION['username']); ?></b></span>
                <a href="profile.php" class="btn btn-outline btn-sm"><?php echo t('profile'); ?></a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin.php" class="btn btn-dark btn-sm"><i class="fa-solid fa-gauge"></i> <?php echo t('admin_panel'); ?></a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-right-from-bracket"></i> <?php echo t('logout'); ?></a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline btn-sm"><?php echo t('login'); ?></a>
                <a href="register.php" class="btn btn-primary btn-sm"><?php echo t('register'); ?></a>
            <?php endif; ?>
        </div>
        <button class="hamburger" id="hamburger" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </nav>
    <div class="main-content">
