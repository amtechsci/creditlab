<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Delete user cookie
if (isset($_COOKIE['user'])) {
    setcookie('user', '', time()-3600, '/');
}

// Clear any other custom cookies
$cookies = ['mobile', 'email', 'remember_me'];
foreach($cookies as $cookie_name) {
    if (isset($_COOKIE[$cookie_name])) {
        setcookie($cookie_name, '', time()-3600, '/');
    }
}

// Destroy the session
session_destroy();

// Redirect to login page
header("location:../account/?logout=success");
exit;
?>