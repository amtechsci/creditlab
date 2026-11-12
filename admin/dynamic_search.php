<?php
include_once 'head.php';
require_once __DIR__ . '/../lib/admin_ui.php';
?>
<body>
<?php
include_once 'Left_menu.php';
include_once 'welcome.php';
include_once 'm_menu.php';
?>
    <div class="breadcome-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <ul class="breadcome-menu">
                        <li><a href="index.php"><i class="fa-solid fa-house" style="font-size:12px;"></i> Admin</a> <span class="bread-slash">/</span></li>
                        <li><span class="bread-blod">Dynamic Search</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="single-pro-review-area mg-b-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="product-payment-inner-st">
                        <div class="cl-card-header">
                            <div class="cl-card-header-left">
                                <h2 class="cl-card-header-title">
                                    <i class="fa-solid fa-magnifying-glass" style="color:var(--cl-brand); font-size:17px;"></i>
                                    Deep Search
                                </h2>
                                <p class="cl-card-header-desc">Search customers across mobile, alternate numbers, references, contacts, PAN, and transaction UTR numbers</p>
                            </div>
                            <div class="cl-card-header-actions" style="max-width:400px; width:100%;">
                                <div class="input-group" style="width:100%;">
                                    <input type="text" id="searchtext" class="form-control" placeholder="Search mobile, PAN, ref, UTR..." style="height:38px; border-radius:6px 0 0 6px; border:1px solid var(--cl-slate-300);">
                                    <span class="input-group-btn">
                                        <button class="btn btn-primary" type="button" onclick="search()" style="height:38px; padding:0 18px; border-radius:0 6px 6px 0; font-weight:600;">
                                            <i class="fa-solid fa-magnifying-glass"></i> Search
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <?= creditlab_render_table_legend() ?>

                        <div class="table-responsive" id="searchtable">
                            <table class="table table-bordered" id="loan_history_datatable">
                                <thead>
                                    <tr>                
                                        <th>Borrower / RCID</th>        
                                        <th>Contact</th>    
                                        <th style="text-align:center;">Status</th>    
                                        <th style="text-align:right; width:110px;">Actions</th>     
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="4" style="text-align:center; padding:40px; color:var(--cl-slate-400);">
                                            <i class="fa-solid fa-magnifying-glass" style="font-size:24px; color:var(--cl-slate-300); margin-bottom:8px; display:block;"></i>
                                            Type a phone number, reference, PAN, or UTR above and click Search
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
include_once 'foot.php';
?>
<script>
    function search(){
        var dsearchtext = $('#searchtext').val();
        if(!dsearchtext || dsearchtext.trim().length === 0){
            alert('Please enter a search query');
            return;
        }
        $('#searchtable').html('<div style="text-align:center; padding:40px; color:var(--cl-slate-400);"><i class="fa-solid fa-spinner fa-spin" style="font-size:24px;"></i><br><br>Searching...</div>');
        $.post("dsearchtable.php",
            {
              search: dsearchtext
            },
            function(data,status) {
                $('#searchtable').html(data);
            });
    }

    $('#searchtext').on('keypress', function(e) {
        if(e.which === 13) {
            search();
        }
    });
</script>
</body>
</html>
