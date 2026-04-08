<?php require_once 'lang.php'; ?>
</div><!-- end main-content -->

<footer class="footer" id="footer">
    <div class="footer-grid" style="max-width:1300px;margin:0 auto;">
        <div class="footer-brand">
            <div class="logo-text"><i class="fa-solid fa-hotel"></i> Royal Hotel x NĐT</div>
            <p><?php echo t('footer_desc'); ?></p>
            <div class="social-links" style="margin-top:20px;">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" aria-label="TripAdvisor"><i class="fab fa-tripadvisor"></i></a>
            </div>
        </div>
        <div class="footer-col">
            <h4><?php echo t('quick_links'); ?></h4>
            <ul>
                <li><a href="index.php"><i class="fa-solid fa-chevron-right fa-xs"></i> <?php echo t('home'); ?></a>
                </li>
                <li><a href="index.php#rooms"><i class="fa-solid fa-chevron-right fa-xs"></i>
                        <?php echo t('rooms'); ?></a></li>
                <li><a href="index.php#services"><i class="fa-solid fa-chevron-right fa-xs"></i>
                        <?php echo t('services'); ?></a></li>
                <li><a href="index.php#reviews"><i class="fa-solid fa-chevron-right fa-xs"></i>
                        <?php echo t('reviews'); ?></a></li>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <li><a href="register.php"><i class="fa-solid fa-chevron-right fa-xs"></i>
                            <?php echo t('register'); ?></a></li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="footer-col">
            <h4><?php echo t('contact_info'); ?></h4>
            <ul class="footer-contact">
                <li><i class="fa-solid fa-location-dot"></i> Nha Trang, Khánh Hòa</li>
                <li><i class="fa-solid fa-phone"></i>096 339 2741</li>
                <li><i class="fa-solid fa-envelope"></i> dinhthanh13112006@gmail.com </li>
                <li><i class="fa-solid fa-clock"></i> 24/7 – Check-in 14:00, Check-out 12:00</li>
            </ul>
        </div>
        <div class="footer-col">
            <h4><?php echo t('footer_amenities'); ?></h4>
            <ul>
                <li><a href="#"><i class="fa-solid fa-wifi fa-xs"></i> <?php echo t('footer_free_wifi'); ?></a></li>
                <li><a href="#"><i class="fa-solid fa-car fa-xs"></i> <?php echo t('footer_parking'); ?></a></li>
                <li><a href="#"><i class="fa-solid fa-spa fa-xs"></i> <?php echo t('footer_spa_fitness'); ?></a></li>
                <li><a href="#"><i class="fa-solid fa-utensils fa-xs"></i> <?php echo t('footer_restaurant'); ?></a>
                </li>
                <li><a href="#"><i class="fa-solid fa-swimming-pool fa-xs"></i> <?php echo t('footer_pool'); ?></a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom" style="max-width:1300px;margin:0 auto;">
        <span><?php echo t('copyright'); ?></span>
        <span style="color:rgba(255,255,255,0.3);"><?php echo t('footer_powered_by'); ?></span>
    </div>
</footer>

<div class="toast-container" id="toastContainer"></div>

<!-- ===== FLOATING CONTACT BUBBLE ===== -->
<div id="contactBubble" class="contact-bubble-wrapper">
    <!-- Các nút liên hệ (hiện ra khi mở) -->
    <div class="contact-options" id="contactOptions">

        <!-- Gọi điện -->
        <a href="tel:0963392741" class="contact-option phone" title="Gọi điện ngay">
            <div class="contact-option-icon">
                <i class="fa-solid fa-phone"></i>
            </div>
            <span class="contact-option-label"><?php echo t('contact_call'); ?></span>
        </a>

        <!-- Zalo -->
        <a href="https://zalo.me/0963392741" target="_blank" class="contact-option zalo" title="Nhắn tin Zalo">
            <div class="contact-option-icon">
                <svg viewBox="0 0 50 50" fill="currentColor" width="22" height="22">
                    <path
                        d="M25 2C12.32 2 2 11.18 2 22.5c0 6.47 3.47 12.23 8.91 16.03L9 40.5l.07.04L8 47l7.38-3.69A25.13 25.13 0 0025 44c12.68 0 23-9.18 23-20.5S37.68 2 25 2zm-8.5 27H13v-13h3.5v13zm-1.75-14.75a2 2 0 110-4 2 2 0 010 4zM37 29h-3.5v-6.75c0-1.8-1.12-2.75-2.5-2.75S28.5 20.45 28.5 22.25V29H25V16h3.5v1.65C29.62 16.6 31 16 32.5 16c2.76 0 4.5 1.88 4.5 5V29z" />
                </svg>
            </div>
            <span class="contact-option-label"><?php echo t('contact_zalo'); ?></span>
        </a>

        <!-- Messenger -->
        <a href="https://www.messenger.com/e2ee/t/29501870559427008" target="_blank" class="contact-option messenger"
            title="Nhắn tin Messenger">
            <div class="contact-option-icon">
                <i class="fab fa-facebook-messenger"></i>
            </div>
            <span class="contact-option-label"><?php echo t('contact_messenger'); ?></span>
        </a>

    </div>

    <!-- Nhãn tooltip -->
    <div class="contact-bubble-tooltip" id="contactTooltip">
        <?php echo t('contact_tooltip'); ?>
    </div>

    <!-- Nút chính -->
    <button class="contact-bubble-btn" id="contactBubbleBtn" onclick="toggleContactBubble()"
        aria-label="Liên hệ đặt phòng">
        <i class="fa-solid fa-headset" id="bubbleIcon"></i>
        <span class="bubble-pulse"></span>
    </button>
</div>

<style>
    /* ===== CONTACT BUBBLE STYLES ===== */
    .contact-bubble-wrapper {
        position: fixed;
        bottom: 32px;
        right: 28px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 10px;
    }

    /* Tooltip */
    .contact-bubble-tooltip {
        background: rgba(15, 15, 26, 0.92);
        color: #C9A84C;
        font-size: 0.78rem;
        font-weight: 700;
        padding: 5px 14px;
        border-radius: 20px;
        border: 1px solid rgba(201, 168, 76, 0.3);
        white-space: nowrap;
        letter-spacing: 0.3px;
        animation: tooltipBounce 2.5s ease-in-out infinite;
        pointer-events: none;
        backdrop-filter: blur(10px);
    }

    @keyframes tooltipBounce {

        0%,
        100% {
            transform: translateY(0);
            opacity: 1;
        }

        50% {
            transform: translateY(-4px);
            opacity: 0.85;
        }
    }

    /* Main button */
    .contact-bubble-btn {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #C9A84C, #A8873A);
        border: none;
        color: white;
        font-size: 1.4rem;
        cursor: pointer;
        box-shadow: 0 6px 24px rgba(201, 168, 76, 0.45);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .contact-bubble-btn:hover {
        transform: scale(1.1) rotate(-8deg);
        box-shadow: 0 10px 32px rgba(201, 168, 76, 0.6);
    }

    .contact-bubble-btn.open {
        background: linear-gradient(135deg, #374151, #1f2937);
        transform: rotate(45deg);
        box-shadow: 0 6px 24px rgba(0, 0, 0, 0.3);
    }

    .contact-bubble-btn.open:hover {
        transform: rotate(45deg) scale(1.05);
    }

    /* Pulse ring */
    .bubble-pulse {
        position: absolute;
        inset: -4px;
        border-radius: 50%;
        border: 2px solid rgba(201, 168, 76, 0.5);
        animation: pulsRing 2s ease-out infinite;
        pointer-events: none;
    }

    @keyframes pulsRing {
        0% {
            transform: scale(1);
            opacity: 0.8;
        }

        100% {
            transform: scale(1.55);
            opacity: 0;
        }
    }

    /* Contact options container */
    .contact-options {
        display: flex;
        flex-direction: column;
        gap: 10px;
        align-items: flex-end;
        opacity: 0;
        visibility: hidden;
        transform: translateY(16px) scale(0.92);
        transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        pointer-events: none;
    }

    .contact-options.open {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
        pointer-events: auto;
    }

    /* Stagger children */
    .contact-option:nth-child(1) {
        transition-delay: 0.06s;
    }

    .contact-option:nth-child(2) {
        transition-delay: 0.12s;
    }

    .contact-option:nth-child(3) {
        transition-delay: 0.18s;
    }

    /* Individual option */
    .contact-option {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        cursor: pointer;
        opacity: 0;
        transform: translateX(20px);
        transition: all 0.3s ease;
    }

    .contact-options.open .contact-option {
        opacity: 1;
        transform: translateX(0);
    }

    .contact-options.open .contact-option:nth-child(1) {
        transition-delay: 0.05s;
    }

    .contact-options.open .contact-option:nth-child(2) {
        transition-delay: 0.12s;
    }

    .contact-options.open .contact-option:nth-child(3) {
        transition-delay: 0.18s;
    }

    /* Label */
    .contact-option-label {
        background: rgba(15, 15, 26, 0.88);
        color: white;
        font-size: 0.8rem;
        font-weight: 700;
        padding: 5px 12px;
        border-radius: 20px;
        white-space: nowrap;
        letter-spacing: 0.3px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.2);
    }

    /* Icon circle */
    .contact-option-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        color: white;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        flex-shrink: 0;
    }

    .contact-option:hover .contact-option-icon {
        transform: scale(1.12);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    }

    /* Colors */
    .contact-option.phone .contact-option-icon {
        background: linear-gradient(135deg, #10b981, #059669);
    }

    .contact-option.zalo .contact-option-icon {
        background: linear-gradient(135deg, #006AF5, #0053C1);
    }

    .contact-option.messenger .contact-option-icon {
        background: linear-gradient(135deg, #00B2FF, #7B2FFF);
    }

    /* Ripple effect on hover */
    .contact-option-icon::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.15);
        opacity: 0;
        transition: opacity 0.2s;
    }

    .contact-option:hover .contact-option-icon::after {
        opacity: 1;
    }

    .contact-option-icon {
        position: relative;
        overflow: hidden;
    }

    @media (max-width: 480px) {
        .contact-bubble-wrapper {
            bottom: 20px;
            right: 16px;
        }

        .contact-bubble-btn {
            width: 54px;
            height: 54px;
            font-size: 1.2rem;
        }

        .contact-option-icon {
            width: 44px;
            height: 44px;
            font-size: 1.1rem;
        }
    }
</style>

<script>
    // Navbar scroll effect
    const nav = document.getElementById('mainNav');
    if (nav) window.addEventListener('scroll', () => {
        nav.classList.toggle('scrolled', window.scrollY > 60);
    });

    // Hamburger menu
    const ham = document.getElementById('hamburger');
    const navLinks = document.getElementById('navLinks');
    if (ham) ham.addEventListener('click', () => {
        navLinks.classList.toggle('open');
        ham.classList.toggle('open');
    });

    // Toast notification
    function showToast(msg, type = 'success') {
        const tc = document.getElementById('toastContainer');
        const icon = type === 'success' ? 'circle-check' : (type === 'error' ? 'circle-xmark' : 'triangle-exclamation');
        const t = document.createElement('div');
        t.className = `toast ${type}`;
        t.innerHTML = `<i class="fa-solid fa-${icon}"></i><span>${msg}</span>`;
        tc.appendChild(t);
        setTimeout(() => { t.style.opacity = '0'; t.style.transform = 'translateX(100px)'; setTimeout(() => t.remove(), 300); }, 3500);
    }

    // Room filter
    function filterRooms(type) {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        event.target.classList.add('active');
        document.querySelectorAll('.room-card').forEach(c => {
            c.style.display = (type === 'all' || c.dataset.type === type) ? '' : 'none';
        });
    }

    // Animate on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) { e.target.style.opacity = '1'; e.target.style.transform = 'translateY(0)'; }
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.room-card, .service-card, .review-card, .stat-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.5s ease';
        observer.observe(el);
    });

    // ===== CONTACT BUBBLE =====
    let bubbleOpen = false;
    const bubbleBtn = document.getElementById('contactBubbleBtn');
    const options = document.getElementById('contactOptions');
    const tooltip = document.getElementById('contactTooltip');
    const bubbleIcon = document.getElementById('bubbleIcon');

    function toggleContactBubble() {
        bubbleOpen = !bubbleOpen;
        options.classList.toggle('open', bubbleOpen);
        bubbleBtn.classList.toggle('open', bubbleOpen);
        tooltip.style.display = bubbleOpen ? 'none' : '';
        bubbleIcon.className = bubbleOpen ? 'fa-solid fa-xmark' : 'fa-solid fa-headset';
    }

    // Đóng khi click ngoài
    document.addEventListener('click', (e) => {
        if (bubbleOpen && !document.getElementById('contactBubble').contains(e.target)) {
            toggleContactBubble();
        }
    });

    // Ẩn tooltip sau 5 giây
    setTimeout(() => {
        if (!bubbleOpen && tooltip) tooltip.style.opacity = '0';
    }, 5000);
</script>
</body>

</html>