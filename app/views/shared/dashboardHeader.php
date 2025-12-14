<?php
require_once(__DIR__ . '/../../models/manageUsers.php');
$userObj = new User();
$userDashboard = $userObj->fetchUserName($_SESSION["user_id"]);

// --- DYNAMIC TITLE LOGIC ---
$currentFile = basename($_SERVER['SCRIPT_NAME']);
$pageTitle = 'Dashboard'; // Default title

switch ($currentFile) {
    case 'booksSection.php':
        $pageTitle = 'Books';
        break;
    case 'categorySection.php':
        $pageTitle = 'Categories';
        break;
    case 'usersSection.php':
        $pageTitle = 'Users';
        break;
    case 'borrowDetailsSection.php':
        $pageTitle = 'Borrowing Details';
        break;
    case 'reportsSection.php':
        $pageTitle = 'Reports';
        break;
    // 'dashboard.php' is already covered by the default
}
// --- END NEW LOGIC ---

?>

<link rel="stylesheet" href="../../../public/assets/fontawesome-free-7.1.0-web/css/all.min.css">

<nav class="flex justify-between items-center w-full sticky py-4 px-8 top-0 z-50">
    <div class="flex items-center gap-3">
        <i class="toggle-btn fa-solid fa-list text-xl" style="color: #545454ff;"></i>
        <img src="../../../public/assets/images/logo.png" alt="Logo" class="h-12 w-12">
        <h2 class="font-bold text-3xl">WMSU Library</h2>
    </div>

    <h1 class="text-3xl font-bold text-gray-800">
        <?php echo htmlspecialchars($pageTitle); ?>
    </h1>

    <div class="account flex items-center">
        <div class="bg-white rounded-full flex items-center justify-center h-8 w-8 px-4 mx-4">
            <i class="fa-solid fa-user" style="color: #bd322f;"></i>
        </div>
        <h2 class="text-lg font-bold">
            <?php if (isset($userDashboard)) {
                echo htmlspecialchars($userDashboard["fName"] . " " . $userDashboard["lName"]);
            } else {
                echo "Admin User";
            } ?>
        </h2>
    </div>
</nav>


<div class="grid grid-cols-1 sm:grid-cols-[auto_1fr] w-full flex-1 overflow-hidden">

    <aside class="h-full sidebar narrow text-white font-bold flex-shrink-0 flex flex-col">
        <ul class="flex flex-col h-full">
            <li class="links">
                <a href="../../../app/views/librarian/dashboard.php" id="dashboardBtn">
                    <i class="fas fa-tachometer-alt"></i> <span class="text-white pl-4">Dashboard</span>
                </a>
            </li>
            <li class="links">
                <a href="../../../app/views/librarian/booksSection.php" id="booksBtn">
                    <i class="fas fa-book"></i> <span class="text-white pl-4">Books</span>
                </a>
            </li>
            <li class="links">
                <a href="../../../app/views/librarian/categorySection.php" id="categoryBtn">
                    <i class="fas fa-tags"></i> <span class="text-white pl-4">Categories</span>
                </a>
            </li>
            <li class="links">
                <a href="../../../app/views/librarian/usersSection.php" id="borrowersBtn">
                    <i class="fas fa-users"></i> <span class="text-white pl-4">Users</span>
                </a>
            </li>
            <li class="links">
                <a href="../../../app/views/librarian/borrowDetailsSection.php" id="detailsBtn">
                    <i class="fas fa-file-alt"></i> <span class="text-white pl-4">Borrowing Details</span>
                </a>
            </li>
            <li class="links">
                <a href="../../../app/views/librarian/reportsSection.php" id="reportsBtn">
                    <i class="fas fa-chart-pie"></i> <span class="text-white pl-4">Reports</span>
                </a>
            </li>
            <div class="mt-auto">
                <li class="links">
                    <a href="../../../app/views/borrower/catalogue.php">
                        <i class="fa-solid fa-book"></i> <span class="text-white pl-4">Brose Catalogue</span>
                    </a>
                </li>
                <li class="links">
                    <a href="../../controllers/logout.php">
                        <i class="fas fa-sign-out-alt"></i> <span class="text-white pl-4">Logout</span>
                    </a>
                </li>
            </div>
        </ul>
    </aside>

   
<script>
    const sidebar = document.querySelector(".sidebar");
    const toggleBtn = document.querySelector(".toggle-btn");

    toggleBtn.addEventListener("click", () => {
        sidebar.classList.toggle("narrow");
    });
</script>