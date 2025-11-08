<?php
require_once(__DIR__ . "/../../models/manageUsers.php");
$user_id = $_SESSION['user_id'];
$userObj = new User();
$userDashboard = $userObj->fetchUserName($user_id);
?>

<link rel="stylesheet" href="../../../public/assets/fontawesome-free-7.1.0-web/css/all.min.css">

<!-- The sidebar's width and narrow state are controlled by the <style> block in each page -->
<aside class="sidebar text-white h-screen flex-shrink-0">
    <div class="sidebar-top flex items-center">
        <div class="logo-section flex items-center gap-3">
            <i class="fa-solid fa-list toggle-btn"></i>
            <img src="../../../public/assets/images/logo.png" alt="Logo" class="h-12 w-12">
            <h2 class="font-extrabold">LIBRARY</h2>
        </div>
    </div>

    <div class="sidebar-main font-bold">
        <ul>
            <li class="links">
                <a href="../../../app/views/librarian/dashboard.php" id="dashboardBtn">
                    <i class="fas fa-tachometer-alt"></i> <span class="link-text">Dashboard</span>
                </a>
            </li>
            <li class="links">
                <a href="../../../app/views/librarian/booksSection.php" id="booksBtn">
                    <i class="fas fa-book"></i> <span class="link-text">Books</span>
                </a>
            </li>
            <li class="links">
                <a href="../../../app/views/librarian/categorySection.php" id="categoryBtn">
                    <i class="fas fa-tags"></i> <span class="link-text">Categories</span>
                </a>
            </li>
            <li class="links">
                <a href="../../../app/views/librarian/usersSection.php" id="borrowersBtn">
                    <i class="fas fa-users"></i> <span class="link-text">Users</span>
                </a>
            </li>
            <li class="links">
                <a href="../../../app/views/librarian/borrowDetailsSection.php" id="detailsBtn">
                    <i class="fas fa-file-alt"></i> <span class="link-text">Borrowing Details</span>
                </a>
            </li>
            <li class="links">
                <a href="../../controllers/logout.php">
                    <i class="fas fa-sign-out-alt"></i> <span class="link-text">Logout</span>
                </a>
            </li>
        </ul>
    </div>
</aside>

<script>
    // Sidebar toggle functionality
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.toggle-btn');

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('narrow');
    });

    // Automatically narrow sidebar on mobile
    const initSidebar = () => {
        if (window.innerWidth <= 1024) {
            sidebar.classList.add('narrow');
        }
    };
    
    window.addEventListener('resize', initSidebar);
    initSidebar(); // Run on page load
</script>