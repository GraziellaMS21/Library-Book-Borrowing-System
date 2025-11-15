<?php
session_start();
// Ensure user is logged in to view reports
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

// --- UPDATED: Include all necessary models ---
require_once(__DIR__ . "/../../models/manageReports.php");
require_once(__DIR__ . '/../../models/manageBook.php');
require_once(__DIR__ . '/../../models/manageBorrowDetails.php');

// --- UPDATED: Initialize all models ---
$bookObj = new Book();
$user_id = $_SESSION['user_id'];


$books = $bookObj->viewBook('', '');


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Library Report</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/admin.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <style>
        @media print {
            @page {
                size: A4 landscape;
                /* Sets page size to A4 and orientation to landscape */
            }
        }
    </style>
</head>

<body>
    <div class="title flex w-full items-center justify-between mb-8">
        <h1 class="text-red-900 font-bold text-4xl mx-auto text-center">
            COMPLETE BOOK LIST<br>
            <span class="text-2xl font-medium">As of <?= date('F j, Y') ?></span>
        </h1>
    </div>
    <div class="view">
        <table>
            <tr>
                <th>No</th>
                <th>Book Cover</th>
                <th>Book Title</th>
                <th>Author</th>
                <th>Category</th>
                <th>Publication Name</th>
                <th>Publication Year</th>
                <th>ISBN</th>
                <th>No. of Copies</th>
                <th>Condition</th>
                <th>Replacement Cost</th>
                <th>Status</th>
                <th>Date Added</th>
            </tr>

            <?php
            $no = 1;
            foreach ($books as $book) {
                $book_cover_url = !empty($book["book_cover_dir"]) ? "../../../" . $book["book_cover_dir"] : null;
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="text-center"> <?php if ($book_cover_url) { ?>
                            <img src="<?= $book_cover_url ?>" alt="Cover"
                                class="w-16 h-16 object-cover rounded mx-auto border border-gray-300"
                                title="<?= $book["book_cover_name"] ?? 'Book Cover' ?>">
                        <?php } else { ?>
                            <span class="text-gray-500 text-xs">N/A</span>
                        <?php } ?>
                    </td>
                    <td><?= $book["book_title"] ?></td>
                    <td><?= $book["author"] ?></td>
                    <td><?= $book["category_name"] ?></td>
                    <td><?= $book["publication_name"] ?></td>
                    <td><?= $book["publication_year"] ?></td>
                    <td><?= $book["ISBN"] ?></td>
                    <td><?= $book["book_copies"] ?></td>
                    <td><?= $book["book_condition"] ?></td>
                    <td><?= $book["replacement_cost"] ?></td>
                    <td><?= $book["status"] ?></td>
                    <td><?= $book["date_added"] ?></td>
                </tr>
                <?php
            }
            ?>
        </table>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(() => {
                window.print();
            }, 500); // 500ms delay to be safe

        });
    </script>
</body>

</html>