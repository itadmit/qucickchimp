<?php
require_once '../config/config.php';

// Set page title
$pageTitle = 'לוח בקרה';
$pageDescription = 'סקירה כללית של החשבון שלך';

// Include header
include_once 'template/header.php';

// Get user stats
$userId = $_SESSION['user_id'] ?? 0;

// Initialize variables with default values
$landingPagesCount = 0;
$subscribersCount = 0;
$campaignsCount = 0;
$recentSubscribers = [];
$recentCampaigns = [];
$monthlySubscribersCount = 0;
$monthlyEmailsSent = 0;
$plan = [
    'name' => 'בסיסי',
    'max_landing_pages' => 1,
    'max_leads' => 300,
    'max_emails' => 300
];

// Check if tables exist before querying
try {
    // Get landing pages count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM landing_pages WHERE user_id = ?");
    $stmt->execute([$userId]);
    $landingPagesCount = $stmt->fetchColumn();

    // Get subscribers count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscribers WHERE user_id = ?");
    $stmt->execute([$userId]);
    $subscribersCount = $stmt->fetchColumn();

    // Get campaigns count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM campaigns WHERE user_id = ?");
    $stmt->execute([$userId]);
    $campaignsCount = $stmt->fetchColumn();

    // Get recent subscribers
    $stmt = $pdo->prepare("
        SELECT s.*, l.title as landing_page_title
        FROM subscribers s
        LEFT JOIN landing_pages l ON s.landing_page_id = l.id
        WHERE s.user_id = ?
        ORDER BY s.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $recentSubscribers = $stmt->fetchAll();

    // Get recent campaigns
    $stmt = $pdo->prepare("
        SELECT c.*, 
               (SELECT COUNT(*) FROM campaign_stats WHERE campaign_id = c.id AND is_sent = 1) as sent_count,
               (SELECT COUNT(*) FROM campaign_stats WHERE campaign_id = c.id AND is_opened = 1) as opened_count
        FROM campaigns c
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $recentCampaigns = $stmt->fetchAll();

    // Check for usage limits
    if (isset($currentUser) && isset($currentUser['plan_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
        $stmt->execute([$currentUser['plan_id']]);
        $planResult = $stmt->fetch();
        if ($planResult) {
            $plan = $planResult;
        }
    }

    // Monthly subscribers (leads)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM subscribers 
        WHERE user_id = ? AND created_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')
    ");
    $stmt->execute([$userId]);
    $monthlySubscribersCount = $stmt->fetchColumn();

    // Monthly emails sent
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM campaign_stats cs
        JOIN campaigns c ON cs.campaign_id = c.id
        WHERE c.user_id = ? AND cs.is_sent = 1 AND cs.sent_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')
    ");
    $stmt->execute([$userId]);
    $monthlyEmailsSent = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Handle the exception - tables may not exist yet
    $_SESSION['error'] = 'נראה שיש בעיה בחיבור למסד הנתונים או שהטבלאות טרם נוצרו. יש ליצור את טבלאות מסד הנתונים.';
}

// Calculate percentages
$subscribersPercentage = ($plan['max_leads'] > 0) ? ($monthlySubscribersCount / $plan['max_leads']) * 100 : 0;
$emailsPercentage = ($plan['max_emails'] > 0) ? ($monthlyEmailsSent / $plan['max_emails']) * 100 : 0;
$landingPagesPercentage = ($plan['max_landing_pages'] > 0) ? ($landingPagesCount / $plan['max_landing_pages']) * 100 : 0;
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center ml-4">
                <i class="ri-layout-4-line text-2xl text-purple-600"></i>
            </div>
            <div>
                <h3 class="text-lg text-gray-500">דפי נחיתה</h3>
                <p class="text-3xl font-bold"><?php echo $landingPagesCount; ?></p>
            </div>
        </div>
        <div class="mt-4">
            <a href="landing_pages.php" class="text-purple-600 flex items-center text-sm hover:underline">
                <span>צפה בכל דפי הנחיתה</span>
                <i class="ri-arrow-left-line mr-1"></i>
            </a>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center ml-4">
                <i class="ri-user-follow-line text-2xl text-green-600"></i>
            </div>
            <div>
                <h3 class="text-lg text-gray-500">מנויים</h3>
                <p class="text-3xl font-bold"><?php echo $subscribersCount; ?></p>
            </div>
        </div>
        <div class="mt-4">
            <a href="subscribers.php" class="text-green-600 flex items-center text-sm hover:underline">
                <span>צפה בכל המנויים</span>
                <i class="ri-arrow-left-line mr-1"></i>
            </a>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center ml-4">
                <i class="ri-mail-send-line text-2xl text-blue-600"></i>
            </div>
            <div>
                <h3 class="text-lg text-gray-500">קמפיינים</h3>
                <p class="text-3xl font-bold"><?php echo $campaignsCount; ?></p>
            </div>
        </div>
        <div class="mt-4">
            <a href="campaigns.php" class="text-blue-600 flex items-center text-sm hover:underline">
                <span>צפה בכל הקמפיינים</span>
                <i class="ri-arrow-left-line mr-1"></i>
            </a>
        </div>
    </div>
</div>

<!-- Usage Limits -->
<div class="bg-white rounded-lg shadow mb-8">
    <div class="p-6 border-b">
        <h2 class="text-xl font-bold">מגבלות שימוש חודשיות</h2>
        <p class="text-gray-600 text-sm">
            <i class="ri-calendar-line ml-1"></i>
            תוכנית: <?php echo htmlspecialchars($plan['name'] ?? 'בסיסי'); ?>
        </p>
    </div>
    
    <div class="p-6">
        <div class="space-y-6">
            <div>
                <div class="flex justify-between mb-1">
                    <span class="text-gray-700">דפי נחיתה</span>
                    <span class="text-gray-700"><?php echo $landingPagesCount; ?> / <?php echo $plan['max_landing_pages']; ?></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-purple-600 h-2 rounded-full" style="width: <?php echo min($landingPagesPercentage, 100); ?>%"></div>
                </div>
            </div>
            
            <div>
                <div class="flex justify-between mb-1">
                    <span class="text-gray-700">לידים החודש</span>
                    <span class="text-gray-700"><?php echo $monthlySubscribersCount; ?> / <?php echo $plan['max_leads']; ?></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="<?php echo $subscribersPercentage > 90 ? 'bg-red-500' : 'bg-green-500'; ?> h-2 rounded-full" style="width: <?php echo min($subscribersPercentage, 100); ?>%"></div>
                </div>
            </div>
            
            <div>
                <div class="flex justify-between mb-1">
                    <span class="text-gray-700">אימיילים שנשלחו החודש</span>
                    <span class="text-gray-700"><?php echo $monthlyEmailsSent; ?> / <?php echo $plan['max_emails']; ?></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="<?php echo $emailsPercentage > 90 ? 'bg-red-500' : 'bg-blue-500'; ?> h-2 rounded-full" style="width: <?php echo min($emailsPercentage, 100); ?>%"></div>
                </div>
            </div>
        </div>
        
        <?php if ($subscribersPercentage > 80 || $emailsPercentage > 80 || $landingPagesPercentage > 80): ?>
            <div class="mt-6 bg-yellow-50 border border-yellow-100 rounded-lg p-4">
                <div class="flex">
                    <i class="ri-information-line text-yellow-500 text-xl ml-3"></i>
                    <div>
                        <h4 class="font-medium text-yellow-800">מתקרב למגבלות השימוש</h4>
                        <p class="text-yellow-700 text-sm">אתה מתקרב למגבלות השימוש של התוכנית שלך. שקול לשדרג לתוכנית גבוהה יותר.</p>
                        <a href="billing.php" class="mt-2 inline-block text-sm text-yellow-800 font-medium hover:underline">שדרג את התוכנית שלך <i class="ri-arrow-left-line"></i></a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Subscribers -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b flex justify-between items-center">
            <h2 class="text-xl font-bold">מנויים אחרונים</h2>
            <a href="subscribers.php" class="text-sm text-purple-600 hover:underline flex items-center">
                הצג הכל
                <i class="ri-arrow-left-line mr-1"></i>
            </a>
        </div>
        
        <div class="divide-y">
            <?php if (empty($recentSubscribers)): ?>
                <div class="p-6 text-gray-500 text-center">
                    <i class="ri-user-follow-line text-5xl mb-2 block"></i>
                    <p>עדיין אין לך מנויים.</p>
                    <p class="text-sm">צור דף נחיתה חדש כדי להתחיל לאסוף מנויים.</p>
                    <a href="landing_pages.php" class="mt-4 inline-block px-4 py-2 bg-purple-600 text-white rounded-lg text-sm hover:bg-purple-700">
                        צור דף נחיתה
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($recentSubscribers as $subscriber): ?>
                    <div class="p-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                <i class="ri-user-line text-gray-500"></i>
                            </div>
                            <div class="mr-3">
                                <p class="font-medium">
                                    <?php 
                                        echo !empty($subscriber['first_name']) ? 
                                            htmlspecialchars($subscriber['first_name'] . ' ' . $subscriber['last_name']) : 
                                            htmlspecialchars($subscriber['email']); 
                                    ?>
                                </p>
                                <p class="text-sm text-gray-500">
                                    <?php if (!empty($subscriber['landing_page_title'])): ?>
                                        <span data-tooltip="דף נחיתה">
                                            <i class="ri-layout-line ml-1"></i>
                                            <?php echo htmlspecialchars($subscriber['landing_page_title']); ?>
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500">
                            <?php echo formatHebrewDate($subscriber['created_at']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Campaigns -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b flex justify-between items-center">
            <h2 class="text-xl font-bold">קמפיינים אחרונים</h2>
            <a href="campaigns.php" class="text-sm text-purple-600 hover:underline flex items-center">
                הצג הכל
                <i class="ri-arrow-left-line mr-1"></i>
            </a>
        </div>
        
        <div class="divide-y">
            <?php if (empty($recentCampaigns)): ?>
                <div class="p-6 text-gray-500 text-center">
                    <i class="ri-mail-send-line text-5xl mb-2 block"></i>
                    <p>עדיין אין לך קמפיינים.</p>
                    <p class="text-sm">צור קמפיין חדש כדי להתחיל לשלוח אימיילים למנויים שלך.</p>
                    <a href="campaigns.php" class="mt-4 inline-block px-4 py-2 bg-purple-600 text-white rounded-lg text-sm hover:bg-purple-700">
                        צור קמפיין
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($recentCampaigns as $campaign): ?>
                    <div class="p-4">
                        <div class="flex justify-between">
                            <div>
                                <p class="font-medium"><?php echo htmlspecialchars($campaign['name']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($campaign['subject']); ?></p>
                            </div>
                            
                            <?php if ($campaign['status'] == 'draft'): ?>
                                <span class="bg-gray-100 text-gray-800 text-xs py-1 px-2 rounded">טיוטה</span>
                            <?php elseif ($campaign['status'] == 'scheduled'): ?>
                                <span class="bg-yellow-100 text-yellow-800 text-xs py-1 px-2 rounded">מתוזמן</span>
                            <?php elseif ($campaign['status'] == 'sent'): ?>
                                <span class="bg-green-100 text-green-800 text-xs py-1 px-2 rounded">נשלח</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($campaign['status'] == 'sent'): ?>
                            <div class="mt-2 flex text-xs text-gray-500">
                                <div class="ml-4">
                                    <i class="ri-mail-check-line ml-1"></i>
                                    נשלח: <?php echo $campaign['sent_count']; ?>
                                </div>
                                <div>
                                    <i class="ri-mail-open-line ml-1"></i>
                                    נפתח: <?php echo $campaign['opened_count']; ?> 
                                    (<?php echo $campaign['sent_count'] > 0 ? round(($campaign['opened_count'] / $campaign['sent_count']) * 100) : 0; ?>%)
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once 'template/footer.php'; ?>