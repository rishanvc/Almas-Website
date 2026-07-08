<?php
$pageTitle = 'Search';
$metaDesc = 'Search Almas Hospital website for departments, doctors, and more.';
require_once 'includes/header.php';
$query = isset($_GET['q']) ? sanitize(trim($_GET['q'])) : '';
$results = [];

if (!empty($query)) {
    $searchTerm = '%' . $query . '%';
    $results['departments'] = [];
    $results['doctors'] = [];
    $results['pages'] = [];

    $stmt = mysqli_prepare($conn, "SELECT id, department_name as title, description as content FROM departments WHERE status = 'Active' AND (department_name LIKE ? OR description LIKE ?) LIMIT 10");
    mysqli_stmt_bind_param($stmt, 'ss', $searchTerm, $searchTerm);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) { $results['departments'][] = $row; }
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "SELECT id, name as title, specialization as content FROM doctors WHERE status = 'Active' AND (name LIKE ? OR specialization LIKE ? OR qualification LIKE ?) LIMIT 10");
    mysqli_stmt_bind_param($stmt, 'sss', $searchTerm, $searchTerm, $searchTerm);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) { $results['doctors'][] = $row; }
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "SELECT page_name, title, SUBSTRING(content, 1, 200) as content FROM website_contents WHERE status = 'Published' AND (title LIKE ? OR content LIKE ?) LIMIT 10");
    mysqli_stmt_bind_param($stmt, 'ss', $searchTerm, $searchTerm);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) { $results['pages'][] = $row; }
    mysqli_stmt_close($stmt);
}
?>
<section class="page-header">
    <div class="container">
        <h1><i class="fas fa-search"></i> Search</h1>
        <p>Find what you're looking for</p>
    </div>
</section>
<section class="section">
    <div class="container">
        <div class="search-box">
            <form method="GET" action="">
                <div class="d-flex gap-15">
                    <input type="text" name="q" class="form-control" placeholder="Search departments, doctors, pages..." value="<?= sanitizeInput($query) ?>">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>
        </div>

        <?php if (!empty($query)): ?>
            <div class="mt-20">
                <p><i class="fas fa-search"></i> <strong>Search results for: "<?= sanitizeInput($query) ?>"</strong></p>
                <?php
                $totalResults = count($results['departments']) + count($results['doctors']) + count($results['pages']);
                if ($totalResults > 0): ?>
                    <?php if (count($results['departments']) > 0): ?>
                    <h3 class="section-title"><i class="fas fa-building"></i> <strong>Departments</strong></h3>
                    <div class="row">
                        <?php foreach ($results['departments'] as $dept): ?>
                        <div class="col-6">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title"><i class="fas fa-building"></i> <?= sanitizeInput($dept['title']) ?></h4>
                                    <p class="card-text"><?= substr(strip_tags($dept['content']), 0, 150) ?>...</p>
                                    <a href="<?= SITE_URL ?>/department.php?id=<?= $dept['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-arrow-right"></i> View Details</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (count($results['doctors']) > 0): ?>
                    <h3 class="section-title"><i class="fas fa-user-md"></i> <strong>Doctors</strong></h3>
                    <div class="row">
                        <?php foreach ($results['doctors'] as $doc): ?>
                        <div class="col-6">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title"><i class="fas fa-user-md"></i> <?= sanitizeInput($doc['title']) ?></h4>
                                    <p class="card-text"><?= sanitizeInput($doc['content']) ?></p>
                                    <a href="<?= SITE_URL ?>/doctor.php?id=<?= $doc['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-user-md"></i> View Profile</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (count($results['pages']) > 0): ?>
                    <h3 class="section-title"><i class="fas fa-file"></i> <strong>Pages</strong></h3>
                    <div class="row">
                        <?php foreach ($results['pages'] as $page): ?>
                        <div class="col-6">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title"><i class="fas fa-file"></i> <?= sanitizeInput($page['title']) ?></h4>
                                    <p class="card-text"><?= strip_tags(substr($page['content'], 0, 150)) ?>...</p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center mt-20">
                        <p><i class="fas fa-info-circle"></i> No results found for "<?= sanitizeInput($query) ?>". Please try different keywords.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
