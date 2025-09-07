<style>
    /* Fixed Mobile Header */
    .mobile-header {
        display: none; /* Hidden by default, only shown on mobile */
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 50px;
        background-color: #00a77b; /* Set background to #00a77b */
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 15px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        z-index: 1001;
    }
    
    .mobile-header h1 {
        font-size: 20px;
        margin: 0;
        color: #fff; /* Set text color to white */
        font-weight: bold;
    }

    .hamburger-menu {
        font-size: 24px;
        cursor: pointer;
        color: #fff; /* Set hamburger icon color to white */
    }

    /* Mobile Menu Styles */
    .mobile-menu-area {
        display: none; /* Hidden by default */
        position: fixed;
        top: 0;
        left: 0;
        height: 100%;
        width: 80%;
        max-width: 250px;
        background: #fff;
        box-shadow: 2px 0 8px rgba(0, 0, 0, 0.3);
        z-index: 1000;
        padding: 60px 10px 20px; /* Adjust for fixed header */
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }

    .mobile-menu-area.open {
        display: block;
        transform: translateX(0);
    }

    .mobile-nav-link {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        color: #fff;
        padding: 10px;
        background-color: #28a745;
        border-radius: 5px;
        text-decoration: none;
        margin: 8px 0;
    }

    .logout-btn .mobile-nav-link {
        background-color: #dc3545;
    }

    /* Show mobile elements only on smaller screens */
    @media (max-width: 768px) {
        .desktop-header, .left-sidebar-pro { display: none; } /* Hide desktop elements */
        .mobile-header, .mobile-menu-area { display: flex; }
    }

    /* Hide mobile elements on larger screens */
    @media (min-width: 769px) {
        .mobile-header, .mobile-menu-area { display: none; }
    }
</style>


<!-- Mobile Header (Visible on Mobile Only) -->
<div class="mobile-header">
    <h1>creditlab.in</h1>
    <div class="hamburger-menu" onclick="toggleMenu()">
        <i class="fas fa-bars"></i>
    </div>
</div>

<!-- Mobile Menu Area (Visible on Mobile Only) -->
<div class="mobile-menu-area" id="mobileMenu">
    <ul class="mobile-nav-list">
        <li><a href="index.php" class="mobile-nav-link"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li><a href="creditlab_score.php" class="mobile-nav-link"><i class="fa fa-history"></i> <span>Creditlab Score</span></a></li>
        <?php 
            $userloan = towquery("SELECT * FROM loan_apply WHERE uid=$user_id AND (status='account manager' OR status='recovery officer')");
            if(townum($userloan) > 0 && $user_approvenew == 1) {
        ?>
        <li><a href="newloan.php" class="mobile-nav-link"><i class="fa fa-history"></i> <span>New Loan</span></a></li>
        <?php } ?>
        <li class="logout-btn"><a href="logout.php" class="mobile-nav-link"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>

<script>
    function toggleMenu() {
        const mobileMenu = document.getElementById('mobileMenu');
        mobileMenu.classList.toggle('open');
    }
</script>
