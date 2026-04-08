<?php
session_start();
require 'db.php';
require_once 'lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=profile.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'profile';
$success = $error = '';

// Lấy thông tin user
$userData = $pdo->prepare("SELECT * FROM users WHERE id=?");
$userData->execute([$user_id]);
$user = $userData->fetch();

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $pdo->prepare("UPDATE users SET email=?, phone=? WHERE id=?")->execute([$email, $phone, $user_id]);
    $success = t('update_success');
    $user['email'] = $email;
    $user['phone'] = $phone;
}

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old = $_POST['old_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_new'];
    if (!password_verify($old, $user['password'])) {
        $error = t('old_password_incorrect');
    } elseif (strlen($new) < 6) {
        $error = t('new_password_min_length');
    } elseif ($new !== $confirm) {
        $error = t('pass_mismatch');
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $user_id]);
        $success = t('password_change_success');
    }
}

// Submit review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $room_id = (int) $_POST['room_id'];
    $rating = (int) ($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment']);
    if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
        // Check if already reviewed
        $chk = $pdo->prepare("SELECT id FROM reviews WHERE user_id=? AND room_id=?");
        $chk->execute([$user_id, $room_id]);
        if ($chk->fetch()) {
            $pdo->prepare("UPDATE reviews SET rating=?, comment=? WHERE user_id=? AND room_id=?")
                ->execute([$rating, $comment, $user_id, $room_id]);
        } else {
            $pdo->prepare("INSERT INTO reviews (user_id, room_id, rating, comment) VALUES (?,?,?,?)")
                ->execute([$user_id, $room_id, $rating, $comment]);
        }
        $success = t('review_submitted');
    } else {
        $error = t('review_error');
    }
}

// Xử lý khách hàng tự Hủy đặt phòng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $cancel_id = (int) $_POST['booking_id'];
    // Kiểm tra xem booking có thuộc về user này và đang ở trạng thái pending không
    $stmt = $pdo->prepare("SELECT room_id, status FROM bookings WHERE id=? AND user_id=?");
    $stmt->execute([$cancel_id, $user_id]);
    $bkData = $stmt->fetch();
    if ($bkData && $bkData['status'] === 'pending') {
        $pdo->prepare("UPDATE bookings SET status='cancelled' WHERE id=?")->execute([$cancel_id]);
        // Giải phóng phòng nếu không có đặt phòng nào khác đang active
        $remain = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id=? AND status NOT IN ('cancelled', 'completed')");
        $remain->execute([$bkData['room_id']]);
        if ($remain->fetchColumn() == 0) {
            $pdo->prepare("UPDATE rooms SET status='available' WHERE id=?")->execute([$bkData['room_id']]);
        }
        $success = 'Đã hủy đặt phòng thành công!';
    } else {
        $error = 'Không thể hủy đặt phòng này!';
    }
}

// Lấy bookings của user
$bookings = $pdo->prepare("SELECT b.*, r.name as room_name, r.image_url,
    p.payment_status, p.payment_method
    FROM bookings b
    JOIN rooms r ON b.room_id=r.id
    LEFT JOIN payments p ON p.booking_id=b.id
    WHERE b.user_id=?
    ORDER BY b.created_at DESC");
$bookings->execute([$user_id]);
$myBookings = $bookings->fetchAll();

// Lấy reviews của user
$reviews = $pdo->prepare("SELECT rv.*, r.name as room_name FROM reviews rv JOIN rooms r ON rv.room_id=r.id WHERE rv.user_id=? ORDER BY rv.created_at DESC");
$reviews->execute([$user_id]);
$myReviews = $reviews->fetchAll();

// Lấy phòng có thể đánh giá (đã hoàn thành booking)
$reviewable = $pdo->prepare("SELECT DISTINCT r.id, r.name FROM bookings b JOIN rooms r ON b.room_id=r.id WHERE b.user_id=? AND b.status IN ('confirmed','completed')");
$reviewable->execute([$user_id]);
$reviewableRooms = $reviewable->fetchAll();

$statusClasses = ['pending' => 'status-pending', 'confirmed' => 'status-confirmed', 'cancelled' => 'status-cancelled', 'completed' => 'status-completed'];
$payStatusClasses = ['pending' => 'pay-status-pending', 'success' => 'pay-status-success', 'failed' => 'pay-status-failed'];
$statusL10n = ['pending' => t('booking_status_pending'), 'confirmed' => t('booking_status_confirmed'), 'cancelled' => t('booking_status_cancelled'), 'completed' => t('booking_status_completed')];
$payStatusL10n = ['pending' => t('payment_status_pending'), 'success' => t('payment_status_success'), 'failed' => t('payment_status_failed')];

include 'header.php';
?>

<div style="background:linear-gradient(135deg,var(--dark),var(--accent2));padding:100px 5% 50px;">
    <div style="max-width:1000px;margin:0 auto;">
        <h1 style="color:white;font-size:1.8rem;"><?php echo t('my_profile'); ?></h1>
        <p style="color:rgba(255,255,255,0.5);margin-top:6px;"><?php echo t('hello'); ?>, <strong
                style="color:var(--primary);"><?php echo htmlspecialchars($user['username']); ?></strong>!</p>
    </div>
</div>

<div class="profile-layout" style="margin-top:0;padding-top:32px;">
    <!-- Sidebar -->
    <div class="profile-sidebar">
        <div class="profile-avatar-section">
            <div class="profile-avatar"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
            <div class="profile-name"><?php echo htmlspecialchars($user['username']); ?></div>
            <div class="profile-role"><?php echo $user['role'] === 'admin' ? t('admin_role') : t('guest_role'); ?></div>
        </div>
        <nav class="profile-nav">
            <a href="?tab=profile" class="<?php echo $tab === 'profile' ? 'active' : ''; ?>">
                <i class="fa-solid fa-user"></i> <?php echo t('edit_profile'); ?>
            </a>
            <a href="?tab=bookings" class="<?php echo $tab === 'bookings' ? 'active' : ''; ?>">
                <i class="fa-solid fa-calendar-check"></i> <?php echo t('my_reservations'); ?>
                <?php if (!empty($myBookings)): ?><span
                        style="margin-left:auto;background:var(--primary);color:white;font-size:0.72rem;padding:1px 7px;border-radius:30px;"><?php echo count($myBookings); ?></span><?php endif; ?>
            </a>
            <a href="?tab=reviews" class="<?php echo $tab === 'reviews' ? 'active' : ''; ?>">
                <i class="fa-solid fa-star"></i> <?php echo t('my_reviews'); ?>
            </a>
            <a href="?tab=password" class="<?php echo $tab === 'password' ? 'active' : ''; ?>">
                <i class="fa-solid fa-lock"></i> <?php echo t('change_password'); ?>
            </a>
            <?php if ($user['role'] === 'admin'): ?>
                <hr class="divider" style="border-color:var(--border);">
                <a href="admin.php"><i class="fa-solid fa-gauge"></i> <?php echo t('admin_panel'); ?></a>
            <?php endif; ?>
            <hr class="divider" style="border-color:var(--border);">
            <a href="logout.php" style="color:var(--danger)!important;"><i class="fa-solid fa-right-from-bracket"></i>
                <?php echo t('logout'); ?></a>
        </nav>
    </div>

    <!-- Content -->
    <div class="profile-content">
        <?php if ($success): ?>
            <div class="alert alert-success" style="margin:24px 24px 0;"><i class="fa-solid fa-circle-check"></i>
                <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin:24px 24px 0;"><i class="fa-solid fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($tab === 'profile'): ?>
            <!-- PROFILE TAB -->
            <div class="profile-content-header">
                <h3><i class="fa-solid fa-user" style="color:var(--primary);"></i> <?php echo t('edit_profile'); ?></h3>
            </div>
            <div class="profile-content-body">
                <form method="POST" class="profile-form light-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo t('username'); ?></label>
                            <input type="text" class="form-control"
                                value="<?php echo htmlspecialchars($user['username']); ?>" disabled
                                style="opacity:0.6;cursor:not-allowed;">
                            <small
                                style="color:var(--text-muted);font-size:0.78rem;"><?php echo t('username_no_change'); ?></small>
                        </div>
                        <div class="form-group">
                            <label><?php echo t('role'); ?></label>
                            <input type="text" class="form-control"
                                value="<?php echo $user['role'] === 'admin' ? 'Administrator' : 'Guest'; ?>" disabled
                                style="opacity:0.6;cursor:not-allowed;">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo t('email'); ?></label>
                            <input type="email" name="email" class="form-control"
                                value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                placeholder="email@example.com">
                        </div>
                        <div class="form-group">
                            <label><?php echo t('phone'); ?></label>
                            <input type="tel" name="phone" class="form-control"
                                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="0912345678">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?php echo t('created_at'); ?></label>
                        <input type="text" class="form-control"
                            value="<?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>" disabled
                            style="opacity:0.6;">
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary"><i
                            class="fa-solid fa-floppy-disk"></i> <?php echo t('save'); ?></button>
                </form>
            </div>

        <?php elseif ($tab === 'bookings'): ?>
            <!-- BOOKINGS TAB -->
            <div class="profile-content-header">
                <h3><i class="fa-solid fa-calendar-check" style="color:var(--primary);"></i>
                    <?php echo t('my_reservations'); ?></h3>
            </div>
            <div class="profile-content-body">
                <?php if (empty($myBookings)): ?>
                    <div class="empty-state">
                        <i class="fa-regular fa-calendar"></i>
                        <p><?php echo t('no_bookings'); ?></p>
                        <a href="index.php#rooms" class="btn btn-primary"
                            style="margin-top:16px;"><?php echo t('book_now'); ?></a>
                    </div>
                <?php else: ?>
                    <div class="bookings-list">
                        <?php foreach ($myBookings as $bk): ?>
                            <div class="booking-item">
                                <div class="booking-item-header">
                                    <div>
                                        <span class="booking-ref">#<?php echo $bk['id']; ?></span>
                                        <span
                                            style="color:var(--text-muted);font-size:0.8rem;margin-left:8px;"><?php echo date('d/m/Y', strtotime($bk['created_at'])); ?></span>
                                    </div>
                                    <div style="display:flex;gap:8px;">
                                        <span
                                            class="status-badge <?php echo $statusClasses[$bk['status']] ?? ''; ?>"><?php echo $statusL10n[$bk['status']] ?? $bk['status']; ?></span>
                                        <span
                                            class="status-badge <?php echo $payStatusClasses[$bk['payment_status'] ?? 'pending'] ?? ''; ?>">💳
                                            <?php echo $payStatusL10n[$bk['payment_status'] ?? 'pending'] ?? ($bk['payment_status'] ?? 'pending'); ?></span>
                                    </div>
                                </div>
                                <div style="display:flex;gap:16px;padding:16px 20px;align-items:center;">
                                    <img src="<?php echo htmlspecialchars($bk['image_url']); ?>" alt=""
                                        style="width:80px;height:60px;object-fit:cover;border-radius:8px;flex-shrink:0;">
                                    <div style="flex:1;">
                                        <div style="font-weight:700;margin-bottom:4px;">
                                            <?php echo htmlspecialchars($bk['room_name']); ?>
                                        </div>
                                        <div class="booking-item-body" style="padding:0;margin-top:4px;">
                                            <div class="booking-detail-item">
                                                <label><?php echo t('check_in_date'); ?></label>
                                                <span><?php echo date('d/m/Y', strtotime($bk['check_in_date'])); ?></span>
                                            </div>
                                            <div class="booking-detail-item">
                                                <label><?php echo t('check_out_date'); ?></label>
                                                <span><?php echo date('d/m/Y', strtotime($bk['check_out_date'])); ?></span>
                                            </div>
                                            <div class="booking-detail-item">
                                                <label><?php echo t('total_price'); ?></label>
                                                <span
                                                    style="color:var(--primary);font-weight:700;"><?php echo number_format($bk['total_price'], 0, ',', '.'); ?>đ</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                // Check services for this booking
                                $bkSvcs = $pdo->prepare("SELECT bs.*, s.name FROM booking_services bs JOIN services s ON bs.service_id=s.id WHERE bs.booking_id=?");
                                $bkSvcs->execute([$bk['id']]);
                                $bkSvcItems = $bkSvcs->fetchAll();
                                ?>
                                <?php if (!empty($bkSvcItems)): ?>
                                    <div style="padding:8px 20px 16px;border-top:1px dashed var(--border);">
                                        <span style="font-size:0.78rem;color:var(--text-muted);"><?php echo t('booking_services'); ?>:
                                        </span>
                                        <?php foreach ($bkSvcItems as $bs): ?>
                                            <span
                                                style="display:inline-block;background:var(--gray-100);padding:2px 10px;border-radius:30px;font-size:0.78rem;margin:2px;">
                                                <?php echo htmlspecialchars($bs['name']); ?>
                                                (<?php echo number_format($bs['price'], 0, ',', '.'); ?>đ)
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="booking-item-footer"
                                    style="padding: 12px 20px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px;">
                                    <?php if ($bk['payment_status'] === 'pending' && $bk['status'] !== 'cancelled'): ?>
                                        <button type="button" class="btn btn-outline btn-sm"
                                            onclick="showPaymentModal(<?php echo $bk['id']; ?>, <?php echo $bk['total_price']; ?>)">
                                            <i class="fa-solid fa-qrcode"></i> Thanh toán
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($bk['status'] === 'pending'): ?>
                                        <form method="POST" style="margin:0;"
                                            onsubmit="return confirm('Bạn có chắc chắn muốn hủy đặt phòng này không?');">
                                            <input type="hidden" name="booking_id" value="<?php echo $bk['id']; ?>">
                                            <button type="submit" name="cancel_booking" class="btn btn-sm"
                                                style="background:rgba(239,68,68,0.1);color:#EF4444;border:1px solid rgba(239,68,68,0.2);">
                                                <i class="fa-solid fa-xmark"></i> Hủy đặt phòng
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($tab === 'reviews'): ?>
            <!-- REVIEWS TAB -->
            <div class="profile-content-header">
                <h3><i class="fa-solid fa-star" style="color:var(--primary);"></i> <?php echo t('my_reviews'); ?></h3>
            </div>
            <div class="profile-content-body">
                <!-- Write Review Form -->
                <?php if (!empty($reviewableRooms)): ?>
                    <div class="review-form-card" style="margin-bottom:28px;background:var(--gray-50);">
                        <h4 style="margin-bottom:16px;font-size:1rem;"><?php echo t('write_review'); ?></h4>
                        <form method="POST" class="light-form">
                            <div class="form-group">
                                <label><?php echo t('room'); ?></label>
                                <select name="room_id" class="form-control" required>
                                    <option value=""><?php echo t('select_stayed_room'); ?></option>
                                    <?php foreach ($reviewableRooms as $r): ?>
                                        <option value="<?php echo $r['id']; ?>" <?php echo (isset($_POST['room_id']) && $_POST['room_id'] == $r['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($r['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo t('rating'); ?></label>
                                <div class="star-rating">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" name="rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>" <?php echo (isset($_POST['rating']) && $_POST['rating'] == $i) ? 'checked' : ''; ?>>
                                        <label for="star<?php echo $i; ?>">★</label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label><?php echo t('your_comment'); ?></label>
                                <textarea name="comment" class="form-control" rows="3"
                                    placeholder="<?php echo t('comment_placeholder'); ?>"
                                    required><?php echo isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : ''; ?></textarea>
                            </div>
                            <button type="submit" name="submit_review" class="btn btn-primary btn-sm"><i
                                    class="fa-solid fa-paper-plane"></i> <?php echo t('submit_review_btn'); ?></button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info" style="margin-bottom:20px;"><i class="fa-solid fa-info-circle"></i>
                        <?php echo t('review_condition_info'); ?></div>
                <?php endif; ?>

                <!-- My Reviews List -->
                <?php if (!empty($myReviews)): ?>
                    <div class="reviews-grid" style="grid-template-columns:1fr;">
                        <?php foreach ($myReviews as $rv): ?>
                            <div class="review-card" style="border:1px solid var(--border);">
                                <div class="d-flex justify-between align-center" style="margin-bottom:10px;">
                                    <div>
                                        <span style="font-weight:700;"><?php echo htmlspecialchars($rv['room_name']); ?></span>
                                        <div class="review-stars" style="margin:4px 0 0;">
                                            <?php for ($i = 1; $i <= 5; $i++)
                                                echo $i <= $rv['rating'] ? '★' : '☆'; ?>
                                        </div>
                                    </div>
                                    <span
                                        style="font-size:0.8rem;color:var(--text-muted);"><?php echo date('d/m/Y', strtotime($rv['created_at'])); ?></span>
                                </div>
                                <p style="color:var(--text-muted);font-size:0.875rem;line-height:1.6;">
                                    <?php echo htmlspecialchars($rv['comment']); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state"><i class="fa-regular fa-star"></i>
                        <p><?php echo t('no_reviews_yet'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($tab === 'password'): ?>
            <!-- PASSWORD TAB -->
            <div class="profile-content-header">
                <h3><i class="fa-solid fa-lock" style="color:var(--primary);"></i> <?php echo t('change_password'); ?></h3>
            </div>
            <div class="profile-content-body">
                <form method="POST" class="light-form" style="max-width:400px;">
                    <div class="form-group">
                        <label><?php echo t('current_password'); ?></label>
                        <input type="password" name="old_password" class="form-control" required placeholder="••••••••">
                    </div>
                    <div class="form-group">
                        <label><?php echo t('new_password'); ?></label>
                        <input type="password" name="new_password" class="form-control" required minlength="6"
                            placeholder="<?php echo t('new_password_placeholder'); ?>">
                    </div>
                    <div class="form-group">
                        <label><?php echo t('confirm_new_password'); ?></label>
                        <input type="password" name="confirm_new" class="form-control" required
                            placeholder="<?php echo t('confirm_new_password_placeholder'); ?>">
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary"><i class="fa-solid fa-key"></i>
                        <?php echo t('change_password_btn'); ?></button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Thanh Toán (Hiển thị QR Code) -->
<div class="modal-overlay" id="paymentModal">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <h3><i class="fa-solid fa-qrcode" style="color:var(--primary);"></i> Thông tin thanh toán</h3>
            <button type="button" class="modal-close" onclick="closePaymentModal()"><i
                    class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body" style="text-align:center;">
            <p style="margin-bottom: 10px;">Quét mã QR dưới đây hoặc chuyển khoản theo thông tin:</p>
            <p><strong>Vietcombank</strong> - 1029384756</p>
            <p>Chủ tài khoản: <strong>ROYAL HOTEL</strong></p>
            <p>Nội dung: <strong style="color:var(--primary);">THUE PHONG <span id="payBookingId"></span></strong></p>
            <p>Số tiền: <strong style="color:var(--danger);" id="payAmount"></strong></p>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=VCB%3A1029384756" alt="QR Code"
                style="margin:15px auto; border-radius:10px; padding:10px; background:white; border:1px solid var(--border);">
            <p style="font-size:0.85rem;color:var(--text-muted);">Hoặc thanh toán bằng tiền mặt tại quầy lễ tân.</p>
        </div>
        <div class="modal-footer" style="justify-content:center;">
            <button type="button" class="btn btn-primary w-100" onclick="completePayment()"><i
                    class="fa-solid fa-check"></i> Hoàn tất thanh toán</button>
        </div>
    </div>
</div>

<script>
    function showPaymentModal(id, amount) {
        document.getElementById('payBookingId').innerText = id;
        document.getElementById('payAmount').innerText = new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
        document.getElementById('paymentModal').classList.add('active');
    }
    function closePaymentModal() {
        document.getElementById('paymentModal').classList.remove('active');
    }
    function completePayment() {
        closePaymentModal();
        alert('Đã xác nhận thao tác! Vui lòng chờ Admin kiểm tra và cập nhật trạng thái.');
    }
</script>

<?php include 'footer.php'; ?>