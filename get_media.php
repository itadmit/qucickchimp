<?php
/**
 * Get Media Files API
 * 
 * Returns a list of media files for a specific landing page
 */

require_once 'config/config.php';

// Check if user is logged in
requireLogin();

// Get user ID
$userId = $_SESSION['user_id'] ?? 0;

// Define response array
$response = [
    'success' => false,
    'message' => '',
    'files' => []
];

// Validate landing page ID
$landingPageId = isset($_GET['landing_page_id']) ? (int)$_GET['landing_page_id'] : 0;

if ($landingPageId <= 0) {
    $response['message'] = 'מזהה דף נחיתה חסר או לא תקין';
    outputResponse($response);
    exit;
}

// Verify landing page belongs to user and get slug
try {
    $stmt = $pdo->prepare("SELECT slug FROM landing_pages WHERE id = ? AND user_id = ?");
    $stmt->execute([$landingPageId, $userId]);
    $landingPage = $stmt->fetch();
    
    if (!$landingPage) {
        $response['message'] = 'דף הנחיתה לא נמצא או שאין לך הרשאה לגשת אליו';
        outputResponse($response);
        exit;
    }
    
    $slug = $landingPage['slug'];
    
    // Get media files from database
    try {
        $stmt = $pdo->prepare("
            SELECT id, filename, file_path, file_type, file_size, created_at
            FROM media
            WHERE user_id = ? AND landing_page_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId, $landingPageId]);
        $mediaFiles = $stmt->fetchAll();
        
        if (!empty($mediaFiles)) {
            foreach ($mediaFiles as $file) {
                // Check if the file_path is an S3 URL
                $url = (strpos($file['file_path'], 's3.') !== false) ? 
                       $file['file_path'] : 
                       getFileUrl($slug, $file['filename']);
                
                $response['files'][] = [
                    'id' => $file['id'],
                    'filename' => $file['filename'],
                    'url' => $url,
                    'type' => $file['file_type'],
                    'size' => formatFileSize($file['file_size']),
                    'created_at' => $file['created_at']
                ];
            }
        }
        
        // Check if media table doesn't exist or no DB entries
        if (empty($response['files'])) {
            // אם S3 מופעל, נסה לקבל קבצים מ-S3
            if (defined('USE_S3_STORAGE') && USE_S3_STORAGE) {
                require_once ROOT_PATH . '/includes/s3.php';
                $s3 = getS3Client();
                
                try {
                    $objects = $s3->listObjects([
                        'Bucket' => S3_BUCKET,
                        'Prefix' => $slug . '/'
                    ]);
                    
                    foreach ($objects['Contents'] as $object) {
                        $filename = basename($object['Key']);
                        $fileInfo = pathinfo($filename);
                        $extension = strtolower($fileInfo['extension'] ?? '');
                        
                        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                            $response['files'][] = [
                                'id' => 0,
                                'filename' => $filename,
                                'url' => S3_URL . '/' . $object['Key'],
                                'type' => 'image/' . $extension,
                                'size' => formatFileSize($object['Size']),
                                'created_at' => $object['LastModified']->format('Y-m-d H:i:s')
                            ];
                        }
                    }
                } catch (Exception $e) {
                    error_log('S3 Error: ' . $e->getMessage());
                }
            }
            
            // אם אין קבצים מ-S3, נסה לקבל מהתיקייה המקומית
            if (empty($response['files'])) {
                $uploadDir = getUploadDir($slug);
                if (is_dir($uploadDir)) {
                    $files = scandir($uploadDir);
                    foreach ($files as $file) {
                        if ($file != '.' && $file != '..' && is_file($uploadDir . '/' . $file)) {
                            $fileInfo = pathinfo($file);
                            $extension = strtolower($fileInfo['extension'] ?? '');
                            
                            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                $filePath = $uploadDir . '/' . $file;
                                $response['files'][] = [
                                    'id' => 0,
                                    'filename' => $file,
                                    'url' => getFileUrl($slug, $file),
                                    'type' => mime_content_type($filePath),
                                    'size' => formatFileSize(filesize($filePath)),
                                    'created_at' => date('Y-m-d H:i:s', filemtime($filePath))
                                ];
                            }
                        }
                    }
                }
            }
        }
        
        $response['success'] = true;
        
    } catch (PDOException $e) {
        error_log('Media query error: ' . $e->getMessage());
        $response['message'] = 'שגיאה בגישה למסד הנתונים: ' . $e->getMessage();
    }
    
} catch (PDOException $e) {
    error_log('Landing page query error: ' . $e->getMessage());
    $response['message'] = 'שגיאה בגישה למסד הנתונים: ' . $e->getMessage();
}

outputResponse($response);

/**
 * Get upload directory path for a specific landing page
 * 
 * @param string $slug Slug of the landing page
 * @return string Path to upload directory
 */
function getUploadDir($slug) {
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
    return APP_URL . '/uploads/' . $slug . '/' . $filename;
}

/**
 * Format file size in human-readable format
 * 
 * @param int $bytes File size in bytes
 * @return string Formatted file size
 */
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
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