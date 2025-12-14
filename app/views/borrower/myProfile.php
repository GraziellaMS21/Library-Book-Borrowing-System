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

// Fetch department name
$departments = $userObj->fetchDepartments();
$deptName = "N/A";
if (isset($user['departmentID'])) {
    foreach ($departments as $dept) {
        if ($dept['departmentID'] == $user['departmentID']) {
            $deptName = $dept['department_name'];
            break;
        }
    }
}

// Image Path Logic
$profileImg = !empty($user['imageID_dir']) ? "../../../" . $user['imageID_dir'] : "../../../public/assets/images/default_id.png";

// Label Logic
$userType = $user['type_name'] ?? ''; 
$imageLabel = ($userType === 'Student') ? "Certificate of Enrollment" : "ID Image";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/borrower.css" />
    <link rel="stylesheet" href="../../../public/assets/css/header_footer.css" />
</head>

<body class="min-h-screen bg-gray-50 flex flex-col">
    <div class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        <?php require_once(__DIR__ . '/../shared/headerBorrower.php'); ?>

        <header class="text-center my-10">
            <h1 class="title text-4xl sm:text-5xl font-extrabold text-red-900">My Profile</h1>
            <p class="text-xl mt-2 text-yellow-300">Personal Information</p>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="col-span-1">
                <div class="bg-white p-6 rounded-xl shadow-lg flex flex-col items-center text-center h-full border-t-4 border-red-800">
                    
                    <h2 class="text-2xl font-bold text-gray-800 mb-1"><?= htmlspecialchars($user['fName'] . ' ' . $user['lName']) ?></h2>
                    <span class="px-3 py-1 mb-6 text-sm font-semibold text-red-800 bg-red-100 rounded-full">
                        <?= htmlspecialchars($userType) ?>
                    </span>

                    <div class="w-full">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 text-left w-full pl-1">
                            <?= htmlspecialchars($imageLabel) ?>
                        </p>
                        <div class="w-full aspect-[4/3] bg-gray-100 rounded-lg border-2 border-gray-300 border-dashed flex items-center justify-center overflow-hidden shadow-inner mb-4 group relative">
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
                    </div>

                    <p class="text-sm text-gray-500 mt-auto pt-4 border-t w-full">
                        Member since <span class="font-medium text-gray-800"><?= date("F Y", strtotime($user['date_registered'])) ?></span>
                    </p>
                </div>
            </div>

            <div class="col-span-1 md:col-span-2">
                <div class="bg-white p-8 rounded-xl shadow-lg h-full">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 border-b pb-2">Account Details</h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Full Name</p>
                            <p class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($user['fName'] . ' ' . $user['middleIn'] . ' ' . $user['lName']) ?></p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Email Address</p>
                            <p class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($user['email']) ?></p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Contact Number</p>
                            <p class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($user['contact_no']) ?></p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Department</p>
                            <p class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($deptName) ?></p>
                        </div>

                        <?php if (!empty($user['id_number'])): ?>
                        <div>
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">ID Number</p>
                            <p class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($user['id_number']) ?></p>
                        </div>
                        <?php endif; ?>

                        <div>
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Account Status</p>
                            <div class="mt-1">
                                <?php if($user['account_status'] == 'Active'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <?= htmlspecialchars($user['account_status']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div>
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Borrower Limit</p>
                            <p class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($user['borrower_limit'] ?? 0) ?> Books</p>
                        </div>
                    </div>

                    <div class="mt-10 flex justify-end">
                        <a href="settings.php" class="px-6 py-2 bg-gray-800 text-white rounded-lg font-semibold hover:bg-gray-700 transition shadow-md flex items-center gap-2">
                            <i class="fa-solid fa-cog"></i> Edit Profile
                        </a>
                    </div>
                </div>
            </div>
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