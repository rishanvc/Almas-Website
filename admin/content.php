<?php
$pageTitle = 'Website Content';
require_once 'header.php';
if (!hasAnyRole(['Administrator', 'Content Creator', 'Content Approver'])) { redirect('index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $pageName = sanitize($_POST['page_name']);
    $title = sanitize($_POST['title']);
    $content = $_POST['content'] ?? '';
    $contentId = isset($_POST['content_id']) ? (int)$_POST['content_id'] : 0;
    $image = '';

    // Build content from blocks when using the About block editor
    if ($pageName === 'about' && isset($_POST['block_content']) && is_array($_POST['block_content'])) {
        $headings = $_POST['block_heading'] ?? [];
        $contents = $_POST['block_content'] ?? [];
        $builtHtml = '';
        foreach ($contents as $i => $blockContent) {
            $blockContent = trim($blockContent);
            if ($blockContent === '') continue;
            $heading = isset($headings[$i]) ? trim($headings[$i]) : '';
            if ($heading) {
                $builtHtml .= '<h3 class="about-block-heading">' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . "</h3>\n";
            }
            $paragraphs = preg_split('/\n\s*\n/', $blockContent);
            foreach ($paragraphs as $p) {
                $p = trim($p);
                if ($p !== '') {
                    $builtHtml .= '<p>' . nl2br(htmlspecialchars($p, ENT_QUOTES, 'UTF-8')) . "</p>\n";
                }
            }
        }
        $content = $builtHtml;
    }

    if (!empty($_FILES['featured_image']['name'])) {
        $upload = uploadFile($_FILES['featured_image'], UPLOAD_PATH . '/gallery');
        if ($upload['success']) $image = $upload['path'];
    }

    $status = 'Pending';
    if (hasRole('Administrator')) $status = 'Published';

    if ($contentId) {
        if ($image) {
            $stmt = mysqli_prepare($conn, "UPDATE website_contents SET page_name=?, title=?, content=?, featured_image=?, status=?, updated_by=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssssii', $pageName, $title, $content, $image, $status, $_SESSION['user_id'], $contentId);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE website_contents SET page_name=?, title=?, content=?, status=?, updated_by=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssssii', $pageName, $title, $content, $status, $_SESSION['user_id'], $contentId);
        }
    } else {
        if ($image) {
            $stmt = mysqli_prepare($conn, "INSERT INTO website_contents (page_name, title, content, featured_image, status, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'sssssii', $pageName, $title, $content, $image, $status, $_SESSION['user_id'], $_SESSION['user_id']);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO website_contents (page_name, title, content, status, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'ssssii', $pageName, $title, $content, $status, $_SESSION['user_id'], $_SESSION['user_id']);
        }
    }
    mysqli_stmt_execute($stmt);
    $newId = $contentId ?: mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // Save About sub-sections when page_name is 'about'
    if ($pageName === 'about') {
        $subSections = [
            'about_gallery' => [
                'title' => sanitize($_POST['gallery_title'] ?? ''),
                'content' => $_POST['gallery_content'] ?? '',
                'is_gallery' => true,
            ],
            'about_mission' => [
                'title' => sanitize($_POST['mission_title'] ?? ''),
                'content' => $_POST['mission_content'] ?? '',
            ],
            'about_vision' => [
                'title' => sanitize($_POST['vision_title'] ?? ''),
                'content' => $_POST['vision_content'] ?? '',
            ],
        ];

        // Handle gallery multi-image upload
        if (!empty($_FILES['gallery_images']['name'][0]) && $_FILES['gallery_images']['name'][0] !== '') {
            $files = $_FILES['gallery_images'];
            $fileCount = count($files['name']);
            $galleryHtml = '';
            for ($i = 0; $i < $fileCount; $i++) {
                if ($files['error'][$i] === 0) {
                    $file = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i],
                    ];
                    $upload = uploadFile($file, UPLOAD_PATH . '/gallery');
                    if ($upload['success']) {
                        $galleryHtml .= '<figure class="about-gallery-item"><img src="' . SITE_URL . '/' . $upload['path'] . '" alt="" width="672" height="448" loading="lazy"></figure>';
                    }
                }
            }
            if ($galleryHtml) {
                $subSections['about_gallery']['content'] .= "\n" . $galleryHtml;
            }
        }

        // Handle mission and vision featured images
        foreach (['mission', 'vision'] as $section) {
            $field = $section . '_image';
            $removeField = $section . '_remove_image';
            if (!empty($_FILES[$field]['name'])) {
                $upload = uploadFile($_FILES[$field], UPLOAD_PATH . '/gallery');
                if ($upload['success']) {
                    $subSections['about_' . $section]['image'] = $upload['path'];
                }
            } elseif (!empty($_POST[$removeField])) {
                $subSections['about_' . $section]['remove_image'] = true;
            }
        }

        // Handle gallery image removals
        $removeGallery = isset($_POST['remove_gallery']) ? $_POST['remove_gallery'] : [];
        if (!empty($removeGallery) && is_array($removeGallery)) {
            $galleryContent = $subSections['about_gallery']['content'];
            preg_match_all('/<figure[^>]*>.*?<\/figure>/s', $galleryContent, $matches);
            $existingFigures = $matches[0] ?? [];
            $filteredContent = '';
            foreach ($existingFigures as $idx => $figure) {
                if (!in_array((string)$idx, $removeGallery, true)) {
                    $filteredContent .= $figure . "\n";
                }
            }
            $subSections['about_gallery']['content'] = trim($filteredContent);
        }

        $subStatus = hasRole('Administrator') ? 'Published' : 'Pending';

        foreach ($subSections as $subPage => $data) {
            $subTitle = $data['title'];
            $subContent = $data['content'];
            $subImage = $data['image'] ?? '';

            $check = mysqli_query($conn, "SELECT id FROM website_contents WHERE page_name='$subPage' LIMIT 1");
            $existing = mysqli_fetch_assoc($check);

            if ($existing) {
                $subId = $existing['id'];
                if (!empty($data['remove_image'])) {
                    $stmt = mysqli_prepare($conn, "UPDATE website_contents SET title=?, content=?, featured_image='', status=?, updated_by=? WHERE id=?");
                    mysqli_stmt_bind_param($stmt, 'sssii', $subTitle, $subContent, $subStatus, $_SESSION['user_id'], $subId);
                } elseif ($subImage) {
                    $stmt = mysqli_prepare($conn, "UPDATE website_contents SET title=?, content=?, featured_image=?, status=?, updated_by=? WHERE id=?");
                    mysqli_stmt_bind_param($stmt, 'ssssii', $subTitle, $subContent, $subImage, $subStatus, $_SESSION['user_id'], $subId);
                } else {
                    $stmt = mysqli_prepare($conn, "UPDATE website_contents SET title=?, content=?, status=?, updated_by=? WHERE id=?");
                    mysqli_stmt_bind_param($stmt, 'sssii', $subTitle, $subContent, $subStatus, $_SESSION['user_id'], $subId);
                }
            } else {
                if ($subImage) {
                    $stmt = mysqli_prepare($conn, "INSERT INTO website_contents (page_name, title, content, featured_image, status, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt, 'sssssii', $subPage, $subTitle, $subContent, $subImage, $subStatus, $_SESSION['user_id'], $_SESSION['user_id']);
                } else {
                    $stmt = mysqli_prepare($conn, "INSERT INTO website_contents (page_name, title, content, status, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt, 'ssssii', $subPage, $subTitle, $subContent, $subStatus, $_SESSION['user_id'], $_SESSION['user_id']);
                }
                $subId = mysqli_insert_id($conn);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($subStatus === 'Pending') {
                createApprovalRequest('website_content', $subId, $_SESSION['user_id']);
            }
        }
    }

    // Create approval request for the main record when submitted by Content Creator
    if ($status === 'Pending' && !hasRole('Administrator')) {
        createApprovalRequest('website_content', $newId, $_SESSION['user_id']);
    }

    $anyPending = ($status === 'Pending' && !hasRole('Administrator'));
    if ($pageName === 'about') {
        foreach (['about_gallery', 'about_mission', 'about_vision'] as $subPage) {
            $check = mysqli_query($conn, "SELECT status FROM website_contents WHERE page_name='$subPage' LIMIT 1");
            $r = mysqli_fetch_assoc($check);
            if ($r && $r['status'] === 'Pending') { $anyPending = true; break; }
        }
    }

    if ($anyPending) {
        $_SESSION['success'] = 'Content saved and submitted for approval.';
    } else {
        $_SESSION['success'] = 'Content saved successfully.';
    }
    redirect('content.php');
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if (hasRole('Administrator')) {
        mysqli_query($conn, "DELETE FROM website_contents WHERE id = $id");
        $_SESSION['success'] = 'Content deleted successfully.';
    } elseif (hasRole('Content Creator')) {
        $res = mysqli_query($conn, "SELECT status FROM website_contents WHERE id = $id");
        $row = mysqli_fetch_assoc($res);
        $prevStatus = $row ? $row['status'] : 'Draft';
        mysqli_query($conn, "UPDATE website_contents SET status='Draft' WHERE id = $id");
        $stmt = mysqli_prepare($conn, "INSERT INTO approval_requests (entity_type, entity_id, requested_by, comments, status) VALUES ('website_content', ?, ?, ?, 'Pending')");
        $delComments = '__DELETE__|' . $prevStatus;
        mysqli_stmt_bind_param($stmt, 'iis', $id, $_SESSION['user_id'], $delComments);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $_SESSION['success'] = 'Deletion request submitted for approval.';
    }
    redirect('content.php');
}

$editContent = null;
$aboutGallery = null;
$aboutMission = null;
$aboutVision = null;

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM website_contents WHERE id = $id");
    $editContent = mysqli_fetch_assoc($result);

    // Load About sub-records
    if ($editContent && $editContent['page_name'] === 'about') {
        $res = mysqli_query($conn, "SELECT * FROM website_contents WHERE page_name='about_gallery' LIMIT 1");
        $aboutGallery = mysqli_fetch_assoc($res);
        $res = mysqli_query($conn, "SELECT * FROM website_contents WHERE page_name='about_mission' LIMIT 1");
        $aboutMission = mysqli_fetch_assoc($res);
        $res = mysqli_query($conn, "SELECT * FROM website_contents WHERE page_name='about_vision' LIMIT 1");
        $aboutVision = mysqli_fetch_assoc($res);
    }

    // Parse stored content into blocks for the block editor
    $aboutBlocks = [];
    if ($editContent && $editContent['page_name'] === 'about' && !empty(trim($editContent['content'] ?? ''))) {
        $stored = $editContent['content'];
        if (preg_match('/<h3 class="about-block-heading">/i', $stored)) {
            // Parse block format: <h3>heading</h3><p>content</p>...
            $parts = preg_split('/<h3 class="about-block-heading">(.*?)<\/h3>/s', $stored, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            $currentHeading = '';
            foreach ($parts as $i => $part) {
                $part = trim($part);
                if ($i % 2 === 0) {
                    // Even index: content part
                    $text = preg_replace('/<\/p>\s*<p>/', "\n\n", $part);
                    $text = preg_replace('/<br\s*\/?>/', "\n", $text);
                    $text = trim(html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8'));
                    if ($i === 0 && $currentHeading === '') {
                        $aboutBlocks[] = ['heading' => '', 'content' => $text];
                    } else {
                        $aboutBlocks[] = ['heading' => $currentHeading, 'content' => $text];
                        $currentHeading = '';
                    }
                } else {
                    // Odd index: heading text
                    $currentHeading = trim(html_entity_decode(strip_tags($part), ENT_QUOTES, 'UTF-8'));
                }
            }
        } else {
            // Plain text or HTML without block headings — single block
            $text = preg_replace('/<\/p>\s*<p>/', "\n\n", $stored);
            $text = preg_replace('/<br\s*\/?>/', "\n", $text);
            $text = trim(html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8'));
            $aboutBlocks[] = ['heading' => '', 'content' => $text];
        }
    }
}
$result = mysqli_query($conn, "SELECT wc.*, u.name as creator FROM website_contents wc LEFT JOIN users u ON wc.created_by = u.id WHERE wc.page_name NOT IN ('about_gallery','about_mission','about_vision') ORDER BY wc.updated_at DESC");
?>
<div class="table-container">
    <div class="header">
        <h5><?= $editContent ? 'Edit Content' : 'Website Content' ?></h5>
        <a href="?add=1" class="btn btn-sm btn-primary"><?= $editContent ? '← Back' : 'Add New Content' ?></a>
    </div>
    <?php if ($editContent || isset($_GET['add'])): ?>
    <form method="POST" action="" enctype="multipart/form-data" style="padding:20px;">
        <input type="hidden" name="content_id" value="<?= $editContent['id'] ?? 0 ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Page Name *</label>
                <select name="page_name" class="form-control" required>
                    <option value="">Select Page</option>
                    <?php
                    $pages = ['home'=>'Home','about'=>'About Us','chairman'=>'Chairman Message','mission'=>'Mission & Vision','announcements'=>'Announcements'];
                    foreach ($pages as $key => $val):
                    ?>
                    <option value="<?= $key ?>" <?= ($editContent['page_name'] ?? '') == $key ? 'selected' : '' ?>><?= $val ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" id="title-group">
                <label>Title *</label>
                <input type="text" name="title" class="form-control" value="<?= sanitizeInput($editContent['title'] ?? '') ?>" required>
            </div>
        </div>

        <!-- Standard single-section form (for non-about pages) -->
        <div id="standard-fields">
            <div class="form-group">
                <label>Content *</label>
                <textarea name="content" class="form-control" style="min-height:300px;"><?= sanitizeInput($editContent['content'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Featured Image</label>
                <input type="file" name="featured_image" class="form-control" accept="image/*">
                <?php if (!empty($editContent['featured_image'])): ?>
                <br><img src="<?= SITE_URL . '/' . sanitizeInput($editContent['featured_image']) ?>" style="max-height:100px;margin-top:5px;">
                <?php endif; ?>
            </div>
        </div>

        <!-- About Us tabbed multi-section form -->
        <div id="about-tabs" style="display:none;">
            <div class="about-tab-nav">
                <button type="button" class="about-tab-btn active" data-tab="about-content">About Content</button>
                <button type="button" class="about-tab-btn" data-tab="about-gallery">Gallery</button>
                <button type="button" class="about-tab-btn" data-tab="about-mission">Our Mission</button>
                <button type="button" class="about-tab-btn" data-tab="about-vision">Our Vision</button>
            </div>

            <!-- Tab: About Content -->
            <div class="about-tab-panel active" id="tab-about-content">
                <div class="about-tab-header">
                    <h4>About Content</h4>
                    <p>Add content blocks with optional headings. Each block can have a heading and multiple paragraphs.</p>
                </div>
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" class="form-control" value="<?= sanitizeInput($editContent['title'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Content Blocks</label>
                    <div id="about-blocks">
                        <?php if (!empty($aboutBlocks)): ?>
                            <?php foreach ($aboutBlocks as $idx => $block): ?>
                            <div class="about-block-item">
                                <div class="about-block-header">
                                    <span class="about-block-label">Block <?= $idx + 1 ?></span>
                                    <div class="about-block-actions">
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="moveBlock(this, -1)" title="Move up">&uarr;</button>
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="moveBlock(this, 1)" title="Move down">&darr;</button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="removeBlock(this)" title="Delete">&times;</button>
                                    </div>
                                </div>
                                <div class="about-block-body">
                                    <div class="form-group">
                                        <label>Heading <small style="color:#94a3b8;font-weight:400;">(optional)</small></label>
                                        <input type="text" name="block_heading[]" class="form-control" value="<?= sanitizeInput($block['heading']) ?>" placeholder="e.g. Our History, Our Team...">
                                    </div>
                                    <div class="form-group">
                                        <label>Content</label>
                                        <textarea name="block_content[]" class="form-control" style="min-height:150px;" placeholder="Enter paragraph text. Use blank lines to separate paragraphs."><?= sanitizeInput($block['content']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="about-block-item">
                                <div class="about-block-header">
                                    <span class="about-block-label">Block 1</span>
                                    <div class="about-block-actions">
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="moveBlock(this, -1)" title="Move up">&uarr;</button>
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="moveBlock(this, 1)" title="Move down">&darr;</button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="removeBlock(this)" title="Delete">&times;</button>
                                    </div>
                                </div>
                                <div class="about-block-body">
                                    <div class="form-group">
                                        <label>Heading <small style="color:#94a3b8;font-weight:400;">(optional)</small></label>
                                        <input type="text" name="block_heading[]" class="form-control" placeholder="e.g. Our History, Our Team...">
                                    </div>
                                    <div class="form-group">
                                        <label>Content</label>
                                        <textarea name="block_content[]" class="form-control" style="min-height:150px;" placeholder="Enter paragraph text. Use blank lines to separate paragraphs."></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addBlock()" style="margin-top:10px;">+ Add Block</button>
                </div>
                <div class="form-group">
                    <label>Featured Image</label>
                    <input type="file" name="featured_image" class="form-control" accept="image/*">
                    <?php if (!empty($editContent['featured_image'])): ?>
                    <br><img src="<?= SITE_URL . '/' . sanitizeInput($editContent['featured_image']) ?>" style="max-height:100px;margin-top:5px;">
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tab: Gallery -->
            <div class="about-tab-panel" id="tab-about-gallery">
                <div class="about-tab-header">
                    <h4>About Gallery</h4>
                    <p>Manage gallery images for the About Us page.</p>
                </div>
                <div class="form-group">
                    <label>Section Title</label>
                    <input type="text" name="gallery_title" class="form-control" value="<?= sanitizeInput($aboutGallery['title'] ?? 'Our Gallery') ?>">
                </div>
                <?php if ($aboutGallery && !empty($aboutGallery['content'])):
                    preg_match_all('/<figure[^>]*>.*?<\/figure>/s', $aboutGallery['content'], $matches);
                    $figures = $matches[0] ?? [];
                    if (!empty($figures)):
                ?>
                <div class="form-group">
                    <label>Existing Gallery Images</label>
                    <div style="display:flex;flex-wrap:wrap;gap:16px;margin-top:8px;">
                        <?php foreach ($figures as $idx => $figure): ?>
                        <div style="position:relative;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;background:#fff;max-width:180px;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                            <?= $figure ?>
                            <div style="padding:6px 8px;border-top:1px solid #e2e8f0;font-size:13px;">
                                <input type="checkbox" name="remove_gallery[]" value="<?= $idx ?>" id="rm_gal_<?= $idx ?>">
                                <label for="rm_gal_<?= $idx ?>" style="margin:0 0 0 4px;cursor:pointer;color:#dc2626;font-weight:500;">Remove</label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <small style="color:#94a3b8;display:block;margin-top:6px;">Check images to remove before saving.</small>
                </div>
                <?php endif; endif; ?>
                <div class="form-group">
                    <label>Upload New Gallery Images</label>
                    <input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple>
                    <small style="color:#94a3b8;">Select one or more images to add to the gallery.</small>
                </div>
                <div class="form-group">
                    <label>Raw Gallery HTML <small style="color:#94a3b8;font-weight:400;">(advanced)</small></label>
                    <textarea name="gallery_content" class="form-control" style="min-height:120px;font-family:monospace;font-size:13px;"><?= sanitizeInput($aboutGallery['content'] ?? '') ?></textarea>
                    <small style="color:#94a3b8;">This holds the HTML for existing gallery images. You can edit it directly if needed.</small>
                </div>
            </div>

            <!-- Tab: Our Mission -->
            <div class="about-tab-panel" id="tab-about-mission">
                <div class="about-tab-header">
                    <h4>Our Mission</h4>
                    <p>Heading, paragraphs, and optional image for the Mission section.</p>
                </div>
                <div class="form-group">
                    <label>Heading</label>
                    <input type="text" name="mission_title" class="form-control" value="<?= sanitizeInput($aboutMission['title'] ?? 'Our Mission') ?>">
                </div>
                <div class="form-group">
                    <label>Content</label>
                    <textarea name="mission_content" class="form-control" style="min-height:250px;"><?= sanitizeInput($aboutMission['content'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Image (optional)</label>
                    <input type="file" name="mission_image" class="form-control" accept="image/*">
                    <?php if (!empty($aboutMission['featured_image'])): ?>
                    <br><img src="<?= SITE_URL . '/' . sanitizeInput($aboutMission['featured_image']) ?>" style="max-height:100px;margin-top:5px;">
                    <label style="display:inline-block;margin-top:6px;font-size:13px;color:#dc2626;cursor:pointer;">
                        <input type="checkbox" name="mission_remove_image" value="1"> Remove current image
                    </label>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tab: Our Vision -->
            <div class="about-tab-panel" id="tab-about-vision">
                <div class="about-tab-header">
                    <h4>Our Vision</h4>
                    <p>Heading, paragraphs, and optional image for the Vision section.</p>
                </div>
                <div class="form-group">
                    <label>Heading</label>
                    <input type="text" name="vision_title" class="form-control" value="<?= sanitizeInput($aboutVision['title'] ?? 'Our Vision') ?>">
                </div>
                <div class="form-group">
                    <label>Content</label>
                    <textarea name="vision_content" class="form-control" style="min-height:250px;"><?= sanitizeInput($aboutVision['content'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Image (optional)</label>
                    <input type="file" name="vision_image" class="form-control" accept="image/*">
                    <?php if (!empty($aboutVision['featured_image'])): ?>
                    <br><img src="<?= SITE_URL . '/' . sanitizeInput($aboutVision['featured_image']) ?>" style="max-height:100px;margin-top:5px;">
                    <label style="display:inline-block;margin-top:6px;font-size:13px;color:#dc2626;cursor:pointer;">
                        <input type="checkbox" name="vision_remove_image" value="1"> Remove current image
                    </label>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <button type="submit" name="save" class="btn btn-success">Save Content</button>
    </form>

    <style>
    .about-tab-nav {
        display: flex;
        gap: 4px;
        border-bottom: 2px solid #e2e8f0;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }
    .about-tab-btn {
        padding: 10px 18px;
        border: none;
        background: none;
        font-size: 0.88rem;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: all 0.2s;
        font-family: inherit;
    }
    .about-tab-btn:hover { color: #981c4e; }
    .about-tab-btn.active {
        color: #981c4e;
        border-bottom-color: #981c4e;
    }
    .about-tab-panel { display: none; }
    .about-tab-panel.active { display: block; }
    .about-tab-header { margin-bottom: 20px; }
    .about-tab-header h4 {
        font-size: 1.1rem;
        color: #1e293b;
        font-weight: 700;
        margin-bottom: 4px;
    }
    .about-tab-header p {
        font-size: 0.85rem;
        color: #94a3b8;
    }
    .about-block-item {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-bottom: 16px;
        background: #f8fafc;
        overflow: hidden;
    }
    .about-block-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        background: #f1f5f9;
        border-bottom: 1px solid #e2e8f0;
    }
    .about-block-label {
        font-weight: 700;
        font-size: 0.9rem;
        color: #334155;
    }
    .about-block-actions {
        display: flex;
        gap: 6px;
    }
    .about-block-body {
        padding: 16px;
    }
    .about-block-body .form-group {
        margin-bottom: 12px;
    }
    .about-block-body .form-group:last-child {
        margin-bottom: 0;
    }
    </style>

    <script>
    (function() {
        var sel = document.querySelector('select[name="page_name"]');
        var stdFields = document.getElementById('standard-fields');
        var aboutTabs = document.getElementById('about-tabs');
        var titleGroup = document.getElementById('title-group');

        function setFieldsDisabled(root, disabled) {
            root.querySelectorAll('textarea, input, select, button').forEach(function(el) {
                el.disabled = disabled;
            });
        }

        function switchMode() {
            var isAbout = sel.value === 'about';
            stdFields.style.display = isAbout ? 'none' : 'block';
            aboutTabs.style.display = isAbout ? 'block' : 'none';
            if (isAbout) {
                sel.style.display = 'none';
                titleGroup.style.display = 'none';
                setFieldsDisabled(stdFields, true);
                setFieldsDisabled(titleGroup, true);
                setFieldsDisabled(aboutTabs, false);
                sel.disabled = false;
            } else {
                sel.style.display = 'block';
                titleGroup.style.display = 'block';
                setFieldsDisabled(aboutTabs, true);
                setFieldsDisabled(stdFields, false);
                setFieldsDisabled(titleGroup, false);
                sel.disabled = false;
            }
        }

        sel.addEventListener('change', switchMode);
        switchMode();

        // Tab navigation
        var tabs = document.querySelectorAll('.about-tab-btn');
        tabs.forEach(function(btn) {
            btn.addEventListener('click', function() {
                tabs.forEach(function(b) { b.classList.remove('active'); });
                document.querySelectorAll('.about-tab-panel').forEach(function(p) { p.classList.remove('active'); });
                btn.classList.add('active');
                var panel = document.getElementById('tab-' + btn.getAttribute('data-tab'));
                if (panel) panel.classList.add('active');
            });
        });

        var firstTab = document.querySelector('.about-tab-btn');
        if (firstTab) firstTab.click();
    })();

    function addBlock() {
        var container = document.getElementById('about-blocks');
        if (!container) return;
        var count = container.children.length + 1;
        var div = document.createElement('div');
        div.className = 'about-block-item';
        div.innerHTML =
            '<div class="about-block-header">' +
                '<span class="about-block-label">Block ' + count + '</span>' +
                '<div class="about-block-actions">' +
                    '<button type="button" class="btn btn-sm btn-secondary" onclick="moveBlock(this, -1)" title="Move up">&uarr;</button>' +
                    '<button type="button" class="btn btn-sm btn-secondary" onclick="moveBlock(this, 1)" title="Move down">&darr;</button>' +
                    '<button type="button" class="btn btn-sm btn-danger" onclick="removeBlock(this)" title="Delete">&times;</button>' +
                '</div>' +
            '</div>' +
            '<div class="about-block-body">' +
                '<div class="form-group">' +
                    '<label>Heading <small style="color:#94a3b8;font-weight:400;">(optional)</small></label>' +
                    '<input type="text" name="block_heading[]" class="form-control" placeholder="e.g. Our History, Our Team...">' +
                '</div>' +
                '<div class="form-group">' +
                    '<label>Content</label>' +
                    '<textarea name="block_content[]" class="form-control" style="min-height:150px;" placeholder="Enter paragraph text. Use blank lines to separate paragraphs."></textarea>' +
                '</div>' +
            '</div>' +
        '</div>';
        container.appendChild(div);
        updateBlockLabels();
    }

    function removeBlock(btn) {
        var items = document.querySelectorAll('.about-block-item');
        if (items.length <= 1) {
            if (!confirm('Remove the last block? Content will be empty.')) return;
        }
        btn.closest('.about-block-item').remove();
        updateBlockLabels();
    }

    function moveBlock(btn, dir) {
        var item = btn.closest('.about-block-item');
        var container = document.getElementById('about-blocks');
        if (!container) return;
        if (dir === -1 && item.previousElementSibling) {
            container.insertBefore(item, item.previousElementSibling);
        } else if (dir === 1 && item.nextElementSibling) {
            container.insertBefore(item.nextElementSibling, item);
        }
        updateBlockLabels();
    }

    function updateBlockLabels() {
        var items = document.querySelectorAll('.about-block-item');
        items.forEach(function(item, i) {
            var label = item.querySelector('.about-block-label');
            if (label) label.textContent = 'Block ' + (i + 1);
        });
    }
    </script>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Page</th>
                <th>Title</th>
                <th>Status</th>
                <th>Created By</th>
                <th>Updated</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><span class="badge badge-info"><?= sanitizeInput($row['page_name']) ?></span></td>
                <td><?= sanitizeInput($row['title']) ?></td>
                <td><span class="badge badge-<?= $row['status'] == 'Published' ? 'success' : ($row['status'] == 'Pending' ? 'warning' : 'secondary') ?>"><?= $row['status'] ?></span></td>
                <td><?= sanitizeInput($row['creator'] ?? 'N/A') ?></td>
                <td><?= timeAgo($row['updated_at']) ?></td>
                <td>
                    <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                    <?php if (hasAnyRole(['Administrator', 'Content Creator'])): ?>
                    <a href="?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Delete this content?">Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>
