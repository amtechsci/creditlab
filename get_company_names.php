<?php
session_start();
$db = mysqli_connect("localhost", "u969389823_credit", "Credit@123", "u969389823_credit");
function towquery($query)
 {
 	$db = mysqli_connect("localhost", "u969389823_credit", "Credit@123", "u969389823_credit");
  	mysqli_set_charset($db,'utf8');
 	$re = mysqli_query($db,$query);
 	return $re;
 }
 function townum($query)
 {
 	$re = mysqli_num_rows($query);
 	return $re;
 }
 function towfetch($query)
 {
 	$re = mysqli_fetch_array($query);
 	return $re;
 }
 function towreal($query)
 {
 	$db = mysqli_connect("localhost", "u969389823_credit", "Credit@123", "u969389823_credit");
 	$re = str_replace("<","&lt;",$query);
 	$re = str_replace(">","&gt;",$re);
 	$re = mysqli_real_escape_string($db,$re);
 	return $re;
 }

$query = isset($_GET['query']) ? towreal($_GET['query']) : '';
$a = towquery("SELECT company_name FROM company_name WHERE company_name LIKE '%$query%' LIMIT 10");
if (townum($a) > 0) {
    while ($row = towfetch($a)) {
        $options .= '<option value="' . htmlspecialchars($row['company_name']) . '">';
    }
}

echo $options;
?>