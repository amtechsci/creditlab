<?php
include_once 'head.php';
if (isset($_POST['email'])) {
    $ext = towrealarray($_POST); extract($ext);
 $headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= 'From: <Rush4cash docs@rush4cash.in>' . "\r\n";
$to=$email;
mail($to,$subject,$message,$headers);          
}
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
                            <div class="breadcome-list">
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="analytics-sparkle-area">
            <div class="container-fluid">
                <div class="card">
                    <form action="testmail.php" method="post">
                        <center><h1>Email</h1></center>
                        <div class="row">
                            <div class="col-md-6">
                                <input type="email" class="form-control" name="email" placeholder="Email">
                                <input type="text" class="form-control" placeholder="Subject" name="subject">
                                <lable> &nbsp;&nbsp;&nbsp;Compose Email</lable>
                                <textarea name="message" class="form-control"></textarea>
                                <input type="submit" class="btn btn-primary">
                            </div>
                            <div class="col-md-6"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </div>

        <br>
        <br>
        <br>
<?php
include_once 'foot.php';
?>
</body>

</html>