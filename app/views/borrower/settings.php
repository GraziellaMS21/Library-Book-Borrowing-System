<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageUsers.php");

$userObj = new User();
$userID = $_SESSION["user_id"];
$user = $userObj->fetchUser($userID);

$profileImg = !empty($user['imageID_dir']) ? "../../../" . $user['imageID_dir'] : "../../../public/assets/images/default_id.png";

// Logic to determine label based on User Type (Student vs Others)
$userType = $user['type_name'] ?? ''; 
$imageLabel = ($userType === 'Student') ? "Certificate of Enrollment Image" : "ID Image";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/borrower.css" />
    <link rel="stylesheet" href="../../../public/assets/css/header_footer.css" />
</head>

<body class="min-h-screen bg-gray-50 flex flex-col">
    <div class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        <?php require_once(__DIR__ . '/../shared/headerBorrower.php'); ?>

        <header class="text-center my-10">
            <h1 class="title text-4xl sm:text-5xl font-extrabold text-red-900">Settings</h1>
            <p class="text-xl mt-2 text-yellow-300">Manage your account preferences</p>
        </header>

        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 text-center">
                <?php 
                    if ($_GET['error'] == 'validation') echo "Please check your inputs and try again.";
                    elseif ($_GET['error'] == 'password') echo "Password mismatch or too short.";
                    elseif ($_GET['error'] == 'db') echo "Database error occurred.";
                    else echo "An error occurred.";
                ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 text-center">
                <?php 
                    if ($_GET['success'] == 'edit') echo "Profile updated successfully!";
                    elseif ($_GET['success'] == 'password') echo "Password changed successfully!";
                ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-8 rounded-xl shadow-lg mb-8">
            <h3 class="text-xl font-bold text-gray-800 mb-6 border-b pb-2 flex items-center gap-2">
                <i class="fa-solid fa-id-card text-red-800"></i> Identity Verification
            </h3>
            
            <div class="flex flex-col items-center">
                <span class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                    <?= htmlspecialchars($imageLabel) ?>
                </span>
                
                <div class="w-full max-w-md h-64 bg-gray-100 rounded-lg border-2 border-gray-300 border-dashed flex items-center justify-center overflow-hidden shadow-inner group relative">
                    <img 
                        src="<?= htmlspecialchars($profileImg) ?>" 
                        alt="Identity Document" 
                        class="w-full h-full object-contain cursor-zoom-in hover:scale-105 transition-transform duration-300"
                        onclick="openImageModal(this.src)"
                    >
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <span class="bg-black bg-opacity-60 text-white text-xs px-2 py-1 rounded">Click to Zoom</span>
                    </div>
                </div>
                
                <p class="text-xs text-gray-400 mt-2 italic">
                    This document is used for identity verification and cannot be changed. Please contact the librarian if this needs updating.
                </p>
            </div>
        </div>

        <div class="bg-white p-8 rounded-xl shadow-lg mb-8">
            <h3 class="text-xl font-bold text-gray-800 mb-6 border-b pb-2 flex items-center gap-2">
                <i class="fa-solid fa-user-pen text-red-800"></i> Update Information
            </h3>

            <form action="../../controllers/userController.php?action=edit_profile_borrower" method="POST">
                <input type="hidden" name="userID" value="<?= $userID ?>">
                <input type="hidden" name="existing_image_dir" value="<?= $user['imageID_dir'] ?>">
                <input type="hidden" name="existing_image_name" value="<?= $user['imageID_name'] ?>">

                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                            <input type="text" name="fName" value="<?= htmlspecialchars($user['fName']) ?>" readonly 
                                class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-500 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                            <input type="text" name="lName" value="<?= htmlspecialchars($user['lName']) ?>" readonly 
                                class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-500 cursor-not-allowed">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" readonly
                            class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-500 cursor-not-allowed">
                    </div>

                    <div>
                        <label for="contact_no" class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                        <input type="text" name="contact_no" id="contact_no" value="<?= htmlspecialchars($user['contact_no']) ?>" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition">
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-red-800 text-white rounded-lg font-semibold hover:bg-red-700 transition shadow-md">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white p-8 rounded-xl shadow-lg">
            <h3 class="text-xl font-bold text-gray-800 mb-6 border-b pb-2 flex items-center gap-2">
                <i class="fa-solid fa-lock text-red-800"></i> Security
            </h3>

            <form action="../../controllers/userController.php?action=change_password" method="POST">
                <input type="hidden" name="userID" value="<?= $userID ?>">
                
                <div class="space-y-4 max-w-lg">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                        <input type="password" name="current_password" id="current_password" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition">
                    </div>

                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <input type="password" name="new_password" id="new_password" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition">
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                        <input type="password" name="c_password" id="confirm_password" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition">
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-gray-800 text-white rounded-lg font-semibold hover:bg-gray-700 transition shadow-md">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="imageModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-95 flex items-center justify-center p-4" onclick="closeImageModal()">
        <span class="absolute top-6 right-6 text-white text-5xl cursor-pointer hover:text-gray-300 font-bold">&times;</span>
        <img id="expandedImg" class="max-w-full max-h-full rounded-md shadow-2xl object-contain" src="">
    </div>

    <?php require_once(__DIR__ . '/../shared/footer.php'); ?>

    <script>
        function openImageModal(src) {
            const modal = document.getElementById('imageModal');
            const img = document.getElementById('expandedImg');
            img.src = src;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto'; // Restore scrolling
        }
    </script>
</body>
</html> 