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
$pageTitle = 'צפייה בקמפיין';
$pageDescription = 'צפייה בפרטי הקמפיין: ' . $campaign['name'];
$backButton = [
    'url' => 'campaigns.php',
    'text' => 'חזרה לקמפיינים'
];

// Get selected lists for this campaign
try {
    $stmt = $pdo->prepare("
        SELECT cl.id, cl.name, COUNT(sl.subscriber_id) as subscribers_count 
        FROM campaign_lists cpl
        JOIN contact_lists cl ON cpl.list_id = cl.id
        LEFT JOIN subscriber_lists sl ON cl.id = sl.list_id
        WHERE cpl.campaign_id = ?
        GROUP BY cl.id, cl.name
    ");
    $stmt->execute([$campaignId]);
    $campaignLists = $stmt->fetchAll();
} catch (PDOException $e) {
    $campaignLists = [];
}

// Get campaign statistics
try {
    // Basic stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_sent,
            SUM(CASE WHEN is_opened = 1 THEN 1 ELSE 0 END) as total_opened,
            SUM(CASE WHEN is_clicked = 1 THEN 1 ELSE 0 END) as total_clicked
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
    
    // Calculate percentages
    $totalSent = $stats['total_sent'] ?: 1; // Avoid division by zero
    $openRate = round(($stats['total_opened'] / $totalSent) * 100, 1);
    $clickRate = round(($stats['total_clicked'] / $totalSent) * 100, 1);
    $clickToOpenRate = $stats['total_opened'] ? round(($stats['total_clicked'] / $stats['total_opened']) * 100, 1) : 0;
    
} catch (PDOException $e) {
    $stats = [
        'total_sent' => 0,
        'total_opened' => 0,
        'total_clicked' => 0
    ];
    $opensByDate = [];
    $clicksByDate = [];
    $openRate = 0;
    $clickRate = 0;
    $clickToOpenRate = 0;
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
            <p class="text-gray-500 text-sm mt-1">נוצר ב-<?php echo date('d/m/Y H:i', strtotime($campaign['created_at'])); ?></p>
        </div>
        <div>
            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status['class']; ?>">
                <?php echo $status['text']; ?>
            </span>
        </div>
    </div>
    
    <div class="p-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Campaign Info -->
            <div class="lg:col-span-2 space-y-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-3">פרטי הקמפיין</h3>
                    
                    <div class="border rounded-md overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 border-b">
                            <div class="flex justify-between">
                                <span class="font-medium">נושא:</span>
                                <span><?php echo htmlspecialchars($campaign['subject']); ?></span>
                            </div>
                        </div>
                        
                        <?php if ($campaign['status'] === 'scheduled'): ?>
                        <div class="px-4 py-3 border-b">
                            <div class="flex justify-between">
                                <span class="font-medium">מתוזמן לשליחה:</span>
                                <span><?php echo date('d/m/Y H:i', strtotime($campaign['scheduled_at'])); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($campaign['status'] === 'sent'): ?>
                        <div class="px-4 py-3 border-b">
                            <div class="flex justify-between">
                                <span class="font-medium">נשלח ב:</span>
                                <span><?php echo date('d/m/Y H:i', strtotime($campaign['sent_at'])); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="px-4 py-3 border-b">
                            <div class="flex justify-between">
                                <span class="font-medium">עודכן לאחרונה:</span>
                                <span><?php echo date('d/m/Y H:i', strtotime($campaign['updated_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="px-4 py-3">
                            <span class="font-medium">רשימות תפוצה:</span>
                            <div class="mt-2 space-y-1">
                                <?php if (empty($campaignLists)): ?>
                                <p class="text-sm text-gray-500">לא נבחרו רשימות תפוצה</p>
                                <?php else: ?>
                                <?php foreach ($campaignLists as $list): ?>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm"><?php echo htmlspecialchars($list['name']); ?></span>
                                    <span class="text-xs text-gray-500"><?php echo $list['subscribers_count']; ?> נמענים</span>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-3">תצוגה מקדימה של התוכן</h3>
                    <div class="border rounded-md p-4 bg-gray-50">
                        <div class="bg-white p-4 rounded-md border shadow-sm">
                            <div class="prose max-w-none">
                                <?php echo $campaign['content']; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Campaign Stats -->
            <div class="space-y-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-3">סטטיסטיקות</h3>
                    
                    <div class="bg-white rounded-md shadow-sm overflow-hidden">
                        <div class="grid grid-cols-3 divide-x divide-x-reverse">
                            <div class="p-4 text-center">
                                <div class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_sent']); ?></div>
                                <div class="text-xs text-gray-500">נשלחו</div>
                            </div>
                            <div class="p-4 text-center">
                                <div class="text-2xl font-bold text-blue-600"><?php echo number_format($stats['total_opened']); ?></div>
                                <div class="text-xs text-gray-500">נפתחו</div>
                            </div>
                            <div class="p-4 text-center">
                                <div class="text-2xl font-bold text-green-600"><?php echo number_format($stats['total_clicked']); ?></div>
                                <div class="text-xs text-gray-500">הקליקו</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-3">אחוזי פתיחה והקלקות</h3>
                    
                    <div class="space-y-4">
                        <div class="bg-white p-4 rounded-md shadow-sm">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium">אחוז פתיחה</span>
                                <span class="text-sm text-gray-500"><?php echo $openRate; ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo min($openRate, 100); ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="bg-white p-4 rounded-md shadow-sm">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium">אחוז הקלקה</span>
                                <span class="text-sm text-gray-500"><?php echo $clickRate; ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo min($clickRate, 100); ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="bg-white p-4 rounded-md shadow-sm">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium">יחס הקלקות לפתיחות</span>
                                <span class="text-sm text-gray-500"><?php echo $clickToOpenRate; ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-indigo-600 h-2 rounded-full" style="width: <?php echo min($clickToOpenRate, 100); ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex space-x-2 space-x-reverse">
                    <?php if ($campaign['status'] === 'draft'): ?>
                    <a href="campaign_edit.php?id=<?php echo $campaignId; ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="ml-2 -mr-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        ערוך
                    </a>
                    <?php endif; ?>
                    <a href="campaign_duplicate.php?id=<?php echo $campaignId; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="ml-2 -mr-1 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        שכפל
                    </a>
                    <a href="campaign_report.php?id=<?php echo $campaignId; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="ml-2 -mr-1 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        דוח מפורט
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'template/footer.php'; ?> 