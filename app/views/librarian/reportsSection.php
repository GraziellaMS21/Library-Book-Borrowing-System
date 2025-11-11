<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageReports.php");
require_once(__DIR__ . '/../../models/manageBook.php');
require_once(__DIR__ . '/../../models/manageBorrowDetails.php');

// Initialize models
$bookObj = new Book();
$borrowObj = new BorrowDetails();
$reportsObj = new Reports();
$user_id = $_SESSION['user_id'];

// --- 1. DATA FOR CARDS (From other models) ---
$total_book_copies = $bookObj->countTotalBookCopies();
$total_book = $bookObj->countTotalDistinctBooks();
$total_borrowed_books = $borrowObj->countTotalBorrowedBooks();
$total_borrowers = $reportsObj->countTotalActiveBorrowers();

// --- 2. FETCH OVERVIEW SUMMARY CARDS (From Reports model) ---
$fine_collection_summary = $reportsObj->getFineCollectionSummary(); // Used for card + chart
$summary_avg_duration = $reportsObj->getSummaryAverageBorrowDuration();
$summary_top_author = $reportsObj->getSummaryMostBorrowedAuthor();
$top_5_categories = $reportsObj->getTopPopularCategories(5); // Used for card + chart

// --- 3. NEW OVERVIEW CARDS ---
$summary_total_borrows_ever = $reportsObj->getSummaryTotalBorrows();
$summary_on_time_rate = $reportsObj->getOnTimeReturnRate();
$summary_total_categories = $reportsObj->getSummaryTotalCategories();

// Calculate Avg Borrowing per User
$avg_borrowing_per_user = ($total_borrowers > 0) ? ($summary_total_borrows_ever / $total_borrowers) : 0;


// --- 4. DATA FOR CHARTS & TABLES ---
// I. Borrowing Reports
$monthly_borrow_return_trend = $reportsObj->getMonthlyBorrowReturnTrend();
$top_5_books = $reportsObj->getTopBorrowedBooks(5);
$borrowing_by_department = $reportsObj->getBorrowingByDepartment();

// II. Fine and Payment Reports
$monthly_fine_collection_trend = $reportsObj->getMonthlyFineCollectionTrend();
$top_5_unpaid_fines = $reportsObj->getTopUnpaidFinesUsers(5);
// $fine_collection_summary is already fetched above

// III. User Reports
$top_5_borrowers = $reportsObj->getTopActiveBorrowers(5);
$monthly_user_reg_trend = $reportsObj->getMonthlyUserRegistrationTrend();
$borrower_type_breakdown = $reportsObj->getBorrowerTypeBreakdown();
// $top_5_categories is already fetched above

// IV. Book Inventory Reports
$book_status_overview = $reportsObj->getBookStatusOverview();
$books_per_category = $reportsObj->getBooksPerCategory();
$avg_borrow_duration = $reportsObj->getAverageBorrowDurationByCategory();

// V. Overdue and Late Return Reports
$overdue_books_summary = $reportsObj->getOverdueBooksSummary();
$late_returns_trend = $reportsObj->getLateReturnsTrend();

?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Reports</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/admin.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">


    <style>
        /* Custom styles for report charts if not in admin.css */
        .report-chart-container {
            background-color: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            min-height: 400px;
            display: flex;
            flex-direction: column;
        }

        /* Style for the navigation links */
        .tabs a {
            padding: 0.75rem 1.25rem;
            font-weight: 600;
            color: #4B5563;
            /* gray-600 */
            text-decoration: none;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease-in-out;
        }

        .tabs a:hover {
            color: #931C19;
            /* red-800 */
            border-bottom-color: #931C19;
        }

        /* This prevents the section title from hiding under the sticky header */
        #overview,
        #user-activity,
        #collection-inventory,
        #fines-compliance {
            scroll-margin-top: 80px;
            /* Adjust this value to match your header height */
        }
    </style>
</head>

<body>
    <div class="w-full h-screen flex flex-col">
        <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>

        <main class="overflow-y-auto relative">
            <div class="tabs bg-white sticky top-0 z-2 flex text-center shadow-lg border-b border-gray-200 mb-6"
                id="report-tabs">
                <a href="#overview">Overview</a>
                <a href="#user-activity">User & Borrowing</a>
                <a href="#collection-inventory">Collection & Inventory</a>
                <a href="#fines-compliance">Fines & Compliance</a>
            </div>
            <div class="container">
                <div class="px-8 manage_users h-full">

                    <div class="title flex w-full items-center justify-between mb-4">
                        <h1 class="text-red-800 font-bold text-4xl">LIBRARY REPORTS</h1>
                        <a href="print.php" target="_blank"
                            class="inline-block rounded-xl text-white bg-red-900 p-4 font-bold transition-transform duration-100 transform hover:scale-105">Print
                            Library Report</a>
                    </div>


                    <div id="overview">
                        <div class="report-chart-container mt-4">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">

                                <div class="info p-6 rounded-lg shadow flex items-start">
                                    <div class="text-5xl text-white mr-4">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <div class="flex flex-col items-end text-white w-full">
                                        <span class="text-2xl font-semibold"><?= htmlspecialchars($total_book) ?></span>
                                        <h2 class="title text-sm text-gray-300">Total Distinct Books</h2>
                                    </div>
                                </div>

                                <div class="info p-6 rounded-lg shadow flex items-start">
                                    <div class="text-5xl text-white mr-4">
                                        <i class="fas fa-book-open"></i>
                                    </div>
                                    <div class="flex flex-col items-end text-white w-full">
                                        <span
                                            class="text-2xl font-semibold"><?= htmlspecialchars($total_book_copies) ?></span>
                                        <h2 class="title text-sm text-gray-300">Total Available Book Copies</h2>
                                    </div>
                                </div>

                                <div class="info p-6 rounded-lg shadow flex items-start">
                                    <div class="text-5xl text-white mr-4">
                                        <i class="fas fa-book-reader"></i>
                                    </div>
                                    <div class="flex flex-col items-end text-white w-full">
                                        <span class="text-4xl font-bold text-red-900">
                                            <?= htmlspecialchars($total_borrowed_books ?? 0) ?>
                                        </span>
                                        <h2 class="title text-sm text-gray-300 font-semibold">Total Borrowed Books</h2>
                                    </div>
                                </div>

                                <div class="info p-6 rounded-lg shadow flex items-start">
                                    <div class="text-5xl text-white mr-4">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="flex flex-col items-end text-white w-full">
                                        <span class="text-4xl font-bold text-red-900">
                                            <?= htmlspecialchars($total_borrowers ?? 0) ?>
                                        </span>
                                        <h2 class="title text-sm text-gray-300 font-semibold">Total Borrowers</h2>
                                    </div>
                                </div>

                                <div class="info p-6 rounded-lg shadow flex items-start">
                                    <div class="text-5xl text-white mr-4">
                                        <i class="fas fa-exchange-alt"></i>
                                    </div>
                                    <div class="flex flex-col items-end text-white w-full">
                                        <span class="text-4xl font-bold text-red-900">
                                            <?= number_format($avg_borrowing_per_user, 1) ?>
                                        </span>
                                        <h2 class="title text-sm text-gray-300 font-semibold">Average Borrowing per User
                                        </h2>
                                    </div>
                                </div>

                                <div class="info p-6 rounded-lg shadow flex items-start">
                                    <div class="text-5xl text-white mr-4">
                                        <i class="fas fa-bookmark"></i>
                                    </div>
                                    <div class="flex flex-col items-end text-white w-full">
                                        <span class="text-4xl font-bold text-red-900">
                                            <?= htmlspecialchars($summary_total_categories['total_categories'] ?? 0) ?>
                                        </span>
                                        <h2 class="title text-sm text-gray-300 font-semibold">Total Categories</h2>
                                    </div>
                                </div>

                                <div class="info p-6 rounded-lg shadow flex items-start">
                                    <div class="text-5xl text-white mr-4">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div class="flex flex-col items-end text-white w-full">
                                        <span class="text-4xl font-bold text-red-900">
                                            <?= number_format($summary_on_time_rate['rate'] ?? 0, 1) ?>%
                                        </span>
                                        <h2 class="title text-sm text-gray-300 font-semibold">On-Time Return Rate
                                        </h2>
                                    </div>
                                </div>

                                <div class="info p-6 rounded-lg shadow flex items-start">
                                    <div class="text-5xl text-white mr-4">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div class="flex flex-col items-end text-white w-full">
                                        <span class="text-4xl font-bold text-red-900">
                                            ₱<?= number_format($fine_collection_summary['total_collected'] ?? 0, 2) ?>
                                        </span>
                                        <h2 class="title text-sm text-gray-300 font-semibold">Total Fines Collected
                                        </h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="user-activity">
                        <h2 class="font-extrabold text-3xl pl-8 pt-4 text-red-900">USER & BORROWING ACTIVITY</h2>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-4">
                            <div class="report-chart-container">
                                <h2 class="text-xl font-bold text-red-800 mb-4">1. Monthly Borrowing Trend (Last
                                    12 Mo.)</h2>
                                <canvas id="monthlyBorrowReturnChart"></canvas>
                            </div>
                            <div class="report-chart-container">
                                <h2 class="text-xl font-bold text-red-800 mb-4">2. User Registration Trend (Last
                                    12 Mo.)</h2>
                                <canvas id="userRegTrendChart"></canvas>
                            </div>
                            <div class="report-chart-container">
                                <h2 class="text-xl font-bold text-red-800 mb-4">3. Top 5 Most Active Borrowers
                                </h2>
                                <canvas id="topBorrowersChart"></canvas>
                            </div>
                            <div class="report-chart-container">
                                <h2 class="text-xl font-bold text-red-800 mb-4">4. Borrowing Activity by
                                    Department</h2>
                                <canvas id="borrowByDeptChart"></canvas>
                            </div>
                            <div class="report-chart-container lg:col-span-2">
                                <h2 class="text-xl font-bold text-red-800 mb-4">5. Borrower Type Breakdown</h2>
                                <canvas id="borrowerTypeChart" class="max-h-80 mx-auto"></canvas>
                            </div>
                        </div>
                    </div>

                    <div id="collection-inventory">
                        <h2 class="font-extrabold text-3xl pl-8 pt-4 text-red-900">COLLECTION & INVENTORY REPORTS</h2>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-4">
                            <div class="report-chart-container">
                                <h2 class="text-xl font-bold text-red-800 mb-4">1. Top 5 Most Borrowed Books
                                </h2>
                                <canvas id="topBooksChart"></canvas>
                            </div>
                            <div class="report-chart-container">
                                <h2 class="text-xl font-bold text-red-800 mb-4">2. Top 5 Popular Categories (By Borrow)
                                </h2>
                                <canvas id="borrowTrendCategoryChart"></canvas>
                            </div>
                            <div class="report-chart-container">
                                <h2 class="text-xl font-bold text-red-800 mb-4">3. Book Status Overview</h2>
                                <canvas id="bookStatusChart" class="max-h-80 mx-auto"></canvas>
                            </div>
                            <div class="report-chart-container">
                                <h2 class="text-xl font-bold text-red-800 mb-4">4. Books per Category (Inventory)</h2>
                                <canvas id="booksPerCategoryChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div id="fines-compliance">
                        <h2 class="font-extrabold text-3xl pl-8 pt-4 text-red-900">FINES & COMPLIANCE REPORTS</h2>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-4">
                            <div class="report-chart-container">
                                <h2 class="text-xl font-bold text-red-800 mb-4">1. Fine Collection Over Time
                                    (Last 12 Mo.)</h2>
                                <canvas id="monthlyFineTrendChart"></canvas>
                            </div>
                            <div class="report-chart-container">
                                <h2 class="text-xl font-bold text-red-800 mb-4">2. Late Returns Trend (Last
                                    12 Mo.)</h2>
                                <canvas id="lateReturnsChart"></canvas>
                            </div>
                            <div class="report-chart-container">
                                <h2 class="text-xl font-bold text-red-800 mb-4">3. Top 5 Users with Unpaid Fines
                                </h2>
                                <canvas id="topUnpaidFinesChart"></canvas>
                            </div>
                            <div class="report-chart-container">
                                <h2 class="text-xl font-bold text-red-800 mb-4">4. Fine Collection Summary</h2>
                                <canvas id="fineSummaryChart" class="max-h-80 mx-auto"></canvas>
                            </div>
                            <div class="report-chart-container lg:col-span-2">
                                <h2 class="text-xl font-bold text-red-800 mb-4">5. Overdue Books Summary
                                </h2>
                                <div class="overflow-x-auto">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Book Title</th>
                                                <th>Borrower</th>
                                                <th>Due Date</th>
                                                <th>Days Overdue</th>
                                                <th>Fine (₱)</th>
                                                <th>Fine Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($overdue_books_summary)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center py-4">No overdue books
                                                        found.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($overdue_books_summary as $item): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($item['book_title']) ?></td>
                                                        <td><?= htmlspecialchars($item['fName'] . ' ' . $item['lName']) ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($item['expected_return_date']) ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($item['days_overdue']) ?></td>
                                                        <td><?= number_format($item['fine_amount'], 2) ?></td>
                                                        <td><?= $item['fine_status'] ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // --- Chart Colors ---
            const chartColors = {
                red: '#931C19',
                redLight: '#BD322F',
                redDark: '#610101',
                gray: '#414141ff',
                lightgray: '#10B981',
                darkgray: '#3B82F6',
            };
            const colorPalette = [
                chartColors.red, chartColors.redLight, chartColors.darkgray,
                chartColors.lightgray, chartColors.redDark, chartColors.gray
            ];

            // --- Helper Functions ---
            // ✅ Generate the last 12 months (e.g., Dec 2024 → Nov 2025)
            function getLast12Months() {
                const months = [];
                const now = new Date();
                for (let i = 11; i >= 0; i--) {
                    const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
                    months.push(date.toLocaleString('default', { month: 'long' }));
                }
                return months;
            }

            // ✅ Align data to months (fill missing with 0)
            function alignMonthlyData(rawData, valueKey) {
                const allMonths = getLast12Months();
                const lookup = {};
                rawData.forEach(d => lookup[d.month] = d[valueKey]);
                return allMonths.map(month => lookup[month] || 0);
            }

            // --- COMMON MONTH LIST FOR ALL MONTHLY CHARTS ---
            const months = getLast12Months();

            // === I. Borrowing Reports (Now User & Borrowing) ===

            // 1. Monthly Borrowing Trend
            const monthlyBorrowData = <?= json_encode($monthly_borrow_return_trend) ?>;
            const borrowLookup = {}, returnLookup = {};
            monthlyBorrowData.forEach(d => {
                borrowLookup[d.month] = d.total_borrows;
                returnLookup[d.month] = d.total_returns;
            });
            new Chart(document.getElementById('monthlyBorrowReturnChart'), {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [
                        {
                            label: 'Books Borrowed',
                            data: months.map(m => borrowLookup[m] || 0),
                            borderColor: chartColors.red,
                            backgroundColor: chartColors.red,
                            tension: 0.1
                        },
                        {
                            label: 'Books Returned',
                            data: months.map(m => returnLookup[m] || 0),
                            borderColor: chartColors.gray,
                            backgroundColor: chartColors.gray,
                            tension: 0.1
                        }
                    ]
                },
                options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
            });

            // 2. Borrowing Trend by Category
            const simpleCategoryData = <?= json_encode($top_5_categories) ?>;
            new Chart(document.getElementById('borrowTrendCategoryChart'), {
                type: 'bar',
                data: {
                    labels: simpleCategoryData.map(d => d.category_name),
                    datasets: [{
                        label: 'Total Borrows',
                        data: simpleCategoryData.map(d => d.borrow_count),
                        backgroundColor: colorPalette,
                    }]
                },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { ticks: { precision: 0 } } } }
            });

            // 3. Top 5 Most Borrowed Books
            const topBooksData = <?= json_encode($top_5_books) ?>;
            new Chart(document.getElementById('topBooksChart'), {
                type: 'bar',
                data: {
                    labels: topBooksData.map(d => d.book_title).reverse(),
                    datasets: [{
                        label: 'Borrows',
                        data: topBooksData.map(d => d.borrow_count).reverse(),
                        backgroundColor: chartColors.red,
                    }]
                },
                options: { indexAxis: 'y', responsive: true, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { display: false } } }
            });

            // 4. Borrowing Activity by Department
            const borrowByDeptData = <?= json_encode($borrowing_by_department) ?>;
            new Chart(document.getElementById('borrowByDeptChart'), {
                type: 'pie',
                data: {
                    labels: borrowByDeptData.map(d => d.college_department),
                    datasets: [{
                        data: borrowByDeptData.map(d => d.total_borrows),
                        backgroundColor: colorPalette,
                    }]
                },
                options: { responsive: true }
            });

            // === II. Fine and Payment Reports (Now Fines & Compliance) ===

            // 1. Fine Collection Over Time
            const monthlyFineData = <?= json_encode($monthly_fine_collection_trend) ?>;
            const fineCollectedMap = {}, fineUncollectedMap = {};
            monthlyFineData.forEach(d => {
                fineCollectedMap[d.month] = d.total_collected;
                fineUncollectedMap[d.month] = d.total_uncollected;
            });
            new Chart(document.getElementById('monthlyFineTrendChart'), {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [
                        {
                            label: 'Collected (₱)',
                            data: months.map(m => fineCollectedMap[m] || 0),
                            backgroundColor: chartColors.red,
                        },
                        {
                            label: 'Uncollected (₱)',
                            data: months.map(m => fineUncollectedMap[m] || 0),
                            backgroundColor: chartColors.redLight,
                        }
                    ]
                },
                options: { responsive: true, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } } }
            });

            // 2. Top 5 Users with Unpaid Fines
            const topUnpaidData = <?= json_encode($top_5_unpaid_fines) ?>;
            new Chart(document.getElementById('topUnpaidFinesChart'), {
                type: 'bar',
                data: {
                    labels: topUnpaidData.map(d => d.fName + ' ' + d.lName).reverse(),
                    datasets: [{
                        label: 'Unpaid Fines (₱)',
                        data: topUnpaidData.map(d => d.total_unpaid).reverse(),
                        backgroundColor: chartColors.red,
                    }]
                },
                options: { indexAxis: 'y', responsive: true, scales: { x: { beginAtZero: true } }, plugins: { legend: { display: false } } }
            });

            // 3. Fine Collection Summary
            const fineSummaryData = <?= json_encode($fine_collection_summary) ?>;
            new Chart(document.getElementById('fineSummaryChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Collected (₱)', 'Outstanding (₱)'],
                    datasets: [{
                        data: [fineSummaryData.total_collected, fineSummaryData.total_outstanding],
                        backgroundColor: [chartColors.redLight, chartColors.red],
                    }]
                },
                options: { responsive: true }
            });

            // === III. User Reports (Now User & Borrowing) ===

            // 1. Top 5 Most Active Borrowers
            const topBorrowersData = <?= json_encode($top_5_borrowers) ?>;
            new Chart(document.getElementById('topBorrowersChart'), {
                type: 'bar',
                data: {
                    labels: topBorrowersData.map(d => d.fName + ' ' + d.lName).reverse(),
                    datasets: [{
                        label: 'Borrows',
                        data: topBorrowersData.map(d => d.borrow_count).reverse(),
                        backgroundColor: chartColors.red,
                    }]
                },
                options: { indexAxis: 'y', responsive: true, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { display: false } } }
            });

            // 2. User Registration Trend (12 months)
            const userRegData = <?= json_encode($monthly_user_reg_trend) ?>;
            const userRegMap = {};
            userRegData.forEach(d => userRegMap[d.month] = d.new_users);
            new Chart(document.getElementById('userRegTrendChart'), {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'New Users',
                        data: months.map(m => userRegMap[m] || 0),
                        borderColor: chartColors.red,
                        backgroundColor: chartColors.gray,
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                    plugins: { legend: { display: false } }
                }
            });

            // 3. Borrower Type Breakdown
            const borrowerTypeData = <?= json_encode($borrower_type_breakdown) ?>;
            new Chart(document.getElementById('borrowerTypeChart'), {
                type: 'pie',
                data: {
                    labels: borrowerTypeData.map(d => d.type_name),
                    datasets: [{
                        data: borrowerTypeData.map(d => d.borrow_count),
                        backgroundColor: colorPalette,
                    }]
                },
                options: { responsive: true }
            });

            // === IV. Book Inventory Reports (Now Collection & Inventory) ===

            // 1. Book Status Overview
            const bookStatusData = <?= json_encode($book_status_overview) ?>;
            new Chart(document.getElementById('bookStatusChart'), {
                type: 'doughnut',
                data: {
                    labels: bookStatusData.map(d => d.status),
                    datasets: [{
                        data: bookStatusData.map(d => d.count),
                        backgroundColor: [chartColors.red, chartColors.redDark, chartColors.gray],
                    }]
                },
                options: { responsive: true }
            });

            // 2. Books per Category
            const booksPerCatData = <?= json_encode($books_per_category) ?>;
            new Chart(document.getElementById('booksPerCategoryChart'), {
                type: 'bar',
                data: {
                    labels: booksPerCatData.map(d => d.category_name),
                    datasets: [{
                        label: 'Total Copies',
                        data: booksPerCatData.map(d => d.total_copies),
                        backgroundColor: chartColors.redLight,
                    }]
                },
                options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { display: false } } }
            });

            // 3. Average Borrow Duration
            const avgDurationData = <?= json_encode($avg_borrow_duration) ?>;
            new Chart(document.getElementById('avgBorrowDurationChart'), {
                type: 'bar',
                data: {
                    labels: avgDurationData.map(d => d.category_name).reverse(),
                    datasets: [{
                        label: 'Average Days Borrowed',
                        data: avgDurationData.map(d => d.avg_duration_days).reverse(),
                        backgroundColor: chartColors.red,
                    }]
                },
                options: { indexAxis: 'y', responsive: true, scales: { x: { beginAtZero: true } }, plugins: { legend: { display: false } } }
            });

            // === V. Overdue and Late Return Reports (Now Fines & Compliance) ===

            // 1. Late Returns Trend
            const lateReturnsData = <?= json_encode($late_returns_trend) ?>;
            const lateReturnMap = {};
            lateReturnsData.forEach(d => lateReturnMap[d.month] = d.late_returns);
            new Chart(document.getElementById('lateReturnsChart'), {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Late Returns',
                        data: months.map(m => lateReturnMap[m] || 0),
                        borderColor: chartColors.redLight,
                        backgroundColor: chartColors.redLight,
                        tension: 0.1,
                        fill: false
                    }]
                },
                options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { display: false } } }
            });

        });
    </script>

</body>

</html>