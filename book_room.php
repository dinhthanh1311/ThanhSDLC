<?php
session_start();
require 'db.php';
require_once 'lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=" . urlencode("book_room.php?id=" . ($_GET['id'] ?? '')));
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$room_id = (int) $_GET['id'];
$stmt = $pdo->prepare("SELECT r.*, GROUP_CONCAT(a.name SEPARATOR '||') as amenity_names 
    FROM rooms r LEFT JOIN room_amenities ra ON r.id=ra.room_id LEFT JOIN amenities a ON ra.amenity_id=a.id
    WHERE r.id=? GROUP BY r.id");
$stmt->execute([$room_id]);
$room = $stmt->fetch();

if (!$room) {
    header("Location: index.php");
    exit();
}

$services = $pdo->query("SELECT * FROM services")->fetchAll();
$successMessage = $errorMessage = '';
$booking_id_created = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_submit'])) {
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $selected_services = $_POST['services'] ?? [];

    $checkInDT = new DateTime($check_in);
    $checkOutDT = new DateTime($check_out);
    $now = new DateTime(date('Y-m-d'));

    if ($checkInDT < $now) {
        $errorMessage = t('past_checkin_date_error');
    } elseif ($checkOutDT <= $checkInDT) {
        $errorMessage = t('checkout_after_checkin_error');
    } elseif ($room['status'] === 'booked') {
        $errorMessage = t('room_unavailable');
    } else {
        $days = max(1, $checkInDT->diff($checkOutDT)->days);
        $total_price = $days * $room['price'];
        $svc_details = [];

        foreach ($selected_services as $svc_id) {
            foreach ($services as $svc) {
                if ($svc['id'] == $svc_id) {
                    $total_price += $svc['price'];
                    $svc_details[] = $svc;
                    break;
                }
            }
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date, total_price, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$_SESSION['user_id'], $room_id, $check_in, $check_out, $total_price]);
            $booking_id_created = $pdo->lastInsertId();

            if (!empty($svc_details)) {
                $stmtSvc = $pdo->prepare("INSERT INTO booking_services (booking_id, service_id, quantity, price) VALUES (?, ?, 1, ?)");
                foreach ($svc_details as $svc) {
                    $stmtSvc->execute([$booking_id_created, $svc['id'], $svc['price']]);
                }
            }

            // Create payment record
            $pdo->prepare("INSERT INTO payments (booking_id, amount, payment_status) VALUES (?, ?, 'pending')")
                ->execute([$booking_id_created, $total_price]);

            // Update room status
            $pdo->prepare("UPDATE rooms SET status='booked' WHERE id=?")->execute([$room_id]);

            $pdo->commit();
            $successMessage = t('book_success') . " #$booking_id_created";

            // Re-fetch room
            $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id=?");
            $stmt->execute([$room_id]);
            $room = $stmt->fetch();

        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMessage = t('general_error') . " " . $e->getMessage();
        }
    }
}

$amenities = $room['amenity_names'] ? explode('||', $room['amenity_names']) : [];
$typeLabels = ['standard' => 'Standard', 'medium' => 'Superior', 'premium' => 'Premium'];
$serviceIcons = ['Bữa sáng' => 'fa-mug-hot', 'Spa' => 'fa-spa', 'Đưa đón' => 'fa-car', 'Massage' => 'fa-spa'];
function getSvcIcon($name)
{
    global $serviceIcons;
    foreach ($serviceIcons as $k => $v)
        if (stripos($name, $k) !== false)
            return $v;
    return 'fa-concierge-bell';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('complete_booking'); ?> - <?php echo htmlspecialchars(t($room['name'])); ?></title>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>

<body class="bg-gray" style="padding-top:72px;">

    <!-- Navbar minimal -->
    <nav class="navbar scrolled">
        <div class="logo"><a href="index.php"><i class="fa-solid fa-hotel"></i> Royal Hotel</a></div>
        <ul class="nav-links">
            <li><a href="index.php"><?php echo t('home'); ?></a></li>
            <li><a href="index.php#rooms"><?php echo t('rooms'); ?></a></li>
        </ul>
        <div class="nav-actions">
            <span class="nav-user-info"><?php echo t('hello'); ?>,
                <b><?php echo htmlspecialchars($_SESSION['username']); ?></b></span>
            <a href="profile.php" class="btn btn-outline btn-sm"><?php echo t('profile'); ?></a>
            <a href="logout.php" class="btn btn-primary btn-sm"><?php echo t('logout'); ?></a>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header" style="padding:80px 5% 50px;">
        <h1 style="font-size:2rem;"><?php echo t('complete_booking'); ?></h1>
        <div class="breadcrumb">
            <a href="index.php"><?php echo t('home'); ?></a>
            <i class="fa-solid fa-chevron-right fa-xs"></i>
            <a href="index.php#rooms"><?php echo t('rooms'); ?></a>
            <i class="fa-solid fa-chevron-right fa-xs"></i>
            <span style="color:var(--primary);"><?php echo htmlspecialchars(t($room['name'])); ?></span>
        </div>
    </div>

    <!-- Booking Layout -->
    <div class="booking-layout" style="margin-top:0;padding-top:0;">
        <!-- Left: Room Detail + Form -->
        <div>
            <!-- Room Card -->
            <div class="booking-panel" style="margin-bottom:24px;">
                <div class="booking-panel-img">
                    <img src="<?php echo htmlspecialchars($room['image_url']); ?>"
                        alt="<?php echo htmlspecialchars(t($room['name'])); ?>">
                </div>
                <div class="booking-panel-body">
                    <div class="d-flex justify-between align-center" style="margin-bottom:8px;">
                        <h2 class="booking-panel-title"><?php echo htmlspecialchars(t($room['name'])); ?></h2>
                        <span
                            class="room-badge <?php echo $room['status'] === 'available' ? 'badge-available' : 'badge-booked'; ?>">
                            <?php echo $room['status'] === 'available' ? t('available') : t('booked'); ?>
                        </span>
                    </div>
                    <p style="color:var(--text-muted);line-height:1.7;margin-bottom:16px;">
                        <?php echo htmlspecialchars(t($room['description'])); ?>
                    </p>

                    <div class="room-meta">
                        <span class="room-meta-item"><i class="fa-solid fa-tag"></i>
                            <?php echo $typeLabels[$room['room_type']] ?? 'Standard'; ?></span>
                        <span class="room-meta-item"><i class="fa-solid fa-user"></i> <?php echo $room['capacity']; ?>
                            <?php echo t('capacity'); ?></span>
                        <span class="room-meta-item"><i class="fa-solid fa-moon"></i> <strong
                                style="color:var(--primary);"><?php echo number_format($room['price'], 0, ',', '.'); ?>đ</strong>/đêm</span>
                    </div>

                    <?php if (!empty($amenities)): ?>
                        <div>
                            <p
                                style="font-size:0.85rem;font-weight:700;color:var(--text-muted);margin-bottom:10px;text-transform:uppercase;letter-spacing:0.5px;">
                                <?php echo t('room_amenities'); ?>
                            </p>
                            <div class="room-amenities">
                                <?php foreach ($amenities as $am): ?>
                                    <span class="amenity-chip"><i class="fa-solid fa-check"
                                            style="font-size:0.65rem;color:var(--primary);"></i>
                                        <?php echo htmlspecialchars(t($am)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Services Selection Panel -->
            <?php if (!$successMessage && $room['status'] !== 'booked'): ?>
                <div class="booking-panel">
                    <div class="booking-panel-body">
                        <p class="panel-section-title"><i class="fa-solid fa-concierge-bell"
                                style="color:var(--primary);"></i> <?php echo t('extra_services'); ?></p>
                        <div class="service-check-list">
                            <?php foreach ($services as $svc): ?>
                                <label class="service-item" id="svc-label-<?php echo $svc['id']; ?>">
                                    <input type="checkbox" name="services[]" value="<?php echo $svc['id']; ?>"
                                        data-price="<?php echo $svc['price']; ?>" class="service-checkbox"
                                        id="svc-<?php echo $svc['id']; ?>" onchange="updateServiceSelection(this)">
                                    <div class="service-info">
                                        <div class="service-info-icon"><i
                                                class="fa-solid <?php echo getSvcIcon($svc['name']); ?>"></i></div>
                                        <div class="service-info-text">
                                            <div class="svc-name"><?php echo htmlspecialchars($svc['name']); ?></div>
                                            <div class="svc-desc"><?php echo htmlspecialchars($svc['description']); ?></div>
                                        </div>
                                    </div>
                                    <div class="service-price-tag">+<?php echo number_format($svc['price'], 0, ',', '.'); ?>đ
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right: Summary + Form -->
        <div class="booking-summary">
            <div class="summary-header">
                <h3><i class="fa-solid fa-receipt"></i> <?php echo t('complete_booking'); ?></h3>
                <p style="color:rgba(255,255,255,0.5);font-size:0.82rem;margin-top:4px;">
                    <?php echo htmlspecialchars(t($room['name'])); ?>
                </p>
            </div>
            <div class="summary-body">
                <?php if ($successMessage): ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-circle-check"></i>
                        <div>
                            <strong><?php echo $successMessage; ?></strong>
                            <br><small><?php echo t('booking_success_detail'); ?></small>
                        </div>
                    </div>

                    <div class="payment-selection"
                        style="margin-top: 20px; padding: 20px; background: rgba(255,255,255,0.05); border-radius: 10px; border: 1px solid rgba(255,255,255,0.1);">
                        <h4 style="margin-bottom: 15px;"><i class="fa-solid fa-credit-card"
                                style="color:var(--primary);"></i> <?php echo t('select_payment_method'); ?></h4>

                        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                            <button type="button" class="btn btn-outline" id="btn-banking"
                                style="flex: 1; justify-content: center; padding: 12px; font-weight: 600;"
                                onclick="selectPayment('banking')">
                                <i class="fa-solid fa-building-columns"></i> <?php echo t('payment_bank_transfer'); ?>
                            </button>
                            <button type="button" class="btn btn-outline" id="btn-cash"
                                style="flex: 1; justify-content: center; padding: 12px; font-weight: 600;"
                                onclick="selectPayment('cash')">
                                <i class="fa-solid fa-money-bill-1-wave"></i> <?php echo t('payment_cash'); ?>
                            </button>
                        </div>

                        <div id="banking-info"
                            style="display: none; text-align: center; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 8px;">
                            <p style="margin-bottom: 8px;"><?php echo t('payment_qr_info'); ?></p>
                            <p><strong>MBbank</strong> - 0963392741</p>
                            <p><?php echo t('account_holder'); ?> <strong>ROYAL HOTEL</strong></p>
                            <p><?php echo t('transfer_content'); ?> <strong style="color:var(--primary);">THUE PHONG
                                    <?php echo $booking_id_created; ?></strong></p>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=VCB%3A1029384756"
                                alt="QR Code" style="margin-top:10px; border-radius:10px; padding:8px; background:white;">
                        </div>

                        <div id="cash-info"
                            style="display: none; text-align: center; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 8px; color: var(--text-muted);">
                            <i class="fa-solid fa-hand-holding-dollar"
                                style="font-size: 2rem; color: var(--primary); margin-bottom: 10px;"></i>
                            <p><?php echo t('payment_cash_info'); ?></p>
                        </div>
                    </div>

                    <a href="profile.php?tab=bookings" class="btn btn-primary w-100"
                        style="justify-content:center;margin-top:15px;"
                        onclick="alert('Đã ghi nhận! Vui lòng chờ Admin kiểm tra và xác nhận thanh toán.');"><i
                            class="fa-solid fa-check" style="margin-right:6px;"></i> Hoàn tất thanh toán</a>

                    <script>
                        function selectPayment(method) {
                            // Toggle UI sections
                            document.getElementById('banking-info').style.display = method === 'banking' ? 'block' : 'none';
                            document.getElementById('cash-info').style.display = method === 'cash' ? 'block' : 'none';

                            // Toggle button styles
                            document.getElementById('btn-banking').style.borderColor = method === 'banking' ? 'var(--primary)' : 'rgba(255,255,255,0.2)';
                            document.getElementById('btn-banking').style.color = method === 'banking' ? 'var(--primary)' : 'var(--text-muted)';

                            document.getElementById('btn-cash').style.borderColor = method === 'cash' ? 'var(--primary)' : 'rgba(255,255,255,0.2)';
                            document.getElementById('btn-cash').style.color = method === 'cash' ? 'var(--primary)' : 'var(--text-muted)';

                            // Send AJAX to update payment method in DB
                            const formData = new FormData();
                            formData.append('update_payment', true);
                            formData.append('booking_id', <?php echo $booking_id_created; ?>);
                            formData.append('method', method);

                            fetch('update_payment.php', {
                                method: 'POST',
                                body: formData
                            }).then(res => res.text()).then(data => console.log('Payment method updated.'));
                        }
                    </script>

                <?php elseif ($room['status'] === 'booked' && !$successMessage): ?>
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <?php echo t('room_unavailable'); ?>
                    </div>
                    <a href="index.php" class="btn btn-primary w-100"
                        style="justify-content:center;"><?php echo t('book_back'); ?></a>

                <?php else: ?>
                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i>
                            <?php echo htmlspecialchars($errorMessage); ?></div>
                    <?php endif; ?>

                    <form action="" method="POST" id="bookingForm">
                        <!-- Hidden: services checkboxes will be cloned here -->
                        <div id="hiddenServices"></div>

                        <div class="date-picker-group">
                            <div class="date-field">
                                <label><?php echo t('check_in'); ?></label>
                                <input type="date" name="check_in" id="check_in" required
                                    value="<?php echo $_POST['check_in'] ?? date('Y-m-d'); ?>"
                                    min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="date-field">
                                <label><?php echo t('check_out'); ?></label>
                                <input type="date" name="check_out" id="check_out" required
                                    value="<?php echo $_POST['check_out'] ?? date('Y-m-d', strtotime('+1 day')); ?>"
                                    min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                            </div>
                        </div>

                        <div class="price-breakdown">
                            <div class="price-row">
                                <span class="label"><?php echo t('room_cost'); ?> (<span id="nightCount">1</span>
                                    <?php echo t('nights'); ?>)</span>
                                <span class="value" id="roomTotal">0đ</span>
                            </div>
                            <div class="price-row">
                                <span class="label"><?php echo t('service_cost'); ?></span>
                                <span class="value" id="serviceTotal">0đ</span>
                            </div>
                            <div class="price-row total">
                                <span><?php echo t('total'); ?></span>
                                <span class="value" id="finalTotal">0đ</span>
                            </div>
                        </div>

                        <button type="submit" name="book_submit" class="btn btn-primary w-100"
                            style="justify-content:center;padding:14px;font-size:1rem;margin-top:20px;border-radius:10px;">
                            <i class="fa-solid fa-credit-card"></i> <?php echo t('confirm_book'); ?>
                        </button>
                        <p style="text-align:center;font-size:0.78rem;color:var(--text-muted);margin-top:12px;"><i
                                class="fa-solid fa-shield-halved" style="color:var(--success);"></i>
                            <?php echo t('secure_payment'); ?></p>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const pricePerNight = <?php echo $room['price']; ?>;
        const checkInInput = document.getElementById('check_in');
        const checkOutInput = document.getElementById('check_out');

        function fmt(n) { return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + 'đ'; }

        function calculateTotal() {
            if (!checkInInput || !checkOutInput) return;
            let ci = new Date(checkInInput.value), co = new Date(checkOutInput.value);
            let days = Math.ceil((co - ci) / 86400000);
            if (days <= 0 || isNaN(days)) days = 1;

            let roomT = days * pricePerNight;
            let svcT = 0;
            document.querySelectorAll('.service-checkbox').forEach(cb => { if (cb.checked) svcT += parseInt(cb.dataset.price); });

            document.getElementById('nightCount').textContent = days;
            document.getElementById('roomTotal').textContent = fmt(roomT);
            document.getElementById('serviceTotal').textContent = fmt(svcT);
            document.getElementById('finalTotal').textContent = fmt(roomT + svcT);
        }

        function updateServiceSelection(cb) {
            const label = document.getElementById('svc-label-' + cb.value);
            label.classList.toggle('selected', cb.checked);
            syncHiddenServices();
            calculateTotal();
        }

        function syncHiddenServices() {
            const container = document.getElementById('hiddenServices');
            container.innerHTML = '';
            document.querySelectorAll('.service-checkbox:checked').forEach(cb => {
                const h = document.createElement('input');
                h.type = 'hidden';
                h.name = 'services[]';
                h.value = cb.value;
                container.appendChild(h);
            });
        }

        // Date validation
        checkInInput.addEventListener('change', function () {
            const nextDay = new Date(this.value);
            nextDay.setDate(nextDay.getDate() + 1);
            checkOutInput.min = nextDay.toISOString().split('T')[0];
            if (checkOutInput.value <= this.value) {
                checkOutInput.value = nextDay.toISOString().split('T')[0];
            }
            calculateTotal();
        });
        checkOutInput.addEventListener('change', calculateTotal);

        // Initial
        calculateTotal();
    </script>
</body>

</html>