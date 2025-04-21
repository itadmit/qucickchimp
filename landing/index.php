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
    $isAjaxSubmit = isset($_POST['ajax_submit']);
    
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
            
            // Extract list ID if form is connected to a contact list
            $listId = null;
            preg_match('/<form[^>]*data-list-id=["\']([^"\']*)["\'][^>]*>/i', $landingPage['content'], $listMatches);
            if (isset($listMatches[1]) && !empty($listMatches[1])) {
                $listId = $listMatches[1];
                error_log('Found data-list-id in the form HTML: ' . $listId);
            }

            // חילוץ מזהה הטופס
            $formId = null;
            preg_match('/<form[^>]*data-form-id=["\']([^"\']*)["\'][^>]*>/i', $landingPage['content'], $formIdMatches);
            if (isset($formIdMatches[1]) && !empty($formIdMatches[1])) {
                $formId = $formIdMatches[1];
                error_log('Found data-form-id in the form HTML: ' . $formId);
            }

            // חילוץ נתוני וובהוק
            $webhookUrl = null;
            preg_match('/<form[^>]*data-webhook-url=["\']([^"\']*)["\'][^>]*>/i', $landingPage['content'], $webhookMatches);
            if (isset($webhookMatches[1]) && !empty($webhookMatches[1])) {
                $webhookUrl = $webhookMatches[1];
                error_log('Found data-webhook-url in the form HTML: ' . $webhookUrl);
            }

            // חילוץ הודעת תודה מותאמת אישית
            $thankYouMessage = null;
            preg_match('/<form[^>]*data-thank-you-message=["\']([^"\']*)["\'][^>]*>/i', $landingPage['content'], $thankYouMatches);
            if (isset($thankYouMatches[1]) && !empty($thankYouMatches[1])) {
                $thankYouMessage = $thankYouMatches[1];
                error_log('Found data-thank-you-message in the form HTML: ' . $thankYouMessage);
            }

            // חילוץ כתובות מייל להתראות
            $notificationEmails = null;
            preg_match('/<form[^>]*data-notification-emails=["\']([^"\']*)["\'][^>]*>/i', $landingPage['content'], $notificationMatches);
            if (isset($notificationMatches[1]) && !empty($notificationMatches[1])) {
                $notificationEmails = $notificationMatches[1];
                error_log('Found data-notification-emails in the form HTML: ' . $notificationEmails);
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
                // Add subscriber to list if list ID was found in the form
                if ($listId) {
                    // Get the subscriber's ID
                    $subscriberId = $existingSubscriber ? $existingSubscriber['id'] : $pdo->lastInsertId();
                    
                    // Check if subscriber is already in the list
                    $stmt = $pdo->prepare("SELECT id FROM subscriber_lists WHERE list_id = ? AND subscriber_id = ?");
                    $stmt->execute([$listId, $subscriberId]);
                    $existingListSubscriber = $stmt->fetch();
                    
                    if (!$existingListSubscriber) {
                        // Add subscriber to list
                        $stmt = $pdo->prepare("
                            INSERT INTO subscriber_lists 
                            (list_id, subscriber_id, created_at) 
                            VALUES (?, ?, NOW())
                        ");
                        $stmt->execute([$listId, $subscriberId]);
                        error_log('Added subscriber ' . $subscriberId . ' to list ' . $listId);
                    } else {
                        error_log('Subscriber ' . $subscriberId . ' already in list ' . $listId);
                    }
                }
                
                // שליחת התראה במייל אם הוגדרו כתובות מייל להתראות
                if ($notificationEmails) {
                    $emailAddresses = explode(',', $notificationEmails);
                    $emailAddresses = array_map('trim', $emailAddresses);
                    
                    foreach ($emailAddresses as $emailAddress) {
                        if (filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                            // נושא המייל
                            $subject = 'התראה על פנייה חדשה מדף הנחיתה: ' . $landingPage['title'];
                            
                            // תוכן המייל
                            $message = "
                                <html>
                                <head>
                                    <title>פנייה חדשה מדף הנחיתה</title>
                                    <style>
                                        body { font-family: Arial, sans-serif; direction: rtl; }
                                        .container { padding: 20px; }
                                        table { border-collapse: collapse; width: 100%; }
                                        th, td { padding: 8px; text-align: right; border-bottom: 1px solid #ddd; }
                                        th { background-color: #f2f2f2; }
                                    </style>
                                </head>
                                <body>
                                    <div class='container'>
                                        <h2>התקבלה פנייה חדשה מדף הנחיתה</h2>
                                        <p>להלן פרטי הפנייה:</p>
                                        <table>
                                            <tr>
                                                <th>שדה</th>
                                                <th>ערך</th>
                                            </tr>
                                            <tr>
                                                <td>אימייל</td>
                                                <td>$email</td>
                                            </tr>";
                            
                            // הוספת שדות נוספים אם קיימים
                            if (!empty($firstName)) {
                                $message .= "<tr><td>שם</td><td>$firstName</td></tr>";
                            }
                            if (!empty($lastName)) {
                                $message .= "<tr><td>שם משפחה</td><td>$lastName</td></tr>";
                            }
                            if (!empty($phone)) {
                                $message .= "<tr><td>טלפון</td><td>$phone</td></tr>";
                            }
                            
                            // הוספת שדות מותאמים אישית
                            if (!empty($customFields)) {
                                foreach ($customFields as $key => $value) {
                                    if ($key !== '_form_tags') {
                                        $message .= "<tr><td>$key</td><td>$value</td></tr>";
                                    }
                                }
                            }
                            
                            $message .= "
                                        </table>
                                        <p>נשלח מדף הנחיתה: <a href='" . APP_URL . "/landing/$slug'>" . $landingPage['title'] . "</a></p>
                                    </div>
                                </body>
                                </html>
                            ";
                            
                            // כותרות המייל
                            $headers = "MIME-Version: 1.0\r\n";
                            $headers .= "Content-type: text/html; charset=utf-8\r\n";
                            $headers .= "From: " . APP_EMAIL . "\r\n";
                            
                            // שליחת המייל
                            mail($emailAddress, $subject, $message, $headers);
                            error_log('Notification email sent to: ' . $emailAddress);
                        }
                    }
                }
                
                // שליחת נתונים לוובהוק אם הוגדר
                if ($webhookUrl) {
                    // הכנת נתונים לשליחה
                    $webhookData = [
                        'email' => $email,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'phone' => $phone,
                        'custom_fields' => $customFields,
                        'landing_page_id' => $landingPage['id'],
                        'landing_page_title' => $landingPage['title'],
                        'timestamp' => time()
                    ];
                    
                    // המרה ל-JSON
                    $jsonData = json_encode($webhookData);
                    
                    // יצירת אובייקט cURL
                    $ch = curl_init($webhookUrl);
                    
                    // הגדרת אפשרויות cURL
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($jsonData)
                    ]);
                    
                    // ביצוע הבקשה
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    
                    // רישום לוג
                    error_log('Webhook response code: ' . $httpCode);
                    error_log('Webhook response: ' . $response);
                    
                    // סגירת אובייקט cURL
                    curl_close($ch);
                }
                
                // בבקשת AJAX, החזר רק את הודעת ההצלחה
                if ($isAjaxSubmit) {
                    // בדוק אם יש הודעת תודה מותאמת אישית
                    $successMsg = 'תודה! פרטיך התקבלו בהצלחה.';
                    
                    if ($thankYouMessage) {
                        $successMsg = htmlspecialchars($thankYouMessage);
                    }
                    
                    echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                            <span class="block sm:inline">' . $successMsg . '</span>
                          </div>';
                    exit;
                }
                
                // Successful submission - redirect to thank you page or the same page with a success parameter
                if (!empty($landingPage['redirect_url'])) {
                    redirect($landingPage['redirect_url']);
                } else {
                    redirect(APP_URL . '/landing/' . $slug . '?success=1');
                }
            } else {
                $formError = 'אירעה שגיאה בעת שליחת הטופס. אנא נסה שנית.';
                
                // בבקשת AJAX, החזר רק את הודעת השגיאה
                if ($isAjaxSubmit) {
                    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                            <span class="block sm:inline">' . $formError . '</span>
                          </div>';
                    exit;
                }
            }
        }
    }
    
    // בבקשת AJAX, החזר הודעת שגיאה אם יש
    if ($isAjaxSubmit && isset($formError)) {
        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <span class="block sm:inline">' . $formError . '</span>
              </div>';
        exit;
    }
}

// Check for success parameter
$showSuccessMessage = isset($_GET['success']) && $_GET['success'] == 1;

// Serve the landing page content
$pageContent = $landingPage['content'];

// Process the HTML to handle form submission
if ($showSuccessMessage) {
    // בדוק אם יש הודעת תודה מותאמת אישית
    $successMsg = 'תודה! פרטיך התקבלו בהצלחה.';
    
    // חיפוש הודעת תודה מותאמת אישית בדף
    preg_match('/<form[^>]*data-thank-you-message=["\']([^"\']*)["\'][^>]*>/i', $landingPage['content'], $thankYouMatches);
    if (isset($thankYouMatches[1]) && !empty($thankYouMatches[1])) {
        $successMsg = htmlspecialchars($thankYouMatches[1]);
    }
    
    // הכנת הודעת ההצלחה
    $successMessage = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                        <span class="block sm:inline">' . $successMsg . '</span>
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

// הוספת קוד JavaScript עבור שליחת טפסים באמצעות AJAX
echo <<<EOT
<script>
document.addEventListener('DOMContentLoaded', function() {
    // איתור כל הטפסים בדף
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        // בדיקה שלא מדובר בטופס עם תכונה מיוחדת שמונעת AJAX
        if (!form.hasAttribute('data-no-ajax')) {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // עצירת שליחת הטופס בדרך הרגילה
                
                // יצירת אובייקט FormData מהטופס
                const formData = new FormData(form);
                
                // הוספת סמן AJAX לבקשה
                formData.append('ajax_submit', '1');
                
                // הצגת אנימציית טעינה
                let loadingDiv = document.createElement('div');
                loadingDiv.className = 'ajax-loading';
                loadingDiv.innerHTML = '<div class="spinner"></div><p>שולח...</p>';
                loadingDiv.style.cssText = 'position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,0.8);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:1000;';
                form.style.position = 'relative';
                form.appendChild(loadingDiv);
                
                // שליחת הטופס באמצעות AJAX
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(html => {
                    // חיפוש הודעת ההצלחה בתשובה
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const successMessage = doc.querySelector('.bg-green-100');
                    
                    if (successMessage) {
                        // יצירת מיכל להודעת ההצלחה
                        const successContainer = document.createElement('div');
                        successContainer.className = 'success-message';
                        successContainer.innerHTML = successMessage.outerHTML;
                        
                        // החלפת הטופס בהודעת ההצלחה
                        form.innerHTML = '';
                        form.appendChild(successContainer);
                        
                        // גלילה חלקה אל הטופס כדי שהמשתמש יראה את ההודעה
                        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        
                        // הצגת אנימציה קלה להדגשת ההודעה
                        successContainer.style.animation = 'fadeIn 0.5s ease-in-out';
                    } else {
                        // בדיקה אם יש הודעת שגיאה
                        const errorMessage = doc.querySelector('.bg-red-100');
                        if (errorMessage) {
                            // אם כבר יש הודעת שגיאה בטופס, הסר אותה
                            const existingError = form.querySelector('.bg-red-100');
                            if (existingError) {
                                existingError.remove();
                            }
                            
                            // הוספת הודעת השגיאה בראש הטופס
                            form.insertBefore(errorMessage, form.firstChild);
                            
                            // גלילה חלקה אל הודעת השגיאה
                            errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        } else {
                            // אם אין הודעת שגיאה, הנח שהייתה בעיה בשרת
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4';
                            errorDiv.innerHTML = '<span class="block sm:inline">אירעה שגיאה בעת שליחת הטופס. אנא נסה שנית.</span>';
                            form.insertBefore(errorDiv, form.firstChild);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4';
                    errorDiv.innerHTML = '<span class="block sm:inline">אירעה שגיאה בעת שליחת הטופס. אנא נסה שנית.</span>';
                    form.insertBefore(errorDiv, form.firstChild);
                })
                .finally(() => {
                    // הסרת אנימציית הטעינה
                    if (loadingDiv && loadingDiv.parentNode) {
                        loadingDiv.parentNode.removeChild(loadingDiv);
                    }
                });
            });
        }
    });
    
    // הוספת סגנונות CSS לאנימציה
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(99, 102, 241, 0.2);
            border-radius: 50%;
            border-top-color: #6366f1;
            animation: spin 1s ease-in-out infinite;
            margin-bottom: 10px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
});
</script>
EOT;