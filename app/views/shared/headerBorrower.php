<div class="color-layer"></div>
<header>
    <nav class="navbar flex justify-between items-center bg-white fixed top-0 left-0 w-full z-10">
        <div class="logo-section flex items-center gap-3">
            <img src="../../../public/assets/images/logo.png" alt="Logo" class="logo">
            <h2 class="title">WMSU LIBRARY</h2>
        </div>
        <!-- Desktop Navigation Links -->
        <ul id="nav-menu" class="hidden md:flex nav-links gap-8">
            <li><a href="catalogue.php">Catalogue</a></li>
            <li><a href="myBorrowedBooks.php">My Borrowed Books</a></li>
            <li><a href="myList.php">My List</a></li>

            <!-- Account: Dropdown Container -->
            <li class="relative">
                <button id="account-dropdown-btn" onclick="toggleDropdown('account-dropdown')"
                    class="flex items-center focus:outline-none">Account</button>
                <!-- Dropdown Content -->
                <div id="account-dropdown"
                    class="absolute right-0 mt-3 w-48 bg-white rounded-lg shadow-xl border border-gray-100 z-30 hidden">
                    <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Profile</a>
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
