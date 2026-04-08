<?php
session_start();
require 'db.php';
require_once 'lang.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success = $error = '';

// Cập nhật trạng thái booking
if (isset($_POST['update_status'])) {
    $bid = (int) $_POST['booking_id'];
    $status = $_POST['status'];
    if (in_array($status, ['pending', 'confirmed', 'cancelled', 'completed'])) {
        $pdo->prepare("UPDATE bookings SET status=? WHERE id=?")->execute([$status, $bid]);
        // If confirmed, also mark payment success
        if ($status === 'confirmed') {
            $pdo->prepare("UPDATE payments SET payment_status='success' WHERE booking_id=?")->execute([$bid]);
        }
        // If cancelled or completed, free the room (check if other bookings exist for this room)
        if (in_array($status, ['cancelled', 'completed'])) {
            $bk = $pdo->prepare("SELECT room_id FROM bookings WHERE id=?");
            $bk->execute([$bid]);
            $bkData = $bk->fetch();
            if ($bkData) {
                $remain = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id=? AND status NOT IN ('cancelled', 'completed') AND id!=?");
                $remain->execute([$bkData['room_id'], $bid]);
                if ($remain->fetchColumn() == 0) {
                    $pdo->prepare("UPDATE rooms SET status='available' WHERE id=?")->execute([$bkData['room_id']]);
                }
            }
        }
        $success = 'Cập nhật trạng thái thành công!';
    }
}

// Cập nhật payment
if (isset($_POST['update_payment'])) {
    $bid = (int) $_POST['booking_id'];
    $method = $_POST['payment_method'];
    $pstatus = $_POST['payment_status'];
    if (in_array($pstatus, ['pending', 'success', 'failed'])) {
        $pdo->prepare("UPDATE payments SET payment_status=?,payment_method=? WHERE booking_id=?")
            ->execute([$pstatus, $method, $bid]);
        $success = 'Cập nhật thanh toán thành công!';
    }
}

// Xóa booking
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM bookings WHERE id=?")->execute([(int) $_GET['delete']]);
    $success = 'Đã xóa đặt phòng thành công!';
}

// Filters
$filterStatus = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT b.*, u.username, r.name as room_name, r.price as room_price,
    p.payment_status, p.payment_method, p.amount as paid_amount
    FROM bookings b
    JOIN users u ON b.user_id=u.id
    JOIN rooms r ON b.room_id=r.id
    LEFT JOIN payments p ON p.booking_id=b.id
    WHERE 1=1";
$params = [];

if ($filterStatus) {
    $sql .= " AND b.status=?";
    $params[] = $filterStatus;
}
if ($search) {
    $sql .= " AND (u.username LIKE ? OR r.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$totalRevenue = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='success'")->fetchColumn();

$statusClasses = ['pending' => 'status-pending', 'confirmed' => 'status-confirmed', 'cancelled' => 'status-cancelled', 'completed' => 'status-completed'];
$payClasses = ['pending' => 'pay-status-pending', 'success' => 'pay-status-success', 'failed' => 'pay-status-failed'];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đặt phòng | Royal Hotel Admin</title>
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
            <li><a href="manage_roles.php"><i class="fa-solid fa-users"></i> <?php echo t('admin_users'); ?></a></li>
            <li><a href="manage_rooms.php"><i class="fa-solid fa-bed"></i> <?php echo t('admin_rooms'); ?></a></li>
            <li><a href="manage_bookings.php" class="active"><i class="fa-solid fa-calendar-check"></i>
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
            <h2><i class="fa-solid fa-calendar-check" style="color:var(--primary);"></i>
                <?php echo t('admin_bookings'); ?></h2>
            <div style="color:var(--text-muted);font-size:0.875rem;">
                Doanh thu xác nhận: <strong
                    style="color:var(--primary);font-size:1.1rem;"><?php echo number_format($totalRevenue, 0, ',', '.'); ?>đ</strong>
            </div>
        </div>

        <div class="admin-content">
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i>
                    <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i>
                    <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <!-- Filters -->
            <div class="admin-card" style="padding:20px;margin-bottom:20px;">
                <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                    <div style="position:relative;flex:1;min-width:200px;">
                        <i class="fa-solid fa-search"
                            style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:0.85rem;"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Tìm kiếm khách hàng, phòng..." class="form-control"
                            style="padding-left:36px;background:var(--gray-50);border:1.5px solid var(--border);color:var(--text);">
                    </div>
                    <select name="status" class="form-control"
                        style="background:var(--gray-50);border:1.5px solid var(--border);color:var(--text);width:auto;padding:10px 36px 10px 14px;">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending" <?php echo $filterStatus == 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                        <option value="confirmed" <?php echo $filterStatus == 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận
                        </option>
                        <option value="cancelled" <?php echo $filterStatus == 'cancelled' ? 'selected' : ''; ?>>Đã hủy
                        </option>
                        <option value="completed" <?php echo $filterStatus == 'completed' ? 'selected' : ''; ?>>Hoàn thành
                        </option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Lọc</button>
                    <a href="manage_bookings.php" class="btn btn-outline btn-sm">Xóa lọc</a>
                </form>
            </div>

            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Danh sách đặt phòng (<?php echo count($bookings); ?>)</h3>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Khách hàng</th>
                                <th>Phòng</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Tổng tiền</th>
                                <th>Đặt phòng</th>
                                <th>Thanh toán</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted);">Không
                                        tìm thấy kết quả nào</td>
                                </tr>
                            <?php else:
                                foreach ($bookings as $bk): ?>
                                    <tr>
                                        <td><strong>#<?php echo $bk['id']; ?></strong><br><small
                                                style="color:var(--text-muted);"><?php echo date('d/m/y', strtotime($bk['created_at'])); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($bk['username']); ?></td>
                                        <td><?php echo htmlspecialchars($bk['room_name']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($bk['check_in_date'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($bk['check_out_date'])); ?></td>
                                        <td style="color:var(--primary);font-weight:700;">
                                            <?php echo number_format($bk['total_price'], 0, ',', '.'); ?>đ</td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="booking_id" value="<?php echo $bk['id']; ?>">
                                                <select name="status" class="form-control"
                                                    style="padding:4px 8px;font-size:0.8rem;width:auto;border:1.5px solid var(--border);border-radius:6px;"
                                                    onchange="this.form.submit()">
                                                    <option value="pending" <?php echo $bk['status'] == 'pending' ? 'selected' : ''; ?>>Chờ</option>
                                                    <option value="confirmed" <?php echo $bk['status'] == 'confirmed' ? 'selected' : ''; ?>>Xác nhận</option>
                                                    <option value="cancelled" <?php echo $bk['status'] == 'cancelled' ? 'selected' : ''; ?>>Hủy</option>
                                                    <option value="completed" <?php echo $bk['status'] == 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                        </td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="booking_id" value="<?php echo $bk['id']; ?>">
                                                <select name="payment_status" class="form-control"
                                                    style="padding:4px 8px;font-size:0.8rem;width:auto;border:1.5px solid var(--border);border-radius:6px;"
                                                    onchange="this.form.submit()">
                                                    <option value="pending" <?php echo ($bk['payment_status'] ?? 'pending') == 'pending' ? 'selected' : ''; ?>>Chờ TT
                                                    </option>
                                                    <option value="success" <?php echo ($bk['payment_status'] ?? '') == 'success' ? 'selected' : ''; ?>>Đã TT</option>
                                                    <option value="failed" <?php echo ($bk['payment_status'] ?? '') == 'failed' ? 'selected' : ''; ?>>Thất bại
                                                    </option>
                                                </select>
                                                <input type="hidden" name="payment_method"
                                                    value="<?php echo $bk['payment_method'] ?? 'cash'; ?>">
                                                <input type="hidden" name="update_payment" value="1">
                                            </form>
                                        </td>
                                        <td>
                                            <a href="?delete=<?php echo $bk['id']; ?>" class="btn btn-sm"
                                                style="background:rgba(239,68,68,0.1);color:#EF4444;border:1px solid rgba(239,68,68,0.2);"
                                                onclick="return confirm('Xác nhận xóa đặt phòng #<?php echo $bk['id']; ?>?')">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>