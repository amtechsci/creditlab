<div class="all-content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="logo-pro">
                        <!--<a href="index.html"><img class="main-logo" src="img/logo/logo.png" alt="" /></a>--><h1>creditlab.in</h1>
                    </div>
                </div>
            </div>
        </div>
        <div class="header-advance-area">
            <div class="header-top-area">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="header-top-wraper">
                                <div class="row" style="display: flex; align-items: center;">
                                    <div class="col-lg-1 col-md-0 col-sm-1 col-xs-12">
                                        <div class="menu-switcher-pro">
                                            <button type="button" id="sidebarCollapse" class="btn bar-button-pro header-drl-controller-btn btn-info navbar-btn">
                                                    <i class="educate-icon educate-nav"></i>
                                                </button>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-7 col-sm-6 col-xs-12">
                                        <div class="header-top-menu tabl-d-n">
                                            <p style="font-size:20px; color:#fff;margin:0px;">
                                                <?php if(in_array($page_state,[1,2,3,4,5,6,7,8,9])){
                                                    $n = 1;
                                                }elseif(in_array($page_state,[12,13,14,15,16,17])){
                                                    $n = 2;
                                                }else{
                                                    $n = 3;
                                                }
                                                 $wa_num = towfetch(towquery("SELECT * FROM `whatsapp_no` WHERE `page_id`='$n'"))['wa_phone'];?>
                                                Contact us on whatsapp <a href="https://api.whatsapp.com/send?phone=91<?=$wa_num?>&text=CLID : <?=$user_rcid?> I need Help in ..."><img src="/ws.svg" style="width:30px;"></a></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>