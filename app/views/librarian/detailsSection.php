<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageDetails.php");
$detailsObj = new Details();

$old = $_SESSION["old"] ?? [];
$errors = $_SESSION["errors"] ?? [];
unset($_SESSION["old"], $_SESSION["errors"]);

$search = isset($_GET['search']) ? trim($_GET['search']) : "";

$details = $detailsObj->viewDetails($search);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../../public/assets/css/librarian/admin1.css" />
</head>

<body class="h-screen w-screen flex">
    <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>

    <main>
        <div class="container">
            <div class="booksSection" id="bookSection">
                <div class="btn-group">
                    <button type="button" class="manage w-3/12" id="manageBooksBtn">Manage Borrowing Details</button>
                    <button type="button" class="manage w-1/6" id="manageCategoriesBtn">Manage Penalties</button>
                </div>

                <div class="section manage_books h-full">
                    <form method="GET" class="searchBook">
                        <input type="text" name="search" placeholder="Search user ID, book ID, or date..."
                            value="<?= htmlspecialchars($search) ?>" class="border border-red-800 rounded-lg p-2 w-1/3">
                        <button type="submit" class="bg-red-800 text-white rounded-lg px-4 py-2">Search</button>
                    </form>

                    <div class="viewDetails">
                        <table>
                            <tr>
                                <th>No</th>
                                <th>User ID</th>
                                <th>Book ID</th>
                                <th>Borrow Date</th>
                                <th>Pickup Date</th>
                                <th>Return Date</th>
                                <th>Returned Condition</th>
                                <th>Request</th>
                                <th>Status</th>
                                <th>Penalty</th>
                                <th>Actions</th>
                            </tr>

                            <?php
                            if (!empty($details)) {
                                $no = 1;
                                foreach ($details as $detail) {
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($detail["userID"]) ?></td>
                                        <td><?= htmlspecialchars($detail["bookID"]) ?></td>
                                        <td><?= htmlspecialchars($detail["borrow_date"]) ?></td>
                                        <td><?= htmlspecialchars($detail["pickup_date"]) ?></td>
                                        <td><?= htmlspecialchars($detail["return_date"]) ?></td>
                                        <td class="font-bold"><?php
                                        $condition = htmlspecialchars($detail["returned_condition"]);
                                        if ($condition == "Damaged") {
                                            echo "<span style='color: orange;'>Damaged</span>";
                                        } elseif ($condition == "Good") {
                                            echo "<span style='color: green;'>Good</span>";
                                        } elseif ($condition == "Lost") {
                                            echo "<span style='color: red;'>Lost</span>";
                                        } else {
                                            echo "<span>—</span>";
                                        }
                                        ?>
                                        </td>

                                        <td class="font-bold">
                                            <?php
                                            $request = htmlspecialchars($detail["request"]);
                                            if ($request == "Pending") {
                                                echo "<span style='color: orange;'>Pending</span>";
                                            } elseif ($request == "Approved") {
                                                echo "<span style='color: green;'>Approved</span>";
                                            } elseif ($request == "Declined") {
                                                echo "<span style='color: red;'>Declined</span>";
                                            } else {
                                                echo "<span>—</span>";
                                            }
                                            ?>
                                        </td>

                                        <td class="font-bold">
                                            <?php
                                            $status = htmlspecialchars($detail["status"]);
                                            if ($status == "Borrowed") {
                                                echo "<span style='color: blue;'>Borrowed</span>";
                                            } elseif ($status == "Returned") {
                                                echo "<span style='color: gray;'>Returned</span>";
                                            } elseif ($status == "Overdue") {
                                                echo "<span style='color: red;'>Overdue</span>";
                                            } elseif ($status == "Lost") {
                                                echo "<span style='color: darkred;'>Lost</span>";
                                            } else {
                                                echo "<span>—</span>";
                                            }
                                            ?>
                                        </td>
                                        <td class="font-bold">
                                            <?php
                                            $status = htmlspecialchars($detail["status"]);
                                            if ($status == NULL) {
                                                echo "<span>—</span>";
                                            }
                                            ?>
                                        </td>
                                        <td class="action text-center">
                                            <a class="editBtn"
                                                href="../../../app/views/librarian/editDetail.php?id=<?= $detail['borrowID'] ?>">Edit</a>

                                            <a class="deleteBtn"
                                                href="../../../app/controllers/deleteDetailController.php?id=<?= $detail['borrowID'] ?>"
                                                onclick="return confirm('Are you sure you want to delete this record?');">
                                                Delete
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="10" class="text-center text-gray-500">No details found.</td></tr>';
                            }
                            ?>
                        </table>
                    </div>
                </div>

                <!-- Penalties Table -->
                <div class="section manage_categories h-full mt-10">
                    <div class="addCat flex items-center gap-3">
                        <a id="addCatBtn" class="rounded-xl p-4 bg-red-800 text-white"
                            href="../../../app/views/librarian/addPenalty.php">
                            Add Penalty
                        </a>
                    </div>
                    <h2 class="text-xl font-bold mb-4">Manage Penalties</h2>
                    <table>
                        <tr>
                            <th>No</th>
                            <th>Penalty ID</th>
                            <th>Borrow ID</th>
                            <th>Book ID</th>
                            <th>Type</th>
                            <th>Cost</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>

                        <?php if (!empty($penalties)):
                            $no = 1;
                            foreach ($penalties as $penalty): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($penalty["penaltyID"]) ?></td>
                                    <td><?= htmlspecialchars($penalty["borrowID"]) ?></td>
                                    <td><?= htmlspecialchars($penalty["bookID"]) ?></td>
                                    <td class="font-bold">
                                        <option value="<?= $penalty['type'] ?>" <?= isset($penalty['type']) && $penalty['type'] == 'Late' ? 'selected' : '' ?>>Late</option>
                                        <option value="<?= $penalty['type'] ?>" <?= isset($penalty['type']) && $penalty['type'] == 'Damaged' ? 'selected' : '' ?>>Damaged</option>
                                        <option value="<?= $penalty['type'] ?>" <?= isset($penalty['type']) && $penalty['type'] == 'Lost' ? 'selected' : '' ?>>Lost</option>
                                    </td>
                                    <td><?= htmlspecialchars(number_format($penalty["cost"], 2)) ?></td>
                                    <td class="font-bold">
                                        <option value="<?= $penalty['status'] ?>" <?= isset($penalty['status']) && $penalty['status'] == 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                        <option value="<?= $penalty['status'] ?>" <?= isset($penalty['status']) && $penalty['status'] == 'Paid' ? 'selected' : '' ?>>Paid</option>
                                    </td>
                                    <td class="action text-center">
                                        <a class="editBtn"
                                            href="../../../app/views/librarian/editPenalty.php?id=<?= $penalty['penaltyID'] ?>">Edit</a>
                                        <a class="deleteBtn"
                                            href="../../../app/controllers/deletePenaltyController.php?id=<?= $penalty['penaltyID'] ?>"
                                            onclick="return confirm('Are you sure you want to delete this penalty?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-gray-500">No penalties found.</td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>

            </div>
        </div>
    </main>
</body>
<script src="../../../public/assets/js/librarian/admin.js"></script>

</html>