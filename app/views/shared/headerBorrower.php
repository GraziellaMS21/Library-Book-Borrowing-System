<?php

require_once(__DIR__ . "/../../models/manageNotifications.php");

$unread_notif_count = 0;
$unread_notif_list = [];

if (isset($_SESSION["user_id"])) {
    $userID = $_SESSION["user_id"];
    $notificationObj_header = new Notification();
    $unread_notif_count = $notificationObj_header->getUnreadNotificationCount($userID);
    $unread_notif_list = $notificationObj_header->getUnreadNotifications($userID);
}

// Helper function for "time ago"
if (!function_exists('timeAgo')) {
    function timeAgo($dateString)
    {
        $date = new DateTime($dateString);
        $now = new DateTime();
        $diff = $now->diff($date);

        if ($diff->y > 0)
            return $diff->y . " year" . ($diff->y > 1 ? "s" : "") . " ago";
        if ($diff->m > 0)
            return $diff->m . " month" . ($diff->m > 1 ? "s" : "") . " ago";
        if ($diff->d > 0)
            return $diff->d . " day" . ($diff->d > 1 ? "s" : "") . " ago";
        if ($diff->h > 0)
            return $diff->h . " hour" . ($diff->h > 1 ? "s" : "") . " ago";
        if ($diff->i > 0)
            return $diff->i . " minute" . ($diff->i > 1 ? "s" : "") . " ago";
        return "Just now";
    }
}
?>

<div class="color-layer"></div>

<link rel="stylesheet" href="../../../public/assets/fontawesome-free-7.1.0-web/css/all.min.css">
</header>
<header>
    <nav class="navbar flex justify-between items-center bg-white fixed top-0 left-0 w-full z-10">
        <div class="logo-section flex items-center gap-3">
            <img src="../../../public/assets/images/logo.png" alt="Logo" class="logo">
            <h2 class="website-name">WMSU LIBRARY</h2>
        </div>
        <ul id="nav-menu" class="hidden md:flex items-center nav-links gap-8">
            <li><a href="catalogue.php"><i class="fa-solid fa-book text-red-900"></i> Catalogue</a></li>
            <li><a href="myList.php"><i class="fa-solid fa-bookmark text-red-900"></i> My List</a></li>

            <li class="relative">

                <button id="notif-dropdown-btn" class="dropdownBtn" onclick="toggleDropdown('notif-dropdown');"
                    class="flex items-center text-red-900 focus:outline-none relative">
                    <i class="fa-solid fa-bell text-red-900"></i>

                    <!-- PHP renders the count badge -->
                    <?php if ($unread_notif_count > 0): ?>
                        <span id="notif-count"
                            class="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">
                            <?= $unread_notif_count ?>
                        </span>
                    <?php endif; ?>

                    Notifications
                </button>

                <div id="notif-dropdown"
                    class="absolute right-0 mt-3 p-4 w-80 bg-white rounded-lg shadow-xl border border-red-900 z-30 hidden overflow-y-auto max-h-96">

                    <div id="notif-list">
                        <?php if (empty($unread_notif_list)): ?>
                            <div class="py-5 text-center">
                                <p class="text-sm text-gray-500">No new notifications.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($unread_notif_list as $notif):
                                $notifID = $notif["notifID"]; ?>
                                    <p class="text-black"><?= $notifID ?></p>
                                <div class="notif-container flex items-center justify-between p-2 border-b border-gray-100 hover:bg-gray-50">
                                    <div class="notif-item">
                                        <div class="flex justify-between items-start">
                                            <strong class="text-sm text-gray-900"><?= htmlspecialchars($notif['title']) ?></strong>
                                        </div>
                                        <p class="text-sm text-gray-600 whitespace-normal">
                                            <?= htmlspecialchars($notif['message']) ?> <?php if (!empty($notif['link'])): ?>
                                                <a href="<?= htmlspecialchars($notif['link']) ?>" class="viewDetails !text-blue-600 !text-xs !underline">View Details</a>
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1"><?= timeAgo($notif['created_at']) ?></p>
                                    </div>
                                    <div class="flex items-center">
                                        <a href="../../controllers/notificationController.php?action=markRead&id=<?= htmlspecialchars($notifID)?>&page=<?= basename($_SERVER['PHP_SELF'])?>"
                                            class="markRead !text-xs whitespace-nowrap ">Mark as Read
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="border-t border-gray-200 mt-2 pt-2">
                        <a href="myBorrowedBooks.php" id="viewNotif" class="text-sm text-center block text-red-900">View
                            All Notifications</a>
                    </div>
                </div>
            </li>

            <!-- Account: Dropdown Container -->
            <li class="relative">
                <button id="account-dropdown-btn" class="dropdownBtn" onclick="toggleDropdown('account-dropdown')"
                    class="flex items-center focus:outline-none"><i class="fa-solid fa-user text-red-900"></i>
                    Account</button>
                <!-- Dropdown Content -->
                <div id="account-dropdown"
                    class="absolute right-0 mt-3 p-4 w-60 bg-white rounded-lg shadow-xl border border-red-900 z-30 hidden">
                    <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Profile</a>
                    <a href="myBorrowedBooks.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My
                        Borrowed Books</a>
                    <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                    <div class="border-t border-gray-100 my-1"></div>
                    <a href="../../controllers/logout.php"
                        class="block px-4 py-2 text-sm text-red-700 hover:bg-red-50">Logout</a>
                </div>
            </li>
        </ul>

        <!-- Burger Icon for Mobile -->
        <div class="burger md:hidden flex flex-col justify-around w-6 h-5 cursor-pointer z-30" onclick="toggleMenu()">
            <span class="bg-gray-700 h-0.5 transition duration-300 transform origin-left"></span>
            <span class="bg-gray-700 h-0.5 transition duration-300"></span>
            <span class="bg-gray-700 h-0.5 transition duration-300 transform origin-left"></span>
        </div>

        <!-- Mobile Menu (Hidden by default) -->
        <div id="mobile-menu"
            class="fixed top-0 left-0 w-full h-full bg-white transition-transform duration-300 transform -translate-y-full md:hidden flex flex-col items-center justify-center space-y-10 z-20 shadow-inner">
            <button onclick="toggleMenu()"
                class="absolute top-4 right-6 text-4xl text-gray-500 hover:text-red-800">&times;</button>
            <a href="catalogue.php" class="text-3xl text-gray-800 hover:text-red-700 transition duration-150"
                onclick="toggleMenu()">Catalogue</a>
            <a href="my_loans.php" class="text-3xl text-gray-800 hover:text-red-700 transition duration-150"
                onclick="toggleMenu()">My Loans</a>
            <a href="my_list.php" class="text-3xl text-gray-800 hover:text-red-700 transition duration-150"
                onclick="toggleMenu()">My List</a>
            <a href="profile.php" class="text-3xl text-gray-800 hover:text-red-700 transition duration-150"
                onclick="toggleMenu()">My Profile</a>
            <a href="settings.php" class="text-3xl text-gray-800 hover:text-red-700 transition duration-150"
                onclick="toggleMenu()">Settings</a>
            <a href="../../controllers/logout.php"
                class="text-3xl text-red-800 hover:text-red-600 transition duration-150 mt-10"
                onclick="toggleMenu()">Logout</a>
        </div>
    </nav>
</header>