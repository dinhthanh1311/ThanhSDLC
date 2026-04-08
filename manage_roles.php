<?php
session_start();
require 'db.php';
require_once 'lang.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success = $error = '';

// Xóa user
if (isset($_GET['delete'])) {
    $del_id = (int) $_GET['delete'];
    if ($del_id !== $_SESSION['user_id']) {
        try {
            $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$del_id]);
            $success = 'Đã xóa người dùng thành công!';
        } catch (PDOException $e) {
            $error = 'Không thể xóa người dùng này do có dữ liệu ràng buộc (đặt phòng, đánh giá).';
        }
    } else {
        $error = 'Không thể xóa tài khoản của chính mình!';
    }
}

// Đổi role
if (isset($_POST['change_role'])) {
    $uid = (int) $_POST['user_id'];
    $role = $_POST['role'];
    if (in_array($role, ['user', 'admin']) && $uid !== $_SESSION['user_id']) {
        $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role, $uid]);
        $success = 'Đã cập nhật quyền thành công!';
    }
}

$users = $pdo->query("SELECT u.*, 
    (SELECT COUNT(*) FROM bookings WHERE user_id=u.id) as booking_count
    FROM users u ORDER BY u.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Users | Royal Hotel Admin</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>

<body class="admin-layout">
    <aside class="admin-sidebar">
        <div class="sidebar-brand"><i class="fa-solid fa-hotel"></i> Royal Admin</div>
        <ul class="sidebar-menu">
            <li><a href="admin.php"><i class="fa-solid fa-gauge"></i> <?php echo t('dashboard'); ?></a></li>
            <li><a href="manage_roles.php" class="active"><i class="fa-solid fa-users"></i>
                    <?php echo t('admin_users'); ?></a></li>
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

    <div class="admin-main">
        <div class="admin-topbar">
            <h2><i class="fa-solid fa-users" style="color:var(--primary);"></i> <?php echo t('admin_users'); ?></h2>
            <div class="admin-topbar-actions">
                <a href="register.php" class="btn btn-primary btn-sm" target="_blank"><i
                        class="fa-solid fa-user-plus"></i> Tạo tài khoản mới</a>
            </div>
        </div>

        <div class="admin-content">
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i>
                    <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i>
                    <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Danh sách người dùng (<?php echo count($users); ?>)</h3>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tên đăng nhập</th>
                                <th>Email</th>
                                <th>SĐT</th>
                                <th>Đặt phòng</th>
                                <th>Quyền</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo $u['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-center gap-10">
                                            <div class="review-avatar" style="width:32px;height:32px;font-size:0.85rem;">
                                                <?php echo strtoupper(substr($u['username'], 0, 1)); ?></div>
                                            <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                                            <?php if ($u['id'] == $_SESSION['user_id']): ?><span
                                                    class="status-badge status-confirmed"
                                                    style="font-size:0.7rem;">Bạn</span><?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($u['email'] ?? '–'); ?></td>
                                    <td><?php echo htmlspecialchars($u['phone'] ?? '–'); ?></td>
                                    <td><span
                                            class="status-badge status-confirmed"><?php echo $u['booking_count']; ?></span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <select name="role" class="form-control"
                                                style="padding:5px 10px;font-size:0.8rem;width:auto;border:1.5px solid var(--border);border-radius:6px;"
                                                onchange="this.form.submit()" <?php echo $u['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                                <option value="user" <?php echo $u['role'] == 'user' ? 'selected' : ''; ?>>User
                                                </option>
                                                <option value="admin" <?php echo $u['role'] == 'admin' ? 'selected' : ''; ?>>Admin
                                                </option>
                                            </select>
                                            <input type="hidden" name="change_role" value="1">
                                        </form>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                                <a href="?delete=<?php echo $u['id']; ?>" class="btn btn-sm"
                                                    style="background:rgba(239,68,68,0.1);color:#EF4444;border:1px solid rgba(239,68,68,0.2);"
                                                    onclick="return confirm('Xác nhận xóa người dùng này?')">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm" disabled style="opacity:0.4;"><i
                                                        class="fa-solid fa-trash"></i></button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>