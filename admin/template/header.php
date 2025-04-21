<?php
// Check if user is logged in
requireLogin();

// Get current user data
$currentUser = getCurrentUser($pdo);

// Default values in case the user data is not available
if (!$currentUser) {
    $currentUser = [
        'trial_ends_at' => null,
        'plan_id' => 1,
        'full_name' => 'משתמש',
        'email' => 'user@example.com'
    ];
}

// Check for plan limits and trial expiration
$trialExpired = $currentUser && isset($currentUser['trial_ends_at']) ? isTrialExpired($currentUser['trial_ends_at']) : false;
$planName = '';

// Get user's plan details
$userPlan = null;
if ($currentUser && isset($currentUser['plan_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
    $stmt->execute([$currentUser['plan_id']]);
    $userPlan = $stmt->fetch();
}

if ($userPlan) {
    $planName = $userPlan['name'];
} else {
    $planName = 'בסיסי';
}

// Get current page for active menu highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' | ' : ''; ?><?php echo APP_NAME; ?> - ניהול</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Hebrew:wght@100..900&display=swap');
        body {
            font-family: "Noto Sans Hebrew", sans-serif;
        }
        
        .sidebar-link {
            transition: all 0.3s;
            border-right: 3px solid transparent;
        }
        
        .sidebar-link.active {
            background-color: rgba(147, 51, 234, 0.1);
            border-right-color: #9333ea;
        }
        
        .sidebar-link:hover:not(.active) {
            background-color: rgba(147, 51, 234, 0.05);
        }
        .space-x-4>:not([hidden])~:not([hidden]) {
    --tw-space-x-reverse: 0;
    margin-left: calc(1rem * var(--tw-space-x-reverse));
    margin-right: calc(1rem * calc(1 - var(--tw-space-x-reverse)));
}
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <!-- Top Navbar -->
    <header class="bg-white shadow-sm">
        <div class=" mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <!-- Logo and mobile menu toggle -->
                <div class="flex items-center">
                    <button id="sidebar-toggle" class="lg:hidden ml-3 text-gray-600 focus:outline-none">
                        <i class="ri-menu-line text-2xl"></i>
                    </button>
                    <a href="index.php" class="text-2xl font-bold text-purple-600">
                        <span class="lg:hidden">MH</span>
                        <span class="hidden lg:inline">QuickSite</span>
                    </a>
                </div>
                
                <!-- User menu -->
                <div class="relative">
                    <button id="user-menu-button" class="flex items-center text-gray-700 focus:outline-none">
                        <span class="hidden md:block ml-2"><?php echo htmlspecialchars($currentUser['full_name'] ?? ''); ?></span>
                        <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
                            <i class="ri-user-line text-purple-600"></i>
                        </div>
                        <i class="ri-arrow-down-s-line mr-1"></i>
                    </button>
                    
                    <!-- Dropdown menu -->
                    <div id="user-menu" class="absolute left-0 top-full mt-2 py-2 w-48 bg-white rounded-lg shadow-xl z-10 hidden">
                        <div class="px-4 py-2 border-b">
                            <p class="text-sm text-gray-500">מחובר כ:</p>
                            <p class="font-medium truncate"><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></p>
                        </div>
                        <a href="settings.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center">
                            <i class="ri-settings-3-line ml-2"></i>
                            הגדרות
                        </a>
                        <a href="billing.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center">
                            <i class="ri-bank-card-line ml-2"></i>
                            חיובים
                        </a>
                        <div class="border-t"></div>
                        <a href="<?php echo APP_URL; ?>/logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center">
                            <i class="ri-logout-box-line ml-2"></i>
                            התנתק
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <div class="flex flex-1">
        <!-- Sidebar -->
        <aside id="sidebar" class="lg:w-64 w-72 bg-white shadow-md lg:block fixed lg:relative inset-y-0 right-0 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out z-20">
            <?php include "sidebar.php"; ?>
        </aside>
        
        <!-- Overlay for mobile -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black opacity-50 z-10 hidden lg:hidden"></div>
        
        <!-- Main content -->
        <main class="flex-1 py-6 px-4">
            <div class="container mx-auto">
                <?php if ($trialExpired && isset($currentUser['plan_id']) && $currentUser['plan_id'] == 1): ?>
                    <div class="bg-yellow-100 border-r-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded">
                        <div class="flex items-center">
                            <i class="ri-error-warning-line ml-3 text-2xl text-yellow-500"></i>
                            <div>
                                <p class="font-bold">תקופת הנסיון שלך הסתיימה</p>
                                <p>על מנת להמשיך להשתמש במערכת, אנא <a href="billing.php" class="underline">שדרג את החשבון שלך</a>.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                        <span class="flex items-center">
                            <i class="ri-check-line ml-2 text-xl"></i>
                            <?php 
                                echo $_SESSION['success'];
                                unset($_SESSION['success']);
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                        <span class="flex items-center">
                            <i class="ri-error-warning-line ml-2 text-xl"></i>
                            <?php 
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
                
                <!-- Page header with title and actions -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800"><?php echo isset($pageTitle) ? $pageTitle : 'לוח בקרה'; ?></h1>
                        <?php if (isset($pageDescription)): ?>
                            <p class="text-gray-600"><?php echo $pageDescription; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (isset($primaryAction)): ?>
                        <a href="<?php echo $primaryAction['url']; ?>" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 flex items-center">
                            <?php if (isset($primaryAction['icon'])): ?>
                                <i class="<?php echo $primaryAction['icon']; ?> ml-2"></i>
                            <?php endif; ?>
                            <?php echo $primaryAction['text']; ?>
                        </a>
                    <?php endif; ?>
                </div>