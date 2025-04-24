<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(isset($emailTemplate['title']) ? $emailTemplate['title'] : 'תבנית חדשה', ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>מערכת התאמה אישית לתבניות דוא"ל</h1>
        </header>
        
        <main>
            <div class="template-selector">
                <h2>בחר תבנית</h2>
                <select id="templateSelect">
                    <option value="">טוען תבניות...</option>
                </select>
            </div>
            
            <div id="templateContent" class="template-content">
                <!-- כאן יוצג תוכן התבנית -->
            </div>
            
            <div class="template-controls">
                <button id="saveTemplate" class="btn btn-primary">שמור שינויים</button>
                <button id="previewTemplate" class="btn btn-secondary">תצוגה מקדימה</button>
            </div>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> מערכת תבניות דוא"ל</p>
        </footer>
    </div>

    <!-- הוספת הסקריפטים -->
    <script src="js/template-loader.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const templateLoader = new TemplateLoader();
            const templateSelect = document.getElementById('templateSelect');
            
            try {
                // טעינת רשימת התבניות
                const templates = await templateLoader.getTemplatesList();
                templateSelect.innerHTML = templates.map(template => 
                    `<option value="${template.id}">${template.name}</option>`
                ).join('');
                
                // הוספת מאזין לשינוי בבחירת התבנית
                templateSelect.addEventListener('change', async (e) => {
                    if (e.target.value) {
                        try {
                            const template = await templateLoader.loadTemplate(e.target.value);
                            document.getElementById('templateContent').innerHTML = template.content;
                        } catch (error) {
                            alert('שגיאה בטעינת התבנית: ' + error.message);
                        }
                    }
                });
            } catch (error) {
                alert('שגיאה בטעינת רשימת התבניות: ' + error.message);
            }
        });
    </script>
</body>
</html> 