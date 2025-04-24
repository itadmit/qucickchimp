<?php
require_once '../config/config.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'מזהה קמפיין לא סופק לשכפול';
    redirect('campaigns.php');
}

$campaignId = (int)$_GET['id'];
$userId = $_SESSION['user_id'] ?? 0;

// Verify user has permission to view this campaign
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

if (!$campaign) {
    $_SESSION['error'] = 'הקמפיין המבוקש לא נמצא או שאין לך הרשאות לשכפל אותו';
    redirect('campaigns.php');
}

// Create a duplicate campaign
try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Format the new campaign name
    $newName = $campaign['name'] . ' (עותק)';
    
    // Insert the duplicate campaign
    $stmt = $pdo->prepare("
        INSERT INTO campaigns (
            user_id, name, subject, content, status, created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, 'draft', NOW(), NOW()
        )
    ");
    
    $stmt->execute([
        $userId,
        $newName,
        $campaign['subject'],
        $campaign['content']
    ]);
    
    $newCampaignId = $pdo->lastInsertId();
    
    // Copy campaign lists
    $stmt = $pdo->prepare("
        SELECT list_id FROM campaign_lists
        WHERE campaign_id = ?
    ");
    $stmt->execute([$campaignId]);
    $campaignLists = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($campaignLists)) {
        $insertListStmt = $pdo->prepare("
            INSERT INTO campaign_lists (campaign_id, list_id, created_at) 
            VALUES (?, ?, NOW())
        ");
        
        foreach ($campaignLists as $listId) {
            $insertListStmt->execute([$newCampaignId, $listId]);
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    $_SESSION['success'] = 'הקמפיין שוכפל בהצלחה';
    redirect('campaign_edit.php?id=' . $newCampaignId);
    
} catch (PDOException $e) {
    // Rollback on error
    $pdo->rollBack();
    $_SESSION['error'] = 'אירעה שגיאה בשכפול הקמפיין: ' . $e->getMessage();
    redirect('campaigns.php');
} 