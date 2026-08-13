<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib/lsp_partners.php';

$partners = creditlab_get_lsp_partners(true);

include_once 'head2.php';
?>


<section class="breadcrumb-area bg-img bg-overlay jarallax" style="background-image: url(https://rush4cash.in/img/bg-img/13.jpg);margin-top:100px">
        <div class="container h-100">
            <div class="row h-100 align-items-center">
                <div class="col-12">
                    <div class="breadcrumb-content">
                        <h2>List of Partners</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">Home / List of Partners</a></li>
<!--                                <li class="breadcrumb-item active" aria-current="page">Instant Loan-->
<!--</li>-->
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div></section>
        
        
        <!--container-->
        <div class="container" style="text-align:center;">
            <h1 style="text-align:center;margin:20px;">
             List of Partners
            </h1>
            <table class="table">
                <tr>
                    <th>S.no</th>
                    <th>Name of Partners</th>
                    <th>category/activities</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($partners as $i => $partner) {
                    $name = htmlspecialchars($partner['name'] ?? '');
                    $category = htmlspecialchars($partner['category'] ?? '');
                    $status = htmlspecialchars($partner['status'] ?? 'Active');
                    $status_color = strtolower($status) === 'active' ? 'red' : '#666';
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= $name ?></td>
                    <td><?= $category ?></td>
                    <td style="color:<?= $status_color ?>;"><?= $status ?></td>
                </tr>
                <?php } ?>
            </table>
        </div>
<?php
include_once 'foot.php';
?>
