<?php
include_once 'head.php';

// Handle PDF file uploads/updates
if (isset($_POST['update_pdf'])) {
    $pdf_type = towreal($_POST['pdf_type']);
    $base_url = getAppUrl();
    
    // Allowed PDF file types - mapping to actual filenames
    $allowed_pdfs = [
        'grievanceredressal' => 'grievanceredressal.pdf',
        'policy' => 'policy.pdf',
        'fair_practice_code' => 'FairPracticeCodeSMPL.pdf',
        'it_policy' => 'it_policy.pdf',
        'fees_policy' => 'fees_policy.pdf',
        'refund_cancellation' => 'RefundCancellationPolicy.pdf'
    ];
    
    if (!isset($allowed_pdfs[$pdf_type])) {
        echo "<script>alert('Invalid PDF type'); window.location.replace('pdf_settings.php');</script>";
        exit;
    }
    
    $pdf_filename = $allowed_pdfs[$pdf_type];
    $upload_dir = __DIR__ . '/../';
    $target_file = $upload_dir . $pdf_filename;
    
    // Check if file was uploaded
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] == UPLOAD_ERR_OK) {
        $uploaded_file = $_FILES['pdf_file'];
        
        // Validate file type
        $file_ext = strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION));
        if ($file_ext !== 'pdf') {
            echo "<script>alert('Only PDF files are allowed'); window.location.replace('pdf_settings.php');</script>";
            exit;
        }
        
        // Validate file size (max 10MB)
        if ($uploaded_file['size'] > 10 * 1024 * 1024) {
            echo "<script>alert('File size must be less than 10MB'); window.location.replace('pdf_settings.php');</script>";
            exit;
        }
        
        // Backup existing file if it exists
        if (file_exists($target_file)) {
            $backup_file = $target_file . '.backup.' . date('YmdHis');
            @copy($target_file, $backup_file);
        }
        
        // Move uploaded file (replaces existing file with same name)
        if (move_uploaded_file($uploaded_file['tmp_name'], $target_file)) {
            echo "<script>alert('PDF file updated successfully! All existing links will now point to the new file.'); window.location.replace('pdf_settings.php');</script>";
            exit;
        } else {
            // Restore backup if upload failed
            if (isset($backup_file) && file_exists($backup_file)) {
                @copy($backup_file, $target_file);
            }
            echo "<script>alert('Error uploading file. Please check file permissions.'); window.location.replace('pdf_settings.php');</script>";
            exit;
        }
    } else {
        echo "<script>alert('Please select a PDF file to upload'); window.location.replace('pdf_settings.php');</script>";
        exit;
    }
}

$pdf_types = [
    'grievanceredressal' => ['name' => 'Grievance Redressal Policy', 'filename' => 'grievanceredressal.pdf'],
    'policy' => ['name' => 'Privacy Policy', 'filename' => 'policy.pdf'],
    'fair_practice_code' => ['name' => 'Fair Practice Code', 'filename' => 'FairPracticeCodeSMPL.pdf'],
    'it_policy' => ['name' => 'IT Policy', 'filename' => 'it_policy.pdf'],
    'fees_policy' => ['name' => 'Fees Policy', 'filename' => 'fees_policy.pdf'],
    'refund_cancellation' => ['name' => 'Refund & Cancellation Policy', 'filename' => 'RefundCancellationPolicy.pdf']
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>PDF Settings - Admin</title>
</head>
<body>
    <?php include_once 'Left_menu.php'; ?>
    <?php include_once 'welcome.php'; ?>
    
    <div class="all-content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="breadcome-area">
                        <div class="breadcome-list">
                            <h2>PDF Settings</h2>
                            <p>Manage policy and document PDF files</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="product-status-wrap">
                        <div class="product-status-content">
                            <?php foreach ($pdf_types as $pdf_type => $pdf_info): ?>
                                <?php 
                                $pdf_name = $pdf_info['name'];
                                $pdf_filename = $pdf_info['filename'];
                                $base_url = getAppUrl();
                                $current_url = $base_url . '/' . $pdf_filename;
                                $file_path = __DIR__ . '/../' . $pdf_filename;
                                $file_exists = file_exists($file_path);
                                $file_size = $file_exists ? filesize($file_path) : 0;
                                $file_date = $file_exists ? date('Y-m-d H:i:s', filemtime($file_path)) : 'Not found';
                                ?>
                                <div class="card" style="margin-bottom: 20px; padding: 20px; border: 1px solid #ddd;">
                                    <h4><?php echo htmlspecialchars($pdf_name); ?></h4>
                                    
                                    <div style="margin-bottom: 15px; padding: 10px; background-color: #f9f9f9; border-radius: 5px;">
                                        <strong>File Name:</strong> <?php echo htmlspecialchars($pdf_filename); ?><br>
                                        <strong>Current URL:</strong> 
                                        <a href="<?php echo htmlspecialchars($current_url); ?>" target="_blank" style="color: #007bff;">
                                            <?php echo htmlspecialchars($current_url); ?>
                                        </a><br>
                                        <strong>File Status:</strong> 
                                        <span style="color: <?php echo $file_exists ? 'green' : 'red'; ?>;">
                                            <?php echo $file_exists ? '✓ File exists' : '✗ File not found'; ?>
                                        </span><br>
                                        <?php if ($file_exists): ?>
                                            <strong>File Size:</strong> <?php echo number_format($file_size / 1024, 2); ?> KB<br>
                                            <strong>Last Modified:</strong> <?php echo $file_date; ?><br>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <form method="post" enctype="multipart/form-data" style="margin-top: 15px;">
                                        <input type="hidden" name="pdf_type" value="<?php echo htmlspecialchars($pdf_type); ?>">
                                        
                                        <div class="form-group">
                                            <label><strong>Upload New PDF File</strong></label>
                                            <input type="file" name="pdf_file" accept=".pdf" class="form-control" required>
                                            <small class="form-text text-muted">
                                                Maximum file size: 10MB. 
                                                <strong>This will replace the existing file with the same name.</strong><br>
                                                All existing links (<?php echo htmlspecialchars($pdf_filename); ?>) will automatically use the new file.
                                            </small>
                                        </div>
                                        
                                        <button type="submit" name="update_pdf" class="btn btn-primary" style="margin-top: 10px;">
                                            <i class="fa fa-upload"></i> Upload & Replace <?php echo htmlspecialchars($pdf_name); ?>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include_once 'foot.php'; ?>
</body>
</html>

