<?php
require_once 'config/config.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect(APP_URL . '/admin/index.php');
}

$error = '';
$success = '';
$fullName = '';
$email = '';
$company = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $fullName = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $company = sanitize($_POST['company'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'נא למלא את כל שדות החובה';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'כתובת המייל אינה תקינה';
    } elseif (strlen($password) < 8) {
        $error = 'הסיסמה חייבת להכיל לפחות 8 תווים';
    } elseif ($password !== $confirmPassword) {
        $error = 'הסיסמאות אינן תואמות';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $emailExists = $stmt->fetchColumn();
        
        if ($emailExists) {
            $error = 'כתובת המייל כבר קיימת במערכת';
        } else {
            // Calculate trial end date (7 days from now)
            $trialEndsAt = date('Y-m-d H:i:s', strtotime('+' . TRIAL_DAYS . ' days'));
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $pdo->prepare("
                INSERT INTO users (email, password, full_name, company, plan_id, trial_ends_at) 
                VALUES (?, ?, ?, ?, 1, ?)
            ");
            
            try {
                $stmt->execute([$email, $hashedPassword, $fullName, $company, $trialEndsAt]);
                $userId = $pdo->lastInsertId();
                
                // Log user in
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $fullName;
                
                // Redirect to admin dashboard
                redirect(APP_URL . '/admin/index.php');
            } catch (PDOException $e) {
                $error = 'אירעה שגיאה בעת ההרשמה. אנא נסה שנית';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>הרשמה | <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500;700&display=swap');
        body {
            font-family: 'Heebo', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <a href="index.php" class="text-3xl font-bold text-purple-600">QuickSite</a>
                <nav>
                    <ul class="flex space-x-6">
                        <li><a href="index.php" class="text-gray-600 hover:text-purple-600">דף הבית</a></li>
                        <li><a href="login.php" class="text-gray-600 hover:text-purple-600">התחברות</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <!-- Registration Form -->
    <main class="flex-1 flex items-center justify-center py-12 px-4">
        <div class="max-w-lg w-full bg-white rounded-xl shadow-md p-8">
            <h1 class="text-3xl font-bold text-center mb-8">הרשמה ל-<?php echo APP_NAME; ?></h1>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="flex items-center">
                        <i class="ri-error-warning-line ml-2 text-xl"></i>
                        <?php echo $error; ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="flex items-center">
                        <i class="ri-check-line ml-2 text-xl"></i>
                        <?php echo $success; ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-6">
                    <label for="full_name" class="block text-gray-700 font-medium mb-2">שם מלא <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute right-0 inset-y-0 flex items-center pr-3 pointer-events-none text-gray-400">
                            <i class="ri-user-line"></i>
                        </div>
                        <input 
                            type="text" 
                            id="full_name" 
                            name="full_name" 
                            value="<?php echo htmlspecialchars($fullName); ?>"
                            class="w-full pr-10 px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            required
                        >
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="email" class="block text-gray-700 font-medium mb-2">כתובת מייל <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute right-0 inset-y-0 flex items-center pr-3 pointer-events-none text-gray-400">
                            <i class="ri-mail-line"></i>
                        </div>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?php echo htmlspecialchars($email); ?>"
                            class="w-full pr-10 px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            required
                        >
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="company" class="block text-gray-700 font-medium mb-2">שם החברה</label>
                    <div class="relative">
                        <div class="absolute right-0 inset-y-0 flex items-center pr-3 pointer-events-none text-gray-400">
                            <i class="ri-building-line"></i>
                        </div>
                        <input 
                            type="text" 
                            id="company" 
                            name="company" 
                            value="<?php echo htmlspecialchars($company); ?>"
                            class="w-full pr-10 px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        >
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 font-medium mb-2">סיסמה <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute right-0 inset-y-0 flex items-center pr-3 pointer-events-none text-gray-400">
                            <i class="ri-lock-line"></i>
                        </div>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="w-full pr-10 px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            required
                        >
                    </div>
                    <p class="text-sm text-gray-500 mt-1">הסיסמה חייבת להכיל לפחות 8 תווים</p>
                </div>
                
                <div class="mb-6">
                    <label for="confirm_password" class="block text-gray-700 font-medium mb-2">אימות סיסמה <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute right-0 inset-y-0 flex items-center pr-3 pointer-events-none text-gray-400">
                            <i class="ri-lock-line"></i>
                        </div>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="w-full pr-10 px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            required
                        >
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="terms" class="ml-2" required>
                        <span class="text-gray-700">אני מסכימ/ה ל<a href="#" class="text-purple-600 hover:underline">תנאי השימוש</a> ול<a href="#" class="text-purple-600 hover:underline">מדיניות הפרטיות</a></span>
                    </label>
                </div>
                
                <button type="submit" class="w-full bg-purple-600 text-white py-3 rounded-lg font-bold hover:bg-purple-700 flex justify-center items-center">
                    <i class="ri-user-add-line ml-2"></i>
                    הירשם והתחל נסיון חינם
                </button>
                
                <p class="text-center text-sm text-gray-500 mt-4">
                    <i class="ri-information-line align-middle ml-1"></i>
                    ללא צורך בכרטיס אשראי. נסיון חינם ל-7 ימים.
                </p>
            </form>
            
            <div class="text-center mt-8">
                <p class="text-gray-600">כבר יש לך חשבון? <a href="login.php" class="text-purple-600 hover:underline">התחבר כאן</a></p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t">
        <div class="container mx-auto px-4 py-6">
            <p class="text-center text-gray-500 flex justify-center items-center">
                <i class="ri-copyright-line ml-1"></i> 
                <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. כל הזכויות שמורות.
            </p>
        </div>
    </footer>
</body>
</html>