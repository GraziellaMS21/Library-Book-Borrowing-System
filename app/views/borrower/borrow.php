<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../views/borrower/login.php");
    exit;
}

$errors = $_SESSION["errors"] ?? [];
unset($_SESSION["errors"]);

require_once(__DIR__ . "/../../models/manageDetails.php");
$details = new BorrowDetails();

$bookID = $_GET["bookID"] ?? null;
$userID = $_SESSION["user_id"];
$userType = $_SESSION["user_type"] ?? "Student"; // fallback

// Determine borrow period based on user type
switch ($userType) {
    case "Staff":
        $borrow_period = 14;
        break;
    case "Guest":
        $borrow_period = 7;
        break;
    default:
        $borrow_period = 10;
        break;
}

// Compute pickup (today) and return dates
$pickup_date = date("Y-m-d"); // today
$return_date = date("Y-m-d", strtotime("+$borrow_period days"));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Book</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <form action="../../../app/controllers/borrowBookController.php" method="POST"
        class="bg-white p-6 rounded-xl shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold mb-4 text-center text-red-800">Borrow Book</h1>

        <!-- Hidden IDs -->
        <input type="hidden" name="bookID" value="<?= htmlspecialchars($bookID) ?>">
        <input type="hidden" name="userID" value="<?= htmlspecialchars($userID) ?>">

        <div class="mb-4">
            <label class="block text-gray-700 font-semibold">Book ID:</label>
            <input type="text" value="<?= htmlspecialchars($bookID) ?>" disabled
                class="w-full border rounded-lg p-2 bg-gray-100">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-semibold">User ID:</label>
            <input type="text" value="<?= htmlspecialchars($userID) ?>" disabled
                class="w-full border rounded-lg p-2 bg-gray-100">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-semibold">Pickup Date:</label>
            <input type="date" name="pickup_date" value="<?= $pickup_date ?>" class="w-full border rounded-lg p-2"
                min="<?= $pickup_date ?>" max="<?= $return_date ?>" required>
            <p class="text-sm text-gray-500 mt-1">* You can pick up your book starting today.</p>
            <p class="errors text-red-500 text-sm"><?= $errors["pickup_date"] ?? "" ?></p>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-semibold">Return Date:</label>
            <input type="date" name="return_date" value="<?= $return_date ?>"
                class="w-full border rounded-lg p-2 bg-gray-100" readonly>
            <p class="text-sm text-gray-500 mt-1">
                * Maximum borrow period: <?= $borrow_period ?> days
            </p>
            <p class="errors text-red-500 text-sm"><?= $errors["return_date"] ?? "" ?></p>
        </div>

        <div class="flex justify-center">
            <button type="submit" class="bg-red-800 text-white px-6 py-2 rounded-lg hover:bg-red-700">
                Confirm Borrow
            </button>
        </div>
    </form>

</body>

</html>