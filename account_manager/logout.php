<?php
session_start();
 if(session_destroy()){
$set= setcookie('admin', 'username', time() + (0), "/");
if($set){
header("location:/");}else{
}
} 
?>