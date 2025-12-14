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

// --- 1. DATA FOR CARDS ---
$total_book_copies = $bookObj->countTotalBookCopies();
$total_book = $bookObj->countTotalDistinctBooks();
$total_borrowed_books = $borrowObj->countTotalBorrowedBooks();
$total_borrowers = $reportsObj->countTotalActiveBorrowers();

// --- 2. FETCH OVERVIEW SUMMARY ---
$fine_collection_summary = $reportsObj->getFineCollectionSummary();
$summary_avg_duration = $reportsObj->getSummaryAverageBorrowDuration();
$summary_top_author = $reportsObj->getSummaryMostBorrowedAuthor();
$top_5_categories = $reportsObj->getTopPopularCategories(5);

// --- 3. NEW OVERVIEW CARDS ---
$summary_total_borrows_ever = $reportsObj->getSummaryTotalBorrows();
$summary_on_time_rate = $reportsObj->getOnTimeReturnRate();
$summary_total_categories = $reportsObj->getSummaryTotalCategories();

$avg_borrowing_per_user = ($total_borrowers > 0) ? ($summary_total_borrows_ever / $total_borrowers) : 0;

// --- 4. DATA FOR CHARTS ---
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
$avg_borrow_duration = $reportsObj->getAverageBorrowDurationByCategory();
$overdue_books_summary = $reportsObj->getOverdueBooksSummary();
$lost_books_summary = $reportsObj->getLostBooksDetails();
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
    <script src="../../../public/assets/js/chart.js"></script>
    <link rel="stylesheet" href="../../../public/assets/fontawesome-free-7.1.0-web/css/all.min.css">
    <style>
        .modal { display: none; }
        .modal.open { display: block; }
    </style>
</head>

<body>
    <div class="w-full h-screen flex flex-col">
        <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>

        <main class="overflow-y-auto relative">
            <div class="tabs bg-white sticky top-0 z-50 flex text-center shadow-lg border-b border-gray-200 mb-6"
                id="report-tabs">
                <a href="#overview">Overview</a>
                <a href="#user-activity">User & Borrowing</a>
                <a href="#collection-inventory">Collection & Inventory</a>
                <a href="#fines-compliance">Fines & Compliance</a>
            </div>

            <div class="px-8 manage_users h-full">

                <div class="title flex w-full items-center justify-between mb-4">
                    <h1 class="text-red-800 font-bold text-4xl">LIBRARY REPORTS</h1>
                    <button 
                        class="inline-block rounded-xl text-white bg-red-900 p-4 font-bold transition-transform duration-100 transform hover:scale-105 cursor-pointer open-modal-btn"
                        data-target="printReportModal">
                        <i class="fas fa-print mr-2"></i> Print Reports & Lists
                    </button>
                </div>

                <div id="overview">
                     <h2 class="font-bold text-3xl pl-8 pt-16 text-red-900">EXECUTIVE SUMMARY</h2>
                    <div class="report-chart-container mt-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                            <div class="info p-6 rounded-lg shadow flex items-start">
                                <div class="text-5xl text-white mr-4"><i class="fas fa-book"></i></div>
                                <div class="flex flex-col items-end text-white w-full">
                                    <span class="text-2xl font-semibold"><?= htmlspecialchars($total_book) ?></span>
                                    <h2 class="title text-sm text-gray-300">Total Distinct Books</h2>
                                </div>
                            </div>
                            <div class="info p-6 rounded-lg shadow flex items-start">
                                <div class="text-5xl text-white mr-4"><i class="fas fa-book-open"></i></div>
                                <div class="flex flex-col items-end text-white w-full">
                                    <span class="text-2xl font-semibold"><?= htmlspecialchars($total_book_copies) ?></span>
                                    <h2 class="title text-sm text-gray-300">Total Available Copies</h2>
                                </div>
                            </div>
                            <div class="info p-6 rounded-lg shadow flex items-start">
                                <div class="text-5xl text-white mr-4"><i class="fas fa-book-reader"></i></div>
                                <div class="flex flex-col items-end text-white w-full">
                                    <span class="text-4xl font-bold text-red-900"><?= htmlspecialchars($total_borrowed_books ?? 0) ?></span>
                                    <h2 class="title text-sm text-gray-300 font-semibold">Total Borrowed Books</h2>
                                </div>
                            </div>
                            <div class="info p-6 rounded-lg shadow flex items-start">
                                <div class="text-5xl text-white mr-4"><i class="fas fa-users"></i></div>
                                <div class="flex flex-col items-end text-white w-full">
                                    <span class="text-4xl font-bold text-red-900"><?= htmlspecialchars($total_borrowers ?? 0) ?></span>
                                    <h2 class="title text-sm text-gray-300 font-semibold">Total Borrowers</h2>
                                </div>
                            </div>
                            <div class="info p-6 rounded-lg shadow flex items-start">
                                <div class="text-5xl text-white mr-4"><i class="fas fa-exchange-alt"></i></div>
                                <div class="flex flex-col items-end text-white w-full">
                                    <span class="text-4xl font-bold text-red-900"><?= number_format($avg_borrowing_per_user, 1) ?></span>
                                    <h2 class="title text-sm text-gray-300 font-semibold">Avg Borrowing/User</h2>
                                </div>
                            </div>
                            <div class="info p-6 rounded-lg shadow flex items-start">
                                <div class="text-5xl text-white mr-4"><i class="fas fa-bookmark"></i></div>
                                <div class="flex flex-col items-end text-white w-full">
                                    <span class="text-4xl font-bold text-red-900"><?= htmlspecialchars($summary_total_categories['total_categories'] ?? 0) ?></span>
                                    <h2 class="title text-sm text-gray-300 font-semibold">Total Categories</h2>
                                </div>
                            </div>
                            <div class="info p-6 rounded-lg shadow flex items-start">
                                <div class="text-5xl text-white mr-4"><i class="fas fa-calendar-check"></i></div>
                                <div class="flex flex-col items-end text-white w-full">
                                    <span class="text-4xl font-bold text-red-900"><?= number_format($summary_on_time_rate['rate'] ?? 0, 1) ?>%</span>
                                    <h2 class="title text-sm text-gray-300 font-semibold">On-Time Return Rate</h2>
                                </div>
                            </div>
                            <div class="info p-6 rounded-lg shadow flex items-start">
                                <div class="text-5xl text-white mr-4"><i class="fas fa-money-bill-wave"></i></div>
                                <div class="flex flex-col items-end text-white w-full">
                                    <span class="text-4xl font-bold text-red-900">₱<?= number_format($fine_collection_summary['total_collected'] ?? 0, 2) ?></span>
                                    <h2 class="title text-sm text-gray-300 font-semibold">Total Fines Collected</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="user-activity">
                    <h2 class="font-bold text-3xl pl-8 pt-16 text-red-900">USER & BORROWING ACTIVITY</h2>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-4">
                        <div class="report-chart-container">
                            <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">1. Monthly Borrowing Trend</h2>
                            <div class="flex justify-center items-center w-full">
                                <canvas id="monthlyBorrowReturnChart"></canvas>
                            </div>
                        </div>
                        <div class="report-chart-container">
                            <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">2. User Registration Trend</h2>
                            <div class="flex justify-center items-center w-full">
                                <canvas id="userRegTrendChart"></canvas>
                            </div>
                        </div>
                        <div class="report-chart-container">
                            <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">3. Top 5 Most Active Borrowers</h2>
                            <div class="flex justify-center items-center w-full">
                                <canvas id="topBorrowersChart"></canvas>
                            </div>
                        </div>
                        <div class="report-chart-container">
                            <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">4. Borrower Type Breakdown</h2>
                            <div class="flex justify-center items-center w-full">
                                <canvas id="borrowerTypeChart" class="max-h-80 mx-auto"></canvas>
                            </div>
                        </div>
                        <div class="report-chart-container lg:col-span-2">
                             <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">5. Borrowing Activity by Department</h2>
                             <div class="overflow-x-auto w-full">
                                <table class="w-full text-sm text-center">
                                    <thead>
                                        <tr class="bg-gray-100 border-b">
                                            <th class="py-2 px-4 font-semibold text-gray-700">Department Name</th>
                                            <th class="py-2 px-4 font-semibold text-gray-700">Total Borrowed Books</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($borrowing_by_department)): ?>
                                            <tr><td colspan="2" class="py-4 text-gray-500">No data available.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($borrowing_by_department as $dept): ?>
                                            <tr class="border-b hover:bg-gray-50">
                                                <td class="py-2 px-4"><?= htmlspecialchars($dept['department']) ?></td>
                                                <td class="py-2 px-4 font-bold text-red-900"><?= htmlspecialchars($dept['total_borrowed']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                             </div>
                        </div>
                    </div>
                </div>

                <div id="collection-inventory">
                    <h2 class="font-bold text-3xl pl-8 pt-16 text-red-900">COLLECTION & INVENTORY REPORTS</h2>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-4">
                        <div class="report-chart-container">
                            <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">1. Top 5 Most Borrowed Books</h2>
                            <div class="flex justify-center items-center w-full">
                                <canvas id="topBooksChart"></canvas>
                            </div>
                        </div>
                        <div class="report-chart-container">
                            <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">2. Top 5 Popular Categories</h2>
                            <div class="flex justify-center items-center w-full">
                                <canvas id="borrowTrendCategoryChart"></canvas>
                            </div>
                        </div>
                        <div class="report-chart-container">
                            <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">3. Book Status Overview</h2>
                            <div class="flex justify-center items-center w-full">
                                <canvas id="bookStatusChart" class="max-h-80 mx-auto"></canvas>
                            </div>
                        </div>
                        <div class="report-chart-container">
                            <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">4. Books per Category</h2>
                            <div class="flex justify-center items-center w-full">
                                <canvas id="booksPerCategoryChart"></canvas>
                            </div>
                        </div>
                        <div class="report-chart-container lg:col-span-2">
                            <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">5. Lost Books Summary</h2>
                             <div class="overflow-x-auto w-full">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr>
                                            <th>Book Title</th>
                                            <th>Borrower</th>
                                            <th>Date Borrowed</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($lost_books_summary)): ?>
                                            <tr><td colspan="4" class="text-center py-4 text-gray-500">No lost books found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($lost_books_summary as $item): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['book_title']) ?></td>
                                                    <td><?= htmlspecialchars($item['fName'] . ' ' . $item['lName']) ?></td>
                                                    <td><?= htmlspecialchars($item['borrow_date']) ?></td>
                                                    <td class="text-center font-semibold text-red-600">
                                                        <?= $item['borrow_status'] ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="fines-compliance">
                    <h2 class="font-bold text-3xl pl-8 pt-16 text-red-900">FINES & COMPLIANCE REPORTS</h2>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-4">
                        <div class="report-chart-container">
                            <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">1. Fine Collection Over Time</h2>
                            <div class="flex justify-center items-center w-full">
                                <canvas id="monthlyFineTrendChart"></canvas>
                            </div>
                        </div>
                        <div class="report-chart-container">
                            <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">2. Late Returns Trend</h2>
                            <div class="flex justify-center items-center w-full">
                                <canvas id="lateReturnsChart"></canvas>
                            </div>
                        </div>
                        <div class="report-chart-container">
                            <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">3. Top 5 Users with Unpaid Fines</h2>
                            <div class="flex justify-center items-center w-full">
                                <canvas id="topUnpaidFinesChart"></canvas>
                            </div>
                        </div>
                        <div class="report-chart-container">
                            <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">4. Fine Collection Summary</h2>
                            <div class="flex justify-center items-center w-full">
                                <canvas id="fineSummaryChart" class="max-h-80 mx-auto"></canvas>
                            </div>
                        </div>
                        <div class="report-chart-container lg:col-span-2 mb-16">
                            <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">5. Overdue Books Summary</h2>
                            <div class="overflow-x-auto w-full">
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
                                            <tr><td colspan="6" class="text-center py-4 text-gray-500">No overdue books found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($overdue_books_summary as $item): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['book_title']) ?></td>
                                                    <td><?= htmlspecialchars($item['fName'] . ' ' . $item['lName']) ?></td>
                                                    <td><?= htmlspecialchars($item['expected_return_date']) ?></td>
                                                    <td><?= htmlspecialchars($item['days_overdue']) ?></td>
                                                    <td class="font-bold"><?= number_format($item['fine_amount'], 2) ?></td>
                                                    <td class="text-center font-semibold <?= ($item['fine_status'] === 'Paid') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                                        <?= $item['fine_status'] ?>
                                                    </td>
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
        </main>
    </div>

    <div id="printReportModal" class="modal">
        <div class="modal-content max-w-lg">
            <span class="close close-times close-modal cursor-pointer float-right text-3xl" data-modal="printReportModal">&times;</span>

            <h2 class="text-2xl font-bold mb-4 text-red-800">
                <i class="fas fa-print mr-2"></i> Print Library Documents
            </h2>

            <form action="print.php" method="GET" target="_blank">
                <div class="grid grid-cols-2 gap-4 text-gray-700 w-full">
                    <div class="col-span-2 border-b pb-2 mb-2">
                        <h3 class="font-semibold text-lg text-red-700">Select Document Type</h3>
                    </div>

                    <div class="col-span-2 bg-gray-100 p-4 rounded">
                        <label class="flex items-center mb-3 cursor-pointer p-2 hover:bg-gray-200 rounded transition">
                            <input type="radio" name="report_type" value="general" checked class="mr-3 transform scale-125">
                            <div>
                                <span class="font-bold block text-gray-800">General Report</span>
                                <span class="text-xs text-gray-500">Includes charts, statistics, and summaries.</span>
                            </div>
                        </label>

                        <label class="flex items-center mb-3 cursor-pointer p-2 hover:bg-gray-200 rounded transition">
                            <input type="radio" name="report_type" value="books" class="mr-3 transform scale-125">
                            <div>
                                <span class="font-bold block text-gray-800">Book Inventory List</span>
                                <span class="text-xs text-gray-500">Full table of all books and their status.</span>
                            </div>
                        </label>

                        <label class="flex items-center mb-3 cursor-pointer p-2 hover:bg-gray-200 rounded transition">
                            <input type="radio" name="report_type" value="users" class="mr-3 transform scale-125">
                            <div>
                                <span class="font-bold block text-gray-800">User Directory List</span>
                                <span class="text-xs text-gray-500">Full table of registered users.</span>
                            </div>
                        </label>

                        <label class="flex items-center cursor-pointer p-2 hover:bg-gray-200 rounded transition">
                            <input type="radio" name="report_type" value="categories" class="mr-3 transform scale-125">
                            <div>
                                <span class="font-bold block text-gray-800">Category List</span>
                                <span class="text-xs text-gray-500">List of all book categories.</span>
                            </div>
                        </label>
                    </div>

                    <div class="col-span-2 text-sm text-gray-500 text-center italic mt-2">
                        Document will open in a new tab ready for printing.
                    </div>
                </div>

                <div class="flex justify-end mt-6 gap-2 w-full">
                    <button type="button" class="w-1/2 close viewBtn bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400 close-modal" data-modal="printReportModal">
                        Close
                    </button>
                    <button type="submit" class="w-1/2 bg-red-800 text-white px-6 py-2 rounded-lg font-semibold hover:bg-red-700 shadow-md">
                        <i class="fas fa-print mr-2"></i> Print Selected
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Modal Logic
            document.querySelectorAll('.open-modal-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const targetId = this.getAttribute('data-target');
                    const modal = document.getElementById(targetId);
                    if (modal) { modal.style.display = 'block'; modal.classList.add('open'); }
                });
            });
            document.querySelectorAll('.close-modal, .close-times').forEach(element => {
                element.addEventListener('click', function () {
                    const modal = this.closest('.modal');
                    if (modal) { modal.style.display = 'none'; modal.classList.remove('open'); }
                });
            });
            window.onclick = function(event) {
                if (event.target.classList.contains('modal')) {
                    event.target.style.display = "none";
                    event.target.classList.remove("open");
                }
            }

            // Chart Logic (Same as before)
            const chartColors = { red: '#931C19', redLight: '#BD322F', redDark: '#610101', gray: '#414141ff', lightgray: '#10B981', darkgray: '#3B82F6' };
            const colorPalette = [chartColors.red, chartColors.redLight, chartColors.darkgray, chartColors.lightgray, chartColors.redDark, chartColors.gray];

            function getLast12Months() {
                const months = []; const now = new Date();
                for (let i = 11; i >= 0; i--) {
                    const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
                    months.push(date.toLocaleString('default', { month: 'long' }));
                } return months;
            }
            const months = getLast12Months();

            // 1. Monthly Borrowing
            const monthlyBorrowData = <?= json_encode($monthly_borrow_return_trend) ?>;
            const borrowLookup = {}, returnLookup = {};
            monthlyBorrowData.forEach(d => { borrowLookup[d.month] = d.total_borrows; returnLookup[d.month] = d.total_returns; });
            if (document.getElementById('monthlyBorrowReturnChart')) {
                new Chart(document.getElementById('monthlyBorrowReturnChart'), {
                    type: 'line',
                    data: {
                        labels: months,
                        datasets: [
                            { label: 'Books Borrowed', data: months.map(m => borrowLookup[m] || 0), borderColor: chartColors.red, backgroundColor: chartColors.red, tension: 0.1 },
                            { label: 'Books Returned', data: months.map(m => returnLookup[m] || 0), borderColor: chartColors.gray, backgroundColor: chartColors.gray, tension: 0.1 }
                        ]
                    }, options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
                });
            }
            // 2. User Reg Trend
            const userRegData = <?= json_encode($monthly_user_reg_trend) ?>;
            const userRegMap = {};
            userRegData.forEach(d => userRegMap[d.month] = d.new_users);
            if (document.getElementById('userRegTrendChart')) {
                new Chart(document.getElementById('userRegTrendChart'), {
                    type: 'line',
                    data: {
                        labels: months,
                        datasets: [{ label: 'New Users', data: months.map(m => userRegMap[m] || 0), borderColor: chartColors.red, backgroundColor: chartColors.gray, tension: 0.1 }]
                    }, options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { display: false } } }
                });
            }
            // 3. Top Borrowers
            const topBorrowersData = <?= json_encode($top_5_borrowers) ?>;
            if (document.getElementById('topBorrowersChart')) {
                new Chart(document.getElementById('topBorrowersChart'), {
                    type: 'bar',
                    data: {
                        labels: topBorrowersData.map(d => d.fName + ' ' + d.lName).reverse(),
                        datasets: [{ label: 'Borrows', data: topBorrowersData.map(d => d.borrow_count).reverse(), backgroundColor: chartColors.red }]
                    }, options: { indexAxis: 'y', responsive: true, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { display: false } } }
                });
            }
            // 4. Borrowing by Dept
            const borrowByDeptData = <?= json_encode($borrowing_by_department) ?>;
            if (document.getElementById('borrowByDeptChart')) {
                new Chart(document.getElementById('borrowByDeptChart'), {
                    type: 'pie',
                    data: {
                        labels: borrowByDeptData.map(d => d.department),
                        datasets: [{ data: borrowByDeptData.map(d => d.total_borrowed), backgroundColor: colorPalette }]
                    }, options: { responsive: true }
                });
            }
            // 5. Borrower Type
            const borrowerTypeData = <?= json_encode($borrower_type_breakdown) ?>;
            if (document.getElementById('borrowerTypeChart')) {
                new Chart(document.getElementById('borrowerTypeChart'), {
                    type: 'pie',
                    data: {
                        labels: borrowerTypeData.map(d => d.type_name),
                        datasets: [{ data: borrowerTypeData.map(d => d.borrow_count), backgroundColor: colorPalette }]
                    }, options: { responsive: true }
                });
            }
            // 6. Top Books
            const topBooksData = <?= json_encode($top_5_books) ?>;
            if (document.getElementById('topBooksChart')) {
                new Chart(document.getElementById('topBooksChart'), {
                    type: 'bar',
                    data: {
                        labels: topBooksData.map(d => d.book_title).reverse(),
                        datasets: [{ label: 'Borrows', data: topBooksData.map(d => d.borrow_count).reverse(), backgroundColor: chartColors.red }]
                    }, options: { indexAxis: 'y', responsive: true, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { display: false } } }
                });
            }
            // 7. Categories
            const simpleCategoryData = <?= json_encode($top_5_categories) ?>;
            if (document.getElementById('borrowTrendCategoryChart')) {
                new Chart(document.getElementById('borrowTrendCategoryChart'), {
                    type: 'bar',
                    data: {
                        labels: simpleCategoryData.map(d => d.category_name),
                        datasets: [{ label: 'Total Borrows', data: simpleCategoryData.map(d => d.borrow_count), backgroundColor: colorPalette }]
                    }, options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { ticks: { precision: 0 } } } }
                });
            }
            // 8. Book Status
            const bookStatusData = <?= json_encode($book_status_overview) ?>;
            if (document.getElementById('bookStatusChart')) {
                new Chart(document.getElementById('bookStatusChart'), {
                    type: 'doughnut',
                    data: {
                        labels: bookStatusData.map(d => d.status),
                        datasets: [{ data: bookStatusData.map(d => d.count), backgroundColor: [chartColors.red, chartColors.redDark, chartColors.gray] }]
                    }, options: { responsive: true }
                });
            }
            // 9. Books Per Category
            const booksPerCatData = <?= json_encode($books_per_category) ?>;
            if (document.getElementById('booksPerCategoryChart')) {
                new Chart(document.getElementById('booksPerCategoryChart'), {
                    type: 'bar',
                    data: {
                        labels: booksPerCatData.map(d => d.category_name),
                        datasets: [{ label: 'Total Copies', data: booksPerCatData.map(d => d.total_copies), backgroundColor: chartColors.redLight }]
                    }, options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { display: false } } }
                });
            }
            // 10. Fine Collection
            const monthlyFineData = <?= json_encode($monthly_fine_collection_trend) ?>;
            const fineCollectedMap = {}, fineUncollectedMap = {};
            monthlyFineData.forEach(d => { fineCollectedMap[d.month] = d.total_collected; fineUncollectedMap[d.month] = d.total_uncollected; });
            if (document.getElementById('monthlyFineTrendChart')) {
                new Chart(document.getElementById('monthlyFineTrendChart'), {
                    type: 'bar',
                    data: {
                        labels: months,
                        datasets: [
                            { label: 'Collected (₱)', data: months.map(m => fineCollectedMap[m] || 0), backgroundColor: chartColors.red },
                            { label: 'Uncollected (₱)', data: months.map(m => fineUncollectedMap[m] || 0), backgroundColor: chartColors.redLight }
                        ]
                    }, options: { responsive: true, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } } }
                });
            }
            // 11. Late Returns
            const lateReturnsData = <?= json_encode($late_returns_trend) ?>;
            const lateReturnMap = {};
            lateReturnsData.forEach(d => lateReturnMap[d.month] = d.late_returns);
            if (document.getElementById('lateReturnsChart')) {
                new Chart(document.getElementById('lateReturnsChart'), {
                    type: 'line',
                    data: {
                        labels: months,
                        datasets: [{ label: 'Late Returns', data: months.map(m => lateReturnMap[m] || 0), borderColor: chartColors.redLight, backgroundColor: chartColors.redLight, tension: 0.1, fill: false }]
                    }, options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { display: false } } }
                });
            }
            // 12. Top Unpaid
            const topUnpaidData = <?= json_encode($top_5_unpaid_fines) ?>;
            if (document.getElementById('topUnpaidFinesChart')) {
                new Chart(document.getElementById('topUnpaidFinesChart'), {
                    type: 'bar',
                    data: {
                        labels: topUnpaidData.map(d => d.fName + ' ' + d.lName).reverse(),
                        datasets: [{ label: 'Unpaid Fines (₱)', data: topUnpaidData.map(d => d.total_unpaid).reverse(), backgroundColor: chartColors.red }]
                    }, options: { indexAxis: 'y', responsive: true, scales: { x: { beginAtZero: true } }, plugins: { legend: { display: false } } }
                });
            }
            // 13. Fine Summary
            const fineSummaryData = <?= json_encode($fine_collection_summary) ?>;
            if (document.getElementById('fineSummaryChart')) {
                new Chart(document.getElementById('fineSummaryChart'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Collected (₱)', 'Outstanding (₱)'],
                        datasets: [{ data: [fineSummaryData.total_collected, fineSummaryData.total_outstanding], backgroundColor: [chartColors.redLight, chartColors.red] }]
                    }, options: { responsive: true }
                });
            }
        });
    </script>
</body>
</html>