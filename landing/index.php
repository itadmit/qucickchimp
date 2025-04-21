<?php
/**
 * Landing Page Processor
 * 
 * This script handles the rendering of landing pages and form submissions
 */

require_once '../config/config.php';

// Get landing page slug from URL
$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

if (empty($slug)) {
    // No slug provided, show 404
    header('HTTP/1.0 404 Not Found');
    echo '<h1>404 - דף הנחיתה לא נמצא</h1>';
    echo '<p>חזרה <a href="' . APP_URL . '">לדף הבית</a></p>';
    exit;
}

// Find landing page by slug
try {
    $stmt = $pdo->prepare("
        SELECT l.*, u.id as user_id 
        FROM landing_pages l
        JOIN users u ON l.user_id = u.id
        WHERE l.slug = ? AND l.is_active = 1
    ");
    $stmt->execute([$slug]);
    $landingPage = $stmt->fetch();
} catch (PDOException $e) {
    // Database error
    header('HTTP/1.0 500 Internal Server Error');
    echo '<h1>500 - שגיאת שרת</h1>';
    echo '<p>אירעה שגיאה בעת טעינת דף הנחיתה. אנא נסה שנית מאוחר יותר.</p>';
    exit;
}

if (!$landingPage) {
    // Landing page not found or not active
    header('HTTP/1.0 404 Not Found');
    echo '<h1>404 - דף הנחיתה לא נמצא</h1>';
    echo '<p>חזרה <a href="' . APP_URL . '">לדף הבית</a></p>';
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $firstName = sanitize($_POST['name'] ?? '');
    $lastName = sanitize($_POST['lastname'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    
    // Validate email (minimum requirement)
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formError = 'כתובת אימייל אינה תקינה';
    } else {
        // Check for plan limits
        if (hasReachedPlanLimits($pdo, $landingPage['user_id'], 'leads')) {
            // Silently log the error but don't show to the user
            error_log('User ' . $landingPage['user_id'] . ' has reached lead limit');
        } else {
            // Extract HTML content from the landing page to find form tags if needed
            $formTags = null;
            preg_match('/<form[^>]*data-tags=["\']([^"\']*)["\'][^>]*>/i', $landingPage['content'], $matches);
            if (isset($matches[1]) && !empty($matches[1])) {
                $formTags = $matches[1];
                error_log('Found data-tags in the form HTML: ' . $formTags);
            }
            
            // Extract custom fields from POST data
            $customFields = [];
            foreach ($_POST as $key => $value) {
                if ($key !== 'email' && $key !== 'name' && $key !== 'lastname' && $key !== 'phone' && $key !== 'submit') {
                    $customFields[$key] = sanitize($value);
                }
            }
            
            // אם יש תגיות, נוודא שהן נשמרות בצורה מיוחדת
            if (isset($_POST['tags']) && !empty($_POST['tags'])) {
                $tags = sanitize($_POST['tags']);
                // שמירת התגיות בשדה מותאם מיוחד לתגיות
                $customFields['_form_tags'] = $tags;
                
                // לוג לצורכי דיבאג
                error_log('Tags found in form submission: ' . $tags);
            } 
            // אם לא נמצאו תגיות בפוסט אבל יש לנו תגיות מהמאפיין data-tags
            else if ($formTags) {
                $customFields['_form_tags'] = $formTags;
                error_log('Using tags from data-tags attribute: ' . $formTags);
            }
            
            // לוג לצורכי דיבאג של כל הפוסט
            error_log('POST data: ' . print_r($_POST, true));
            error_log('Custom Fields: ' . print_r($customFields, true));
            
            // Check if subscriber already exists
            $stmt = $pdo->prepare("SELECT id FROM subscribers WHERE email = ? AND user_id = ?");
            $stmt->execute([$email, $landingPage['user_id']]);
            $existingSubscriber = $stmt->fetch();
            
            if ($existingSubscriber) {
                // Update existing subscriber
                $stmt = $pdo->prepare("
                    UPDATE subscribers 
                    SET landing_page_id = ?, first_name = ?, last_name = ?, phone = ?, 
                        custom_fields = ?, is_subscribed = 1, created_at = NOW()
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([
                    $landingPage['id'],
                    $firstName,
                    $lastName,
                    $phone,
                    !empty($customFields) ? json_encode($customFields) : null,
                    $existingSubscriber['id']
                ]);
            } else {
                // Insert new subscriber
                $stmt = $pdo->prepare("
                    INSERT INTO subscribers 
                    (user_id, landing_page_id, email, first_name, last_name, phone, custom_fields, is_subscribed, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
                ");
                
                $result = $stmt->execute([
                    $landingPage['user_id'],
                    $landingPage['id'],
                    $email,
                    $firstName,
                    $lastName,
                    $phone,
                    !empty($customFields) ? json_encode($customFields) : null
                ]);
            }
            
            if ($result) {
                // Successful submission - redirect to thank you page or the same page with a success parameter
                if (!empty($landingPage['redirect_url'])) {
                    redirect($landingPage['redirect_url']);
                } else {
                    redirect(APP_URL . '/landing/' . $slug . '?success=1');
                }
            } else {
                $formError = 'אירעה שגיאה בעת שליחת הטופס. אנא נסה שנית.';
            }
        }
    }
}

// Check for success parameter
$showSuccessMessage = isset($_GET['success']) && $_GET['success'] == 1;

// Serve the landing page content
$pageContent = $landingPage['content'];

// Process the HTML to handle form submission
if ($showSuccessMessage) {
    // Replace form with success message or show a notification
    $successMessage = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                        <span class="block sm:inline">תודה! פרטיך התקבלו בהצלחה.</span>
                       </div>';
                       
    // Look for form tags or specific elements to replace
    $pattern = '/<form[^>]*>.*?<\/form>/is';
    $replacement = $successMessage;
    
    // Try to replace the form with success message
    $formReplacedContent = preg_replace($pattern, $replacement, $pageContent);
    
    // Only use the replaced content if a form was actually found and replaced
    if ($formReplacedContent !== $pageContent) {
        $pageContent = $formReplacedContent;
    } else {
        // If no form tag found, try to inject the message at the beginning of the body
        $pattern = '/<body[^>]*>/i';
        $replacement = '$0' . $successMessage;
        $pageContent = preg_replace($pattern, $replacement, $pageContent);
    }
}

// Handle form errors
if (isset($formError)) {
    $errorMessage = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                      <span class="block sm:inline">' . $formError . '</span>
                     </div>';
    
    // Try to insert error message before the first form
    $pattern = '/<form[^>]*>/i';
    $replacement = $errorMessage . '$0';
    $pageContent = preg_replace($pattern, $replacement, $pageContent);
}

// Process form actions to handle submission to this script
$pattern = '/<form[^>]*action=["\'][^"\']*["\'][^>]*>/i';
$replacement = '<form action="' . APP_URL . '/landing/' . $slug . '" method="post">';
$pageContent = preg_replace($pattern, $replacement, $pageContent);

// Make sure form has method="post"
$pattern = '/<form[^>]*method=["\'][^"\']*["\'][^>]*>/i';
$formWithMethodRegex = preg_match($pattern, $pageContent);

if (!$formWithMethodRegex) {
    $pattern = '/<form[^>]*>/i';
    $replacement = '<form action="' . APP_URL . '/landing/' . $slug . '" method="post">';
    $pageContent = preg_replace($pattern, $replacement, $pageContent);
}

// Output the processed content
echo $pageContent;