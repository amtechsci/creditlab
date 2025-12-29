<?php
include_once 'head.php';

// Handle WhatsApp number updates
if (isset($_POST['update_whatsapp'])) {
    $page_id = towreal($_POST['page_id']);
    $wa_phone = towreal($_POST['wa_phone']);
    
    // Validate phone number (10 digits, starts with 6-9)
    if (!preg_match('/^[6-9][0-9]{9}$/', $wa_phone)) {
        echo "<script>alert('Invalid phone number! Must be 10 digits starting with 6-9'); window.location.replace('whatsapp_settings.php');</script>";
        exit;
    }
    
    // Validate page_id
    if (!in_array($page_id, [1, 2, 3])) {
        echo "<script>alert('Invalid page ID'); window.location.replace('whatsapp_settings.php');</script>";
        exit;
    }
    
    // Update the WhatsApp number
    $update_query = "UPDATE `whatsapp_no` SET `wa_phone` = '$wa_phone' WHERE `page_id` = '$page_id'";
    if (towquery($update_query)) {
        echo "<script>alert('WhatsApp number updated successfully!'); window.location.replace('whatsapp_settings.php');</script>";
        exit;
    } else {
        echo "<script>alert('Error updating WhatsApp number'); window.location.replace('whatsapp_settings.php');</script>";
        exit;
    }
}

// Fetch current WhatsApp numbers
$whatsapp_numbers = [];
$query = towquery("SELECT * FROM `whatsapp_no` ORDER BY `page_id` ASC");
while ($row = towfetch($query)) {
    $whatsapp_numbers[$row['page_id']] = $row;
}

// Define page groups
$page_groups = [
    1 => [
        'name' => 'Start to Loan Apply Pages',
        'description' => 'Shown to users during registration, profile setup, and loan application',
        'pages' => 'Index, Welcome, Registration, Profile, Apply'
    ],
    2 => [
        'name' => 'Disbursal & Active Loan Pages',
        'description' => 'Shown to users after loan approval and during active loan period',
        'pages' => 'Dashboard, Loan Agreement, Payment, Disbursal'
    ],
    3 => [
        'name' => 'Account Manager & Recovery Pages',
        'description' => 'Shown to users with overdue loans or in recovery',
        'pages' => 'Account Manager Dashboard, Recovery Pages'
    ]
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>WhatsApp Settings - Admin</title>
    <style>
        .whatsapp-card {
            margin-bottom: 30px;
            padding: 25px;
            border: 2px solid #25D366;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .whatsapp-card h3 {
            color: #25D366;
            margin-bottom: 15px;
            font-size: 24px;
            display: flex;
            align-items: center;
        }
        .whatsapp-card h3 i {
            margin-right: 10px;
        }
        .current-number {
            padding: 15px;
            background-color: #E8F5E9;
            border-left: 4px solid #25D366;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .current-number strong {
            color: #2E7D32;
            font-size: 18px;
        }
        .page-description {
            padding: 10px;
            background-color: #F5F5F5;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #666;
        }
        .test-link {
            margin-top: 10px;
        }
        .test-link a {
            color: #25D366;
            text-decoration: none;
            font-weight: bold;
        }
        .test-link a:hover {
            text-decoration: underline;
        }
        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
        .update-btn {
            background-color: #25D366;
            border: none;
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        .update-btn:hover {
            background-color: #1EBE57;
        }
        .info-box {
            padding: 15px;
            background-color: #FFF3CD;
            border-left: 4px solid #FFC107;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .info-box strong {
            color: #856404;
        }
    </style>
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
                            <h2><i class="fab fa-whatsapp" style="color: #25D366;"></i> WhatsApp Support Settings</h2>
                            <p>Manage WhatsApp support numbers displayed to users on different pages</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="info-box">
                        <strong><i class="fa fa-info-circle"></i> How It Works:</strong><br>
                        Users see different WhatsApp numbers based on which section of the application they're using. 
                        This helps route support queries to the right team. The user's Customer ID (CLID) is automatically included in the WhatsApp message.
                    </div>
                </div>
            </div>
            
            <?php foreach ($page_groups as $page_id => $group_info): ?>
                <?php $current = isset($whatsapp_numbers[$page_id]) ? $whatsapp_numbers[$page_id] : null; ?>
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="whatsapp-card">
                            <h3>
                                <i class="fab fa-whatsapp"></i>
                                <?php echo htmlspecialchars($group_info['name']); ?>
                            </h3>
                            
                            <div class="page-description">
                                <strong>Description:</strong> <?php echo htmlspecialchars($group_info['description']); ?><br>
                                <strong>Pages:</strong> <?php echo htmlspecialchars($group_info['pages']); ?>
                            </div>
                            
                            <?php if ($current): ?>
                                <div class="current-number">
                                    <strong>Current Number:</strong> 
                                    <span style="font-size: 20px; color: #1565C0;">
                                        +91 <?php echo htmlspecialchars($current['wa_phone']); ?>
                                    </span>
                                    <div class="test-link">
                                        <a href="https://wa.me/91<?php echo htmlspecialchars($current['wa_phone']); ?>?text=Test message from Admin" target="_blank">
                                            <i class="fab fa-whatsapp"></i> Test this number on WhatsApp
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">No WhatsApp number configured for this page group.</div>
                            <?php endif; ?>
                            
                            <form method="post" style="margin-top: 20px;">
                                <input type="hidden" name="page_id" value="<?php echo $page_id; ?>">
                                
                                <div class="form-group">
                                    <label>Update WhatsApp Number:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">+91</span>
                                        </div>
                                        <input 
                                            type="text" 
                                            name="wa_phone" 
                                            class="form-control" 
                                            placeholder="Enter 10-digit mobile number"
                                            pattern="[6-9][0-9]{9}"
                                            maxlength="10"
                                            value="<?php echo $current ? htmlspecialchars($current['wa_phone']) : ''; ?>"
                                            required
                                        >
                                    </div>
                                    <small class="form-text text-muted">
                                        Must be a valid 10-digit Indian mobile number (starting with 6-9)
                                    </small>
                                </div>
                                
                                <button type="submit" name="update_whatsapp" class="update-btn">
                                    <i class="fa fa-save"></i> Update WhatsApp Number
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="card" style="padding: 20px; background-color: #F5F5F5;">
                        <h4>Preview: How Users See It</h4>
                        <p style="font-size: 16px; margin-top: 10px;">
                            Users will see: <strong style="color: #fff; background: #25D366; padding: 5px 10px; border-radius: 5px;">
                                Contact us on whatsapp <img src="/ws.svg" style="width:20px; vertical-align: middle;">
                            </strong>
                        </p>
                        <p style="margin-top: 10px; color: #666;">
                            When clicked, it opens WhatsApp with their Customer ID pre-filled: 
                            <em>"CLID : CL12345 I need Help in ..."</em>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include_once 'foot.php'; ?>
    
    <script>
        // Auto-format phone number input
        document.querySelectorAll('input[name="wa_phone"]').forEach(function(input) {
            input.addEventListener('input', function(e) {
                // Remove non-numeric characters
                this.value = this.value.replace(/[^0-9]/g, '');
                
                // Limit to 10 digits
                if (this.value.length > 10) {
                    this.value = this.value.slice(0, 10);
                }
            });
        });
        
        // Form validation
        document.querySelectorAll('form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                const phoneInput = this.querySelector('input[name="wa_phone"]');
                const phone = phoneInput.value;
                
                if (phone.length !== 10) {
                    e.preventDefault();
                    alert('Phone number must be exactly 10 digits!');
                    return false;
                }
                
                if (!/^[6-9][0-9]{9}$/.test(phone)) {
                    e.preventDefault();
                    alert('Invalid phone number! Must start with 6, 7, 8, or 9 and be 10 digits long.');
                    return false;
                }
                
                return confirm('Are you sure you want to update this WhatsApp number?');
            });
        });
    </script>
</body>
</html>

