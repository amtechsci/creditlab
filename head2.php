<?php
$app_url = "https://creditlab.in/";
?><!DOCTYPE html>
<html lang="zxx" class="theme-light">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="stylesheet" href="<?=$app_url?>assets/css/bootstrap.min.css" />

    <link rel="stylesheet" href="<?=$app_url?>assets/css/boxicons.min.css" />

    <link rel="stylesheet" href="<?=$app_url?>assets/css/magnific-popup.min.css" />

    <link rel="stylesheet" href="<?=$app_url?>assets/css/owl.carousel.min.css" />
    <link rel="stylesheet" href="<?=$app_url?>assets/css/owl.theme.default.min.css" />

    <link rel="stylesheet" href="<?=$app_url?>assets/css/nice-select.min.css" />

    <link rel="stylesheet" href="<?=$app_url?>assets/css/animate.min.css" />

    <link rel="stylesheet" href="<?=$app_url?>assets/css/dark.css" />

    <link rel="stylesheet" href="<?=$app_url?>assets/css/style.css" />

    <link rel="stylesheet" href="<?=$app_url?>assets/css/responsive.css" />
    
    
    <link rel="shortcut icon" href="/img/icon.png" type="image/png"/>
    <style>
        .top-btn {
    display: none;
    padding: 5px 15px;
    border-radius: 5px;
    background: #00c195;
    color: #fff;
    -webkit-transition: .5s all ease;
    transition: .5s all ease;
}



.bg-overlay {
    position: relative;
    z-index: 2;
    background-position: center center;
    background-size: cover;
}
.breadcrumb-area {
    position: relative;
    z-index: 1;
    width: 100%;
    height: 255px;
}

.breadcrumb-area .breadcrumb-content h2 {
    color: #ffffff;
    font-size: 30px;
    text-transform: uppercase;
    display: block;
}

.breadcrumb-area .breadcrumb-content .breadcrumb {
    background-color: transparent;
    padding: 0;
    margin-bottom: 0;
}
.breadcrumb {
    background-color: transparent;
    padding: 0;
    margin-bottom: 0;
}
.breadcrumb-item a {
    font-size: 16px;
    color: #ffffff;
    font-weight: 600;
}
.breadcrumb-item.active {
    color: #00a77b;
    font-size: 16px;
    font-weight: 600;
}

/* Style The Dropdown Button */
/*.dropbtn {*/
/*  background-color: #4CAF50;*/
/*  color: white;*/
/*  padding: 16px;*/
/*  font-size: 16px;*/
/*  border: none;*/
/*  cursor: pointer;*/
/*}*/

/* The container <div> - needed to position the dropdown content */
.dropdown {
  position: relative;
  display: inline-block;
}

/* Dropdown Content (Hidden by Default) */
.dropdown-content {
  display: none;
  position: absolute;
  background-color: #f9f9f9;
  min-width: 250px;
  padding-bottom:10px;
  box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
  z-index: 1;
}

/* Links inside the dropdown */
.dropdown-content a {
  color: black;
  padding: 12px 16px;
  text-decoration: none;
  display: block;
  margin-top:10px;
}

/* Change color of dropdown links on hover */
.dropdown-content a:hover {background-color: #f1f1f1}

/* Show the dropdown menu on hover */
.dropdown:hover .dropdown-content {
  display: block;
}

/* Change the background color of the dropdown button when the dropdown content is shown */
/*.dropdown:hover .dropbtn {*/
/*  background-color: #3e8e41;*/
/*}*/

@media only screen and (max-width: 600px){
     .top-btn {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 5px;
    background: #00c195;
    color: #fff;
    -webkit-transition: .5s all ease;
    transition: .5s all ease;
}
}
    </style>
    <title>Loan</title>
    <!-- <link rel="icon" type="image/png" href="<?=$app_url?>assets/img/favicon.png" /> -->
  </head>
  <body data-spy="scroll" data-offset="70">
    <div class="loader">
      <div class="d-table">
        <div class="d-table-cell">
          <div class="spinner" style="height:auto; width:70px; background-color:#23b893;"><img src="/img/icon.png" style="width:100%;"></div>
        </div>
      </div>
    </div>

    <div class="main-navbar">
      <nav class="navbar navbar-style-two navbar-expand-lg navbar-light">
        <div class="container-fluid">
          <div class="logo" style="width:100px;">
            <a href="index">
               <img src="https://creditlab.in/logo.svg" class="black-logo" style="max-width:100px;" alt="Logo" />
            </a>
          </div>
          <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent"
            aria-expanded="false"
            aria-label="Toggle navigation"
          >
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav">
              <li class="nav-item">
                <a href="/index" class="nav-link"> Home </a>
              </li>
              <li class="nav-item">
                <a href="/about" class="nav-link"> About Us</a>
              </li>
              <li class="nav-item">
                <a href="/contact" class="nav-link"> Contact Us</a>
              </li>
              <li class="nav-item">
                <a href="/index#how">How It Works</a>
              </li>
              <li class="nav-item">
                <a href="/index#faqs" class="nav-link"> FAQS </a>
              </li>
               <li class="nav-item">
               <div class="dropdown">
                    <button class="dropbtn nav-link" style="background:#fff;border:none;padding:0;margin:0">Products</button>
                  <div class="dropdown-content">
                  <a href="/short" style="padding-left:10px">Short Term Personal Loans</a>
                  </div>
                </div>
              </li>
              <!--<li class="nav-item">-->
              <!--  <a href="/ethical" class="nav-link">Ethical Lending</a>-->
              <!--</li>-->
              <li class="nav-item">
                <a href="/grievanceredressal.pdf" class="nav-link">Grievance Redressal</a>
              </li>
            </ul>
            <div class="others-option">
              <div class="d-flex align-items-center">
                <div class="option-item">
                  <!-- <a href="tel:15140228419" class="call-btn"
                    >Call Us: +91 8373985998</a
                  > -->
                </div>
                <div class="option-item">
                  <a href="/account/" class="log-in">Log In</a>
                </div>
                <div class="option-item">
                  <a href="/account/" class="sign-up">Sign Up</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </nav>
    </div>
