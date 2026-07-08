<?php $settings = getSetting(); ?>
</main>

<?php
// Dynamic matching of specialty names to database departments
$db_departments = getActiveDepartments();
$dept_links = [];
foreach ($db_departments as $dept) {
    $clean_name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $dept['department_name']));
    $dept_links[$clean_name] = SITE_URL . '/department.php?id=' . $dept['id'];
}

function getSpecialtyLink($name, $dept_links) {
    $clean_name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
    if (isset($dept_links[$clean_name])) {
        return $dept_links[$clean_name];
    }
    foreach ($dept_links as $c_name => $link) {
        if (strpos($clean_name, $c_name) !== false || strpos($c_name, $clean_name) !== false) {
            return $link;
        }
    }
    return SITE_URL . '/departments.php';
}

$col1_specialities = [
    'Almas Aesthetics',
    'Almas IVF Center',
    'Anesthesiology',
    'Critical Care Medicine',
    'Dental And Maxillofacial Surgery',
    'Dermatology & Cosmetology',
    'Emergency & Trauma Care',
    'Endocrinology & Diabetology',
    'ENT, Head & Neck Surgery',
    'Fetal Medicine',
    'Gastro Sciences',
    'General & Advanced Laparoscopic Surgery',
    'General Medicine',
    'Interventional Cardiology',
    'Nephrology & Dialysis',
    'Neuroscience'
];

$col2_specialities = [
    'Nutrition & Dietetics',
    'Obstetrics and Gynecology',
    'Oncology',
    'Ophthalmology',
    'Orthopedic, Joint Replacement & Robotic Surgery',
    'Pediatric Critical Care Medicine',
    'Pediatric Medicine & Neonatology',
    'Pediatric Surgery',
    'Physical Medicine & Rehabilitation',
    'Physiotherapy',
    'Plastic, Microvascular, Reconstructive & Cosmetic surgery',
    'Psychiatry & Psychology',
    'Pulmonology',
    'Radio Diagnosis & Advanced Imaging',
    'Urology & Andrology'
];

$quick_links = [
    'Home' => SITE_URL . '/index.php',
    'About' => SITE_URL . '/about.php',
    'Blood Center' => '#',
    'International Patient Services' => '#',
    'TPA & Insurance Services' => '#',
    'Blogs' => '#',
    'Health Checkup' => '#',
    'Home Care Services' => '#',
    'Postgraduate Programs' => '#',
    'Laboratory Medicine' => '#',
    'Branches' => SITE_URL . '/branches.php',
    'Careers' => SITE_URL . '/careers.php',
    'Gallery' => SITE_URL . '/gallery.php',
    'Biomedical Report' => '#'
];
?>

<footer class="footer-new">
    <div class="footer-container">
        <!-- Top Booking & Emergency Bar -->
        <div class="footer-top-row">
            <div class="footer-top-card">
                <div class="card-icon-wrapper"><i class="far fa-calendar-alt"></i></div>
                <div class="card-info-content">
                    <span class="footer-top-label">For booking</span>
                    <span class="footer-top-val">0483 2809 100, 0483 3509 100</span>
                </div>
            </div>
            <div class="footer-top-card">
                <div class="card-icon-wrapper"><i class="fas fa-phone-volume"></i></div>
                <div class="card-info-content">
                    <span class="footer-top-label">Emergency</span>
                    <span class="footer-top-val">+91 9544 070707</span>
                </div>
            </div>
            <div class="footer-top-card">
                <div class="card-icon-wrapper"><i class="far fa-envelope-open"></i></div>
                <div class="card-info-content">
                    <span class="footer-top-label">Patient Enquiries</span>
                    <span class="footer-top-val"><a href="mailto:info@almashospital.com">info@almashospital.com</a></span>
                </div>
            </div>
        </div>

        <div class="footer-divider-container">
            <div class="footer-divider-glow"></div>
        </div>

        <!-- Main Footer Links & Info Grid -->
        <div class="footer-grid">
            <!-- Specialities Columns -->
            <div class="footer-col-specialities">
                <h4 class="footer-heading">Specialities</h4>
                <div class="specialities-subgrid">
                    <div class="specialities-col">
                        <?php foreach ($col1_specialities as $spec): ?>
                            <a href="<?= getSpecialtyLink($spec, $dept_links) ?>" class="footer-animated-link"><?= $spec ?></a>
                        <?php endforeach; ?>
                    </div>
                    <div class="specialities-col">
                        <?php foreach ($col2_specialities as $spec): ?>
                            <a href="<?= getSpecialtyLink($spec, $dept_links) ?>" class="footer-animated-link"><?= $spec ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Links Column -->
            <div class="footer-col-links">
                <h4 class="footer-heading">Quick Links</h4>
                <div class="quick-links-list">
                    <?php foreach ($quick_links as $label => $url): ?>
                        <a href="<?= $url ?>" class="footer-animated-link"><?= $label ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Logo, Contact & Newsletter Column -->
            <div class="footer-col-info">
                <!-- Logo -->
                <div class="footer-logo-row">
                    <svg class="footer-logo-svg" viewBox="0 0 120 120" width="48" height="48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Left Loop / Heart shape tilted left -->
                        <path d="M 45,85 C 20,85 10,65 10,45 C 10,25 25,10 45,10 C 65,10 75,30 75,45 C 75,65 60,80 45,85 Z" stroke="currentColor" stroke-width="7" stroke-linecap="round" stroke-linejoin="round" />
                        <!-- Right Loop / Heart shape tilted right interlocking -->
                        <path d="M 75,85 C 100,85 110,65 110,45 C 110,25 95,10 75,10 C 55,10 45,30 45,45 C 45,65 60,80 75,85 Z" stroke="currentColor" stroke-width="7" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <div class="footer-logo-text">
                        <span class="brand-almas">ALMAS</span>
                        <span class="brand-hospital">HOSPITAL</span>
                    </div>
                </div>

                <div class="footer-address-block">
                    <p class="address-text">Almas Junction, Kottakkal,<br>Malappuram Kerala India,<br>Pincode: 676503</p>
                    <p class="contact-text">Email: <a href="mailto:info@almashospital.com">info@almashospital.com</a></p>
                    <p class="contact-text">Phone: 0483 2809 100</p>
                    <p class="contact-text">0483 3509 100</p>
                </div>

                <hr class="info-divider">

                <div class="footer-newsletter">
                    <h5 class="newsletter-heading">Let's Stay in Touch</h5>
                    <form class="newsletter-form" action="#" method="POST" onsubmit="event.preventDefault(); alert('Subscribed successfully!');">
                        <input type="email" placeholder="Please enter your email" required class="newsletter-input">
                        <button type="submit" class="newsletter-submit" aria-label="Subscribe">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                    <span class="newsletter-privacy">We Never Spam You! 100% Privacy..</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom copyright bar -->
    <div class="footer-bottom-bar">
        <div class="footer-bottom-container">
            <span class="copyright-text">&copy; Copyright 2026 | ALMAS HOSPITAL</span>
            <div class="footer-socials">
                <span class="follow-label">Follow us on:</span>
                <a href="<?= sanitizeInput($settings['facebook'] ?? '#') ?>" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="<?= sanitizeInput($settings['instagram'] ?? '#') ?>" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="<?= sanitizeInput($settings['linkedin'] ?? '#') ?>" target="_blank" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                <a href="<?= sanitizeInput($settings['youtube'] ?? '#') ?>" target="_blank" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
            </div>
        </div>
    </div>
</footer>

<!-- Floating WhatsApp Button -->
<a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $settings['whatsapp'] ?? '919544070707') ?>" class="whatsapp-floating" target="_blank" aria-label="Chat on WhatsApp">
    <div class="whatsapp-pulse"></div>
    <i class="fab fa-whatsapp"></i>
</a>

<!-- Floating Back to Top Button -->
<button onclick="scrollToTop()" class="scroll-to-top" id="scrollTopBtn" aria-label="Back to top">
    <i class="fas fa-chevron-up"></i>
</button>

<script>
// Scroll to Top functionality
window.onscroll = function() { scrollFunction() };

function scrollFunction() {
    var mybutton = document.getElementById("scrollTopBtn");
    if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
        mybutton.classList.add("show");
    } else {
        mybutton.classList.remove("show");
    }
}

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}
</script>

<script src="<?= SITE_URL ?>/assets/js/script.js"></script>
</body>
</html>
