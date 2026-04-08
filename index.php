<?php
require 'db.php';
include 'header.php';

// Lấy phòng với amenities
$stmt = $pdo->query("SELECT r.*, 
    GROUP_CONCAT(a.name SEPARATOR '||') as amenity_names,
    GROUP_CONCAT(a.icon SEPARATOR '||') as amenity_icons,
    (SELECT AVG(rating) FROM reviews rv WHERE rv.room_id = r.id) as avg_rating,
    (SELECT COUNT(*) FROM reviews rv WHERE rv.room_id = r.id) as review_count
    FROM rooms r
    LEFT JOIN room_amenities ra ON r.id = ra.room_id
    LEFT JOIN amenities a ON ra.amenity_id = a.id
    GROUP BY r.id ORDER BY r.price ASC");
$rooms = $stmt->fetchAll();

// Lấy dịch vụ
$services = $pdo->query("SELECT * FROM services")->fetchAll();

// Lấy đánh giá nổi bật
$reviews = $pdo->query("SELECT rv.*, u.username, r.name as room_name 
    FROM reviews rv 
    JOIN users u ON rv.user_id = u.id 
    JOIN rooms r ON rv.room_id = r.id 
    ORDER BY rv.created_at DESC LIMIT 6")->fetchAll();

$serviceIcons = ['Bữa sáng' => 'fa-mug-hot', 'Breakfast' => 'fa-mug-hot', 'Spa' => 'fa-spa', 'Đưa đón' => 'fa-car', 'Airport' => 'fa-car', 'Massage' => 'fa-spa'];
function getServiceIcon($name)
{
    global $serviceIcons;
    foreach ($serviceIcons as $k => $v) {
        if (stripos($name, $k) !== false)
            return $v;
    }
    return 'fa-concierge-bell';
}

$roomTypeLabels = ['standard' => 'Standard', 'medium' => 'Superior', 'premium' => 'Premium'];
$roomTypeColors = ['standard' => 'badge-standard', 'medium' => 'badge-medium', 'premium' => 'badge-premium'];
?>

<!-- HERO SECTION -->
<section class="hero">
    <div class="hero-content">
        <div class="hero-badge"><i class="fa-solid fa-star"></i> 5-Star Luxury Hotel</div>
        <h1 class="hero-title"><?php echo t('hero_title'); ?></h1>
        <p class="hero-desc"><?php echo t('hero_desc'); ?></p>
        <div class="hero-actions">
            <a href="#rooms" class="btn btn-primary btn-lg"><i class="fa-solid fa-calendar-check"></i>
                <?php echo t('book_now'); ?></a>
            <a href="#services" class="btn btn-outline btn-lg"
                style="border-color:rgba(255,255,255,0.4);color:white;"><?php echo t('explore_rooms'); ?></a>
        </div>
        <div class="hero-stats">
            <div class="stat-item">
                <span class="stat-num"><?php echo t('hero_stat1_num'); ?></span>
                <span class="stat-label"><?php echo t('hero_stat1_label'); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-num"><?php echo t('hero_stat2_num'); ?></span>
                <span class="stat-label"><?php echo t('hero_stat2_label'); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-num"><?php echo t('hero_stat3_num'); ?></span>
                <span class="stat-label"><?php echo t('hero_stat3_label'); ?></span>
            </div>
        </div>
    </div>
</section>

<!-- ROOMS SECTION -->
<section class="section" id="rooms" style="padding-top:60px;">
    <div class="section-header">
        <div class="section-tag"><?php echo t('room_tag'); ?></div>
        <h2 class="section-title"><?php echo t('room_types'); ?></h2>
        <p class="section-desc"><?php echo t('room_desc'); ?></p>
    </div>

    <div class="filter-bar" style="justify-content:center;">
        <button class="filter-btn active" onclick="filterRooms('all')"><?php echo t('filter_all'); ?></button>
        <button class="filter-btn" onclick="filterRooms('standard')"><?php echo t('filter_standard'); ?></button>
        <button class="filter-btn" onclick="filterRooms('medium')"><?php echo t('filter_medium'); ?></button>
        <button class="filter-btn" onclick="filterRooms('premium')"><?php echo t('filter_premium'); ?></button>
    </div>

    <div class="rooms-grid">
        <?php foreach ($rooms as $r):
            $amenities = $r['amenity_names'] ? explode('||', $r['amenity_names']) : [];
            $rating = $r['avg_rating'] ? round($r['avg_rating'], 1) : null;
            $typeLabel = $roomTypeLabels[$r['room_type']] ?? 'Standard';
            $typeClass = $roomTypeColors[$r['room_type']] ?? 'badge-standard';
            ?>
            <div class="room-card" data-type="<?php echo $r['room_type']; ?>">
                <div class="room-img">
                    <img src="<?php echo htmlspecialchars($r['image_url']); ?>"
                        alt="<?php echo htmlspecialchars(t($r['name'])); ?>" loading="lazy">
                    <div class="room-type-tag">
                        <span class="room-badge <?php echo $typeClass; ?>"><?php echo $typeLabel; ?></span>
                    </div>
                    <div class="room-badge <?php echo $r['status'] === 'available' ? 'badge-available' : 'badge-booked'; ?>"
                        style="top:14px;right:14px;position:absolute;">
                        <?php echo $r['status'] === 'available' ? t('available') : t('booked'); ?>
                    </div>
                </div>
                <div class="room-info">
                    <div class="d-flex justify-between align-center" style="margin-bottom:6px;">
                        <h3 class="room-name"><?php echo htmlspecialchars(t($r['name'])); ?></h3>
                        <?php if ($rating): ?>
                            <span
                                style="display:flex;align-items:center;gap:4px;font-size:0.82rem;color:var(--warning);font-weight:700;">
                                <i class="fa-solid fa-star"></i> <?php echo $rating; ?>
                                <span
                                    style="color:var(--text-muted);font-weight:400;">(<?php echo $r['review_count']; ?>)</span>
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="room-desc"><?php echo htmlspecialchars(t($r['description'])); ?></p>

                    <?php if (!empty($amenities)): ?>
                        <div class="room-amenities">
                            <?php foreach (array_slice($amenities, 0, 4) as $am): ?>
                                <span class="amenity-chip"><i class="fa-solid fa-check"
                                        style="font-size:0.65rem;color:var(--primary);"></i>
                                    <?php echo htmlspecialchars(t($am)); ?></span>
                            <?php endforeach; ?>
                            <?php if (count($amenities) > 4): ?>
                                <span class="amenity-chip">+<?php echo count($amenities) - 4; ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="room-footer">
                        <div class="room-price">
                            <div class="amount"><?php echo number_format($r['price'], 0, ',', '.'); ?>đ</div>
                            <div class="per-night"><?php echo t('night'); ?></div>
                        </div>
                        <div class="d-flex align-center" style="gap:8px;">
                            <span class="room-capacity"><i class="fa-solid fa-user"></i>
                                <?php echo $r['capacity']; ?></span>
                            <?php if ($r['status'] === 'available'): ?>
                                <a href="book_room.php?id=<?php echo $r['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fa-solid fa-calendar-check"></i> <?php echo t('book_room'); ?>
                                </a>
                            <?php else: ?>
                                <button class="btn btn-outline btn-sm" disabled
                                    style="opacity:0.5;"><?php echo t('booked'); ?></button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- SERVICES SECTION -->
<section class="section services-section" id="services">
    <div class="section-header">
        <div class="section-tag" style="background:rgba(201,168,76,0.15);color:var(--primary);">
            <?php echo t('svc_tag'); ?>
        </div>
        <h2 class="section-title" style="color:white;"><?php echo t('our_services'); ?></h2>
        <p class="section-desc" style="color:rgba(255,255,255,0.5);"><?php echo t('svc_desc'); ?></p>
    </div>
    <div class="services-grid" style="max-width:1300px;margin:0 auto;">
        <?php foreach ($services as $svc): ?>
            <div class="service-card">
                <div class="service-icon">
                    <i class="fa-solid <?php echo getServiceIcon($svc['name']); ?>"></i>
                </div>
                <div class="service-name"><?php echo htmlspecialchars(t($svc['name'])); ?></div>
                <div class="service-desc"><?php echo htmlspecialchars(t($svc['description'])); ?></div>
                <div class="service-price"><?php echo number_format($svc['price'], 0, ',', '.'); ?>đ</div>
            </div>
        <?php endforeach; ?>
        <!-- Fixed amenities cards -->
        <div class="service-card">
            <div class="service-icon"><i class="fa-solid fa-wifi"></i></div>
            <div class="service-name"><?php echo t('WiFi Tốc Độ Cao'); ?></div>
            <div class="service-desc"><?php echo t('Internet không giới hạn tại tất cả khu vực'); ?></div>
            <div class="service-price" style="color:var(--success);"><?php echo t('Miễn phí'); ?></div>
        </div>
        <div class="service-card">
            <div class="service-icon"><i class="fa-solid fa-swimming-pool"></i></div>
            <div class="service-name"><?php echo t('Hồ Bơi & Gym'); ?></div>
            <div class="service-desc"><?php echo t('Bể bơi vô cực và phòng tập gym hiện đại'); ?></div>
            <div class="service-price" style="color:var(--success);"><?php echo t('Miễn phí'); ?></div>
        </div>
        <div class="service-card">
            <div class="service-icon"><i class="fa-solid fa-utensils"></i></div>
            <div class="service-name"><?php echo t('Nhà Hàng Fine Dining'); ?></div>
            <div class="service-desc"><?php echo t('Ẩm thực Á-Âu với đầu bếp 5 sao'); ?></div>
            <div class="service-price"><?php echo t('Theo thực đơn'); ?></div>
        </div>
    </div>
</section>

<!-- REVIEWS SECTION -->
<section class="section" id="reviews" style="background:var(--gray-100);">
    <div class="section-header">
        <div class="section-tag"><?php echo t('reviews_tag'); ?></div>
        <h2 class="section-title"><?php echo t('reviews_title'); ?></h2>
        <p class="section-desc"><?php echo t('reviews_desc'); ?></p>
    </div>
    <?php if (!empty($reviews)): ?>
        <div class="reviews-grid" style="max-width:1300px;margin:0 auto;">
            <?php foreach ($reviews as $rv): ?>
                <div class="review-card">
                    <div class="review-stars">
                        <?php for ($i = 1; $i <= 5; $i++)
                            echo $i <= $rv['rating'] ? '★' : '☆'; ?>
                    </div>
                    <p class="review-comment">"<?php echo htmlspecialchars($rv['comment'] ?: t('no_reviews_fallback')); ?>"</p>
                    <div class="review-author">
                        <div class="review-avatar"><?php echo strtoupper(substr($rv['username'], 0, 1)); ?></div>
                        <div>
                            <div class="review-name"><?php echo htmlspecialchars($rv['username']); ?></div>
                            <div class="review-room"><?php echo htmlspecialchars($rv['room_name']); ?></div>
                            <div class="review-date"><?php echo date('d/m/Y', strtotime($rv['created_at'])); ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fa-regular fa-comments"></i>
            <p><?php echo t('no_reviews_yet_public'); ?> <a
                    href="<?php echo isset($_SESSION['user_id']) ? 'profile.php?tab=reviews' : 'login.php'; ?>"
                    style="color:var(--primary);"><?php echo t('write_review'); ?></a>!</p>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['user_id'])): ?>
        <div style="text-align:center;margin-top:40px;">
            <a href="profile.php?tab=reviews" class="btn btn-outline"><i class="fa-solid fa-pen"></i>
                <?php echo t('write_review'); ?></a>
        </div>
    <?php endif; ?>
</section>

<?php include 'footer.php'; ?>