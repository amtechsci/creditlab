<?php
include_once 'head.php';
$prefill = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$searchErr = isset($_GET['err']) ? (string) $_GET['err'] : '';
?>
<body>
<?php
include_once 'Left_menu.php';
include_once 'welcome.php';
include_once 'm_menu.php';
?>
<div class="breadcome-area">
    <div class="container-fluid">
        <ul class="breadcome-menu">
            <li><a href="index.php">Agency</a> <span class="bread-slash">/</span></li>
            <li><span class="bread-blod">Search customer</span></li>
        </ul>
    </div>
</div>
</div>
<div class="single-pro-review-area mg-b-15">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="product-payment-inner-st">
                    <div class="cl-card-header">
                        <div class="cl-card-header-left">
                            <h3 class="cl-card-header-title"><i class="fa-solid fa-magnifying-glass"></i> Search</h3>
                            <p class="cl-card-header-desc">Find a customer on your agency list by UID, mobile, loan ID (CLL…), or account number.</p>
                        </div>
                    </div>
                    <?php if ($searchErr === 'not_found') { ?>
                    <div class="alert alert-warning" role="alert">No customer found for that search.</div>
                    <?php } elseif ($searchErr === 'not_on_list') { ?>
                    <div class="alert alert-danger" role="alert">Customer found but not on your agency list (DPD &gt; 35 only).</div>
                    <?php } ?>
                    <form action="sea.php" method="get" class="header-search-form" style="max-width:640px;" id="agencySearchForm">
                        <div class="form-group">
                            <label for="agencySearchInput" style="font-size:11px;font-weight:600;text-transform:uppercase;color:#64748b;">Search term</label>
                            <div class="input-group">
                                <span class="input-group-addon" style="background:#f8fafc;border-color:#e2e8f0;"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="text" name="sea" id="agencySearchInput" class="form-control" required
                                    placeholder="CL2607…, mobile, CLL90656, account no…"
                                    value="<?= htmlspecialchars($prefill) ?>" autofocus>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top:12px;font-weight:600;">
                            <i class="fa-solid fa-arrow-right"></i> Search
                        </button>
                    </form>
                    <p class="text-muted" style="margin-top:16px;font-size:13px;">
                        Tip: You can also use the search bar at the top on any page.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once 'foot.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('agencySearchInput');
    if (el) {
        el.focus();
        el.select();
    }
});
</script>
</body>
</html>
