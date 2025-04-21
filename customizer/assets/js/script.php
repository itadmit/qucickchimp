<script>
        // Initialize variables
        let currentContent = <?php echo json_encode($pageContent); ?>;
        
        // בדיקה שיש תוכן ואם לא - הוספת תבנית בסיסית
        if (!currentContent || currentContent.trim() === '') {
            console.log("Empty content - using basic template");
            currentContent = `
            <!DOCTYPE html>
            <html lang="he" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>דף נחיתה חדש</title>
                <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
                <style>
                    :root {
                        --primary-color: #6366f1;
                        --secondary-color: #4f46e5;
                        --accent-color: #ec4899;
                        --bg-color: #ffffff;
                        --text-color: #1f2937;
                    }
                </style>
            </head>
            <body class="font-sans bg-white">
                <header class="bg-purple-600 text-white py-8">
                    <div class="container mx-auto px-6 text-center">
                        <h1 class="text-4xl font-bold">כותרת דף הנחיתה</h1>
                        <p class="mt-4 text-xl">תיאור קצר של דף הנחיתה שלך</p>
                    </div>
                </header>
                <section class="py-12" data-section-type="content">
                    <div class="container mx-auto px-6">
                        <h2 class="text-3xl font-bold text-center mb-8">הכותרת שלך כאן</h2>
                        <p class="text-lg text-center max-w-3xl mx-auto">
                            זהו טקסט לדוגמה. כאן תוכל לכתוב את התוכן שלך. 
                            הוסף טקסט שמתאר את המוצר או השירות שלך.
                        </p>
                    </div>
                </section>
                <section class="bg-gray-100 py-12" data-section-type="contact">
                    <div class="container mx-auto px-6">
                        <h2 class="text-3xl font-bold text-center mb-8">צור קשר</h2>
                        <div class="max-w-lg mx-auto">
                            <form class="bg-white shadow-md rounded p-6">
                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                                        שם מלא
                                    </label>
                                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="name" type="text" placeholder="הזן את שמך">
                                </div>
                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                                        אימייל
                                    </label>
                                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="email" type="email" placeholder="הזן את האימייל שלך">
                                </div>
                                <div class="mb-6">
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="message">
                                        הודעה
                                    </label>
                                    <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="message" rows="4" placeholder="הזן את ההודעה שלך"></textarea>
                                </div>
                                <div class="flex items-center justify-center">
                                    <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="button">
                                        שלח
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>
                <section class="bg-gray-100 py-12" data-section-type="contact">
                    <div class="container mx-auto px-6">
                        <h2 class="text-3xl font-bold text-center mb-8">צור קשר</h2>
                        <div class="max-w-lg mx-auto">
                            <form class="bg-white shadow-md rounded p-6">
                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                                        שם מלא
                                    </label>
                                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="name" type="text" placeholder="הזן את שמך">
                                </div>
                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                                        אימייל
                                    </label>
                                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="email" type="email" placeholder="הזן את האימייל שלך">
                                </div>
                                <div class="mb-6">
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="message">
                                        הודעה
                                    </label>
                                    <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="message" rows="4" placeholder="הזן את ההודעה שלך"></textarea>
                                </div>
                                <div class="flex items-center justify-center">
                                    <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="button">
                                        שלח
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>
                <footer class="bg-gray-800 text-white py-8 text-center">
                    <div class="container mx-auto px-6">
                        <p>© 2023 כל הזכויות שמורות</p>
                    </div>
                </footer>
            </body>
            </html>
            `;
        }
        
        // עדכון התצוגה המקדימה מיד בטעינה
        document.addEventListener('DOMContentLoaded', function() {
            console.log("DOM loaded - updating preview");
            updatePreview();
        });
        
        let editor;
        let selectedSection = null;
        let activeSectionId = null;
        let sectionsList = [];
        let isMediaLibraryOpening = false;
        document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM loaded");
    
    // בדוק אם ספריית המדיה נטענה
    if (window.mediaLibrary && typeof window.mediaLibrary.open === 'function') {
        console.log("Media library loaded successfully");
    } else {
        console.error("Media library not loaded properly");
    }
    
    // בדוק שמודל המדיה קיים בדף
    const mediaModal = document.getElementById('media-library-modal');
    if (mediaModal) {
        console.log("Media modal found in page");
    } else {
        console.error("Media modal not found in page");
    }
});

        // Color schemes
        const colorSchemes = <?php echo json_encode($colorSchemes); ?>;
        const availableTemplates = <?php echo json_encode(getAvailableTemplates()); ?>;
        // Initialize preview iframe
        function updatePreview() {
            const iframe = document.getElementById('preview-iframe');
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            
            iframeDoc.open();
            iframeDoc.write(currentContent);
            iframeDoc.close();
            
            // Add click event to iframe content
            setTimeout(() => {
                // Get all sections
                const sections = iframeDoc.querySelectorAll('section, header, footer');
                
                // Reset sections list
                sectionsList = [];
                
                // Create sections list
                sections.forEach((section, index) => {
                    // Add ID if not present
                    if (!section.id) {
                        section.id = `section-${index}`;
                    }
                    
                    // Add data attributes
                    let sectionTitle = section.getAttribute('data-title') || section.tagName.toLowerCase();
                    if (section.querySelector('h1, h2, h3')) {
                        sectionTitle = section.querySelector('h1, h2, h3').textContent;
                    }
                    
                    section.setAttribute('data-index', index);
                    section.setAttribute('data-title', sectionTitle);
                    
                    // Add to sections list
                    sectionsList.push({
                        id: section.id,
                        type: section.tagName.toLowerCase(),
                        title: sectionTitle,
                        element: section,
                        visible: !section.hasAttribute('hidden')
                    });
                    
                    // Add click event
                    section.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        selectSection(section, index);
                    });
                    
                    // Add hover effects
                    section.addEventListener('mouseover', () => {
                        if (selectedSection !== section) {
                            section.style.outline = '2px dashed rgba(99, 102, 241, 0.5)';
                        }
                    });
                    
                    section.addEventListener('mouseout', () => {
                        if (selectedSection !== section) {
                            section.style.outline = 'none';
                        }
                    });
                });
                
                // Populate sections list in sidebar
                populateSectionsList();
                setupImageEvents(iframeDoc);
            }, 500);
        }


        function getLandingPageId() {
    // חילוץ מזהה דף הנחיתה מה-URL
    const urlParams = new URLSearchParams(window.location.search);
    const landingPageId = urlParams.get('id');
    
    console.log("Extracted landing page ID:", landingPageId);
    
    // בדיקה אם המזהה קיים וחוקי
    if (!landingPageId || isNaN(parseInt(landingPageId))) {
        console.error('מזהה דף נחיתה חסר או לא תקין');
        return null;
    }
    
    return landingPageId;
}

        function setupImageEvents(iframeDoc) {
    if (!iframeDoc) return;
    
    // טיפול בתמונות תוך מניעת אירועים כפולים
    const iframeImages = iframeDoc.querySelectorAll('img');
    
    iframeImages.forEach(image => {
        // הוספת סמן מיוחד בעת מעבר עכבר
        image.style.cursor = 'pointer';
        
        // הסרת אירועים קודמים
        const newImage = image.cloneNode(true);
        if (image.parentNode) {
            image.parentNode.replaceChild(newImage, image);
        }
        
        // הגדרת מיקום יחסי להורה אם צריך
        if (newImage.parentNode && getComputedStyle(newImage.parentNode).position === 'static') {
            newImage.parentNode.style.position = 'relative';
        }
        
        // הסרת overlay קיים אם יש
        if (newImage.parentNode) {
            const existingOverlay = newImage.parentNode.querySelector('.image-edit-overlay');
            if (existingOverlay) {
                existingOverlay.remove();
            }
            
            // יצירת overlay חדש
            const overlay = document.createElement('div');
            overlay.className = 'image-edit-overlay';
            overlay.style.position = 'absolute';
            overlay.style.inset = '0';
            overlay.style.backgroundColor = 'rgba(79, 70, 229, 0.2)';
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.style.opacity = '0';
            overlay.style.transition = 'opacity 0.2s';
            overlay.style.marginLeft = '40px';
            overlay.style.pointerEvents = 'none';

            
            const icon = document.createElement('div');
            icon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="white"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>';
            overlay.appendChild(icon);
            
            newImage.parentNode.appendChild(overlay);
            
            // אירועי המעבר של העכבר
            newImage.addEventListener('mouseover', () => {
                overlay.style.opacity = '1';
            });
            
            newImage.addEventListener('mouseout', () => {
                overlay.style.opacity = '0';
            });
            
            // אירוע לחיצה על התמונה
            newImage.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    
    if (isMediaLibraryOpening) return;
    isMediaLibraryOpening = true;
    
    // הדפס את מזהה דף הנחיתה לבדיקה
    const landingPageId = getLandingPageId();
    console.log("Landing page ID:", landingPageId);
    
    if (!landingPageId) {
        alert('לא נמצא מזהה דף נחיתה');
        isMediaLibraryOpening = false;
        return;
    }
    
    // פתיחת ספריית המדיה
    window.mediaLibrary.open(landingPageId, (newImageUrl) => {
        if (newImageUrl) {
            newImage.src = newImageUrl;
            currentContent = iframeDoc.documentElement.outerHTML;
        }
        isMediaLibraryOpening = false;
    });
});

        }
    });
}



        
        // Populate sections list in sidebar
        function initDraggableSections() {
    const sectionsContainer = document.getElementById('page-sections-list');
    
    // Enable HTML5 drag and drop
    sectionsContainer.addEventListener('dragover', handleDragOver);
    sectionsContainer.addEventListener('dragenter', handleDragEnter);
    sectionsContainer.addEventListener('dragleave', handleDragLeave);
    sectionsContainer.addEventListener('drop', handleDrop);
    sectionsContainer.addEventListener('dragend', handleDragEnd);
    
    let dropIndicator = null;
    
    // יצירת מחוון השמטה
    function createDropIndicator() {
        const indicator = document.createElement('div');
        indicator.className = 'drop-indicator';
        indicator.innerHTML = `<div class="drop-indicator-line"></div>`;
        return indicator;
    }
    
    function handleDragOver(e) {
        e.preventDefault(); // הכרחי כדי לאפשר השמטה
        e.dataTransfer.dropEffect = 'move';
        
        // מצא את האלמנט הנגרר (מאוחסן במשתנה גלובלי)
        const draggedItem = window._draggedSectionItem;
        
        // עדכן את מיקום מחוון ההשמטה
        if (draggedItem) {
            // אם המחוון לא קיים, צור אותו
            if (!dropIndicator) {
                dropIndicator = createDropIndicator();
                sectionsContainer.appendChild(dropIndicator);
            }
            
            // מצא את כל פריטי הסקשן חוץ מהפריט הנגרר
            const sectionItems = Array.from(sectionsContainer.querySelectorAll('.section-item:not(.dragging)'));
            
            // מצא את האלמנט הקרוב ביותר למיקום העכבר
            let closestItem = null;
            let closestDistance = Number.MAX_VALUE;
            let insertBefore = true;
            
            sectionItems.forEach((item) => {
                const rect = item.getBoundingClientRect();
                const mouseY = e.clientY;
                
                // חישוב מרחק מהמרכז האנכי של האלמנט
                const centerY = rect.top + rect.height / 2;
                const distance = Math.abs(mouseY - centerY);
                
                if (distance < closestDistance) {
                    closestDistance = distance;
                    closestItem = item;
                    
                    // קביעה אם להוסיף לפני או אחרי האלמנט הקרוב ביותר
                    insertBefore = mouseY < centerY;
                }
            });
            
            // עדכון מיקום מחוון ההשמטה
            if (closestItem) {
                if (insertBefore) {
                    // שמה לפני האלמנט הקרוב ביותר
                    sectionsContainer.insertBefore(dropIndicator, closestItem);
                } else if (closestItem.nextSibling) {
                    // שמה אחרי האלמנט הקרוב ביותר
                    sectionsContainer.insertBefore(dropIndicator, closestItem.nextSibling);
                } else {
                    // אם זה האלמנט האחרון, הוסף בסוף
                    sectionsContainer.appendChild(dropIndicator);
                }
            } else if (sectionItems.length === 0) {
                // אם אין פריטים אחרים, הוסף בתחילת הרשימה
                if (sectionsContainer.firstChild) {
                    sectionsContainer.insertBefore(dropIndicator, sectionsContainer.firstChild);
                } else {
                    sectionsContainer.appendChild(dropIndicator);
                }
            }
        }
        
        return false;
    }
    
    function handleDragEnter(e) {
        e.preventDefault();
        // לא צריך לעשות כלום נוסף כאן, כי handleDragOver ייצור את המחוון
    }
    
    function handleDragLeave(e) {
        // הסר את המחוון רק אם עזבנו את אזור הרשימה לגמרי
        if (!sectionsContainer.contains(e.relatedTarget)) {
            removeDropIndicator();
        }
    }
    
    function handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // הסרת מחוון ההשמטה
        removeDropIndicator();
        
        // מצא את האלמנט הנגרר
        const draggedItem = window._draggedSectionItem;
        
        if (draggedItem) {
            // מצא את המיקום להכנסה בהתאם למיקום המחוון
            const allItems = Array.from(sectionsContainer.querySelectorAll('.section-item'));
            const mouseY = e.clientY;
            
            let targetItem = null;
            let insertBefore = true;
            
            // מצא את האלמנט הקרוב ביותר למיקום העכבר
            for (let i = 0; i < allItems.length; i++) {
                const item = allItems[i];
                if (item === draggedItem) continue;
                
                const rect = item.getBoundingClientRect();
                const centerY = rect.top + rect.height / 2;
                
                if (mouseY < centerY) {
                    targetItem = item;
                    insertBefore = true;
                    break;
                } else if (i === allItems.length - 1 || mouseY < allItems[i+1].getBoundingClientRect().top) {
                    targetItem = item;
                    insertBefore = false;
                    break;
                }
            }
            
            // הכנסת האלמנט הנגרר במיקום החדש
            if (targetItem && targetItem !== draggedItem) {
                if (insertBefore) {
                    sectionsContainer.insertBefore(draggedItem, targetItem);
                } else {
                    sectionsContainer.insertBefore(draggedItem, targetItem.nextSibling);
                }
            } else if (!targetItem && allItems.length > 0) {
                // אם לא נמצא אלמנט יעד, הוסף בסוף
                sectionsContainer.appendChild(draggedItem);
            }
            
            // עדכון סדר הסקשנים ב-iframe
            updateSectionsOrderInIframe();
            
            // ניקוי המשתנה הגלובלי
            window._draggedSectionItem = null;
        }
        
        return false;
    }
    
    function handleDragEnd(e) {
        // הסרת סגנון הגרירה
        const draggedItem = window._draggedSectionItem;
        if (draggedItem) {
            draggedItem.classList.remove('dragging');
            window._draggedSectionItem = null;
        }
        
        // הסרת מחוון ההשמטה
        removeDropIndicator();
    }
    
    // פונקציה להסרת מחוון ההשמטה
    function removeDropIndicator() {
        if (dropIndicator && dropIndicator.parentNode) {
            dropIndicator.parentNode.removeChild(dropIndicator);
            dropIndicator = null;
        }
    }
    
    // עדכון סדר הסקשנים ב-iframe
    function updateSectionsOrderInIframe() {
        const iframe = document.getElementById('preview-iframe');
        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
        const iframeBody = iframeDoc.body;
        
        // קבלת הסדר החדש מרשימת הסקשנים
        const newOrder = [];
        document.querySelectorAll('.section-item').forEach(item => {
            newOrder.push(item.getAttribute('data-section-id'));
        });
        
        // יצירת מערך זמני להחזקת הסקשנים המסודרים מחדש
        const reorderedSections = [];
        
        // איסוף כל הסקשנים בסדר החדש
        newOrder.forEach(sectionId => {
            const sectionElement = iframeDoc.getElementById(sectionId);
            if (sectionElement) {
                reorderedSections.push(sectionElement);
            }
        });
        
        // הוספת הסקשנים לגוף ה-iframe בסדר החדש
        reorderedSections.forEach(sectionElement => {
            iframeBody.appendChild(sectionElement);
        });
        
        // עדכון תוכן נוכחי
        currentContent = iframeDoc.documentElement.outerHTML;
        
        // עדכון מערך sectionsList כדי לשקף את הסדר החדש
        const updatedSectionsList = [];
        newOrder.forEach(sectionId => {
            const section = sectionsList.find(s => s.id === sectionId);
            if (section) {
                updatedSectionsList.push(section);
            }
        });
        
        // החלפת מערך sectionsList המקורי במערך המעודכן
        sectionsList = updatedSectionsList;
    }
}

// Modify the populateSectionsList function to make items draggable
function populateSectionsList() {
    const listContainer = document.getElementById('page-sections-list');
    listContainer.innerHTML = '';
    
    if (sectionsList.length === 0) {
        listContainer.innerHTML = `
            <div class="p-4 text-center text-gray-500">
                <p>אין סקשנים בדף זה</p>
                <button id="add-first-section-btn" class="mt-2 px-4 py-2 bg-purple-100 text-purple-700 hover:bg-purple-200 rounded">
                    <i class="ri-add-line ml-1"></i>
                    הוסף סקשן ראשון
                </button>
            </div>
        `;
        return;
    }
    
    sectionsList.forEach((section, index) => {
        const sectionItem = document.createElement('div');
        sectionItem.className = `section-item p-3 flex items-center justify-between border-b ${activeSectionId === section.id ? 'active' : ''}`;
        sectionItem.setAttribute('data-section-id', section.id);
        sectionItem.setAttribute('data-section-index', index);
        
        // אין צורך לעשות את כל האלמנט גריר - רק את הידית
        sectionItem.draggable = false;
        
        // Icon for section type
        let icon = 'ri-layout-3-line';
        if (section.type === 'header') icon = 'ri-layout-top-line';
        if (section.type === 'footer') icon = 'ri-layout-bottom-line';
        
        sectionItem.innerHTML = `
            <div class="drag-handle mr-2" draggable="true">
                <i class="ri-drag-move-fill text-gray-400"></i>
            </div>
            <div class="flex items-center flex-1">
                <i class="${icon} text-gray-500 ml-2"></i>
                <span class="section-title">${section.title}</span>

            </div>
            <div class="section-visibility">
                <div class="green-dot ${section.visible ? 'active' : ''}"></div>
            </div>
        `;
        
        listContainer.appendChild(sectionItem);
        
        // הוסף אירוע dragstart לידית הגרירה בלבד
        const dragHandle = sectionItem.querySelector('.drag-handle');
        dragHandle.addEventListener('dragstart', (e) => {
            // שמור את מצביע לאלמנט האב (section-item)
            const parentItem = dragHandle.closest('.section-item');
            if (parentItem) {
                e.dataTransfer.setData('text/plain', parentItem.getAttribute('data-section-index'));
                
                // הוסף את האלמנט האב כמידע למעקב אחר הגרירה
                window._draggedSectionItem = parentItem;
                
                // הוסף סגנון לאלמנט הנגרר
                setTimeout(() => {
                    parentItem.classList.add('dragging');
                }, 0);
            }
        });
        
        // Add click event
        sectionItem.addEventListener('click', (e) => {
            // Don't trigger if clicked on drag handle or green dot
            if (e.target.closest('.drag-handle') || 
                e.target.closest('.section-visibility')) {
                return;
            }
            
            // Select corresponding section in iframe
            const iframe = document.getElementById('preview-iframe');
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            const sectionElement = iframeDoc.getElementById(section.id);
            
            if (sectionElement) {
                selectSection(sectionElement, index);
            }
        });
        
        // Add toggle event for green dot
        const greenDot = sectionItem.querySelector('.green-dot');
        greenDot.addEventListener('click', () => {
            // Toggle visibility
            const iframe = document.getElementById('preview-iframe');
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            const sectionElement = iframeDoc.getElementById(section.id);
            
            if (sectionElement) {
                if (greenDot.classList.contains('active')) {
                    // Currently visible, make invisible
                    greenDot.classList.remove('active');
                    sectionElement.setAttribute('hidden', '');
                    section.visible = false;
                } else {
                    // Currently invisible, make visible
                    greenDot.classList.add('active');
                    sectionElement.removeAttribute('hidden');
                    section.visible = true;
                }
                
                // Update current content
                currentContent = iframeDoc.documentElement.outerHTML;
            }
        });
    });
    
    // Add event for add first section button
    const addFirstBtn = document.getElementById('add-first-section-btn');
    if (addFirstBtn) {
        addFirstBtn.addEventListener('click', () => {
            showAddSectionModal();
        });
    }
    
    // Initialize drag and drop functionality
    initDraggableSections();
}

// Add some styles for better drag and drop visual feedback
document.addEventListener('DOMContentLoaded', () => {
    const style = document.createElement('style');
    style.textContent = `
        .section-item {
            transition: background-color 0.2s, opacity 0.2s;
        }
        .section-item.active {
            background-color: rgba(124, 58, 237, 0.1);
            border-left: 3px solid rgba(124, 58, 237, 0.8);
        }
        .section-item:hover {
            background-color: rgba(243, 244, 246, 0.7);
        }
        .cursor-move {
            cursor: move;
        }
    `;
    document.head.appendChild(style);
});
        
// הוספת אירועי לחיצה למודל המדיה
const closeMediaBtn = document.getElementById('close-media-library');
    if (closeMediaBtn) {
        closeMediaBtn.addEventListener('click', () => {
            const mediaModal = document.getElementById('media-library-modal');
            if (mediaModal) {
                mediaModal.classList.add('hidden');
                isMediaLibraryOpening = false;
            }
        });
    }

    // אירוע לסגירת המודל בלחיצה על האזור האפור
    const mediaModal = document.getElementById('media-library-modal');
    if (mediaModal) {
        mediaModal.addEventListener('click', (event) => {
            if (event.target === mediaModal) {
                mediaModal.classList.add('hidden');
                isMediaLibraryOpening = false;
            }
        });
    }

// הוספת אירועי לחיצה על תמונות בספריית המדיה
const mediaItems = document.querySelectorAll('#media-grid .media-item');
mediaItems.forEach(item => {
    item.addEventListener('click', () => {
        if (mediaLibrary._currentCallback) {
            const imageUrl = item.getAttribute('data-url');
            if (imageUrl) {
                mediaLibrary._currentCallback(imageUrl);
                mediaLibrary.close();
            }
        }
    });
});

        // Select a section for editing
        function selectSection(section, index) {
            const iframe = document.getElementById('preview-iframe');
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            
            // Remove highlight from previously selected section
            if (selectedSection) {
                selectedSection.style.outline = 'none';
            }
            
            // Highlight current section
            selectedSection = section;
            section.style.outline = '2px solid rgba(99, 102, 241, 1)';
            activeSectionId = section.id;

            section.scrollIntoView({ behavior: 'smooth', block: 'start' });

            
            // Update sections list active state
            document.querySelectorAll('.section-item').forEach(item => {
                item.classList.remove('active');
                if (item.getAttribute('data-section-id') === activeSectionId) {
                    item.classList.add('active');
                }
            });
            
            // Show section editor panel
            document.getElementById('section-editor-panel').classList.remove('hidden');
            document.getElementById('page-settings-panel').classList.add('hidden');
            document.getElementById('add-section-panel').classList.add('hidden');
            
            // Set section title
            document.getElementById('settings-panel-title').textContent = `עריכת ${section.getAttribute('data-title') || 'סקשן'}`;
            
            // Show back button
            document.getElementById('back-to-list-btn').classList.remove('hidden');
            
            // Update tabs
            document.getElementById('page-settings-tab').classList.remove('bg-purple-100', 'text-purple-700');
            document.getElementById('page-settings-tab').classList.add('text-gray-600', 'hover:bg-gray-100');
            document.getElementById('section-editor-tab').classList.remove('text-gray-600', 'hover:bg-gray-100');
            document.getElementById('section-editor-tab').classList.add('bg-purple-100', 'text-purple-700');
            document.getElementById('add-section-tab').classList.remove('bg-purple-100', 'text-purple-700');
            document.getElementById('add-section-tab').classList.add('text-gray-600', 'hover:bg-gray-100');
            
            // Populate section controls
            const controlsContainer = document.getElementById('section-controls');
            controlsContainer.innerHTML = '';
            
            // Add controls based on section type
            const headings = section.querySelectorAll('h1, h2, h3');
            const paragraphs = section.querySelectorAll('p');
            const buttons = section.querySelectorAll('a.btn, button, a.button, .btn, .button, a[class*="bg-"]');
            const images = section.querySelectorAll('img');
            
            // Check if this is a contact form section
            const isSectionContact = section.getAttribute('data-section-type') && section.getAttribute('data-section-type').includes('contact');
            const hasForm = section.querySelector('form');
            
            // טיפול בלחיצות על תמונות
            setupImageEvents(iframeDoc);

            // Title controls
            if (headings.length > 0) {
                headings.forEach((heading, index) => {
                    const controlDiv = document.createElement('div');
                    controlDiv.innerHTML = `
                        <label class="block text-sm font-medium text-gray-700 mb-1">כותרת ${index + 1}</label>
                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" 
                               value="${heading.textContent}" data-target="heading-${index}">
                    `;
                    
                    controlsContainer.appendChild(controlDiv);
                    
                    const input = controlDiv.querySelector('input');
                    input.addEventListener('input', () => {
                        heading.textContent = input.value;
                        
                        // Update current content
                        currentContent = iframeDoc.documentElement.outerHTML;
                        
                        // Update section title in list if it's the first heading
                        if (index === 0) {
                            section.setAttribute('data-title', input.value);
                            populateSectionsList();
                        }
                    });
                });
            }
            
            // Text controls
            if (paragraphs.length > 0) {
                paragraphs.forEach((paragraph, index) => {
                    const controlDiv = document.createElement('div');
                    controlDiv.innerHTML = `
                        <label class="block text-sm font-medium text-gray-700 mb-1">טקסט ${index + 1}</label>
                        <textarea class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" 
                                 rows="3" data-target="paragraph-${index}">${paragraph.textContent}</textarea>
                    `;
                    
                    controlsContainer.appendChild(controlDiv);
                    
                    const textarea = controlDiv.querySelector('textarea');
                    textarea.addEventListener('input', () => {
                        paragraph.textContent = textarea.value;
                        
                        // Update current content
                        currentContent = iframeDoc.documentElement.outerHTML;
                    });
                });
            }
            
            // Button controls
            if (buttons.length > 0) {
                buttons.forEach((button, index) => {
                    const controlDiv = document.createElement('div');
                    controlDiv.innerHTML = `
                        <label class="block text-sm font-medium text-gray-700 mb-1">כפתור ${index + 1}</label>
                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500 mb-2" 
                               value="${button.textContent.trim()}" data-target="button-text-${index}">
                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" 
                               value="${button.getAttribute('href') || '#'}" placeholder="קישור" data-target="button-link-${index}">
                    `;
                    
                    controlsContainer.appendChild(controlDiv);
                    
                    const textInput = controlDiv.querySelector('input[data-target^="button-text"]');
                    const linkInput = controlDiv.querySelector('input[data-target^="button-link"]');
                    
                    textInput.addEventListener('input', () => {
                        button.textContent = textInput.value;
                        
                        // Update current content
                        currentContent = iframeDoc.documentElement.outerHTML;
                    });
                    
                    linkInput.addEventListener('input', () => {
                        button.setAttribute('href', linkInput.value);
                        
                        // Update current content
                        currentContent = iframeDoc.documentElement.outerHTML;
                    });
                });
            }
            
            // Form editor for contact sections
            if (isSectionContact && hasForm) {
                // Add separator
                const formSeparator = document.createElement('div');
                formSeparator.className = 'border-t border-gray-200 my-6 pt-6';
                controlsContainer.appendChild(formSeparator);
                
                // Add form editor header
                const formHeader = document.createElement('div');
                formHeader.innerHTML = `
                    <h3 class="text-lg font-medium text-purple-700 mb-4">הגדרות טופס</h3>
                `;
                controlsContainer.appendChild(formHeader);
                
                // Add form settings button
                const formSettingsButton = document.createElement('div');
                formSettingsButton.innerHTML = `
                    <button id="open-form-settings-btn" type="button" class="w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                        <i class="ri-settings-3-line ml-1"></i> פתח הגדרות טופס
                    </button>
                `;
                controlsContainer.appendChild(formSettingsButton);
                
                // Setup event listener for form settings button
                document.getElementById('open-form-settings-btn').addEventListener('click', function() {
                    // יצירת המודל
                    let modalElement = document.getElementById('form-settings-modal');
                    if (!modalElement) {
                        modalElement = document.createElement('div');
                        modalElement.id = 'form-settings-modal';
                        modalElement.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50';
                        document.body.appendChild(modalElement);
                    }
                    
                    // וידוא שיש טופס בסקשן
                    const currentForm = section.querySelector('form');
                    if (!currentForm) {
                        console.error('אין טופס בסקשן');
                        return;
                    }
                    
                    // תוכן המודל
                    modalElement.innerHTML = `
                        <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl mx-4 max-h-[90vh] overflow-hidden flex flex-col">
                            <div class="p-3 border-b flex justify-between items-center bg-purple-50">
                                <h3 class="text-lg font-medium text-purple-700">הגדרות טופס יצירת קשר</h3>
                                <button id="close-form-settings-modal" class="text-gray-500 hover:text-gray-700">
                                    <i class="ri-close-line text-xl"></i>
                                </button>
                            </div>
                            
                            <div class="overflow-y-auto flex-grow p-3">
                                <!-- Form Fields Container -->
                                <div class="mb-4">
                                    <div class="flex justify-between items-center mb-3">
                                        <h4 class="text-md font-medium text-gray-700">שדות הטופס</h4>
                                        <span id="modal-fields-count" class="text-sm font-medium text-purple-600">0</span>
                                    </div>
                                    
                                    <div id="modal-form-fields-section">
                                        <div id="modal-form-fields-container" class="space-y-1 mb-2"></div>
                                    </div>
                                    <div id="modal-no-fields-message" class="text-center text-gray-500 py-3">
                                        <p>לא נמצאו שדות בטופס. הוסף שדה חדש!</p>
                                    </div>
                                    <button id="modal-add-form-field-btn" type="button" class="mt-2 w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-purple-500">
                                        <i class="ri-add-line ml-1"></i> הוסף שדה חדש
                                    </button>
                                </div>
                                
                                <!-- Advanced Settings -->
                                <div class="border-t border-gray-200 pt-4">
                                    <h4 class="text-md font-medium text-gray-700 mb-3">הגדרות מתקדמות</h4>
                                    
                                    <div class="bg-gray-50 p-3 rounded-md">
                                        <div class="mb-3">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">שייך לרשימת תפוצה</label>
                                            <select id="modal-form-list-select" class="block w-full py-1.5 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm">
                                                <option value="">לא משויך (ברירת מחדל)</option>
                                                <?php
                                                // קבלת משתמש נוכחי
                                                $userId = $_SESSION['user_id'] ?? 0;
                                                
                                                // שליפת רשימות התפוצה של המשתמש
                                                $contactListsQuery = $pdo->prepare("SELECT id, name FROM contact_lists WHERE user_id = ? ORDER BY name");
                                                $contactListsQuery->execute([$userId]);
                                                $contactLists = $contactListsQuery->fetchAll();
                                                
                                                // הצגת הרשימות בתפריט הבחירה
                                                foreach($contactLists as $list) {
                                                    echo '<option value="' . $list['id'] . '">' . htmlspecialchars($list['name']) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">תיוג</label>
                                            <input type="text" id="modal-form-tag-input" class="block w-full py-1.5 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm" placeholder="הוסף תיוגים מופרדים בפסיק">
                                        </div>
                                        
                                        <div class="flex items-center mt-3">
                                            <input id="modal-redirect-checkbox" type="checkbox" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                                            <label for="modal-redirect-checkbox" class="mr-2 block text-sm text-gray-700">עבור לדף "תודה" לאחר שליחה</label>
                                        </div>
                                        
                                        <div id="modal-redirect-url-container" class="mt-2 hidden">
                                            <input type="text" id="modal-redirect-url-input" class="block w-full py-1.5 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm" placeholder="URL לדף תודה">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-3 border-t flex justify-end">
                                <button id="cancel-form-settings-btn" class="px-3 py-1.5 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                                    ביטול
                                </button>
                                <button id="save-form-settings-btn" class="mr-3 px-3 py-1.5 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none">
                                    שמור הגדרות
                                </button>
                            </div>
                        </div>
                    `;
                    
                    // הצגת המודל
                    modalElement.classList.remove('hidden');
                    
                    // אתחול עורך הטופס
                    initFormEditor(section, iframeDoc);
                    
                    // הצגת השדות במודל
                    const formFieldsContainer = document.getElementById('modal-form-fields-container');
                    if (formFieldsContainer) {
                        // ניקוי קונטיינר השדות
                        formFieldsContainer.innerHTML = '';
                        
                        // הוספת השדות למודל
                        if (currentFormFields.length > 0) {
                            currentFormFields.forEach((field, index) => {
                                const fieldElement = createFieldUI(field, index);
                                formFieldsContainer.appendChild(fieldElement);
                            });
                            
                            // הפעלת drag & drop
                            if (typeof Sortable !== 'undefined') {
                                new Sortable(formFieldsContainer, {
                                    animation: 150,
                                    handle: '.handle',
                                    ghostClass: 'sortable-ghost',
                                    chosenClass: 'sortable-chosen',
                                    dragClass: 'sortable-drag',
                                    onEnd: function(evt) {
                                        // סידור מחדש של השדות
                                        const newOrder = Array.from(formFieldsContainer.children).map(element => 
                                            parseInt(element.getAttribute('data-field-index'), 10)
                                        );
                                        
                                        // עדכון השדות במערך
                                        const reorderedFields = [];
                                        newOrder.forEach(oldIndex => {
                                            reorderedFields.push(currentFormFields[oldIndex]);
                                        });
                                        
                                        // עדכון המערך
                                        currentFormFields = reorderedFields;
                                        
                                        // עדכון הטופס במסגרת
                                        updateFormInIframe();
                                    }
                                });
                            }
                            
                            // עדכון ההצגה
                            const formFieldsSection = document.getElementById('modal-form-fields-section');
                            const noFieldsMessage = document.getElementById('modal-no-fields-message');
                            
                            if (formFieldsSection) {
                                formFieldsSection.classList.remove('hidden');
                            }
                            
                            if (noFieldsMessage) {
                                noFieldsMessage.classList.add('hidden');
                            }
                            
                            // עדכון מספר השדות
                            const fieldsCountElement = document.getElementById('modal-fields-count');
                            if (fieldsCountElement) {
                                fieldsCountElement.textContent = currentFormFields.length;
                            }
                        } else {
                            // הצגת הודעה שאין שדות
                            const formFieldsSection = document.getElementById('modal-form-fields-section');
                            const noFieldsMessage = document.getElementById('modal-no-fields-message');
                            
                            if (formFieldsSection) {
                                formFieldsSection.classList.add('hidden');
                            }
                            
                            if (noFieldsMessage) {
                                noFieldsMessage.classList.remove('hidden');
                            }
                        }
                    }
                    
                    // טעינת נתוני הטופס
                    const listSelect = document.getElementById('modal-form-list-select');
                    const tagsInput = document.getElementById('modal-form-tag-input');
                    const redirectCheckbox = document.getElementById('modal-redirect-checkbox');
                    const redirectUrlContainer = document.getElementById('modal-redirect-url-container');
                    const redirectUrlInput = document.getElementById('modal-redirect-url-input');
                    
                    if (listSelect) {
                        const listId = currentForm.getAttribute('data-list-id') || '';
                        listSelect.value = listId;
                    }
                    
                    if (tagsInput) {
                        const tags = currentForm.getAttribute('data-tags') || '';
                        tagsInput.value = tags;
                    }
                    
                    if (redirectCheckbox && redirectUrlContainer && redirectUrlInput) {
                        const redirectUrl = currentForm.getAttribute('data-redirect') || '';
                        
                        if (redirectUrl) {
                            redirectCheckbox.checked = true;
                            redirectUrlContainer.classList.remove('hidden');
                            redirectUrlInput.value = redirectUrl;
                        } else {
                            redirectCheckbox.checked = false;
                            redirectUrlContainer.classList.add('hidden');
                            redirectUrlInput.value = '';
                        }
                        
                        // הגדרת אירוע שינוי מצב תיבת הסימון
                        redirectCheckbox.addEventListener('change', function() {
                            if (this.checked) {
                                redirectUrlContainer.classList.remove('hidden');
                            } else {
                                redirectUrlContainer.classList.add('hidden');
                            }
                        });
                    }
                    
                    // הגדרת אירועים
                    const closeBtn = document.getElementById('close-form-settings-modal');
                    const cancelBtn = document.getElementById('cancel-form-settings-btn');
                    const saveBtn = document.getElementById('save-form-settings-btn');
                    const addFieldBtn = document.getElementById('modal-add-form-field-btn');
                    
                    // פונקציית סגירת המודל
                    const closeModal = () => {
                        if (modalElement) {
                            modalElement.remove();
                        }
                    };
                    
                    // לחצני סגירה
                    if (closeBtn) closeBtn.addEventListener('click', closeModal);
                    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
                    
                    // לחצן הוספת שדה
                    if (addFieldBtn) {
                        addFieldBtn.addEventListener('click', () => {
                            showFieldEditModal();
                        });
                    }
                    
                    // לחצן שמירה
                    if (saveBtn) {
                        saveBtn.addEventListener('click', () => {
                            // שמירת הנתונים לטופס
                            if (listSelect) {
                                currentForm.setAttribute('data-list-id', listSelect.value);
                            }
                            
                            if (tagsInput) {
                                currentForm.setAttribute('data-tags', tagsInput.value);
                            }
                            
                            if (redirectCheckbox && redirectUrlInput) {
                                if (redirectCheckbox.checked && redirectUrlInput.value) {
                                    currentForm.setAttribute('data-redirect', redirectUrlInput.value);
                                } else {
                                    currentForm.removeAttribute('data-redirect');
                                }
                            }
                            
                            // עדכון הטופס במסגרת
                            updateFormInIframe();
                            
                            // עדכון התוכן הכללי
                            currentContent = iframeDoc.documentElement.outerHTML;
                            
                            // סגירת המודל
                            closeModal();
                        });
                    }
                });
            }
        }
        
        // Remove selected section
        function removeSelectedSection() {
            if (!selectedSection) return;
            
            if (confirm('האם אתה בטוח שברצונך למחוק את הסקשן הזה?')) {
                selectedSection.remove();
                selectedSection = null;
                activeSectionId = null;
                
                // Hide section settings
                document.getElementById('section-editor-panel').classList.add('hidden');
                document.getElementById('page-settings-panel').classList.remove('hidden');
                document.getElementById('add-section-panel').classList.add('hidden');
                
                document.getElementById('page-settings-tab').classList.remove('text-gray-600', 'hover:bg-gray-100');
                document.getElementById('page-settings-tab').classList.add('bg-purple-100', 'text-purple-700');
                document.getElementById('section-editor-tab').classList.remove('bg-purple-100', 'text-purple-700');
                document.getElementById('section-editor-tab').classList.add('text-gray-600', 'hover:bg-gray-100');
                document.getElementById('add-section-tab').classList.remove('bg-purple-100', 'text-purple-700');
                document.getElementById('add-section-tab').classList.add('text-gray-600', 'hover:bg-gray-100');
                
                document.getElementById('settings-panel-title').textContent = 'הגדרות כלליות';
                document.getElementById('back-to-list-btn').classList.add('hidden');
                
                // Update content
                const iframe = document.getElementById('preview-iframe');
                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                currentContent = iframeDoc.documentElement.outerHTML;
                
                // Refresh preview and sections list
                updatePreview();
            }
        }
        
        // Open HTML editor
        function openHtmlEditor() {
    // הצגת המודל תחילה כדי שהעורך יתאתחל בגודל הנכון
    document.getElementById('html-editor-modal').classList.remove('hidden');
    
    // איפוס ושחזור ה-textarea
    document.getElementById('html-editor').value = currentContent;
    
    // אתחול מחדש של CodeMirror בכל פעם שהמודל נפתח
    if (editor) {
        editor.toTextArea(); // שחרור של המופע הקודם
    }
    
    // יצירת מופע חדש
    editor = CodeMirror.fromTextArea(document.getElementById('html-editor'), {
        mode: 'htmlmixed',
        lineNumbers: true,
        indentUnit: 4,
        autoCloseTags: true,
        lineWrapping: true
    });
    
    // רענון העורך אחרי השהייה קצרה
    setTimeout(() => {
        editor.refresh();
        // עדכון תוכן העורך בתוכן הנוכחי
        editor.setValue(currentContent);
    }, 100);
}

function setupImageControlButtons() {
    console.log("Empty setup function called");
    // אין צורך לעשות כלום, הפונקציה המקורית תישאר ריקה
    // כי אנחנו נטפל באירועי הלחיצה ישירות בתוך selectSection
}
        // Close HTML editor
        function closeHtmlEditor() {
            document.getElementById('html-editor-modal').classList.add('hidden');
        }
        
        // Apply HTML edits
        function applyHtmlEdit() {
            currentContent = editor.getValue();
            updatePreview();
            closeHtmlEditor();
        }
        
        // Save content
        function saveContent() {
            // Update current content from iframe
            const iframe = document.getElementById('preview-iframe');
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            currentContent = iframeDoc.documentElement.outerHTML;
            
            // Set form input value
            document.getElementById('content-input').value = currentContent;
            
            // Submit form
            document.getElementById('save-form').submit();
        }
         // פתיחת מודל בחירת תבנית
    document.getElementById('change-template-btn').addEventListener('click', function() {
        document.getElementById('templates-modal').classList.remove('hidden');
    });
    
    // סגירת מודל תבניות
    document.getElementById('close-templates-modal').addEventListener('click', function() {
        document.getElementById('templates-modal').classList.add('hidden');
    });
    
    document.getElementById('cancel-template-change').addEventListener('click', function() {
        document.getElementById('templates-modal').classList.add('hidden');
    });
    
    // בחירת תבנית
    document.querySelectorAll('.template-item').forEach(function(item) {
        item.addEventListener('click', function() {
            const templateId = this.getAttribute('data-template-id');
            
            if (confirm('פעולה זו תחליף את כל התוכן הנוכחי בתבנית חדשה. האם אתה בטוח?')) {
                // שליחת בקשה לשרת
                const formData = new FormData();
                formData.append('action', 'change_template');
                formData.append('template_id', templateId);
                formData.append('landing_page_id', <?php echo $landingPageId; ?>);
                
                fetch('functions/template_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // טען מחדש את העמוד
                        window.location.reload();
                    } else {
                        alert('שגיאה: ' + (data.message || 'לא ניתן להחליף תבנית'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('שגיאה בהחלפת תבנית');
                });
            }
            
            document.getElementById('templates-modal').classList.add('hidden');
        });
    });
    
    // הוספת פונקציונליות לכפתורי החלפת תצוגה
document.addEventListener('DOMContentLoaded', () => {
    // הגדרת כפתורי התצוגה
    const desktopViewBtn = document.getElementById('desktop-view-btn');
    const tabletViewBtn = document.getElementById('tablet-view-btn');
    const mobileViewBtn = document.getElementById('mobile-view-btn');
    
    // הגדרת מסגרת התצוגות המקדימה
    const previewContainer = document.querySelector('.preview-container'); // שים לב לשינוי כאן
    const previewIframe = document.getElementById('preview-iframe');
    
    // וודא שהמיכל קיים לפני שאתה מנסה להשתמש בו
    if (previewContainer) {
        previewContainer.classList.add('flex', 'justify-center');
    }
    
    // וודא שכל האלמנטים קיימים לפני שממשיכים
    if (!desktopViewBtn || !tabletViewBtn || !mobileViewBtn || !previewIframe) {
        console.error('לא נמצאו כל האלמנטים הנדרשים להחלפת תצוגה');
        return; // יציאה מהפונקציה אם חסרים אלמנטים
    }
    
    // הגדרת רוחב התצוגות השונות
    const viewportSizes = {
        desktop: '100%',
        tablet: '768px',
        mobile: '375px'
    };
    
    // פונקציה להסרת מצב פעיל מכל הכפתורים
    function clearActiveState() {
        desktopViewBtn.classList.remove('text-purple-600', 'bg-purple-50');
        desktopViewBtn.classList.add('text-gray-700', 'hover:bg-gray-100');
        
        tabletViewBtn.classList.remove('text-purple-600', 'bg-purple-50');
        tabletViewBtn.classList.add('text-gray-700', 'hover:bg-gray-100');
        
        mobileViewBtn.classList.remove('text-purple-600', 'bg-purple-50');
        mobileViewBtn.classList.add('text-gray-700', 'hover:bg-gray-100');
    }
    
    // מעבר לתצוגת מחשב שולחני
    desktopViewBtn.addEventListener('click', () => {
        clearActiveState();
        previewIframe.style.width = viewportSizes.desktop;
        previewIframe.style.maxWidth = '100%';
        
        desktopViewBtn.classList.remove('text-gray-700', 'hover:bg-gray-100');
        desktopViewBtn.classList.add('text-purple-600', 'bg-purple-50');
    });
    
    // מעבר לתצוגת טאבלט
    tabletViewBtn.addEventListener('click', () => {
        clearActiveState();
        previewIframe.style.width = viewportSizes.tablet;
        previewIframe.style.maxWidth = '100%';
        
        tabletViewBtn.classList.remove('text-gray-700', 'hover:bg-gray-100');
        tabletViewBtn.classList.add('text-purple-600', 'bg-purple-50');
    });
    
    // מעבר לתצוגת נייד
    mobileViewBtn.addEventListener('click', () => {
        clearActiveState();
        previewIframe.style.width = viewportSizes.mobile;
        previewIframe.style.maxWidth = '100%';
        
        mobileViewBtn.classList.remove('text-gray-700', 'hover:bg-gray-100');
        mobileViewBtn.classList.add('text-purple-600', 'bg-purple-50');
    });
    
    // הגדרת תצוגת מחשב שולחני כברירת מחדל
    desktopViewBtn.click();


      // הטיפול בכפתורי ההדר - הוסף כאן
      document.getElementById('preview-button').addEventListener('click', function() {
        const newWindow = window.open('', '_blank');
        newWindow.document.open();
        newWindow.document.write(currentContent);
        newWindow.document.close();
    });
    
    document.getElementById('header-change-template-btn').addEventListener('click', function() {
        document.getElementById('templates-modal').classList.remove('hidden');
    });



    // טיפול בכפתור ייבא מקוד HTML
document.getElementById('import-section-btn').addEventListener('click', function() {
    // הצגת המודל
    document.getElementById('import-html-modal').classList.remove('hidden');
});

// סגירת המודל
document.getElementById('close-import-html').addEventListener('click', function() {
    document.getElementById('import-html-modal').classList.add('hidden');
});

document.getElementById('cancel-import-html').addEventListener('click', function() {
    document.getElementById('import-html-modal').classList.add('hidden');
});

// ייבוא הקוד
document.getElementById('apply-import-html').addEventListener('click', function() {
    const importedHTML = document.getElementById('import-html-editor').value.trim();
    
    if (!importedHTML) {
        alert('אנא הכנס קוד HTML לייבוא');
        return;
    }
    
    // בדיקה בסיסית שזה באמת HTML
    if (!importedHTML.includes('<') || !importedHTML.includes('>')) {
        alert('הקוד שהוכנס אינו נראה כמו HTML תקין');
        return;
    }
    
    try {
        // בדיקה אם זה סקשן או דף שלם
        let content = importedHTML;
        
        // אם זה סקשן בודד, עטוף אותו ב-section
        if (!importedHTML.includes('<html') && !importedHTML.includes('<body')) {
            // בדוק אם הקוד כבר עטוף ב-section
            if (!importedHTML.trim().startsWith('<section') && !importedHTML.trim().startsWith('<header') && !importedHTML.trim().startsWith('<footer')) {
                content = '<section id="imported-section-' + Date.now() + '">' + importedHTML + '</section>';
            }
            
            // הוסף את הסקשן לגוף הדף
            const iframe = document.getElementById('preview-iframe');
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            
            // מצא איפה להוסיף את הסקשן (לפני ה-footer אם קיים)
            const footer = iframeDoc.querySelector('footer');
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = content;
            const newSection = tempDiv.firstElementChild;
            
            if (footer) {
                iframeDoc.body.insertBefore(newSection, footer);
            } else {
                iframeDoc.body.appendChild(newSection);
            }
            
            // עדכן את התוכן הנוכחי
            currentContent = iframeDoc.documentElement.outerHTML;
            
            // רענן את התצוגה המקדימה
            updatePreview();
            
            alert('הקוד יובא בהצלחה כסקשן חדש');
            
        } else {
            // אם זה דף שלם, החלף את כל התוכן
            if (confirm('הקוד שהוכנס נראה כמו דף HTML שלם. האם אתה בטוח שברצונך להחליף את כל התוכן הנוכחי?')) {
                currentContent = content;
                updatePreview();
                alert('הדף יובא בהצלחה');
            }
        }
        
        // סגור את המודל
        document.getElementById('import-html-modal').classList.add('hidden');
        
        // נקה את שדה הטקסט
        document.getElementById('import-html-editor').value = '';
        
    } catch (error) {
        console.error('שגיאה בייבוא הקוד:', error);
        alert('שגיאה בייבוא הקוד: ' + error.message);
    }
});




    
});


function setupModalOverlayClose(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    modal.addEventListener('click', function(event) {
        // בדיקה אם הלחיצה היתה על האוברליי עצמו (האזור האפור) ולא על תוכן המודל
        if (event.target === this) {
            // הסתרת המודל
            modal.classList.add('hidden');
        }
    });
}

// הפעלת הפונקציה על כל המודלים הקיימים
document.addEventListener('DOMContentLoaded', () => {
    // הרשימה של כל המודלים באתר
    const modals = [
        'templates-modal',
        'add-section-modal',
        'html-editor-modal',
        'form-settings-modal'
    ];
    
    // הפעלת הפונקציה על כל מודל
    modals.forEach(modalId => {
        setupModalOverlayClose(modalId);
    });
    
    // חיבור כפתור שמירה לפונקציית שמירה
    const saveButton = document.getElementById('save-button');
    if (saveButton) {
        saveButton.addEventListener('click', function() {
            // שמירת התוכן
            saveContent();
        });
        console.log('Save button initialized');
    } else {
        console.error('Save button not found');
    }
});




    </script>
