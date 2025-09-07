<?php
include_once 'head.php';
if($user_type == 1){
    include 'verify_profile.php';
}else{
    include 'nbfc_profile.php';
}

?>