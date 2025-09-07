<?php
// $ext = towrealarray($_POST); extract($ext);
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= 'From: <Mohit Mohit.sisodiya@insurejoy.com>' . "\r\n";
$to="amproapk@gmail.com";
$message="Resignation Approved. \r\n
Best wishes for your future Kamni Gautam.\r\n
\r\n
Regards..\r\n
Mohit Sisodiya\r\n
+91 7534567650";
mail($to,"Resignation Approved",$message,$headers);          
?>