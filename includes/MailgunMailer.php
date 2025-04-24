<?php
/**
 * Mailgun Mailer
 * 
 * עטיפה עבור שליחת מיילים באמצעות Mailgun API
 */

class MailgunMailer {
    private $mailgun;
    private $domain;
    private $fromName;
    private $fromEmail;
    
    /**
     * יוצר אובייקט חדש של Mailgun Mailer
     * 
     * @param string $apiKey API key של Mailgun
     * @param string $domain הדומיין הראשי של Mailgun
     * @param string $fromName שם ברירת מחדל לשולח
     * @param string $fromEmail כתובת אימייל ברירת מחדל לשולח
     */
    public function __construct($apiKey, $domain, $fromName, $fromEmail) {
        // יצירת לקוח Mailgun
        $this->mailgun = Mailgun\Mailgun::create($apiKey);
        $this->domain = $domain;
        $this->fromName = $fromName;
        $this->fromEmail = $fromEmail;
    }
    
    /**
     * שולח אימייל באמצעות Mailgun API
     * 
     * @param string $to כתובת הנמען
     * @param string $subject נושא האימייל
     * @param string $message תוכן האימייל (HTML)
     * @param string $fromName שם השולח (אופציונלי)
     * @param string $fromEmail כתובת אימייל השולח (אופציונלי)
     * @param string $replyTo כתובת לתשובה (אופציונלי)
     * @param array $attachments קבצים מצורפים (אופציונלי)
     * @param array $customDomain דומיין מותאם אישית לשליחה (אופציונלי)
     * 
     * @return bool אמת אם נשלח בהצלחה, שקר אחרת
     */
    public function sendEmail($to, $subject, $message, $fromName = '', $fromEmail = '', $replyTo = '', $attachments = [], $customDomain = null) {
        try {
            // הגדרת השולח
            $fromName = $fromName ?: $this->fromName;
            $fromEmail = $fromEmail ?: $this->fromEmail;
            $from = "{$fromName} <{$fromEmail}>";
            
            // הגדרת הדומיין לשימוש (מותאם אישית או ברירת מחדל)
            $domain = $customDomain ?: $this->domain;
            
            // הגדרת הפרמטרים הבסיסיים לאימייל
            $params = [
                'from'    => $from,
                'to'      => $to,
                'subject' => $subject,
                'html'    => $message,
                'text'    => strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $message))
            ];
            
            // הוספת שדה Reply-To אם סופק
            if (!empty($replyTo)) {
                $params['h:Reply-To'] = $replyTo;
            }
            
            // הוספת קבצים מצורפים אם יש
            $attachmentFiles = [];
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    if (isset($attachment['path']) && file_exists($attachment['path'])) {
                        $attachmentFiles['attachment'][] = [
                            'filePath' => $attachment['path'],
                            'filename' => $attachment['name'] ?? basename($attachment['path'])
                        ];
                    }
                }
            }
            
            // שליחת האימייל
            $this->mailgun->messages()->send($domain, $params, $attachmentFiles);
            
            return true;
        } catch (Exception $e) {
            error_log('Mailgun sending error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * שולח אימייל מותאם אישית באמצעות Mailgun API
     * 
     * @param array $params כל הפרמטרים של האימייל (from, to, subject וכו')
     * @param string $customDomain דומיין מותאם אישית (אופציונלי)
     * 
     * @return bool אמת אם נשלח בהצלחה, שקר אחרת
     */
    public function sendCustomEmail($params, $customDomain = null) {
        try {
            // הגדרת הדומיין לשימוש (מותאם אישית או ברירת מחדל)
            $domain = $customDomain ?: $this->domain;
            
            // שליחת האימייל
            $this->mailgun->messages()->send($domain, $params);
            
            return true;
        } catch (Exception $e) {
            error_log('Mailgun sending error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * מוסיף דומיין חדש למערכת Mailgun (עבור מערכת מרובת לקוחות)
     * 
     * @param string $domain הדומיין להוספה
     * @param array $smtpCredentials אישורי SMTP (אופציונלי)
     * 
     * @return bool אמת אם הצליח, שקר אחרת
     */
    public function addDomain($domain, $smtpCredentials = []) {
        try {
            // הגדרות ברירת מחדל
            $options = [
                'web_scheme' => 'https',
                'spam_action' => 'tag',
                'wildcard' => true
            ];
            
            // הוספת הדומיין ל-Mailgun
            $this->mailgun->domains()->create($domain, $options);
            
            return true;
        } catch (Exception $e) {
            error_log('Mailgun domain creation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * מקבל את רשימת האימותים (DNS) שצריך להגדיר עבור דומיין
     * 
     * @param string $domain הדומיין
     * 
     * @return array רשימת הרשומות DNS
     */
    public function getDomainVerificationRecords($domain) {
        try {
            $domainInfo = $this->mailgun->domains()->show($domain);
            return $domainInfo->getReceivinDnsRecords();
        } catch (Exception $e) {
            error_log('Mailgun domain info error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * בודק אם הדומיין אומת
     * 
     * @param string $domain הדומיין לבדיקה
     * 
     * @return bool אמת אם הדומיין אומת, שקר אחרת
     */
    public function isDomainVerified($domain) {
        try {
            $domainInfo = $this->mailgun->domains()->show($domain);
            $state = $domainInfo->getDomain()->getState();
            return $state === 'active';
        } catch (Exception $e) {
            error_log('Mailgun domain verification check error: ' . $e->getMessage());
            return false;
        }
    }
} 