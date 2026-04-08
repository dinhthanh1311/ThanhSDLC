<?php
session_start();
require 'db.php';
require_once 'lang.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success = $error = '';

// Thêm dịch vụ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $price = (int)$_POST['price'];
    if (!empty($name) && $price > 0) {
        $pdo->prepare("INSERT INTO services (name, description, price) VALUES (?,?,?)")->execute([$name,$desc,$price]);
        $success = 'Đã thêm dịch vụ thành công!';
    } else {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    }
}

// Xóa dịch vụ
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM services WHERE id=?")->execute([(int)$_GET['delete']]);
    $success = 'Đã xóa dịch vụ!';
}

// Cập nhật dịch vụ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
    $sid = (int)$_POST['service_id'];
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $price = (int)$_POST['price'];
    $pdo->prepare("UPDATE services SET name=?,description=?,price=? WHERE id=?")->execute([$name,$desc,$price,$sid]);
    $success = 'Đã cập nhật dịch vụ!';
}

$services = $pdo->query("SELECT s.*,
    (SELECT COUNT(*) FROM booking_services WHERE service_id=s.id) as usage_count
    FROM services s ORDER BY s.id ASC")->fetchAll();

// Amenities management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_amenity'])) {
    $name = trim($_POST['amenity_name']);
    if (!empty($name)) {
        $pdo->prepare("INSERT INTO amenities (name) VALUES (?)")->execute([$name]);
        $success = 'Đã thêm tiện nghi!';
    }
}
if (isset($_GET['del_amenity'])) {
    $pdo->prepare("DELETE FROM amenities WHERE id=?")->execute([(int)$_GET['del_amenity']]);
    $success = 'Đã xóa tiện nghi!';
}

$amenities = $pdo->query("SELECT a.*, (SELECT COUNT(*) FROM room_amenities WHERE amenity_id=a.id) as room_count FROM amenities a ORDER BY a.name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Dịch vụ | Royal Hotel Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            <li><a href="manage_bookings.php"><i class="fa-solid fa-calendar-check"></i> <?php echo t('admin_bookings'); ?></a></li>
            <li><a href="manage_services.php" class="active"><i class="fa-solid fa-concierge-bell"></i> <?php echo t('admin_services'); ?></a></li>
            <hr class="sidebar-divider">
            <li><a href="index.php"><i class="fa-solid fa-house"></i> <?php echo t('back_to_home'); ?></a></li>
            <li class="logout-link"><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> <?php echo t('logout'); ?></a></li>
        </ul>
    </aside>

    <div class="admin-main">
        <div class="admin-topbar">
            <h2><i class="fa-solid fa-concierge-bell" style="color:var(--primary);"></i> <?php echo t('admin_services'); ?></h2>
        </div>

        <div class="admin-content">
            <?php if($success): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            <?php if($error): ?><div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                <!-- Services -->
                <div>
                    <div class="admin-card" style="margin-bottom:20px">
                        <div class="admin-card-header"><h3><i class="fa-solid fa-plus" style="color:var(--primary);"></i> Thêm dịch vụ mới</h3></div>
                        <div style="padding:20px;">
                            <form method="POST" class="light-form">
                                <div class="form-group">
                                    <label>Tên dịch vụ *</label>
                                    <input type="text" name="name" class="form-control" placeholder="VD: Spa & Massage" required>
                                </div>
                                <div class="form-group">
                                    <label>Mô tả</label>
                                    <textarea name="description" class="form-control" rows="2" placeholder="Mô tả ngắn về dịch vụ..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Giá (VNĐ) *</label>
                                    <input type="number" name="price" class="form-control" min="0" step="10000" required>
                                </div>
                                <button type="submit" name="add_service" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Thêm dịch vụ</button>
                            </form>
                        </div>
                    </div>

                    <div class="admin-card">
                        <div class="admin-card-header"><h3>Danh sách dịch vụ (<?php echo count($services); ?>)</h3></div>
                        <?php foreach($services as $svc): ?>
                        <div style="padding:16px 20px;border-bottom:1px solid var(--gray-100);">
                            <form method="POST" class="light-form">
                                <input type="hidden" name="service_id" value="<?php echo $svc['id']; ?>">
                                <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:10px;align-items:end;">
                                    <div>
                                        <label style="font-size:0.75rem;">Tên dịch vụ</label>
                                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($svc['name']); ?>" required style="padding:8px 10px;font-size:0.85rem;">
                                    </div>
                                    <div>
                                        <label style="font-size:0.75rem;">Giá (đ)</label>
                                        <input type="number" name="price" class="form-control" value="<?php echo $svc['price']; ?>" required style="padding:8px 10px;font-size:0.85rem;">
                                    </div>
                                    <div style="display:flex;gap:6px;">
                                        <button type="submit" name="update_service" class="btn btn-sm" style="background:rgba(59,130,246,0.1);color:#3B82F6;border:1px solid rgba(59,130,246,0.2);"><i class="fa-solid fa-floppy-disk"></i></button>
                                        <a href="?delete=<?php echo $svc['id']; ?>" class="btn btn-sm" style="background:rgba(239,68,68,0.1);color:#EF4444;border:1px solid rgba(239,68,68,0.2);"
                                           onclick="return confirm('Xóa dịch vụ này?')"><i class="fa-solid fa-trash"></i></a>
                                    </div>
                                </div>
                                <div style="margin-top:8px;">
                                    <input type="text" name="description" class="form-control" value="<?php echo htmlspecialchars($svc['description']??''); ?>" placeholder="Mô tả..." style="padding:6px 10px;font-size:0.82rem;">
                                </div>
                                <small style="color:var(--text-muted);font-size:0.75rem;margin-top:4px;display:block;">Đã đặt <?php echo $svc['usage_count']; ?> lần</small>
                            </form>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($services)): ?><div class="empty-state"><i class="fa-solid fa-concierge-bell"></i><p>Chưa có dịch vụ nào</p></div><?php endif; ?>
                    </div>
                </div>

                <!-- Amenities -->
                <div>
                    <div class="admin-card" style="margin-bottom:20px">
                        <div class="admin-card-header"><h3><i class="fa-solid fa-star" style="color:var(--primary);"></i> Tiện nghi phòng</h3></div>
                        <div style="padding:20px;">
                            <form method="POST" class="light-form" style="display:flex;gap:10px;">
                                <div class="form-group" style="flex:1;margin:0;">
                                    <input type="text" name="amenity_name" class="form-control" placeholder="VD: Bồn tắm, Wifi, Điều hòa..." required>
                                </div>
                                <button type="submit" name="add_amenity" class="btn btn-primary btn-sm" style="white-space:nowrap;"><i class="fa-solid fa-plus"></i> Thêm</button>
                            </form>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead><tr><th>#</th><th>Tên tiện nghi</th><th>Số phòng</th><th></th></tr></thead>
                                <tbody>
                                    <?php foreach($amenities as $am): ?>
                                    <tr>
                                        <td><?php echo $am['id']; ?></td>
                                        <td><i class="fa-solid fa-check" style="color:var(--primary);margin-right:6px;"></i><?php echo htmlspecialchars($am['name']); ?></td>
                                        <td><span class="status-badge status-confirmed"><?php echo $am['room_count']; ?> phòng</span></td>
                                        <td>
                                            <a href="?del_amenity=<?php echo $am['id']; ?>" class="btn btn-sm" style="background:rgba(239,68,68,0.1);color:#EF4444;border:1px solid rgba(239,68,68,0.2);"
                                               onclick="return confirm('Xóa tiện nghi này?')"><i class="fa-solid fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
