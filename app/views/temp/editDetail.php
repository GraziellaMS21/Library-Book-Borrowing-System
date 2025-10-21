<?php
session_start();
$errors = $_SESSION["errors"] ?? [];
$old = $_SESSION["old"] ?? [];
unset($_SESSION["errors"], $_SESSION["old"]);

require_once(__DIR__ . "/../../models/manageDetails.php");
$detailsObj = new Details();

// Fetch detail data by ID
if (isset($_GET['id'])) {
    $detailID = $_GET['id'];
    $detail = $detailsObj->fetchDetail($detailID);
} else {
    die("Invalid detail ID.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Borrowing Detail</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/librarian/manage_category.css" />
</head>

<body class="w-screen flex">
    <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>

    <main class="flex-1 p-6">
        <div class="container mx-auto">
            <div class="booksSection" id="bookSection">
                <div class="btn-group mb-4">
                    <button type="button" class="manage w-3/12" id="manageBooksBtn">Manage Borrowing Details</button>
                    <button type="button" class="manage w-1/6" id="manageCategoriesBtn">Manage Penalties</button>
                </div>

                <div class="section manage_categories bg-white rounded-lg shadow-md p-6">
                    <div class="mb-4">
                        <a href="../../../app/views/librarian/detailsSection.php"
                            class="bg-red-800 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                            ← Back
                        </a>
                    </div>

                    <h2 class="text-xl font-bold mb-4 text-red-800">Edit Borrowing Detail</h2>

                    <form action="../../../app/controllers/editDetailController.php?id=<?= $detail['borrowID'] ?>"
                        method="POST" class="space-y-4">

                        <div>
                            <label class="block text-gray-700 font-semibold">User ID:</label>
                            <input type="text" name="userID" value="<?= $detail['userID'] ?? "" ?>"
                                class="w-full border border-gray-300 rounded-lg p-2 bg-gray-100" readonly>
                                    <p class="errors"><?= $errors["userID"] ?? "" ?></p>
                                
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold">Book ID:</label>
                            <input type="text" name="bookID" value="<?= $detail['bookID'] ?? "" ?>"
                                class="w-full border border-gray-300 rounded-lg p-2 bg-gray-100" readonly>
                                    <p class="errors"><?= $errors["bookID"] ?? "" ?></p>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold">Pickup Date:</label>
                            <input type="date" name="pickup_date" value="<?= $detail['pickup_date'] ?? "" ?>"
                                class="w-full border border-gray-300 rounded-lg p-2">
                                    <p class="errors"><?= $errors["pickup_date"] ?? "" ?></p>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold">Return Date:</label>
                            <input type="date" name="return_date" value="<?= $detail['return_date'] ?? "" ?>"
                                class="w-full border border-gray-300 rounded-lg p-2">
                                    <p class="errors"><?= $errors["return_date"] ?? "" ?></p>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold">Returned Condition:</label>
                            <select name="returned_condition" class="w-full border border-gray-300 rounded-lg p-2">
                                <option value="">--Select Option--</option>
                                <option value="Good" <?= isset($detail['returned_condition']) && $detail['returned_condition'] == 'Good' ? 'selected' : '' ?>>Good</option>
                                <option value="Damaged" <?= isset($detail['returned_condition']) && $detail['returned_condition'] == 'Damaged' ? 'selected' : '' ?>>Damaged</option>
                                <option value="Lost" <?= isset($detail['returned_condition']) && $detail['returned_condition'] == 'Lost' ? 'selected' : '' ?>>Lost</option>
                            </select>
                                    <p class="errors"><?= $errors["returned_condition"] ?? "" ?></p>
                        </div>


                        <div>
                            <label class="block text-gray-700 font-semibold">Request:</label>
                            <select name="request" class="w-full border border-gray-300 rounded-lg p-2">
                                <option value="">--Select Option--</option>
                                <option value="Pending" <?= isset($detail['request']) && $detail['request'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Approved" <?= isset($detail['request']) && $detail['request'] == 'Approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="Declined" <?= isset($detail['request']) && $detail['request'] == 'Declined' ? 'selected' : '' ?>>Declined</option>
                            </select>
                            
                                    <p class="errors"><?= $errors["request"] ?? "" ?></p>
                        </div>


                        <div>
                            <label class="block text-gray-700 font-semibold">Status:</label>
                            <select name="status" class="w-full border border-gray-300 rounded-lg p-2">
                                <option value="">--Select Option--</option>
                                <option value="Borrowed" <?= isset($detail['status']) && $detail['status'] == 'Borrowed' ? 'selected' : '' ?>>Borrowed</option>
                                <option value="Returned" <?= isset($detail['status']) && $detail['status'] == 'Returned' ? 'selected' : '' ?>>Returned</option>
                            </select>
                            
                                    <p class="errors"><?= $errors["status"] ?? "" ?></p>
                        </div>

                        <div class="flex justify-center">
                            <button type="submit" class="bg-red-800 text-white px-6 py-2 rounded-lg hover:bg-red-700">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
<script src="../../../public/assets/js/librarian/admin2.js"></script>

</html>