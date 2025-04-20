<div class="h-full flex flex-col">
    <!-- User info -->
    <div class="p-4 border-b">
        <div class="flex items-center mb-4">
            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                <i class="ri-user-line text-xl text-purple-600"></i>
            </div>
            <div class="mr-3">
                <p class="font-medium"><?php echo htmlspecialchars($currentUser['full_name'] ?? ''); ?></p>
                <div class="flex items-center text-sm text-purple-600">
                    <i class="ri-vip-crown-line ml-1"></i>
                    <?php echo htmlspecialchars($planName); ?>
                </div>
            </div>
        </div>
        
        <?php if ((!$trialExpired || ($currentUser && isset($currentUser['plan_id']) && $currentUser['plan_id'] > 1))): ?>
            <div class="bg-purple-50 rounded p-2 text-center">
                <?php if (!$trialExpired && isset($currentUser['plan_id']) && $currentUser['plan_id'] == 1 && isset($currentUser['trial_ends_at'])): ?>
                    <p class="text-sm text-purple-700">
                        <i class="ri-time-line ml-1"></i>
                        נותרו <?php echo ceil((strtotime($currentUser['trial_ends_at']) - time()) / 86400); ?> ימים לנסיון
                    </p>
                <?php else: ?>
                    <p class="text-sm text-purple-700">
                        <i class="ri-check-double-line ml-1"></i>
                        החשבון שלך פעיל
                    </p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <a href="billing.php" class="block w-full bg-purple-600 text-white py-2 px-4 rounded text-center text-sm">
                <i class="ri-arrow-up-circle-line ml-1"></i>
                שדרג עכשיו
            </a>
        <?php endif; ?>
    </div>
    
    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-4">
        <ul>
            <li>
                <a href="index.php" class="sidebar-link flex items-center px-4 py-3 text-gray-700 <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>">
                    <i class="ri-dashboard-line ml-3 text-xl"></i>
                    <span>לוח בקרה</span>
                </a>
            </li>
            
            <li>
                <a href="landing_pages.php" class="sidebar-link flex items-center px-4 py-3 text-gray-700 <?php echo $currentPage == 'landing_pages.php' ? 'active' : ''; ?>">
                    <i class="ri-layout-4-line ml-3 text-xl"></i>
                    <span>דפי נחיתה</span>
                </a>
            </li>
            
            <li>
                <a href="subscribers.php" class="sidebar-link flex items-center px-4 py-3 text-gray-700 <?php echo $currentPage == 'subscribers.php' ? 'active' : ''; ?>">
                    <i class="ri-user-follow-line ml-3 text-xl"></i>
                    <span>מנויים</span>
                </a>
            </li>
            
            <li>
                <a href="campaigns.php" class="sidebar-link flex items-center px-4 py-3 text-gray-700 <?php echo $currentPage == 'campaigns.php' ? 'active' : ''; ?>">
                    <i class="ri-mail-send-line ml-3 text-xl"></i>
                    <span>קמפיינים</span>
                </a>
            </li>
            
            <li>
                <a href="reports.php" class="sidebar-link flex items-center px-4 py-3 text-gray-700 <?php echo $currentPage == 'reports.php' ? 'active' : ''; ?>">
                    <i class="ri-line-chart-line ml-3 text-xl"></i>
                    <span>דוחות</span>
                </a>
            </li>
        </ul>
        
        <div class="border-t border-gray-200 my-4"></div>
        
        <ul>
            <li>
                <a href="settings.php" class="sidebar-link flex items-center px-4 py-3 text-gray-700 <?php echo $currentPage == 'settings.php' ? 'active' : ''; ?>">
                    <i class="ri-settings-3-line ml-3 text-xl"></i>
                    <span>הגדרות</span>
                </a>
            </li>
            
            <li>
                <a href="billing.php" class="sidebar-link flex items-center px-4 py-3 text-gray-700 <?php echo $currentPage == 'billing.php' ? 'active' : ''; ?>">
                    <i class="ri-bank-card-line ml-3 text-xl"></i>
                    <span>חיובים ותשלומים</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- Footer -->
    <div class="border-t p-4">
        <div class="flex items-center justify-between text-sm text-gray-500">
            <span>גרסה <?php echo APP_VERSION; ?></span>
            <a href="<?php echo APP_URL; ?>/logout.php" class="hover:text-red-500 flex items-center">
                <i class="ri-logout-box-line ml-1"></i>
                התנתק
            </a>
        </div>
    </div>
</div>

<script>
    // Toggle user dropdown
    document.getElementById('user-menu-button').addEventListener('click', function() {
        document.getElementById('user-menu').classList.toggle('hidden');
    });
    
    // Hide dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const userMenu = document.getElementById('user-menu');
        const userMenuButton = document.getElementById('user-menu-button');
        
        if (!userMenuButton.contains(event.target) && !userMenu.contains(event.target)) {
            userMenu.classList.add('hidden');
        }
    });
    
    // Mobile sidebar toggle
    document.getElementById('sidebar-toggle').addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    });
    
    // Close sidebar when clicking overlay
    document.getElementById('sidebar-overlay').addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    });
</script>