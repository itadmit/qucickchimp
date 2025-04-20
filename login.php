<?php
require_once 'config/config.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect(APP_URL . '/admin/index.php');
}

$error = '';
$email = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error = 'נא למלא את כל השדות';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Password is correct, create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            
            // Redirect to admin dashboard
            redirect(APP_URL . '/admin/index.php');
        } else {
            $error = 'כתובת המייל או הסיסמה אינם נכונים';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>התחברות | <?php echo APP_NAME; ?></title>
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
                        <li><a href="register.php" class="bg-purple-600 text-white px-6 py-2 rounded-md hover:bg-purple-700">הרשמה</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <!-- Login Form -->
    <main class="flex-1 flex items-center justify-center py-12 px-4">
        <div class="max-w-md w-full bg-white rounded-xl shadow-md p-8">
            <h1 class="text-3xl font-bold text-center mb-8">התחברות</h1>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="flex items-center">
                        <i class="ri-error-warning-line ml-2 text-xl"></i>
                        <?php echo $error; ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-6">
                    <label for="email" class="block text-gray-700 font-medium mb-2">כתובת מייל</label>
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
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="text-gray-700 font-medium">סיסמה</label>
                        <a href="#" class="text-sm text-purple-600 hover:underline">שכחת סיסמה?</a>
                    </div>
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
                </div>
                
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="ml-2">
                        <span class="text-gray-700">זכור אותי</span>
                    </label>
                </div>
                
                <button type="submit" class="w-full bg-purple-600 text-white py-3 rounded-lg font-bold hover:bg-purple-700 flex justify-center items-center">
                    <i class="ri-login-circle-line ml-2"></i>
                    התחבר
                </button>
            </form>
            
            <div class="text-center mt-8">
                <p class="text-gray-600">עדיין אין לך חשבון? <a href="register.php" class="text-purple-600 hover:underline">הירשם עכשיו</a></p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t">
        <div class="container mx-auto px-4 py-6">
            <p class="text-center text-gray-500">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. כל הזכויות שמורות.</p>
        </div>
    </footer>
</body>
</html>