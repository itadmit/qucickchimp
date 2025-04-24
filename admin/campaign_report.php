<?php
require_once '../config/config.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'מזהה קמפיין לא סופק';
    redirect('campaigns.php');
}

$campaignId = (int)$_GET['id'];
$userId = $_SESSION['user_id'] ?? 0;

// Load campaign data
try {
    $stmt = $pdo->prepare("
        SELECT * FROM campaigns 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$campaignId, $userId]);
    $campaign = $stmt->fetch();
} catch (PDOException $e) {
    $_SESSION['error'] = 'אירעה שגיאה בטעינת נתוני הקמפיין';
    redirect('campaigns.php');
}

// Check if campaign exists and belongs to user
if (!$campaign) {
    $_SESSION['error'] = 'הקמפיין המבוקש לא נמצא או שאין לך הרשאות לצפות בו';
    redirect('campaigns.php');
}

// Set page title
$pageTitle = 'דוח מפורט - ' . $campaign['name'];
$pageDescription = 'סטטיסטיקות מפורטות עבור הקמפיין: ' . $campaign['name'];
$backButton = [
    'url' => 'campaign_view.php?id=' . $campaignId,
    'text' => 'חזרה לקמפיין'
];

// Get campaign statistics
try {
    // Basic stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_sent,
            SUM(CASE WHEN is_opened = 1 THEN 1 ELSE 0 END) as total_opened,
            SUM(CASE WHEN is_clicked = 1 THEN 1 ELSE 0 END) as total_clicked,
            SUM(CASE WHEN is_bounced = 1 THEN 1 ELSE 0 END) as total_bounced,
            SUM(CASE WHEN is_complained = 1 THEN 1 ELSE 0 END) as total_complained
        FROM campaign_stats
        WHERE campaign_id = ?
    ");
    $stmt->execute([$campaignId]);
    $stats = $stmt->fetch();
    
    // Opens by date
    $stmt = $pdo->prepare("
        SELECT 
            DATE(opened_at) as date,
            COUNT(*) as count
        FROM campaign_stats
        WHERE campaign_id = ? AND is_opened = 1 AND opened_at IS NOT NULL
        GROUP BY DATE(opened_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$campaignId]);
    $opensByDate = $stmt->fetchAll();
    
    // Clicks by date
    $stmt = $pdo->prepare("
        SELECT 
            DATE(clicked_at) as date,
            COUNT(*) as count
        FROM campaign_stats
        WHERE campaign_id = ? AND is_clicked = 1 AND clicked_at IS NOT NULL
        GROUP BY DATE(clicked_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$campaignId]);
    $clicksByDate = $stmt->fetchAll();
    
    // Device stats
    $stmt = $pdo->prepare("
        SELECT 
            device_type,
            COUNT(*) as count
        FROM campaign_stats
        WHERE campaign_id = ? AND device_type IS NOT NULL
        GROUP BY device_type
    ");
    $stmt->execute([$campaignId]);
    $deviceStats = $stmt->fetchAll();
    
    // Calculate percentages
    $totalSent = $stats['total_sent'] ?: 1; // Avoid division by zero
    $openRate = round(($stats['total_opened'] / $totalSent) * 100, 1);
    $clickRate = round(($stats['total_clicked'] / $totalSent) * 100, 1);
    $clickToOpenRate = $stats['total_opened'] ? round(($stats['total_clicked'] / $stats['total_opened']) * 100, 1) : 0;
    $bounceRate = round(($stats['total_bounced'] / $totalSent) * 100, 1);
    $complaintRate = round(($stats['total_complained'] / $totalSent) * 100, 1);
    
    // Top email domains
    $stmt = $pdo->prepare("
        SELECT 
            SUBSTRING_INDEX(s.email, '@', -1) as domain,
            COUNT(*) as count
        FROM campaign_stats cs
        JOIN subscribers s ON cs.subscriber_id = s.id
        WHERE cs.campaign_id = ?
        GROUP BY domain
        ORDER BY count DESC
        LIMIT 5
    ");
    $stmt->execute([$campaignId]);
    $topDomains = $stmt->fetchAll();
    
    // Activity timeline
    $stmt = $pdo->prepare("
        SELECT 
            HOUR(opened_at) as hour,
            COUNT(*) as count
        FROM campaign_stats
        WHERE campaign_id = ? AND is_opened = 1 AND opened_at IS NOT NULL
        GROUP BY HOUR(opened_at)
        ORDER BY hour ASC
    ");
    $stmt->execute([$campaignId]);
    $activityTimeline = $stmt->fetchAll();
    
    // Open to click time
    $stmt = $pdo->prepare("
        SELECT 
            TIMESTAMPDIFF(MINUTE, opened_at, clicked_at) as minutes,
            COUNT(*) as count
        FROM campaign_stats
        WHERE campaign_id = ? 
          AND is_opened = 1 
          AND is_clicked = 1 
          AND opened_at IS NOT NULL 
          AND clicked_at IS NOT NULL
          AND clicked_at > opened_at
        GROUP BY minutes
        ORDER BY minutes ASC
    ");
    $stmt->execute([$campaignId]);
    $openToClickTime = $stmt->fetchAll();
    
    // Format data for charts
    $opensByDateLabels = [];
    $opensByDateValues = [];
    foreach ($opensByDate as $item) {
        $opensByDateLabels[] = date('d/m', strtotime($item['date']));
        $opensByDateValues[] = (int)$item['count'];
    }
    
    $clicksByDateLabels = [];
    $clicksByDateValues = [];
    foreach ($clicksByDate as $item) {
        $clicksByDateLabels[] = date('d/m', strtotime($item['date']));
        $clicksByDateValues[] = (int)$item['count'];
    }
    
    $deviceLabels = [];
    $deviceValues = [];
    foreach ($deviceStats as $item) {
        $deviceLabels[] = $item['device_type'] ?: 'לא ידוע';
        $deviceValues[] = (int)$item['count'];
    }
    
    $domainLabels = [];
    $domainValues = [];
    foreach ($topDomains as $item) {
        $domainLabels[] = $item['domain'];
        $domainValues[] = (int)$item['count'];
    }
    
    $timelineLabels = [];
    $timelineValues = [];
    for ($i = 0; $i < 24; $i++) {
        $timelineLabels[] = sprintf('%02d:00', $i);
        $timelineValues[] = 0;
    }
    foreach ($activityTimeline as $item) {
        $timelineValues[(int)$item['hour']] = (int)$item['count'];
    }
    
} catch (PDOException $e) {
    // Default values if query fails
    $stats = [
        'total_sent' => 0,
        'total_opened' => 0,
        'total_clicked' => 0,
        'total_bounced' => 0,
        'total_complained' => 0
    ];
    $openRate = 0;
    $clickRate = 0;
    $clickToOpenRate = 0;
    $bounceRate = 0;
    $complaintRate = 0;
    
    $opensByDateLabels = [];
    $opensByDateValues = [];
    $clicksByDateLabels = [];
    $clicksByDateValues = [];
    $deviceLabels = [];
    $deviceValues = [];
    $domainLabels = [];
    $domainValues = [];
    $timelineLabels = [];
    $timelineValues = [];
}

// Format campaign status
$statusLabels = [
    'draft' => ['text' => 'טיוטה', 'class' => 'bg-gray-100 text-gray-800'],
    'scheduled' => ['text' => 'מתוזמן', 'class' => 'bg-blue-100 text-blue-800'],
    'sending' => ['text' => 'בשליחה', 'class' => 'bg-yellow-100 text-yellow-800'],
    'sent' => ['text' => 'נשלח', 'class' => 'bg-green-100 text-green-800'],
    'error' => ['text' => 'שגיאה', 'class' => 'bg-red-100 text-red-800']
];
$status = $statusLabels[$campaign['status']] ?? $statusLabels['draft'];

// Include header
include_once 'template/header.php';
?>

<!-- Main Content -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-6 border-b flex justify-between items-center">
        <div>
            <h2 class="text-xl font-medium"><?php echo htmlspecialchars($campaign['name']); ?></h2>
            <p class="text-gray-500 text-sm mt-1">נשלח ב-<?php echo $campaign['sent_at'] ? date('d/m/Y H:i', strtotime($campaign['sent_at'])) : 'טרם נשלח'; ?></p>
        </div>
        <div>
            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status['class']; ?>">
                <?php echo $status['text']; ?>
            </span>
        </div>
    </div>
    
    <div class="p-6">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-white p-4 rounded-md shadow-sm border text-center">
                <div class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_sent']); ?></div>
                <div class="text-sm text-gray-500">נשלחו</div>
            </div>
            <div class="bg-white p-4 rounded-md shadow-sm border text-center">
                <div class="text-2xl font-bold text-blue-600"><?php echo number_format($stats['total_opened']); ?></div>
                <div class="text-sm text-gray-500">נפתחו</div>
                <div class="text-xs text-gray-400"><?php echo $openRate; ?>%</div>
            </div>
            <div class="bg-white p-4 rounded-md shadow-sm border text-center">
                <div class="text-2xl font-bold text-green-600"><?php echo number_format($stats['total_clicked']); ?></div>
                <div class="text-sm text-gray-500">הקליקו</div>
                <div class="text-xs text-gray-400"><?php echo $clickRate; ?>%</div>
            </div>
            <div class="bg-white p-4 rounded-md shadow-sm border text-center">
                <div class="text-2xl font-bold text-red-600"><?php echo number_format($stats['total_bounced']); ?></div>
                <div class="text-sm text-gray-500">נדחו</div>
                <div class="text-xs text-gray-400"><?php echo $bounceRate; ?>%</div>
            </div>
            <div class="bg-white p-4 rounded-md shadow-sm border text-center">
                <div class="text-2xl font-bold text-orange-600"><?php echo number_format($stats['total_complained']); ?></div>
                <div class="text-sm text-gray-500">תלונות</div>
                <div class="text-xs text-gray-400"><?php echo $complaintRate; ?>%</div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Opens By Date Chart -->
            <div class="bg-white p-4 rounded-md shadow-sm border">
                <h3 class="text-lg font-medium text-gray-900 mb-4">פתיחות לפי תאריך</h3>
                <div class="h-64">
                    <canvas id="opensByDateChart"></canvas>
                </div>
            </div>
            
            <!-- Clicks By Date Chart -->
            <div class="bg-white p-4 rounded-md shadow-sm border">
                <h3 class="text-lg font-medium text-gray-900 mb-4">הקלקות לפי תאריך</h3>
                <div class="h-64">
                    <canvas id="clicksByDateChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Devices Chart -->
            <div class="bg-white p-4 rounded-md shadow-sm border">
                <h3 class="text-lg font-medium text-gray-900 mb-4">התפלגות מכשירים</h3>
                <div class="h-64">
                    <canvas id="devicesChart"></canvas>
                </div>
            </div>
            
            <!-- Email Domains Chart -->
            <div class="bg-white p-4 rounded-md shadow-sm border">
                <h3 class="text-lg font-medium text-gray-900 mb-4">דומיינים מובילים</h3>
                <div class="h-64">
                    <canvas id="domainsChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-4 rounded-md shadow-sm border mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">זמני פעילות לאורך היום</h3>
            <div class="h-64">
                <canvas id="timelineChart"></canvas>
            </div>
        </div>
        
        <div class="flex justify-between">
            <a href="campaign_view.php?id=<?php echo $campaignId; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="ml-2 -mr-1 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                חזרה לקמפיין
            </a>
            <button onclick="window.print()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="ml-2 -mr-1 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                הדפס דוח
            </button>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<script>
    // Define chart colors
    const colors = {
        blue: 'rgba(59, 130, 246, 0.5)',
        blueBorder: 'rgba(59, 130, 246, 1)',
        green: 'rgba(16, 185, 129, 0.5)',
        greenBorder: 'rgba(16, 185, 129, 1)',
        indigo: 'rgba(99, 102, 241, 0.5)',
        indigoBorder: 'rgba(99, 102, 241, 1)',
        purple: 'rgba(139, 92, 246, 0.5)',
        purpleBorder: 'rgba(139, 92, 246, 1)',
        red: 'rgba(239, 68, 68, 0.5)',
        redBorder: 'rgba(239, 68, 68, 1)',
    };
    
    // Common chart options
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    font: {
                        family: 'system-ui, -apple-system, sans-serif'
                    }
                }
            }
        },
        layout: {
            padding: 10
        }
    };
    
    // Opens by date chart
    const opensByDateChart = new Chart(
        document.getElementById('opensByDateChart'),
        {
            type: 'line',
            data: {
                labels: <?php echo json_encode($opensByDateLabels); ?>,
                datasets: [
                    {
                        label: 'פתיחות',
                        data: <?php echo json_encode($opensByDateValues); ?>,
                        backgroundColor: colors.blue,
                        borderColor: colors.blueBorder,
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        }
    );
    
    // Clicks by date chart
    const clicksByDateChart = new Chart(
        document.getElementById('clicksByDateChart'),
        {
            type: 'line',
            data: {
                labels: <?php echo json_encode($clicksByDateLabels); ?>,
                datasets: [
                    {
                        label: 'הקלקות',
                        data: <?php echo json_encode($clicksByDateValues); ?>,
                        backgroundColor: colors.green,
                        borderColor: colors.greenBorder,
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        }
    );
    
    // Devices chart
    const devicesChart = new Chart(
        document.getElementById('devicesChart'),
        {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($deviceLabels); ?>,
                datasets: [
                    {
                        data: <?php echo json_encode($deviceValues); ?>,
                        backgroundColor: [
                            colors.blue,
                            colors.green,
                            colors.indigo,
                            colors.purple,
                            colors.red
                        ],
                        borderColor: [
                            colors.blueBorder,
                            colors.greenBorder,
                            colors.indigoBorder,
                            colors.purpleBorder,
                            colors.redBorder
                        ],
                        borderWidth: 1
                    }
                ]
            },
            options: {
                ...commonOptions
            }
        }
    );
    
    // Domains chart
    const domainsChart = new Chart(
        document.getElementById('domainsChart'),
        {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($domainLabels); ?>,
                datasets: [
                    {
                        label: 'מספר נמענים',
                        data: <?php echo json_encode($domainValues); ?>,
                        backgroundColor: colors.indigo,
                        borderColor: colors.indigoBorder,
                        borderWidth: 1
                    }
                ]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        }
    );
    
    // Timeline chart
    const timelineChart = new Chart(
        document.getElementById('timelineChart'),
        {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($timelineLabels); ?>,
                datasets: [
                    {
                        label: 'פתיחות',
                        data: <?php echo json_encode($timelineValues); ?>,
                        backgroundColor: colors.purple,
                        borderColor: colors.purpleBorder,
                        borderWidth: 1
                    }
                ]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        }
    );
</script>

<?php include_once 'template/footer.php'; ?> 