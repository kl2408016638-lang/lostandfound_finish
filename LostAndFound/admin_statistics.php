<?php
session_start();
include 'db_connect.php';


if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['name'];

// Default period filter
$period = isset($_GET['period']) ? $_GET['period'] : 'all';
$valid_periods = ['today', 'week', 'month', 'year', 'all'];
if(!in_array($period, $valid_periods)) {
    $period = 'all';
}

// Build date condition based on period
$date_condition = "";
switch($period) {
    case 'today':
        $date_condition = "WHERE DATE(created_at) = CURDATE()";
        break;
    case 'week':
        $date_condition = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $date_condition = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case 'year':
        $date_condition = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
        break;
    case 'all':
    default:
        $date_condition = "";
        break;
}

// Function to get count with period filter
function getCount($connect, $table, $condition = "", $period_condition = "") {
    $sql = "SELECT COUNT(*) as count FROM $table";
    if(!empty($period_condition)) {
        $sql .= " " . $period_condition;
    }
    if(!empty($condition)) {
        $sql .= strpos($period_condition, 'WHERE') === false ? " WHERE $condition" : " AND $condition";
    }
    $result = mysqli_query($connect, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['count'] ?? 0;
}

// Calculate statistics
$total_cases = getCount($connect, 'items', '', $date_condition);
$today_cases = getCount($connect, 'items', "DATE(created_at) = CURDATE()");
$pending_cases = getCount($connect, 'items', "status = 'pending'", $date_condition);
$claimed_cases = getCount($connect, 'items', "status = 'claimed'", $date_condition);

// Calculate Claimed Rate (items claimed vs total excluding closed items)
$total_excluding_closed = $total_cases - getCount($connect, 'items', "status = 'closed'", $date_condition);
$claimed_rate = $total_excluding_closed > 0 ? round(($claimed_cases / $total_excluding_closed) * 100, 1) : 0;

// Get status distribution
$status_sql = "SELECT status, COUNT(*) as count 
               FROM items 
               $date_condition 
               GROUP BY status 
               ORDER BY FIELD(status, 'pending', 'approved', 'matched', 'claimed')";
$status_result = mysqli_query($connect, $status_sql);
$status_data = [];
$total_status = 0;
while($row = mysqli_fetch_assoc($status_result)) {
    $status_data[$row['status']] = $row['count'];
    $total_status += $row['count'];
}

// Calculate percentages
foreach($status_data as $status => $count) {
    $status_data[$status . '_percent'] = $total_status > 0 ? round(($count / $total_status) * 100, 1) : 0;
}

// Get top 10 common items
$top_items_sql = "SELECT type_item, COUNT(*) as frequency 
                  FROM items 
                  $date_condition 
                  GROUP BY type_item 
                  ORDER BY frequency DESC 
                  LIMIT 10";
$top_items_result = mysqli_query($connect, $top_items_sql);
$top_items = [];
while($row = mysqli_fetch_assoc($top_items_result)) {
    $top_items[] = $row;
}

// Get cases trend for last 7 days
$trend_sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
              FROM items 
              WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
              GROUP BY DATE(created_at) 
              ORDER BY date";
$trend_result = mysqli_query($connect, $trend_sql);
$trend_data = [];
$trend_labels = [];
$trend_counts = [];
while($row = mysqli_fetch_assoc($trend_result)) {
    $trend_data[] = $row;
    $trend_labels[] = date('D', strtotime($row['date']));
    $trend_counts[] = $row['count'];
}

// Include admin sidebar
if(file_exists('admin_sidebar_nav.php')) {
    include 'admin_sidebar_nav.php';
} else {
    echo '<div style="padding:20px;background:#f8d7da;color:#721c24;">Admin sidebar not found.</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Statistics Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .main-content {
            margin-left: 250px;
            transition: all 0.3s;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
        
        .dashboard-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .page-title {
            color: #2c3e50;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .page-title i {
            color: #3498db;
        }
        
        .period-selector {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .period-btn {
            padding: 8px 15px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .period-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .period-btn:hover:not(.active) {
            background: #e9ecef;
        }
        
        .refresh-btn {
            padding: 8px 15px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .refresh-btn:hover {
            background: #219653;
        }
        
        .refresh-btn.loading {
            background: #95a5a6;
            cursor: not-allowed;
        }
        
        /* Quick Stats */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 1024px) {
            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .quick-stats {
                grid-template-columns: 1fr;
            }
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .stat-card.total { border-left-color: #3498db; }
        .stat-card.today { border-left-color: #2ecc71; }
        .stat-card.claimed { border-left-color: #27ae60; }
        .stat-card.pending { border-left-color: #f39c12; }
        
        .stat-icon {
            font-size: 24px;
            margin-bottom: 10px;
            color: #7f8c8d;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .stat-trend {
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 5px;
        }
        
        .trend-up { color: #27ae60; }
        .trend-down { color: #e74c3c; }
        
        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 1024px) {
            .charts-section {
                grid-template-columns: 1fr;
            }
        }
        
        .chart-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .chart-title {
            color: #2c3e50;
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chart-wrapper {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        /* Tables Section */
        .tables-section {
            display: block;
            margin-bottom: 30px;
        }
        
        .table-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            width: 100%;
            margin-bottom: 25px;
        }
        
        .table-title {
            color: #2c3e50;
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 700;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid #eee;
            font-size: 14px;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        
        tr:hover {
            background: #f9f9f9;
        }
        
        /* Status badges */
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #cce5ff; color: #004085; }
        .status-matched { background: #d1ecf1; color: #0c5460; }
        .status-claimed { background: #d4edda; color: #155724; }
        
        
        /* Progress bar */
        .progress-container {
            margin-top: 10px;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 14px;
            color: #34495e;
        }
        
        .progress-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        
        /* Empty states */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #7f8c8d;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #bdc3c7;
        }
        
        /* Export section */
        .export-section {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .export-btn {
            padding: 8px 15px;
            background: #7f8c8d;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .export-btn:hover {
            background: #6c757d;
        }
        
        .export-btn.print { background: #3498db; }
        .export-btn.print:hover { background: #2980b9; }
        
        .last-updated {
            font-size: 12px;
            color: #95a5a6;
            text-align: right;
            margin-top: 10px;
        }
        
        /* Loading animation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .fa-spin {
            animation: spin 1s linear infinite;
        }
        
        /* Print styles */
        @media print {
            .main-content {
                margin-left: 0;
            }
            
            .refresh-btn,
            .export-btn:not(.print),
            .period-btn {
                display: none;
            }
            
            .stat-card:hover {
                transform: none;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-content">
            <!-- Dashboard Card -->
            <div class="dashboard-card">
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-chart-bar"></i>
                        Statistics Dashboard
                    </h1>
                    
                    <div class="period-selector">
                        <span style="color: #34495e; font-weight: 600; font-size: 14px;">Period:</span>
                        <?php 
                        $periods = [
                            'today' => 'Today',
                            'week' => 'Last 7 Days',
                            'month' => 'Last 30 Days',
                            'year' => 'This Year',
                            'all' => 'All Time'
                        ];
                        
                        foreach($periods as $key => $label): 
                        ?>
                            <a href="?period=<?php echo $key; ?>" 
                               class="period-btn <?php echo $period == $key ? 'active' : ''; ?>">
                                <?php echo $label; ?>
                            </a>
                        <?php endforeach; ?>
                        
                        <button id="refreshBtn" class="refresh-btn">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="quick-stats">
                    <div class="stat-card total">
                        <div class="stat-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($total_cases); ?></div>
                        <div class="stat-label">Total Cases</div>
                        <div class="stat-trend">
                            <i class="fas fa-chart-line"></i>
                            <span>All time data</span>
                        </div>
                    </div>
                    
                    <div class="stat-card today">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($today_cases); ?></div>
                        <div class="stat-label">Today's Cases</div>
                        <div class="stat-trend">
                            <?php if($today_cases > 0): ?>
                                <i class="fas fa-arrow-up trend-up"></i>
                                <span class="trend-up">Active day</span>
                            <?php else: ?>
                                <i class="fas fa-minus"></i>
                                <span>No cases today</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="stat-card claimed">
                        <div class="stat-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <div class="stat-number"><?php echo $claimed_rate; ?>%</div>
                        <div class="stat-label">Claimed Rate</div>
                        <div class="stat-trend">
                            <?php if($claimed_rate >= 50): ?>
                                <i class="fas fa-arrow-up trend-up"></i>
                                <span class="trend-up">
                                    <?php 
                                    if($claimed_rate >= 80) echo "Excellent return rate";
                                    elseif($claimed_rate >= 60) echo "Good return rate";
                                    else echo "Average return rate";
                                    ?>
                                </span>
                            <?php elseif($claimed_rate > 0): ?>
                                <i class="fas fa-arrow-down trend-down"></i>
                                <span class="trend-down">Needs improvement</span>
                            <?php else: ?>
                                <i class="fas fa-minus"></i>
                                <span>No claimed items</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="stat-card pending">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($pending_cases); ?></div>
                        <div class="stat-label">Pending Cases</div>
                        <div class="stat-trend">
                            <?php if($pending_cases > 0): ?>
                                <i class="fas fa-exclamation-circle" style="color: #f39c12;"></i>
                                <span>Requires attention</span>
                            <?php else: ?>
                                <i class="fas fa-check-circle trend-up"></i>
                                <span class="trend-up">All clear</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Section -->
                <div class="charts-section">
                    <!-- Status Distribution Chart -->
                    <div class="chart-container">
                        <div class="chart-title">
                            <span>Status Distribution</span>
                            <span style="font-size: 14px; color: #7f8c8d;">
                                Total: <?php echo number_format($total_status); ?> cases
                            </span>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="statusChart"></canvas>
                        </div>
                        
                        <!-- Status Legend -->
                        <div style="margin-top: 20px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: center;">
                            <?php 
                            $status_colors = [
                                'pending' => '#ffc107',
                                'approved' => '#007bff', 
                                'matched' => '#17a2b8',
                                'claimed' => '#28a745'
                                
                            ];
                            
                            foreach($status_colors as $status => $color): 
                                $count = $status_data[$status] ?? 0;
                                $percent = $status_data[$status . '_percent'] ?? 0;
                                if($count > 0):
                            ?>
                                <div style="display: flex; align-items: center; gap: 5px; font-size: 12px;">
                                    <span style="width: 12px; height: 12px; border-radius: 50%; background: <?php echo $color; ?>;"></span>
                                    <span style="color: #34495e;"><?php echo ucfirst($status); ?>:</span>
                                    <span style="font-weight: 600;"><?php echo $count; ?> (<?php echo $percent; ?>%)</span>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                    
                    <!-- Cases Trend Chart -->
                    <div class="chart-container">
                        <div class="chart-title">
                            <span>Cases Trend (Last 7 Days)</span>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="trendChart"></canvas>
                        </div>
                        
                        <?php if(empty($trend_data)): ?>
                            <div class="empty-state">
                                <i class="fas fa-chart-line"></i>
                                <p>No case data available for the last 7 days</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Tables Section (Only Top 10 Common Items) -->
                <div class="tables-section">
                    <!-- Top 10 Common Items -->
                    <div class="table-container">
                        <div class="chart-title">
                            <span>Top 10 Common Items</span>
                            <span style="font-size: 14px; color: #7f8c8d;">
                                <?php echo count($top_items); ?> items
                            </span>
                        </div>
                        
                        <?php if(!empty($top_items)): ?>
                            <div style="overflow-x: auto;">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Item Type</th>
                                            <th>Frequency</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $rank = 1;
                                        $total_frequency = array_sum(array_column($top_items, 'frequency'));
                                        foreach($top_items as $item): 
                                            $percentage = $total_frequency > 0 ? round(($item['frequency'] / $total_frequency) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td>#<?php echo $rank; ?></td>
                                            <td><?php echo htmlspecialchars(ucfirst($item['type_item'])); ?></td>
                                            <td><?php echo $item['frequency']; ?></td>
                                            <td>
                                                <div class="progress-container">
                                                    <div class="progress-label">
                                                        <span><?php echo $percentage; ?>%</span>
                                                    </div>
                                                    <div class="progress-bar">
                                                        <div class="progress-fill" 
                                                             style="width: <?php echo $percentage; ?>%; background: #3498db;"></div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php 
                                        $rank++;
                                        endforeach; 
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-box-open"></i>
                                <p>No item data available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Export Section -->
                <div class="export-section">
                    <button class="export-btn print" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Dashboard
                    </button>
                    <button class="export-btn" onclick="exportDashboard()">
                        <i class="fas fa-file-export"></i> Export Data
                    </button>
                </div>
                
                <div class="last-updated">
                    Last updated: <?php echo date('d/m/Y H:i:s'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Initialize Status Distribution Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    'Pending (<?php echo $status_data['pending'] ?? 0; ?>)',
                    'Approved (<?php echo $status_data['approved'] ?? 0; ?>)',
                    'Matched (<?php echo $status_data['matched'] ?? 0; ?>)',
                    'Claimed (<?php echo $status_data['claimed'] ?? 0; ?>)'
                    
                ],
                datasets: [{
                    data: [
                        <?php echo $status_data['pending'] ?? 0; ?>,
                        <?php echo $status_data['approved'] ?? 0; ?>,
                        <?php echo $status_data['matched'] ?? 0; ?>,
                        <?php echo $status_data['claimed'] ?? 0; ?>
                        
                    ],
                    backgroundColor: [
                        '#ffc107', // pending - yellow
                        '#007bff', // approved - blue
                        '#17a2b8', // matched - teal
                        '#28a745', // claimed - green
                        
                    ],
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                label += value + ' cases (' + percentage + '%)';
                                return label;
                            }
                        }
                    }
                }
            }
        });
        
        // Initialize Trend Chart
        <?php if(!empty($trend_data)): ?>
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        const trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($trend_labels); ?>,
                datasets: [{
                    label: 'Cases',
                    data: <?php echo json_encode($trend_counts); ?>,
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderColor: '#3498db',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#3498db',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    }
                }
            }
        });
        <?php endif; ?>
        
        // Refresh button functionality
        document.getElementById('refreshBtn').addEventListener('click', function() {
            const btn = this;
            const icon = btn.querySelector('i');
            
            // Show loading state
            btn.classList.add('loading');
            icon.className = 'fas fa-sync-alt fa-spin';
            btn.disabled = true;
            
            // Reload page after 1 second
            setTimeout(function() {
                window.location.reload();
            }, 1000);
        });
        
        // Auto-refresh every 2 minutes (optional)
        let autoRefreshTimer;
        function startAutoRefresh() {
            autoRefreshTimer = setTimeout(function() {
                if(!document.hidden) {
                    window.location.reload();
                }
            }, 120000); // 2 minutes
        }
        
        function stopAutoRefresh() {
            clearTimeout(autoRefreshTimer);
        }
        
        // Start auto-refresh on page load
        startAutoRefresh();
        
        // Stop auto-refresh when user is interacting
        document.addEventListener('mousemove', stopAutoRefresh);
        document.addEventListener('keypress', stopAutoRefresh);
        document.addEventListener('click', stopAutoRefresh);
        
        // Restart auto-refresh when page becomes visible again
        document.addEventListener('visibilitychange', function() {
            if(!document.hidden) {
                startAutoRefresh();
            } else {
                stopAutoRefresh();
            }
        });
        
        // Export dashboard data
        function exportDashboard() {
            // Create a simple CSV export
            let csvContent = "data:text/csv;charset=utf-8,";
            
            // Add header
            csvContent += "Statistic,Value\n";
            
            // Add quick stats
            csvContent += "Total Cases,<?php echo $total_cases; ?>\n";
            csvContent += "Today's Cases,<?php echo $today_cases; ?>\n";
            csvContent += "Claimed Rate,<?php echo $claimed_rate; ?>%\n";
            csvContent += "Pending Cases,<?php echo $pending_cases; ?>\n";
            
            // Add status distribution
            csvContent += "\nStatus Distribution\n";
            <?php foreach($status_colors as $status => $color): ?>
            csvContent += "<?php echo ucfirst($status); ?>,<?php echo $status_data[$status] ?? 0; ?>\n";
            <?php endforeach; ?>
            
            // Add top items
            csvContent += "\nTop Items\n";
            <?php 
            $rank = 1;
            foreach($top_items as $item): 
                $percentage = $total_frequency > 0 ? round(($item['frequency'] / $total_frequency) * 100, 1) : 0;
            ?>
            csvContent += "<?php echo $rank; ?>,<?php echo htmlspecialchars($item['type_item']); ?>,<?php echo $item['frequency']; ?>,<?php echo $percentage; ?>%\n";
            <?php 
            $rank++;
            endforeach; 
            ?>
            
            // Create download link
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "statistics_<?php echo date('Y-m-d'); ?>.csv");
            document.body.appendChild(link);
            
            // Trigger download
            link.click();
            document.body.removeChild(link);
        }
        
        // Period selector active state
        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                // If already active, prevent navigation
                if(this.classList.contains('active')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>