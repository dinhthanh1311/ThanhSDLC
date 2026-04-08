<?php
session_start();
require 'db.php';
require_once 'lang.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success = $error = '';
$editRoom = null;

// Xóa phòng
if (isset($_GET['delete'])) {
    try {
        $pdo->prepare("DELETE FROM rooms WHERE id=?")->execute([(int) $_GET['delete']]);
        $success = 'Đã xóa phòng thành công!';
    } catch (PDOException $e) {
        $error = 'Không thể xóa phòng này vì đã có dữ liệu liên quan (lịch sử đặt phòng, đánh giá, v.v.).';
    }
}

// Load phòng để sửa
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT r.*, GROUP_CONCAT(ra.amenity_id) as amenity_ids FROM rooms r LEFT JOIN room_amenities ra ON r.id=ra.room_id WHERE r.id=? GROUP BY r.id");
    $stmt->execute([(int) $_GET['edit']]);
    $editRoom = $stmt->fetch();
}

// Thêm / Cập nhật phòng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_room'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (int) $_POST['price'];
    $image_url = trim($_POST['image_url']);
    $status = $_POST['status'];
    $room_type = $_POST['room_type'];
    $capacity = (int) $_POST['capacity'];
    $amenity_ids = $_POST['amenity_ids'] ?? [];
    $room_id_edit = (int) ($_POST['room_id'] ?? 0);

    if (empty($name) || $price <= 0) {
        $error = 'Vui lòng điền đầy đủ thông tin phòng!';
    } else {
        try {
            $pdo->beginTransaction();
            if ($room_id_edit > 0) {
                $pdo->prepare("UPDATE rooms SET name=?,description=?,price=?,image_url=?,status=?,room_type=?,capacity=? WHERE id=?")
                    ->execute([$name, $description, $price, $image_url, $status, $room_type, $capacity, $room_id_edit]);
                $pdo->prepare("DELETE FROM room_amenities WHERE room_id=?")->execute([$room_id_edit]);
                foreach ($amenity_ids as $aid) {
                    $pdo->prepare("INSERT INTO room_amenities (room_id,amenity_id) VALUES (?,?)")->execute([$room_id_edit, $aid]);
                }
                $success = 'Đã cập nhật phòng thành công!';
            } else {
                $pdo->prepare("INSERT INTO rooms (name,description,price,image_url,status,room_type,capacity) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$name, $description, $price, $image_url, $status, $room_type, $capacity]);
                $new_id = $pdo->lastInsertId();
                foreach ($amenity_ids as $aid) {
                    $pdo->prepare("INSERT INTO room_amenities (room_id,amenity_id) VALUES (?,?)")->execute([$new_id, $aid]);
                }
                $success = 'Đã thêm phòng mới thành công!';
            }
            $pdo->commit();
            $editRoom = null;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

$rooms = $pdo->query("SELECT r.*,
    (SELECT COUNT(*) FROM bookings WHERE room_id=r.id) as booking_count,
    (SELECT AVG(rating) FROM reviews WHERE room_id=r.id) as avg_rating,
    GROUP_CONCAT(a.name SEPARATOR ', ') as amenity_names
    FROM rooms r LEFT JOIN room_amenities ra ON r.id=ra.room_id LEFT JOIN amenities a ON ra.amenity_id=a.id
    GROUP BY r.id ORDER BY r.created_at DESC")->fetchAll();

$amenities = $pdo->query("SELECT * FROM amenities ORDER BY name")->fetchAll();
$editAmenityIds = $editRoom ? ($editRoom['amenity_ids'] ? explode(',', $editRoom['amenity_ids']) : []) : [];

$typeLabel = ['standard' => 'Standard', 'medium' => 'Superior', 'premium' => 'Premium'];
$typeClass = ['standard' => 'badge-standard', 'medium' => 'badge-medium', 'premium' => 'badge-premium'];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Phòng | Royal Hotel Admin</title>
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
            <li><a href="manage_rooms.php" class="active"><i class="fa-solid fa-bed"></i>
                    <?php echo t('admin_rooms'); ?></a></li>
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
            <h2><i class="fa-solid fa-bed" style="color:var(--primary);"></i> <?php echo t('admin_rooms'); ?></h2>
            <button class="btn btn-primary btn-sm" onclick="openAddModal()"><i class="fa-solid fa-plus"></i>
                <?php echo t('add_room'); ?></button>
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
                    <h3>Danh sách phòng (<?php echo count($rooms); ?>)</h3>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Ảnh</th>
                                <th>Tên phòng</th>
                                <th>Loại</th>
                                <th>Giá/đêm</th>
                                <th>Sức chứa</th>
                                <th>Tiện nghi</th>
                                <th>Đánh giá</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rooms as $r): ?>
                                <tr>
                                    <td><?php echo $r['id']; ?></td>
                                    <td><img src="<?php echo htmlspecialchars($r['image_url']); ?>"
                                            style="width:70px;height:50px;object-fit:cover;border-radius:6px;"
                                            loading="lazy"></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($r['name']); ?></strong><br>
                                        <small style="color:var(--text-muted);"><?php echo $r['booking_count']; ?> lượt
                                            đặt</small>
                                    </td>
                                    <td><span class="room-badge <?php echo $typeClass[$r['room_type']] ?? ''; ?>"
                                            style="position:static;display:inline-block;"><?php echo $typeLabel[$r['room_type']] ?? ''; ?></span>
                                    </td>
                                    <td style="color:var(--primary);font-weight:700;">
                                        <?php echo number_format($r['price'], 0, ',', '.'); ?>đ</td>
                                    <td><?php echo $r['capacity']; ?> người</td>
                                    <td style="max-width:160px;font-size:0.8rem;color:var(--text-muted);">
                                        <?php echo htmlspecialchars($r['amenity_names'] ?? '—'); ?></td>
                                    <td>
                                        <?php if ($r['avg_rating']): ?>
                                            <span
                                                style="color:var(--warning);font-weight:700;"><?php echo round($r['avg_rating'], 1); ?>★</span>
                                        <?php else: ?><span style="color:var(--gray-300);">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span
                                            class="status-badge <?php echo $r['status'] === 'available' ? 'status-confirmed' : 'status-cancelled'; ?>">
                                            <?php echo $r['status'] === 'available' ? t('available') : t('booked'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="btn btn-sm"
                                                style="background:rgba(59,130,246,0.1);color:#3B82F6;border:1px solid rgba(59,130,246,0.2);"
                                                onclick="openEditModal(<?php echo htmlspecialchars(json_encode($r)); ?>, <?php echo htmlspecialchars(json_encode($editAmenityIds)); ?>)">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <a href="?delete=<?php echo $r['id']; ?>" class="btn btn-sm"
                                                style="background:rgba(239,68,68,0.1);color:#EF4444;border:1px solid rgba(239,68,68,0.2);"
                                                onclick="return confirm('Xác nhận xóa phòng này?')"><i
                                                    class="fa-solid fa-trash"></i></a>
                                            <a href="book_room.php?id=<?php echo $r['id']; ?>"
                                                class="btn btn-sm btn-outline" target="_blank"><i
                                                    class="fa-solid fa-eye"></i></a>
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

    <!-- Modal Add/Edit Room -->
    <div class="modal-overlay" id="roomModal">
        <div class="modal" style="max-width:620px;">
            <div class="modal-header">
                <h3 id="modalTitle"><i class="fa-solid fa-bed" style="color:var(--primary);"></i> Thêm phòng mới</h3>
                <button class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" id="roomForm">
                <input type="hidden" name="room_id" id="room_id_input" value="0">
                <div class="modal-body" style="max-height:70vh;overflow-y:auto;">
                    <div class="light-form">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                            <div class="form-group" style="grid-column:1/-1;">
                                <label>Tên phòng *</label>
                                <input type="text" name="name" id="field_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Loại phòng</label>
                                <select name="room_type" id="field_type" class="form-control">
                                    <option value="standard">Standard</option>
                                    <option value="medium">Superior/Medium</option>
                                    <option value="premium">Premium/VIP</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Trạng thái</label>
                                <select name="status" id="field_status" class="form-control">
                                    <option value="available">Còn trống</option>
                                    <option value="booked">Đã đặt</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Giá/đêm (VNĐ) *</label>
                                <input type="number" name="price" id="field_price" class="form-control" required min="0"
                                    step="50000">
                            </div>
                            <div class="form-group">
                                <label>Sức chứa (người)</label>
                                <input type="number" name="capacity" id="field_capacity" class="form-control" min="1"
                                    max="20" value="2">
                            </div>
                            <div class="form-group" style="grid-column:1/-1;">
                                <label>URL Hình ảnh</label>
                                <input type="url" name="image_url" id="field_image" class="form-control"
                                    placeholder="https://...">
                            </div>
                            <div class="form-group" style="grid-column:1/-1;">
                                <label>Mô tả</label>
                                <textarea name="description" id="field_desc" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="form-group" style="grid-column:1/-1;">
                                <label>Tiện nghi phòng</label>
                                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                                    <?php foreach ($amenities as $am): ?>
                                        <label
                                            style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.875rem;color:var(--text);font-weight:400;">
                                            <input type="checkbox" name="amenity_ids[]" value="<?php echo $am['id']; ?>"
                                                class="amenity-cb" id="am_<?php echo $am['id']; ?>"
                                                style="width:15px;height:15px;">
                                            <?php echo htmlspecialchars($am['name']); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline"
                        onclick="closeModal()"><?php echo t('cancel'); ?></button>
                    <button type="submit" name="save_room" class="btn btn-primary"><i
                            class="fa-solid fa-floppy-disk"></i> <?php echo t('save'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-plus" style="color:var(--primary);"></i> Thêm phòng mới';
            document.getElementById('room_id_input').value = '0';
            document.getElementById('roomForm').reset();
            document.querySelectorAll('.amenity-cb').forEach(cb => cb.checked = false);
            document.getElementById('roomModal').classList.add('active');
        }
        function openEditModal(room, editIds) {
            document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen" style="color:var(--primary);"></i> Sửa phòng: ' + room.name;
            document.getElementById('room_id_input').value = room.id;
            document.getElementById('field_name').value = room.name;
            document.getElementById('field_type').value = room.room_type;
            document.getElementById('field_status').value = room.status;
            document.getElementById('field_price').value = room.price;
            document.getElementById('field_capacity').value = room.capacity;
            document.getElementById('field_image').value = room.image_url;
            document.getElementById('field_desc').value = room.description || '';
            document.querySelectorAll('.amenity-cb').forEach(cb => { cb.checked = editIds.includes(cb.value); });
            document.getElementById('roomModal').classList.add('active');
        }
        function closeModal() {
            document.getElementById('roomModal').classList.remove('active');
        }
        document.getElementById('roomModal').addEventListener('click', function (e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>

</html>