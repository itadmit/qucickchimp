<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>עורך תבניות מייל</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="assets/css/components/sidebar.css" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                        secondary: '#4f46e5',
                        sidebar: '#f8fafc',
                        header: '#ffffff',
                        main: '#ffffff',
                        border: '#e2e8f0'
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: "Noto Sans Hebrew", sans-serif;
            background-color: #f5f7fb;
        }
        
        .header-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 0.375rem;
            transition: all 0.2s;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            border: 1px solid transparent;
        }
        
        .header-button-primary {
            background-color: #6366f1;
            color: white;
            border-color: #4f46e5;
        }
        
        .header-button-primary:hover {
            background-color: #4f46e5;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header-button-secondary {
            background-color: white;
            color: #374151;
            border-color: #e5e7eb;
        }
        
        .header-button-secondary:hover {
            background-color: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>
    <!-- ראש העמוד -->
    <header class="bg-white border-b border-border px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-6">
            <h1 class="text-xl font-bold">עורך תבניות מייל</h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="#" class="header-button header-button-secondary" id="previewBtn">
                <i class="ri-eye-line"></i>
                <span>תצוגה מקדימה</span>
            </a>
            <button class="header-button header-button-secondary" id="loadTemplateBtn">
                <i class="ri-template-line"></i>
                <span>טען תבנית</span>
            </button>
            <button class="header-button header-button-primary" id="saveBtn">
                <i class="ri-save-line"></i>
                <span>שמור תבנית</span>
            </button>
        </div>
    </header>

    <div class="grid grid-cols-[280px_1fr_300px] h-[calc(100vh-60px)]">
        <!-- תפריט צדדי -->
        <div class="bg-white border-l border-border">
            <div class="p-4">
                <h2 class="text-sm font-semibold text-gray-500 uppercase mb-4">סקשנים</h2>
                <div id="sectionsList" class="space-y-1"></div>
            </div>
        </div>

        <!-- תצוגה מקדימה -->
        <div class="p-8 flex flex-col items-center overflow-y-auto bg-gray-50">
            <div id="preview" class="bg-white shadow-sm rounded-lg w-full max-w-2xl min-h-[500px] p-6 mb-8"></div>
        </div>

        <!-- הגדרות -->
        <div class="bg-white border-r border-border overflow-y-auto">
            <div class="p-4">
                <h2 class="text-sm font-semibold text-gray-500 uppercase mb-4">הגדרות</h2>
                <div id="settings" class="space-y-4"></div>
            </div>
        </div>

        <!-- דיאלוג שמירת תבנית -->
        <div id="saveTemplateOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50"></div>
        <div id="saveTemplateDialog" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-[400px] shadow-xl">
                <h3 class="text-xl font-semibold mb-4">שמירת תבנית</h3>
                <input type="text" id="templateName" placeholder="שם התבנית" class="w-full border border-gray-300 rounded-lg p-2 mb-4 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                <div class="flex justify-end gap-2">
                    <button class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors" id="cancelSaveTemplate">ביטול</button>
                    <button class="px-4 py-2 text-white bg-primary rounded-lg hover:bg-secondary transition-colors" id="confirmSaveTemplate">שמירה</button>
                </div>
            </div>
        </div>

        <!-- דיאלוג טעינת תבנית -->
        <div id="loadTemplateOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50"></div>
        <div id="loadTemplateDialog" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-[800px] shadow-xl max-h-[80vh] flex flex-col">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold">בחר תבנית</h3>
                    <button class="text-gray-500 hover:text-gray-700" id="cancelLoadTemplate">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>
                <div class="overflow-y-auto flex-grow" id="templatesContainer">
                    <!-- כאן יוצגו התבניות -->
                </div>
            </div>
        </div>

        <!-- דיאלוג הוספת סקשן -->
        <div id="addSectionOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50"></div>
        <div id="addSectionDialog" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-[600px] shadow-xl">
                <h3 class="text-xl font-semibold mb-4">בחר סקשן להוספה</h3>
                <div class="grid grid-cols-3 gap-4">
                    <button class="section-button" data-type="heading">
                        <i class="ri-heading text-2xl mb-2"></i>
                        <span>כותרת</span>
                    </button>
                    <button class="section-button" data-type="text">
                        <i class="ri-text text-2xl mb-2"></i>
                        <span>טקסט</span>
                    </button>
                    <button class="section-button" data-type="image">
                        <i class="ri-image-2-line text-2xl mb-2"></i>
                        <span>תמונה</span>
                    </button>
                    <button class="section-button" data-type="button">
                        <i class="ri-cursor-line text-2xl mb-2"></i>
                        <span>כפתור</span>
                    </button>
                    <button class="section-button" data-type="divider">
                        <i class="ri-separator text-2xl mb-2"></i>
                        <span>קו מפריד</span>
                    </button>
                    <button class="section-button" data-type="social">
                        <i class="ri-share-line text-2xl mb-2"></i>
                        <span>רשתות חברתיות</span>
                    </button>
                </div>
                <div class="flex justify-end mt-6">
                    <button class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors" id="cancelAddSection">ביטול</button>
                </div>
            </div>
        </div>
    </div>
    
    <script type="module" src="assets/js/sections.js"></script>
    <script type="module" src="assets/js/templates.js"></script>
    <script type="module" src="assets/js/email-editor.js"></script>
</body>
</html> 