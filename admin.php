<?php
session_start();
require 'db.php';
require_once 'lang.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?redirect=admin.php");
    exit();
}

// Dashboard stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalRooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='success'")->fetchColumn();
$pendingBooks = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();

// Recent bookings
$recentBookings = $pdo->query("SELECT b.*, u.username, r.name as room_name 
    FROM bookings b JOIN users u ON b.user_id=u.id JOIN rooms r ON b.room_id=r.id 
    ORDER BY b.created_at DESC LIMIT 8")->fetchAll();

// Recent reviews
$recentReviews = $pdo->query("SELECT rv.*, u.username, r.name as room_name 
    FROM reviews rv JOIN users u ON rv.user_id=u.id JOIN rooms r ON rv.room_id=r.id 
    ORDER BY rv.created_at DESC LIMIT 5")->fetchAll();

$statusBadge = ['pending' => 'status-pending', 'confirmed' => 'status-confirmed', 'cancelled' => 'status-cancelled', 'completed' => 'status-completed'];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – <?php echo t('dashboard'); ?> | Royal Hotel</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>

<body class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-brand"><i class="fa-solid fa-hotel"></i> Royal Admin</div>
        <ul class="sidebar-menu">
            <li><a href="admin.php" class="active"><i class="fa-solid fa-gauge"></i> <?php echo t('dashboard'); ?></a>
            </li>
            <li><a href="manage_roles.php"><i class="fa-solid fa-users"></i> <?php echo t('admin_users'); ?></a></li>
            <li><a href="manage_rooms.php"><i class="fa-solid fa-bed"></i> <?php echo t('admin_rooms'); ?></a></li>
            <li><a href="manage_bookings.php"><i class="fa-solid fa-calendar-check"></i>
                    <?php echo t('admin_bookings'); ?></a></li>
            <li><a href="manage_services.php"><i class="fa-solid fa-concierge-bell"></i>
                    <?php echo t('admin_services'); ?></a></li>
            <hr class="sidebar-divider">
            <li><a href="index.php"><i class="fa-solid fa-house"></i> <?php echo t('back_to_home'); ?></a></li>
            <li class="logout-link"><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i>
                    <?php echo t('logout'); ?></a></li>
        </ul>
    </aside>

    <!-- Main -->
    <div class="admin-main">
        <div class="admin-topbar">
            <div class="d-flex align-center gap-16">
                <button class="hamburger" id="sidebarToggle"
                    style="display:flex;"><span></span><span></span><span></span></button>
                <h2><?php echo t('dashboard'); ?></h2>
            </div>
            <div class="admin-topbar-actions">
                <div class="lang-switcher">
                    <a href="<?php echo switchLangUrl('vi'); ?>" class="<?php echo $lang == 'vi' ? 'active' : ''; ?>">VI</a>
                    <span>|</span>
                    <a href="<?php echo switchLangUrl('en'); ?>" class="<?php echo $lang == 'en' ? 'active' : ''; ?>">EN</a>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="profile-avatar"
                        style="width:36px;height:36px;font-size:0.9rem;margin:0;background:linear-gradient(135deg,var(--primary),var(--primary-dark));">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-size:0.85rem;font-weight:700;">
                            <?php echo htmlspecialchars($_SESSION['username']); ?></div>
                        <div style="font-size:0.75rem;color:var(--text-muted);">Administrator</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-content">
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-icon icon-blue"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-card-info">
                        <h3><?php echo t('admin_users'); ?></h3>
                        <p><?php echo $totalUsers; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon icon-gold"><i class="fa-solid fa-bed"></i></div>
                    <div class="stat-card-info">
                        <h3>Phòng khách sạn</h3>
                        <p><?php echo $totalRooms; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon icon-green"><i class="fa-solid fa-calendar-check"></i></div>
                    <div class="stat-card-info">
                        <h3>Đặt phòng</h3>
                        <p><?php echo $totalBookings; ?></p>
                        <?php if ($pendingBooks > 0): ?><small><?php echo $pendingBooks; ?> chờ xử
                                lý</small><?php endif; ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon icon-purple"><i class="fa-solid fa-sack-dollar"></i></div>
                    <div class="stat-card-info">
                        <h3><?php echo t('total_revenue'); ?></h3>
                        <p style="font-size:1.3rem;"><?php echo number_format($totalRevenue / 1000000, 1); ?>M</p>
                        <small style="color:var(--success);">đồng</small>
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 380px;gap:24px;">
                <!-- Recent Bookings -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3><i class="fa-solid fa-clock" style="color:var(--primary);"></i> Đặt phòng gần đây</h3>
                        <a href="manage_bookings.php" class="btn btn-outline btn-sm">Xem tất cả</a>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#ID</th>
                                    <th>Khách hàng</th>
                                    <th>Phòng</th>
                                    <th>Check-in</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBookings as $bk): ?>
                                    <tr>
                                        <td><strong>#<?php echo $bk['id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($bk['username']); ?></td>
                                        <td><?php echo htmlspecialchars($bk['room_name']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($bk['check_in_date'])); ?></td>
                                        <td style="color:var(--primary);font-weight:700;">
                                            <?php echo number_format($bk['total_price'], 0, ',', '.'); ?>đ</td>
                                        <td><span
                                                class="status-badge <?php echo $statusBadge[$bk['status']] ?? ''; ?>"><?php echo $bk['status']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Reviews -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3><i class="fa-solid fa-star" style="color:var(--warning);"></i> Đánh giá gần đây</h3>
                        <a href="manage_rooms.php" class="btn btn-outline btn-sm">Xem</a>
                    </div>
                    <div style="padding:0 4px;">
                        <?php if (empty($recentReviews)): ?>
                            <div class="empty-state"><i class="fa-regular fa-star"></i>
                                <p>Chưa có đánh giá</p>
                            </div>
                        <?php else:
                            foreach ($recentReviews as $rv): ?>
                                <div style="padding:14px 20px;border-bottom:1px solid var(--gray-100);">
                                    <div class="d-flex justify-between align-center" style="margin-bottom:4px;">
                                        <strong
                                            style="font-size:0.875rem;"><?php echo htmlspecialchars($rv['username']); ?></strong>
                                        <div style="color:var(--warning);font-size:0.85rem;">
                                            <?php for ($i = 1; $i <= 5; $i++)
                                                echo $i <= $rv['rating'] ? '★' : '☆'; ?>
                                        </div>
                                    </div>
                                    <div style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">
                                        <?php echo htmlspecialchars($rv['room_name']); ?></div>
                                    <?php if ($rv['comment']): ?>
                                        <div
                                            style="font-size:0.82rem;color:var(--gray-600);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                            <?php echo htmlspecialchars($rv['comment']); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="admin-card" style="margin-top:24px;padding:24px;">
                <h3 style="margin-bottom:20px;font-size:1rem;font-weight:700;">Thao tác nhanh</h3>
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <a href="manage_rooms.php?action=add" class="btn btn-primary"><i class="fa-solid fa-plus"></i>
                        <?php echo t('add_room'); ?></a>
                    <a href="manage_bookings.php" class="btn btn-outline"><i class="fa-solid fa-calendar-check"></i>
                        <?php echo t('admin_bookings'); ?></a>
                    <a href="manage_roles.php" class="btn btn-outline"><i class="fa-solid fa-users"></i>
                        <?php echo t('admin_users'); ?></a>
                    <a href="manage_services.php" class="btn btn-outline"><i class="fa-solid fa-concierge-bell"></i>
                        <?php echo t('admin_services'); ?></a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.getElementById('adminSidebar').classList.toggle('open');
        });
    </script>
</body>

</html>