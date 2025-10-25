<?php

require_once(__DIR__ . "/../../models/manageUsers.php");
$user_id = $_SESSION['user_id'];
$userObj = new User();
$userDashboard = $userObj->fetchUserName($user_id);
?>

<aside class="w-2/12 text-white">
    <div class="sidebar-top">
        <h2 class="text-lg font-bold p-4">Welcome Admin <?= $userDashboard["lName"] ?? "" ?>!</h2>
    </div>
    <div class="sidebar-main font-bold">
        <ul>
            <li class="links"><a href="../../../app/views/librarian/dashboard.php" id="dashboardBtn">Dashboard</a></li>
            <li class="links"><a href="../../../app/views/librarian/booksSection.php" id="booksBtn">Books</a></li>
            <li class="links"><a href="../../../app/views/librarian/categorySection.php" id="categoryBtn">Categories</a>
            </li>
            <li class="links"><a href="../../../app/views/librarian/usersSection.php" id="borrowersBtn">Users</a>
            </li>
            <li class="links"><a href="../../../app/views/librarian/borrowDetailsSection.php" id="detailsBtn">Borrowing
                    Details</a></li>
            <li class="links">
                <a href="../../controllers/logout.php">Logout</a>
            </li>
        </ul>
    </div>
</aside>