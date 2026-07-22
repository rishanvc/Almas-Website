<?php
$pageTitle = 'Home';
$metaDesc = 'Almas Hospital - Providing quality healthcare services with advanced medical facilities and experienced doctors.';
require_once 'includes/header.php';
$homeContent = getPublishedContent('home');
$departments = getActiveDepartments();
$doctors = getActiveDoctors();
$gallery = getActiveGallery();
$branches = getActiveBranches();
$settings = getSetting();
?>
<section class="hero">
    <div class="container">
        <h1>Welcome to <span><?= sanitizeInput($settings['website_name'] ?? 'Almas Hospital') ?></span></h1>
        <p>Providing quality healthcare services with compassion, advanced technology, and a team of experienced medical professionals dedicated to your well-being.</p>
        <a href="<?= SITE_URL ?>/appointment.php" class="btn btn-primary"><i class="fas fa-calendar-check"></i> Book an Appointment</a>
        <a href="<?= SITE_URL ?>/contact.php" class="btn btn-outline"><i class="fas fa-phone-alt"></i> Contact Us</a>
        <div class="hero-stats">
            <div class="hero-stat">
                <span class="number">15+</span>
                <span class="label">Years of Service</span>
            </div>
            <div class="hero-stat">
                <span class="number">50+</span>
                <span class="label">Expert Doctors</span>
            </div>
            <div class="hero-stat">
                <span class="number">30+</span>
                <span class="label">Departments</span>
            </div>
            <div class="hero-stat">
                <span class="number">10K+</span>
                <span class="label">Happy Patients</span>
            </div>
        </div>
    </div>
</section>

<?php if ($homeContent): ?>
<section class="section">
    <div class="container">
        <div class="content-area"><?= $homeContent['content'] ?></div>
    </div>
</section>
<?php endif; ?>

<section class="section bg-light-section">
    <div class="container">
        <h2 class="section-title">Why Choose Us</h2>
        <p class="section-subtitle">We are committed to providing the highest quality medical care</p>
        <div class="features-grid">
            <div class="feature-card animate-in">
                <div class="icon"><i class="fas fa-user-md"></i></div>
                <h3>Expert Doctors</h3>
                <p>Highly qualified and experienced medical professionals across all specialties.</p>
            </div>
            <div class="feature-card animate-in animate-in-delay-1">
                <div class="icon"><i class="fas fa-microscope"></i></div>
                <h3>Advanced Technology</h3>
                <p>State-of-the-art medical equipment and modern diagnostic facilities.</p>
            </div>
            <div class="feature-card animate-in animate-in-delay-2">
                <div class="icon"><i class="fas fa-heart"></i></div>
                <h3>Compassionate Care</h3>
                <p>Patient-centric approach with personalized treatment plans.</p>
            </div>
            <div class="feature-card animate-in animate-in-delay-3">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <h3>24/7 Emergency</h3>
                <p>Round-the-clock emergency services with rapid response teams.</p>
            </div>
        </div>
    </div>
</section>

<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item">
                <i class="fas fa-calendar-check"></i>
                <span class="stat-number">15+</span>
                <span class="stat-label">Years Experience</span>
            </div>
            <div class="stat-item">
                <i class="fas fa-user-md"></i>
                <span class="stat-number">50+</span>
                <span class="stat-label">Expert Doctors</span>
            </div>
            <div class="stat-item">
                <i class="fas fa-building"></i>
                <span class="stat-number">30+</span>
                <span class="stat-label">Departments</span>
            </div>
            <div class="stat-item">
                <i class="fas fa-smile"></i>
                <span class="stat-number">10K+</span>
                <span class="stat-label">Happy Patients</span>
            </div>
            <div class="stat-item">
                <i class="fas fa-trophy"></i>
                <span class="stat-number">5+</span>
                <span class="stat-label">Awards</span>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2 class="section-title">Our Departments</h2>
        <p class="section-subtitle">Comprehensive medical care across all specialties</p>
        <div class="row">
            <?php foreach (array_slice($departments, 0, 6) as $dept): ?>
            <div class="col-4">
                <div class="card department-card">
                    <?php if ($dept['image']): ?>
                    <div class="card-img-wrapper">
                        <img src="<?= SITE_URL . '/' . sanitizeInput($dept['image']) ?>" alt="<?= sanitizeInput($dept['department_name']) ?>" class="card-img">
                    </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h3 class="card-title"><?= sanitizeInput($dept['department_name']) ?></h3>
                        <p class="card-text"><?= substr(strip_tags($dept['description']), 0, 140) ?>...</p>
                        <a href="<?= SITE_URL ?>/department.php?id=<?= $dept['id'] ?>" class="btn-dept-explore">Explore &rarr;</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-20">
            <a href="<?= SITE_URL ?>/departments.php" class="btn btn-primary">View All Departments <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</section>

<section class="section bg-light-section">
    <div class="container">
        <h2 class="section-title">Our Doctors</h2>
        <p class="section-subtitle">Experienced and compassionate medical professionals</p>
        <div class="row">
            <?php foreach (array_slice($doctors, 0, 4) as $doc): ?>
            <div class="col-3">
                <div class="card doctor-card">
                    <?php if ($doc['photo']): ?>
                    <img src="<?= SITE_URL . '/' . sanitizeInput($doc['photo']) ?>" alt="<?= sanitizeInput($doc['name']) ?>" class="card-img">
                    <?php else: ?>
                    <div class="card-img" style="background:var(--light-bg);display:flex;align-items:center;justify-content:center;font-size:40px;color:var(--primary);"><i class="fas fa-user-md"></i></div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h3 class="card-title"><?= sanitizeInput($doc['name']) ?></h3>
                        <p class="designation"><?= sanitizeInput($doc['designation'] ?? '') ?></p>
                        <p class="card-text"><?= sanitizeInput($doc['specialization']) ?></p>
                        <a href="<?= SITE_URL ?>/doctor.php?id=<?= $doc['id'] ?>" class="btn-sm"><i class="fas fa-user"></i> View Profile</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-20">
            <a href="<?= SITE_URL ?>/doctors.php" class="btn btn-primary">View All Doctors <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2 class="section-title">Gallery</h2>
        <p class="section-subtitle">A glimpse into our hospital and facilities</p>
        <div class="gallery-grid">
            <?php foreach (array_slice($gallery, 0, 6) as $item): ?>
            <div class="gallery-item">
                <img src="<?= SITE_URL . '/' . sanitizeInput($item['image']) ?>" alt="<?= sanitizeInput($item['title']) ?>">
                <div class="overlay"><?= sanitizeInput($item['title']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-20">
            <a href="<?= SITE_URL ?>/gallery.php" class="btn btn-primary">View Full Gallery <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</section>

<?php if ($branches): ?>
<section class="section bg-light-section">
    <div class="container">
        <h2 class="section-title">Our Branches</h2>
        <p class="section-subtitle">Find a branch near you</p>
        <div class="row">
            <?php foreach ($branches as $branch): ?>
            <div class="col-4">
                <div class="card branch-card">
                    <?php if ($branch['image']): ?>
                    <img src="<?= SITE_URL . '/' . sanitizeInput($branch['image']) ?>" alt="<?= sanitizeInput($branch['branch_name']) ?>" class="card-img">
                    <?php endif; ?>
                    <div class="card-body">
                        <h3 class="card-title"><i class="fas fa-map-marked-alt"></i> <?= sanitizeInput($branch['branch_name']) ?></h3>
                        <p class="card-text"><i class="fas fa-map-marker-alt"></i> <?= sanitizeInput($branch['address']) ?></p>
                        <p><strong>Phone:</strong> <?= sanitizeInput($branch['phone'] ?? '') ?></p>
                        <p><strong>Email:</strong> <?= sanitizeInput($branch['email'] ?? '') ?></p>
                        <?php if ($branch['google_map']): ?>
                        <a href="<?= sanitizeInput($branch['google_map']) ?>" target="_blank" class="btn-sm"><i class="fas fa-map"></i> View on Map</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
