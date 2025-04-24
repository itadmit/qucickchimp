<?php
/**
 * File Upload Handler
 * 
 * Handles image uploads for landing pages, converts them to WebP format,
 * and stores them in a designated folder structure.
 */

require_once 'config/config.php';

// Check if user is logged in
requireLogin();

// Get user ID
$userId = $_SESSION['user_id'] ?? 0;

// Define allowed file types
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

// Define response array
$response = [
    'success' => false,
    'message' => '',
    'file_url' => ''
];

// Check if it's a POST request with a file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    
    // Validate landing page ID and slug
    $landingPageId = isset($_POST['landing_page_id']) ? (int)$_POST['landing_page_id'] : 0;
    $slug = '';
    
    // Verify landing page belongs to user and get slug
    if ($landingPageId) {
        try {
            $stmt = $pdo->prepare("SELECT slug FROM landing_pages WHERE id = ? AND user_id = ?");
            $stmt->execute([$landingPageId, $userId]);
            $landingPage = $stmt->fetch();
            
            if ($landingPage) {
                $slug = $landingPage['slug'];
            } else {
                $response['message'] = 'דף הנחיתה לא נמצא או שאין לך הרשאה לערוך אותו';
                outputResponse($response);
                exit;
            }
        } catch (PDOException $e) {
            $response['message'] = 'שגיאה בגישה למסד הנתונים';
            outputResponse($response);
            exit;
        }
    } else {
        $response['message'] = 'מזהה דף נחיתה חסר';
        outputResponse($response);
        exit;
    }
    
    // Check file for errors
    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = getUploadErrorMessage($_FILES['file']['error']);
        outputResponse($response);
        exit;
    }
    
    // Check file size
    if ($_FILES['file']['size'] > $maxFileSize) {
        $response['message'] = 'הקובץ גדול מדי. גודל מקסימלי הוא 5MB';
        outputResponse($response);
        exit;
    }
    
    // Check file type
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $fileType = finfo_file($fileInfo, $_FILES['file']['tmp_name']);
    finfo_close($fileInfo);
    
    if (!in_array($fileType, $allowedTypes)) {
        $response['message'] = 'סוג הקובץ אינו נתמך. רק קבצי תמונה מסוג JPG, PNG, GIF או WebP מותרים';
        outputResponse($response);
        exit;
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = getUploadDir($slug);
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $filename = generateUniqueFilename($uploadDir);
    $webpFilePath = $uploadDir . '/' . $filename . '.webp';
    
    // Convert to WebP and save
    if (convertToWebP($_FILES['file']['tmp_name'], $webpFilePath, $fileType)) {
        // Generate URL for the file
        $fileUrl = getFileUrl($slug, $filename . '.webp');
        
        // Upload to S3 if enabled
        if (defined('USE_S3_STORAGE') && USE_S3_STORAGE) {
            require_once ROOT_PATH . '/includes/s3.php';
            
            // Generate S3 key
            $s3Key = $slug . '/' . $filename . '.webp';
            
            // Upload to S3
            $s3Url = uploadToS3($webpFilePath, $s3Key);
            
            if ($s3Url) {
                $fileUrl = $s3Url;
                // Delete local file after successful S3 upload
                unlink($webpFilePath);
            }
        }
        
        // Save file information to database
        try {
            $stmt = $pdo->prepare("
                INSERT INTO media 
                (user_id, landing_page_id, filename, file_path, file_type, file_size, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $userId,
                $landingPageId,
                $filename . '.webp',
                $fileUrl, // Store the S3 URL instead of local path
                'image/webp',
                filesize($webpFilePath)
            ]);
            
            $response['success'] = true;
            $response['message'] = 'הקובץ הועלה בהצלחה';
            $response['file_url'] = $fileUrl;
            
        } catch (PDOException $e) {
            // If table doesn't exist, just return the file URL without saving to DB
            $response['success'] = true;
            $response['message'] = 'הקובץ הועלה בהצלחה';
            $response['file_url'] = $fileUrl;
        }
    } else {
        $response['message'] = 'שגיאה בהמרת הקובץ לפורמט WebP';
    }
} else {
    $response['message'] = 'לא נשלח קובץ';
}

outputResponse($response);

/**
 * Convert image to WebP format
 * 
 * @param string $sourcePath Path to source image file
 * @param string $destPath Path where WebP file should be saved
 * @param string $sourceType MIME type of source image
 * @return bool True if conversion was successful, false otherwise
 */
function convertToWebP($sourcePath, $destPath, $sourceType) {
    // Check if GD is installed and WebP support is available
    if (!function_exists('imagecreatefromjpeg') || !function_exists('imagewebp')) {
        return false;
    }
    
    // Create image resource based on file type
    $image = null;
    switch ($sourceType) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($sourcePath);
            // Handle transparency
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            // Already WebP, just copy the file
            return copy($sourcePath, $destPath);
        default:
            return false;
    }
    
    if (!$image) {
        return false;
    }
    
    // Save as WebP with 80% quality
    $result = imagewebp($image, $destPath, 80);
    
    // Free up memory
    imagedestroy($image);
    
    return $result;
}

/**
 * Get upload directory path for a specific landing page
 * 
 * @param string $slug Slug of the landing page
 * @return string Path to upload directory
 */
function getUploadDir($slug) {
    // In production, this would be S3 or other cloud storage
    // For development, we use local directory
    return ROOT_PATH . '/uploads/' . $slug;
}

/**
 * Get public URL for an uploaded file
 * 
 * @param string $slug Slug of the landing page
 * @param string $filename Name of the file
 * @return string Public URL for the file
 */
function getFileUrl($slug, $filename) {
    // In production, this would be S3 or other cloud storage URL
    // For development, we use local URL
    return APP_URL . '/uploads/' . $slug . '/' . $filename;
}

/**
 * Generate a unique filename
 * 
 * @param string $dir Directory where file will be saved
 * @return string Unique filename (without extension)
 */
function generateUniqueFilename($dir) {
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    $filename = $timestamp . '_' . $random;
    
    // Ensure filename is unique in the directory
    while (file_exists($dir . '/' . $filename . '.webp')) {
        $random = bin2hex(random_bytes(8));
        $filename = $timestamp . '_' . $random;
    }
    
    return $filename;
}

/**
 * Get human-readable error message for upload errors
 * 
 * @param int $errorCode PHP upload error code
 * @return string Human-readable error message
 */
function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return 'הקובץ גדול מהמותר בהגדרות השרת';
        case UPLOAD_ERR_FORM_SIZE:
            return 'הקובץ גדול מהמותר בטופס';
        case UPLOAD_ERR_PARTIAL:
            return 'הקובץ הועלה באופן חלקי בלבד';
        case UPLOAD_ERR_NO_FILE:
            return 'לא הועלה קובץ';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'תיקיית קבצים זמניים חסרה בשרת';
        case UPLOAD_ERR_CANT_WRITE:
            return 'שגיאה בכתיבת הקובץ לדיסק';
        case UPLOAD_ERR_EXTENSION:
            return 'העלאת הקובץ נעצרה על ידי הרחבה';
        default:
            return 'שגיאה לא ידועה בהעלאת הקובץ';
    }
}

/**
 * Output JSON response and exit
 * 
 * @param array $response Response data
 */
function outputResponse($response) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>