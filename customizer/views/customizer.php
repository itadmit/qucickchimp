<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>עורך דפי נחיתה | <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="../customizer/assets/css/style.css">
</head>
<body class="bg-gray-100">
    <!-- Header -->
<!-- Header - מאורגן מחדש -->
<header class="bg-white shadow">
    <div class="flex justify-between items-center px-4 py-3">
        <!-- צד ימין: כפתור חזרה, לוגו, ושם הדף -->
        <div class="flex items-center">
            <a href="../admin/landing_pages.php" class="text-gray-500 hover:text-purple-600 ml-3">
                <i class="ri-arrow-right-line text-xl"></i>
            </a>
            <div class="ml-3">
                <img src="../assets/images/logo.png" alt="Logo" class="h-8">
            </div>
            <div class="mx-4 border-r border-gray-300 h-6"></div>
            <h1 class="text-lg font-medium text-gray-800">
                עריכת דף נחיתה: <span class="font-bold"><?php echo htmlspecialchars($landingPage['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></span>
            </h1>
        </div>
        
        <!-- מרכז: כפתורי החלפת תצוגה -->
        <div class="flex border border-gray-300 rounded-md">
            <button id="desktop-view-btn" class="px-3 py-2 text-purple-600 bg-purple-50 hover:bg-purple-100 rounded-r-md flex items-center">
                <i class="ri-computer-line text-lg"></i>
            </button>
            <button id="tablet-view-btn" class="px-3 py-2 text-gray-700 hover:bg-gray-100 flex items-center border-r border-l border-gray-300">
                <i class="ri-tablet-line text-lg"></i>
            </button>
            <button id="mobile-view-btn" class="px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-l-md flex items-center">
                <i class="ri-smartphone-line text-lg"></i>
            </button>
        </div>
        
        <!-- צד שמאל: כפתורי פעולות -->
        <div class="flex items-center space-x-3 rtl:space-x-reverse">
            <!-- כפתור החלפת תבנית -->
            <button id="header-change-template-btn" class="px-3 py-2 border border-gray-300 rounded-md text-gray-700 bg-gray-50 hover:bg-gray-100 flex items-center">
                <i class="ri-layout-line ml-1"></i>
                החלפת תבנית
            </button>
            
            <!-- כפתור תצוגה מקדימה - עכשיו כפתור ולא לינק -->
            <button id="preview-button" class="px-3 py-2 border border-gray-300 rounded-md text-gray-700 bg-gray-50 hover:bg-gray-100 flex items-center">
                <i class="ri-external-link-line ml-1"></i>
                תצוגה מקדימה
            </button>
            
            <!-- כפתור שמירה -->
            <button id="save-button" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 flex items-center">
                <i class="ri-save-line ml-1"></i>
                שמור שינויים
            </button>
        </div>
    </div>
    <script src="../customizer/assets/js/upload.js"></script>
</header>
    
    <div class="flex">
        <!-- Right Panel - Sections List -->
        <div class="sections-panel w-64 bg-white shadow-md">
            <div class="p-4 border-b">
                <h2 class="font-bold text-gray-700">הסקשנים בדף</h2>
                <p class="text-sm text-gray-500">לחץ על סקשן כדי לערוך אותו</p>
            </div>
            
            <div class="overflow-y-auto" style="max-height: calc(100vh - 130px);">
                <div id="page-sections-list" class="p-0">
                    <!-- Will be populated by JS with current sections -->
                    <div class="p-4 text-center text-gray-500">
                        <i class="ri-loader-4-line animate-spin text-xl"></i>
                        <p class="mt-2">טוען סקשנים...</p>
                    </div>
                </div>
                
                <div class="p-4 border-t">
                    <button id="add-section-btn" class="w-full bg-purple-100 text-purple-700 hover:bg-purple-200 py-2 rounded flex items-center justify-center">
                        <i class="ri-add-line ml-1"></i>
                        הוסף סקשן חדש
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Main Content - Preview -->
        <div class="flex-1 preview-container">
            <iframe id="preview-iframe" class="preview-iframe"></iframe>
        </div>
        
        <!-- Left Panel - Section Editor -->
        <div class="settings-panel w-80 bg-white shadow-md">
            <div class="border-b">
                <div class="flex items-center p-4 border-b">
                    <button id="back-to-list-btn" class="text-gray-500 hover:text-gray-700 ml-2 hidden">
                        <i class="ri-arrow-right-line"></i>
                    </button>
                    <h2 class="font-bold text-gray-700" id="settings-panel-title">הגדרות כלליות</h2>
                </div>
                
                <div class="p-2">
 <div class="flex justify-center space-x-3 rtl:space-x-reverse">
<button id="section-editor-tab" class="px-4 py-2 rounded text-sm font-medium bg-purple-100 text-purple-700">
<i class="ri-edit-2-line ml-1"></i>
 עריכה
</button>
<button id="page-settings-tab" class="px-4 py-2 rounded text-sm font-medium text-gray-600 hover:bg-gray-100">
<i class="ri-settings-3-line ml-1"></i>
 הגדרות כלליות
</button>
<button id="add-section-tab" class="px-4 py-2 rounded text-sm font-medium text-gray-600 hover:bg-gray-100 hidden">
<i class="ri-add-line ml-1"></i>
 הוספה
</button>
</div>
</div>
            </div>
            
            <!-- Section Editor Panel -->
            <div id="section-editor-panel" class="overflow-y-auto p-4 hidden" style="max-height: calc(100vh - 140px);">
                <div id="section-controls" class="space-y-4">
                    <!-- Will be populated by JS when a section is selected -->
                    <div class="p-4 text-center text-gray-500">
                        <i class="ri-information-line text-xl mb-2"></i>
                        <p>בחר סקשן כדי לצפות בהגדרות שלו</p>
                    </div>
                </div>

                
                <div class="mt-6 pt-4 border-t">
                    <button id="remove-section-btn" class="w-full border border-red-300 text-red-600 hover:bg-red-50 py-2 rounded flex items-center justify-center">
                        <i class="ri-delete-bin-line ml-1"></i>
                        מחק סקשן זה
                    </button>
                </div>
            </div>
            
            <!-- Add Section Panel -->
            <div id="add-section-panel" class="overflow-y-auto p-4 hidden" style="max-height: calc(100vh - 140px);">
                <div class="mb-4">
                    <label for="section-group-filter" class="block text-sm font-medium text-gray-700 mb-1">סנן לפי קטגוריה</label>
                    <select id="section-group-filter" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                        <option value="">כל הקטגוריות</option>
                        <?php foreach ($sectionGroups as $groupId => $group): ?>
                        <option value="<?php echo $groupId; ?>"><?php echo $group['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="space-y-4" id="sections-list">
                    <?php foreach ($sectionGroups as $groupId => $group): ?>
                    <div class="section-group" data-group="<?php echo $groupId; ?>">
                        <h3 class="font-medium text-sm text-gray-600 mb-2"><?php echo $group['name']; ?></h3>
                        <div class="grid grid-cols-2 gap-2">
                            <?php foreach ($group['sections'] as $sectionId => $sectionName): ?>
                            <button class="section-button bg-gray-50 border rounded p-2 text-xs text-center hover:bg-gray-100" data-section="<?php echo $sectionId; ?>">
                            <div class="w-full h-10 bg-gray-200 mb-1 flex items-center justify-center rounded">
                                    <i class="ri-layout-3-line text-gray-400"></i>
                                </div>
                                <?php echo $sectionName; ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Page Settings Panel -->
            <div id="page-settings-panel" class="overflow-y-auto p-4" style="max-height: calc(100vh - 140px);">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">כותרת הדף</label>
                        <input type="text" id="page-title" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" value="<?php echo htmlspecialchars($landingPage['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ערכת צבעים</label>
                        <select id="color-scheme" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                            <?php foreach ($colorSchemes as $id => $scheme): ?>
                                <option value="<?php echo $id; ?>"><?php echo $scheme['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">צבע ראשי</label>
                        <div class="flex">
                            <input type="color" id="primary-color" class="h-10 w-10 border border-gray-300 rounded-md ml-2" value="#6366f1">
                            <input type="text" id="primary-color-text" class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" value="#6366f1" dir="ltr">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">צבע משני</label>
                        <div class="flex">
                            <input type="color" id="secondary-color" class="h-10 w-10 border border-gray-300 rounded-md ml-2" value="#4f46e5">
                            <input type="text" id="secondary-color-text" class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" value="#4f46e5" dir="ltr">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">פונט</label>
                        <select id="font-family" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                            <option value="Heebo, sans-serif">Heebo (ברירת מחדל)</option>
                            <option value="Assistant, sans-serif">Assistant</option>
                            <option value="Rubik, sans-serif">Rubik</option>
                            <option value="Open Sans Hebrew, sans-serif">Open Sans Hebrew</option>
                        </select>
                    </div>
                    
                    <div class="pt-4 border-t">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">תבניות</h3>
                        <button id="change-template-btn" class="w-full flex items-center justify-center px-3 py-2 border border-gray-300 rounded-md text-gray-700 bg-gray-50 hover:bg-gray-100">
                            <i class="ri-layout-line ml-1"></i>
                            החלף תבנית
                        </button>
                        
                        <!-- כפתורים נוספים -->
                        <button id="edit-html-btn" class="w-full flex items-center justify-center px-3 py-2 border border-gray-300 rounded-md text-gray-700 bg-gray-50 hover:bg-gray-100 mt-2">
                            <i class="ri-code-line ml-1"></i>
                            ערוך HTML
                        </button>
                        
                        <button id="import-section-btn" class="w-full flex items-center justify-center px-3 py-2 border border-gray-300 rounded-md text-gray-700 bg-gray-50 hover:bg-gray-100 mt-2">
                            <i class="ri-file-code-line ml-1"></i>
                            ייבא מקוד HTML
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- הוסף מודל לבחירת תבנית -->
<div id="templates-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-xl w-3/4 max-w-4xl">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="text-lg font-medium">החלף תבנית</h3>
            <button id="close-templates-modal" class="text-gray-500 hover:text-gray-700">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 max-h-96 overflow-y-auto">
            <?php foreach (getAvailableTemplates() as $template): ?>
            <div class="template-item border rounded-lg overflow-hidden hover:shadow-md cursor-pointer transition duration-300" data-template-id="<?php echo $template['id']; ?>">
                <div class="p-2 border-b bg-gray-50">
                    <?php 
                    $thumbnailPath = 'templates/' . $template['thumbnail'];
                    if (file_exists($thumbnailPath)): 
                    ?>
                        <img src="<?php echo $thumbnailPath; ?>" alt="<?php echo htmlspecialchars($template['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>" class="w-full h-32 object-contain">
                    <?php else: ?>
                        <div class="w-full h-32 bg-gray-100 flex items-center justify-center">
                            <i class="ri-layout-line text-4xl text-gray-400"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="p-3">
                    <h4 class="font-bold"><?php echo htmlspecialchars($template['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></h4>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="p-4 border-t flex justify-end">
            <button id="cancel-template-change" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                ביטול
            </button>
        </div>
    </div>
</div>
    
    <!-- Add Section Modal -->
    <div id="add-section-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl w-3/4 max-w-4xl">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-medium">הוסף סקשן</h3>
                <button id="close-add-section-modal" class="text-gray-500 hover:text-gray-700">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 max-h-96 overflow-y-auto">
                <!-- Content will be populated by JS -->
            </div>
            <div class="p-4 border-t flex justify-end">
                <button id="cancel-add-section" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                    ביטול
                </button>
            </div>
        </div>
    </div>


    <!-- Media Library Modal -->
    <div id="media-library-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl w-4/5 h-4/5 flex flex-col">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-medium">ספריית מדיה</h3>
                <button id="close-media-library" class="text-gray-500 hover:text-gray-700">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>
            <div class="flex flex-1 overflow-hidden">
                <!-- Left Column - Media Grid -->
                <div class="w-3/4 p-4 overflow-y-auto" id="media-grid-container">
                    <div class="mb-4 flex justify-between">
                        <h4 class="text-gray-700 font-medium">תמונות קיימות</h4>
                        <div class="flex">
                            <input type="text" id="media-search" placeholder="חיפוש..." class="px-3 py-1 border border-gray-300 rounded-md mr-2">
                            <button id="refresh-media" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded-md">
                                <i class="ri-refresh-line"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div id="media-grid" class="grid grid-cols-4 gap-4">
                        <!-- Media items will be loaded here -->
                        <div class="text-center py-10 text-gray-500 col-span-4">
                            <i class="ri-loader-4-line animate-spin text-3xl mb-2"></i>
                            <p>טוען תמונות...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column - Upload -->
                <div class="w-1/4 border-r p-4 bg-gray-50">
                    <h4 class="text-gray-700 font-medium mb-4">העלאת תמונה חדשה</h4>
                    
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center mb-4">
                        <div id="upload-preview" class="hidden mb-4">
                            <img src="" alt="תצוגה מקדימה" class="max-h-40 mx-auto">
                        </div>
                        
                        <div id="upload-placeholder">
                            <i class="ri-image-add-line text-4xl text-gray-400 mb-2"></i>
                            <p class="text-sm text-gray-500">גרור תמונה לכאן או לחץ לבחירה</p>
                        </div>
                        
                        <input type="file" id="media-upload-input" class="hidden" accept="image/jpeg,image/png,image/gif,image/webp">
                    </div>
                    
                    <button id="upload-media-button" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        העלאת תמונה
                    </button>
                    
                    <div class="mt-4 text-sm text-gray-500">
                        <p>פורמטים נתמכים: JPG, PNG, GIF, WebP</p>
                        <p>גודל מקסימלי: 5MB</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    
    <!-- HTML Editor Modal -->
    <div id="html-editor-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl w-4/5 h-4/5 flex flex-col">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-medium">עריכת HTML</h3>
                <button id="close-html-editor" class="text-gray-500 hover:text-gray-700">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>
            <div class="flex-1 overflow-hidden">
                <textarea id="html-editor"></textarea>
            </div>
            <div class="p-4 border-t flex justify-end">
                <button id="cancel-html-edit" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 ml-2">
                    ביטול
                </button>
                <button id="apply-html-edit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                    החל שינויים
                </button>
            </div>
        </div>
    </div>


    <!-- Import HTML Modal -->
    <div id="import-html-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl w-4/5 h-4/5 flex flex-col">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-medium">ייבא מקוד HTML</h3>
                <button id="close-import-html" class="text-gray-500 hover:text-gray-700">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>
            <div class="flex-1 overflow-hidden">
                <textarea id="import-html-editor" class="w-full h-full p-4" placeholder="הדבק כאן את קוד ה-HTML שברצונך לייבא..."></textarea>
            </div>
            <div class="p-4 border-t flex justify-end">
                <button id="cancel-import-html" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 ml-2">
                    ביטול
                </button>
                <button id="apply-import-html" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                    ייבא קוד
                </button>
            </div>
        </div>
    </div>
    
    <!-- Save Form (Hidden) -->
    <form id="save-form" method="POST" action="" class="hidden">
        <input type="hidden" name="content" id="content-input">
        <input type="hidden" name="save_content" value="1">
    </form>
    
    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="../customizer/assets/js/upload.js"></script>
<script src="../customizer/assets/js/media-library.js"></script>
<script src="../customizer/assets/js/form-editor.js"></script>

    <?php include '../customizer/assets/js/script.php'; ?>

</body>
</html>