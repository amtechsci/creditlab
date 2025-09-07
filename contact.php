<?php
include_once 'head2.php';
if (isset($_POST['name'])) {
    $ext = ($_POST); extract($ext);
 $headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= 'From: <creditlab info@creditlab.in>' . "\r\n";
$to="support@creditlab.in";
$message="name : $name, phone : $mobile, email : $email, \r\n message : $message ";
mail($to,$subject,$message,$headers);
$se = 1;
}
?>
<section class="breadcrumb-area bg-img bg-overlay jarallax" style="background-image: url(https://rush4cash.in/img/bg-img/13.jpg);margin-top:100px">
        <div class="container h-100">
            <div class="row h-100 align-items-center">
                <div class="col-12">
                    <div class="breadcrumb-content">
                        <h2>Contact US</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">Home /</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Contact US
</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div></section>
        
        
        <!--container-->
        <div class="container">
            <div class="card p-5 m-5">
                    <div class="row">
                        <div class="col-12 col-lg-12">
                            <div class="contact-form-area contact-page">
                                <h4 class="mb-50">Send a message</h4>
                                <form action="" method="post">
                                    <div class="row">
                                        <div class="col-lg-6 p-1">
                                            <div class="form-group">
                                                <input type="text" class="form-control" name="name" id="name" placeholder="Your Name">
                                            </div>
                                        </div>
                                        <div class="col-lg-6 p-1">
                                            <div class="form-group">
                                                <input type="email" class="form-control" name="email" id="email" placeholder="Your E-mail">
                                            </div>
                                        </div>
                                        <div class="col-lg-6 p-1">
                                            <div class="form-group">
                                                <input type="text" class="form-control" name="mobile" id="mobile" placeholder="Your Mobile">
                                            </div>
                                        </div>
                                        <div class="col-lg-6 p-1">
                                            <div class="form-group">
                                                <input type="text" class="form-control" name="subject" id="subject" placeholder="Your Subject">
                                            </div>
                                        </div>
                                        <div class="col-12 p-1">
                                            <div class="form-group">
                                                <textarea name="message" class="form-control" id="message" name="message" cols="30" rows="10" placeholder="Your Message"></textarea>
                                            </div>
                                        </div>
                                        <div class="col-12 p-1">
                                            <button class="btn btn-success mt-30" type="submit">Send</button>
                                            <h6><?php if(isset($se)){ ?>Thank you for your request. We have received it. We will respond as soon as possible.<?php }?></h6>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
<?php
include_once 'foot.php';
?>
