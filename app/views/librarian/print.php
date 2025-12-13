<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

// --- MODELS ---
require_once(__DIR__ . "/../../models/manageReports.php");
require_once(__DIR__ . '/../../models/manageBook.php');
require_once(__DIR__ . '/../../models/manageBorrowDetails.php');
require_once(__DIR__ . '/../../models/manageUsers.php');
require_once(__DIR__ . '/../../models/manageCategory.php');

$bookObj = new Book();
$borrowObj = new BorrowDetails();
$reportsObj = new Reports();
$userObj = new User();
$categoryObj = new Category();

$user_id = $_SESSION['user_id'];

// --- REPORT TYPE ---
$report_type = $_GET['report_type'] ?? 'general';
$report_title = 'General Library Report';
if ($report_type === 'books')
    $report_title = 'Complete Book Inventory';
if ($report_type === 'users')
    $report_title = 'User Directory List';
if ($report_type === 'categories')
    $report_title = 'Category List';

// --- DATA FETCHING BASED ON TYPE ---

// 1. GENERAL REPORT DATA
if ($report_type === 'general') {
    $total_book_copies = $bookObj->countTotalBookCopies();
    $total_book = $bookObj->countTotalDistinctBooks();
    $total_borrowed_books = $borrowObj->countTotalBorrowedBooks();
    $total_borrowers = $reportsObj->countTotalActiveBorrowers();
    $fine_collection_summary = $reportsObj->getFineCollectionSummary();
    $summary_total_borrows_ever = $reportsObj->getSummaryTotalBorrows();
    $summary_on_time_rate = $reportsObj->getOnTimeReturnRate();
    $summary_total_categories = $reportsObj->getSummaryTotalCategories();
    $avg_borrowing_per_user = ($total_borrowers > 0) ? ($summary_total_borrows_ever / $total_borrowers) : 0;

    // Charts Data
    $monthly_borrow_return_trend = $reportsObj->getMonthlyBorrowReturnTrend();
    $top_5_books = $reportsObj->getTopBorrowedBooks(5);
    $borrowing_by_department = $reportsObj->getBorrowingByDepartment();
    $monthly_fine_collection_trend = $reportsObj->getMonthlyFineCollectionTrend();
    $top_5_unpaid_fines = $reportsObj->getTopUnpaidFinesUsers(5);
    $top_5_borrowers = $reportsObj->getTopActiveBorrowers(5);
    $monthly_user_reg_trend = $reportsObj->getMonthlyUserRegistrationTrend();
    $borrower_type_breakdown = $reportsObj->getBorrowerTypeBreakdown();
    $book_status_overview = $reportsObj->getBookStatusOverview();
    $books_per_category = $reportsObj->getBooksPerCategory();
    $late_returns_trend = $reportsObj->getLateReturnsTrend();
    $top_5_categories = $reportsObj->getTopPopularCategories(5);
    $overdue_books_summary = $reportsObj->getOverdueBooksSummary();
}

// 2. BOOK LIST DATA
$book_list = [];
if ($report_type === 'books') {
    $book_list = $bookObj->viewBook('', '');
}

// 3. USER LIST DATA
$user_list = [];
if ($report_type === 'users') {
    $user_list = $userObj->viewUser('', '', '');
}

// 4. CATEGORY LIST DATA
$category_list = [];
if ($report_type === 'categories') {
    $category_list = $categoryObj->viewCategory();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print - <?= htmlspecialchars($report_title) ?></title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/admin.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* PRINT SPECIFIC STYLES */
        @media print {
            @page {
                /* Use landscape for wide tables (Books/Users), Portrait for General/Categories */
                size: <?= ($report_type == 'books' || $report_type == 'users') ? 'landscape' : 'auto' ?>;
                margin: 0.5in;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                background-color: white;
                display: block; 
            }

            .no-print {
                display: none !important;
            }

            /* Ensure container takes full width */
            .container {
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                font-size: 10pt; /* Smaller font for print to fit more columns */
            }

            th, td {
                border: 1px solid #ddd;
                padding: 4px 8px; /* Tighter padding for print */
                text-align: left;
            }

            th {
                background-color: #f3f4f6 !important; /* Force background color */
                color: #1f2937;
                font-weight: bold;
            }

            tr:nth-child(even) {
                background-color: #f9fafb !important;
            }

            .report-section-header {
                padding: 0.5rem;
                font-size: 1.25rem;
                font-weight: bold;
                margin-top: 1.5rem;
                margin-bottom: 1rem;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .report-chart-container {
                page-break-inside: avoid;
                border: 1px solid #e5e7eb;
                margin-bottom: 15px;
                padding: 10px;
                width: 100%;
                background: #fff;
            }

            /* FIX FOR CHART OVERFLOW */
            .chart-wrapper {
                position: relative;
                height: 200px !important; /* Fixed height for print */
                width: 100% !important;
            }
            
            canvas {
                max-width: 100% !important;
                max-height: 100% !important;
            }
            
            .page-break {
                page-break-before: always;
            }
        }

        /* SCREEN STYLES */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            border: 1px solid #e5e7eb;
            padding: 0.75rem;
            text-align: left;
        }

        th {
            background-color: #f9fafb;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            color: #374151;
        }

        .report-section-header {
            color: #991b1b;
            font-weight: 800;
            font-size: 1.5rem;
            border-bottom: 2px solid #991b1b;
            padding-bottom: 0.5rem;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        
        .chart-wrapper {
            position: relative;
            height: 300px; /* View height on screen */
            width: 100%;
        }
    </style>
</head>

<body class="bg-white text-gray-800">

    <div id="loading-msg" class="text-center text-xl font-semibold my-20 p-10 no-print">
        Generating report, please wait...
    </div>

    <div class="container mx-auto p-4">
        
        <div class="flex flex-col items-center justify-center mb-6 border-b-2 border-red-900 pb-4">
            <h1 class="text-red-900 font-bold text-3xl uppercase text-center tracking-wide">
                <?= htmlspecialchars($report_title) ?>
            </h1>
            <span class="text-lg font-medium text-gray-600 mt-2">Generated on <?= date('F j, Y') ?></span>
        </div>

        <?php if ($report_type === 'general'): ?>
            
            <div id="overview">
                <h2 class="report-section-header">1. EXECUTIVE SUMMARY</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-6">
                    <div class="p-4 border rounded bg-gray-50">
                        <p class="text-gray-500 text-xs uppercase font-bold">Total Books</p>
                        <p class="text-xl font-bold"><?= htmlspecialchars($total_book) ?></p>
                    </div>
                    <div class="p-4 border rounded bg-gray-50">
                        <p class="text-gray-500 text-xs uppercase font-bold">Total Copies</p>
                        <p class="text-xl font-bold"><?= htmlspecialchars($total_book_copies) ?></p>
                    </div>
                    <div class="p-4 border rounded bg-gray-50">
                        <p class="text-gray-500 text-xs uppercase font-bold">Active Borrowers</p>
                        <p class="text-xl font-bold"><?= htmlspecialchars($total_borrowers ?? 0) ?></p>
                    </div>
                    <div class="p-4 border rounded bg-gray-50">
                        <p class="text-gray-500 text-xs uppercase font-bold">Total Fines Collected</p>
                        <p class="text-xl font-bold text-red-800">₱<?= number_format($fine_collection_summary['total_collected'] ?? 0, 2) ?></p>
                    </div>
                    <div class="p-4 border rounded bg-gray-50">
                        <p class="text-gray-500 text-xs uppercase font-bold">Borrowed Books</p>
                        <p class="text-xl font-bold"><?= htmlspecialchars($total_borrowed_books ?? 0) ?></p>
                    </div>
                    <div class="p-4 border rounded bg-gray-50">
                        <p class="text-gray-500 text-xs uppercase font-bold">Avg Borrow/User</p>
                        <p class="text-xl font-bold"><?= number_format($avg_borrowing_per_user, 1) ?></p>
                    </div>
                    <div class="p-4 border rounded bg-gray-50">
                        <p class="text-gray-500 text-xs uppercase font-bold">Total Categories</p>
                        <p class="text-xl font-bold"><?= htmlspecialchars($summary_total_categories['total_categories'] ?? 0) ?></p>
                    </div>
                    <div class="p-4 border rounded bg-gray-50">
                        <p class="text-gray-500 text-xs uppercase font-bold">On-Time Return Rate</p>
                        <p class="text-xl font-bold"><?= number_format($summary_on_time_rate['rate'] ?? 0, 1) ?>%</p>
                    </div>
                </div>
            </div>

            <div id="user-activity" >
                <h2 class="report-section-header">2. USER & BORROWING ACTIVITY</h2>
                <div class="grid grid-cols-2 gap-6">
                    <div class="report-chart-container">
                        <h3 class="font-bold text-center mb-2">Monthly Borrowing Trend</h3>
                        <div class="chart-wrapper">
                            <canvas id="monthlyBorrowReturnChart"></canvas>
                        </div>
                    </div>
                    <div class="report-chart-container">
                        <h3 class="font-bold text-center mb-2">User Registration Trend</h3>
                        <div class="chart-wrapper">
                            <canvas id="userRegTrendChart"></canvas>
                        </div>
                    </div>
                    <div class="report-chart-container">
                        <h3 class="font-bold text-center mb-2">Top 5 Most Active Borrowers</h3>
                        <div class="chart-wrapper">
                            <canvas id="topBorrowersChart"></canvas>
                        </div>
                    </div>
                    <div class="report-chart-container">
                        <h3 class="font-bold text-center mb-2">Borrowing by Department</h3>
                        <div class="chart-wrapper">
                            <canvas id="borrowByDeptChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="report-chart-container mt-4">
                    <h3 class="font-bold text-center mb-2">Borrower Type Breakdown</h3>
                    <div class="chart-wrapper" style="height: 350px;">
                        <canvas id="borrowerTypeChart"></canvas>
                    </div>
                </div>
            </div>

            <div></div> <div id="collection-inventory">
                <h2 class="report-section-header">3. COLLECTION & INVENTORY</h2>
                <div class="grid grid-cols-2 gap-6">
                    <div class="report-chart-container">
                        <h3 class="font-bold text-center mb-2">Top 5 Most Borrowed Books</h3>
                        <div class="chart-wrapper">
                            <canvas id="topBooksChart"></canvas>
                        </div>
                    </div>
                    <div class="report-chart-container">
                        <h3 class="font-bold text-center mb-2">Top 5 Popular Categories</h3>
                        <div class="chart-wrapper">
                            <canvas id="borrowTrendCategoryChart"></canvas>
                        </div>
                    </div>
                    <div class="report-chart-container">
                        <h3 class="font-bold text-center mb-2">Book Status Overview</h3>
                        <div class="chart-wrapper">
                            <canvas id="bookStatusChart"></canvas>
                        </div>
                    </div>
                    <div class="report-chart-container">
                        <h3 class="font-bold text-center mb-2">Books per Category</h3>
                        <div class="chart-wrapper">
                            <canvas id="booksPerCategoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div id="fines-compliance">
                <h2 class="report-section-header">4. FINES & COMPLIANCE</h2>
                <div class="grid grid-cols-2 gap-6">
                    <div class="report-chart-container">
                        <h3 class="font-bold text-center mb-2">Fine Collection Over Time</h3>
                        <div class="chart-wrapper">
                            <canvas id="monthlyFineTrendChart"></canvas>
                        </div>
                    </div>
                    <div class="report-chart-container">
                        <h3 class="font-bold text-center mb-2">Late Returns Trend</h3>
                        <div class="chart-wrapper">
                            <canvas id="lateReturnsChart"></canvas>
                        </div>
                    </div>
                    <div class="report-chart-container">
                        <h3 class="font-bold text-center mb-2">Top 5 Users with Unpaid Fines</h3>
                        <div class="chart-wrapper">
                            <canvas id="topUnpaidFinesChart"></canvas>
                        </div>
                    </div>
                    <div class="report-chart-container">
                        <h3 class="font-bold text-center mb-2">Fine Collection Summary</h3>
                        <div class="chart-wrapper">
                            <canvas id="fineSummaryChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="mt-8 page-break-inside-avoid">
                    <h3 class="font-bold text-lg mb-2">Overdue Books Summary Table</h3>
                    <table class="w-full text-sm">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>Borrower</th>
                                <th>Due Date</th>
                                <th>Days Overdue</th>
                                <th>Fine (₱)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($overdue_books_summary)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-gray-500 py-4">No overdue books found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($overdue_books_summary as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['book_title']) ?></td>
                                        <td><?= htmlspecialchars($item['fName'] . ' ' . $item['lName']) ?></td>
                                        <td><?= htmlspecialchars($item['expected_return_date']) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($item['days_overdue']) ?></td>
                                        <td class="font-bold text-right"><?= number_format($item['fine_amount'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($report_type === 'books'): ?>
            <div class="view w-full">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th style="width: 5%">No</th>
                            <th style="width: 25%">Title</th>
                            <th style="width: 15%">Author</th>
                            <th style="width: 10%">Category</th>
                            <th style="width: 12%">ISBN</th>
                            <th style="width: 5%">Copies</th>
                            <th style="width: 8%">Condition</th>
                            <th style="width: 8%">Status</th>
                            <th style="width: 12%">Date Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        foreach ($book_list as $book): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="font-semibold"><?= htmlspecialchars($book["book_title"]) ?></td>
                                <td><?= htmlspecialchars($book["author_names"] ?? $book["author"] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($book["category_name"]) ?></td>
                                <td><?= htmlspecialchars($book["ISBN"]) ?></td>
                                <td class="text-center"><?= htmlspecialchars($book["book_copies"]) ?></td>
                                <td><?= htmlspecialchars($book["book_condition"]) ?></td>
                                <td><?= htmlspecialchars($book["status"]) ?></td>
                                <td><?= htmlspecialchars($book["date_added"]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($report_type === 'users'): ?>
            <div class="view w-full">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th style="width: 5%">No</th>
                            <th style="width: 20%">Last Name</th>
                            <th style="width: 20%">First Name</th>
                            <th style="width: 20%">Email</th>
                            <th style="width: 10%">Type</th>
                            <th style="width: 15%">Department</th>
                            <th style="width: 10%">Date Reg.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        foreach ($user_list as $user): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($user["lName"]) ?></td>
                                <td><?= htmlspecialchars($user["fName"]) ?></td>
                                <td><?= htmlspecialchars($user["email"]) ?></td>
                                <td><?= htmlspecialchars($user["type_name"]) ?></td>
                                <td><?= htmlspecialchars($user['department_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($user['date_registered'] ?? 'N/A') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($report_type === 'categories'): ?>
            <div class="view max-w-2xl mx-auto w-full">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th style="width: 20%">No</th>
                            <th style="width: 80%">Category Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        foreach ($category_list as $cat): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td class="font-semibold"><?= htmlspecialchars($cat["category_name"]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>

    <?php if ($report_type === 'general'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Colors
                const colors = { red: '#931C19', gray: '#414141' };
                const palette = ['#931C19', '#BD322F', '#3B82F6', '#10B981', '#610101', '#414141'];
                const months = ['Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov']; // Placeholder logic
                Chart.defaults.animation.duration = 0; // Disable animation for print

                // Shared Options to Fix Overflow
                const chartOptions = {
                    responsive: true,
                    maintainAspectRatio: false, // Critical for fixed height containers
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } } }
                };

                const horizontalBarOptions = {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
                };

                // Helper to check element exists before creating chart
                function createChart(id, config) {
                    const el = document.getElementById(id);
                    if (el) new Chart(el, config);
                }

                // 1. Monthly Borrowing
                const borrowData = <?= json_encode($monthly_borrow_return_trend) ?>;
                createChart('monthlyBorrowReturnChart', {
                    type: 'line',
                    data: {
                        labels: borrowData.map(d => d.month),
                        datasets: [
                            { label: 'Borrowed', data: borrowData.map(d => d.total_borrows), borderColor: colors.red, tension: 0.1 },
                            { label: 'Returned', data: borrowData.map(d => d.total_returns), borderColor: colors.gray, tension: 0.1 }
                        ]
                    },
                    options: chartOptions
                });

                // 2. User Reg
                const userReg = <?= json_encode($monthly_user_reg_trend) ?>;
                createChart('userRegTrendChart', {
                    type: 'line',
                    data: {
                        labels: userReg.map(d => d.month),
                        datasets: [{ label: 'New Users', data: userReg.map(d => d.new_users), borderColor: colors.red }]
                    },
                    options: chartOptions
                });

                // 3. Top Borrowers
                const topBorrowers = <?= json_encode($top_5_borrowers) ?>;
                createChart('topBorrowersChart', {
                    type: 'bar',
                    data: {
                        labels: topBorrowers.map(d => d.fName + ' ' + d.lName),
                        datasets: [{ label: 'Borrows', data: topBorrowers.map(d => d.borrow_count), backgroundColor: colors.red }]
                    },
                    options: horizontalBarOptions
                });

                // 4. Dept
                const deptData = <?= json_encode($borrowing_by_department) ?>;
                createChart('borrowByDeptChart', {
                    type: 'pie',
                    data: {
                        labels: deptData.map(d => d.department),
                        datasets: [{ data: deptData.map(d => d.total_borrowed), backgroundColor: palette }]
                    },
                    options: chartOptions
                });

                // 5. Borrower Type
                const borrowerTypeData = <?= json_encode($borrower_type_breakdown) ?>;
                createChart('borrowerTypeChart', {
                    type: 'pie',
                    data: {
                        labels: borrowerTypeData.map(d => d.type_name),
                        datasets: [{ data: borrowerTypeData.map(d => d.borrow_count), backgroundColor: palette }]
                    },
                    options: chartOptions
                });

                // 6. Top Books
                const topBooks = <?= json_encode($top_5_books) ?>;
                createChart('topBooksChart', {
                    type: 'bar',
                    data: {
                        labels: topBooks.map(d => d.book_title),
                        datasets: [{ label: 'Borrows', data: topBooks.map(d => d.borrow_count), backgroundColor: colors.red }]
                    },
                    options: horizontalBarOptions
                });

                // 7. Categories
                const topCats = <?= json_encode($top_5_categories) ?>;
                createChart('borrowTrendCategoryChart', {
                    type: 'bar',
                    data: {
                        labels: topCats.map(d => d.category_name),
                        datasets: [{ label: 'Borrows', data: topCats.map(d => d.borrow_count), backgroundColor: palette }]
                    },
                    options: { ...chartOptions, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
                });

                // 8. Book Status
                const bookStatusData = <?= json_encode($book_status_overview) ?>;
                createChart('bookStatusChart', {
                    type: 'doughnut',
                    data: {
                        labels: bookStatusData.map(d => d.status),
                        datasets: [{ data: bookStatusData.map(d => d.count), backgroundColor: [colors.red, '#610101', '#9ca3af'] }]
                    },
                    options: chartOptions
                });

                // 9. Books per Category
                const booksPerCat = <?= json_encode($books_per_category) ?>;
                createChart('booksPerCategoryChart', {
                    type: 'bar',
                    data: {
                        labels: booksPerCat.map(d => d.category_name),
                        datasets: [{ label: 'Total Copies', data: booksPerCat.map(d => d.total_copies), backgroundColor: '#BD322F' }]
                    },
                    options: chartOptions
                });

                // 10. Fine Collection
                const fineData = <?= json_encode($monthly_fine_collection_trend) ?>;
                createChart('monthlyFineTrendChart', {
                    type: 'bar',
                    data: {
                        labels: fineData.map(d => d.month),
                        datasets: [
                            { label: 'Collected (₱)', data: fineData.map(d => d.total_collected), backgroundColor: colors.red },
                            { label: 'Uncollected (₱)', data: fineData.map(d => d.total_uncollected), backgroundColor: '#f87171' }
                        ]
                    },
                    options: { ...chartOptions, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } } }
                });

                // 11. Late Returns
                const lateData = <?= json_encode($late_returns_trend) ?>;
                createChart('lateReturnsChart', {
                    type: 'line',
                    data: {
                        labels: lateData.map(d => d.month),
                        datasets: [{ label: 'Late Returns', data: lateData.map(d => d.late_returns), borderColor: '#f87171', tension: 0.1 }]
                    },
                    options: chartOptions
                });

                // 12. Top Unpaid
                const topUnpaid = <?= json_encode($top_5_unpaid_fines) ?>;
                createChart('topUnpaidFinesChart', {
                    type: 'bar',
                    data: {
                        labels: topUnpaid.map(d => d.fName + ' ' + d.lName),
                        datasets: [{ label: 'Unpaid (₱)', data: topUnpaid.map(d => d.total_unpaid), backgroundColor: colors.red }]
                    },
                    options: horizontalBarOptions
                });

                // 13. Fine Summary
                const fineSummary = <?= json_encode($fine_collection_summary) ?>;
                createChart('fineSummaryChart', {
                    type: 'doughnut',
                    data: {
                        labels: ['Collected', 'Outstanding'],
                        datasets: [{ data: [fineSummary.total_collected, fineSummary.total_outstanding], backgroundColor: ['#BD322F', colors.red] }]
                    },
                    options: chartOptions
                });

                // Auto-print
                document.getElementById('loading-msg').style.display = 'none';
                setTimeout(() => { window.print(); }, 1000);
            });
        </script>
    <?php else: ?>
        <script>
            // Simple auto-print for lists
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('loading-msg').style.display = 'none';
                setTimeout(() => { window.print(); }, 500);
            });
        </script>
    <?php endif; ?>

</body>

</html>