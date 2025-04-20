<script>
        // Initialize variables
        let currentContent = <?php echo json_encode($pageContent); ?>;
        let editor;
        let selectedSection = null;
        let activeSectionId = null;
        let sectionsList = [];
        
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
                
            }, 500);
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
            
            // Image controls
            if (images.length > 0) {
                images.forEach((image, index) => {
                    const controlDiv = document.createElement('div');
                    controlDiv.innerHTML = `
                        <label class="block text-sm font-medium text-gray-700 mb-1">תמונה ${index + 1}</label>
                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500 mb-2" 
                               value="${image.getAttribute('src')}" placeholder="קישור לתמונה" data-target="image-src-${index}">
                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" 
                               value="${image.getAttribute('alt') || ''}" placeholder="טקסט חלופי" data-target="image-alt-${index}">
                    `;
                    
                    controlsContainer.appendChild(controlDiv);
                    
                    const srcInput = controlDiv.querySelector('input[data-target^="image-src"]');
                    const altInput = controlDiv.querySelector('input[data-target^="image-alt"]');
                    
                    srcInput.addEventListener('input', () => {
                        image.setAttribute('src', srcInput.value);
                        
                        // Update current content
                        currentContent = iframeDoc.documentElement.outerHTML;
                    });
                    
                    altInput.addEventListener('input', () => {
                        image.setAttribute('alt', altInput.value);
                        
                        // Update current content
                        currentContent = iframeDoc.documentElement.outerHTML;
                    });
                });
            }
            
            // Background color control
            const controlDiv = document.createElement('div');
            controlDiv.innerHTML = `
                <label class="block text-sm font-medium text-gray-700 mb-1">צבע רקע</label>
                <div class="flex">
                    <input type="color" class="h-10 w-10 border border-gray-300 rounded-md ml-2" 
                          value="${rgbToHex(getComputedStyle(section).backgroundColor)}" data-target="bg-color">
                    <input type="text" class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" 
                          value="${rgbToHex(getComputedStyle(section).backgroundColor)}" data-target="bg-color-text" dir="ltr">
                </div>
            `;
            
            controlsContainer.appendChild(controlDiv);
            
            const colorInput = controlDiv.querySelector('input[type="color"]');
            const colorTextInput = controlDiv.querySelector('input[type="text"]');
            
            colorInput.addEventListener('input', () => {
                section.style.backgroundColor = colorInput.value;
                colorTextInput.value = colorInput.value;
                
                // Update current content
                currentContent = iframeDoc.documentElement.outerHTML;
            });
            
            colorTextInput.addEventListener('input', () => {
                if (/^#[0-9A-F]{6}$/i.test(colorTextInput.value)) {
                    section.style.backgroundColor = colorTextInput.value;
                    colorInput.value = colorTextInput.value;
                    
                    // Update current content
                    currentContent = iframeDoc.documentElement.outerHTML;
                }
            });
        }
        
        // Convert RGB to Hex
        function rgbToHex(rgb) {
            // Check if already hex
            if (rgb.startsWith('#')) {
                return rgb;
            }
            
            // Parse RGB
            const matches = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
            if (!matches) return '#ffffff';
            
            function hex(x) {
                return ("0" + parseInt(x).toString(16)).slice(-2);
            }
            
            return "#" + hex(matches[1]) + hex(matches[2]) + hex(matches[3]);
        }
        
        // Apply color scheme
        function applyColorScheme(schemeId) {
            const scheme = colorSchemes[schemeId];
            if (!scheme) return;
            
            // Set color inputs
            document.getElementById('primary-color').value = scheme.primary;
            document.getElementById('primary-color-text').value = scheme.primary;
            document.getElementById('secondary-color').value = scheme.secondary;
            document.getElementById('secondary-color-text').value = scheme.secondary;
            
            // Apply to iframe
            const iframe = document.getElementById('preview-iframe');
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            
            const style = document.createElement('style');
            style.textContent = `
                :root {
                    --primary-color: ${scheme.primary};
                    --secondary-color: ${scheme.secondary};
                    --accent-color: ${scheme.accent};
                    --bg-color: ${scheme.background};
                    --text-color: ${scheme.text};
                }
                
                body {
                    background-color: var(--bg-color);
                    color: var(--text-color);
                }
                
                .bg-primary, .bg-indigo-600, .bg-purple-600, .bg-blue-600 {
                    background-color: var(--primary-color) !important;
                }
                
                .text-primary, .text-indigo-600, .text-purple-600, .text-blue-600 {
                    color: var(--primary-color) !important;
                }
                
                .border-primary, .border-indigo-600, .border-purple-600, .border-blue-600 {
                    border-color: var(--primary-color) !important;
                }
                
                .bg-secondary, .bg-indigo-700, .bg-purple-700, .bg-blue-700 {
                    background-color: var(--secondary-color) !important;
                }
                
                .text-secondary, .text-indigo-700, .text-purple-700, .text-blue-700 {
                    color: var(--secondary-color) !important;
                }
                
                .border-secondary, .border-indigo-700, .border-purple-700, .border-blue-700 {
                    border-color: var(--secondary-color) !important;
                }
                
                a, button, .btn, .button {
                    transition: all 0.3s ease;
                }
                
                a.btn, button.btn, .btn, .button {
                    display: inline-block;
                    padding: 0.5rem 1rem;
                    border-radius: 0.375rem;
                    font-weight: 500;
                    text-align: center;
                }
                
                a.btn-primary, button.btn-primary, .btn-primary {
                    background-color: var(--primary-color);
                    color: white;
                }
                
                a.btn-primary:hover, button.btn-primary:hover, .btn-primary:hover {
                    background-color: var(--secondary-color);
                }
                
                a.btn-outline, button.btn-outline, .btn-outline {
                    background-color: transparent;
                    border: 1px solid var(--primary-color);
                    color: var(--primary-color);
                }
                
                a.btn-outline:hover, button.btn-outline:hover, .btn-outline:hover {
                    background-color: var(--primary-color);
                    color: white;
                }
            `;
            
            // Apply or update style
            const existingStyle = iframeDoc.head.querySelector('style#color-scheme');
            if (existingStyle) {
                existingStyle.textContent = style.textContent;
            } else {
                style.id = 'color-scheme';
                iframeDoc.head.appendChild(style);
            }
            
            // Update current content
            currentContent = iframeDoc.documentElement.outerHTML;
        }
        
        // Apply font
        function applyFont(fontFamily) {
            const iframe = document.getElementById('preview-iframe');
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            
            const style = document.createElement('style');
            style.textContent = `
                body, html {
                    font-family: ${fontFamily};
                }
            `;
            
            // Apply or update style
            const existingStyle = iframeDoc.head.querySelector('style#font-style');
            if (existingStyle) {
                existingStyle.textContent = style.textContent;
            } else {
                style.id = 'font-style';
                iframeDoc.head.appendChild(style);
            }
            
            // Update current content
            currentContent = iframeDoc.documentElement.outerHTML;
        }
        
 // Initialize page
 document.addEventListener('DOMContentLoaded', () => {
            // Initialize preview
            updatePreview();
            // הצגת פאנל העריכה כברירת מחדל במקום פאנל ההגדרות
            document.getElementById('page-settings-panel').classList.add('hidden');
            document.getElementById('section-editor-panel').classList.remove('hidden');
            document.getElementById('add-section-panel').classList.add('hidden');

            // עדכון סגנון הכפתורים
            document.getElementById('page-settings-tab').classList.remove('bg-purple-100', 'text-purple-700');
            document.getElementById('page-settings-tab').classList.add('text-gray-600', 'hover:bg-gray-100');
            document.getElementById('section-editor-tab').classList.remove('text-gray-600', 'hover:bg-gray-100');
            document.getElementById('section-editor-tab').classList.add('bg-purple-100', 'text-purple-700');


            // עדכון כותרת הפאנל
            document.getElementById('settings-panel-title').textContent = 'עריכת סקשן';
            // Initialize HTML editor
            editor = CodeMirror.fromTextArea(document.getElementById('html-editor'), {
                mode: 'htmlmixed',
                lineNumbers: true,
                indentUnit: 4,
                autoCloseTags: true,
                lineWrapping: true
            });
            
            // Tab switching
            document.getElementById('page-settings-tab').addEventListener('click', () => {
                document.getElementById('page-settings-panel').classList.remove('hidden');
                document.getElementById('section-editor-panel').classList.add('hidden');
                document.getElementById('add-section-panel').classList.add('hidden');
                
                document.getElementById('page-settings-tab').classList.remove('text-gray-600', 'hover:bg-gray-100');
                document.getElementById('page-settings-tab').classList.add('bg-purple-100', 'text-purple-700');
                document.getElementById('section-editor-tab').classList.remove('bg-purple-100', 'text-purple-700');
                document.getElementById('section-editor-tab').classList.add('text-gray-600', 'hover:bg-gray-100');
                document.getElementById('add-section-tab').classList.remove('bg-purple-100', 'text-purple-700');
                document.getElementById('add-section-tab').classList.add('text-gray-600', 'hover:bg-gray-100');
                
                document.getElementById('settings-panel-title').textContent = 'הגדרות כלליות';
                document.getElementById('back-to-list-btn').classList.add('hidden');
            });
            
             document.getElementById('section-editor-tab').addEventListener('click', () => {
                // עובר תמיד למצב עריכה בלי לבדוק אם יש selectedSection
                document.getElementById('page-settings-panel').classList.add('hidden');
                document.getElementById('section-editor-panel').classList.remove('hidden');
                document.getElementById('add-section-panel').classList.add('hidden');
                
                document.getElementById('page-settings-tab').classList.remove('bg-purple-100', 'text-purple-700');
                document.getElementById('page-settings-tab').classList.add('text-gray-600', 'hover:bg-gray-100');
                document.getElementById('section-editor-tab').classList.remove('text-gray-600', 'hover:bg-gray-100');
                document.getElementById('section-editor-tab').classList.add('bg-purple-100', 'text-purple-700');
                document.getElementById('add-section-tab').classList.remove('bg-purple-100', 'text-purple-700');
                document.getElementById('add-section-tab').classList.add('text-gray-600', 'hover:bg-gray-100');
                
                // אם אין סקשן נבחר, מציג הודעת ברירת מחדל
                if (!selectedSection) {
                    document.getElementById('section-controls').innerHTML = `
                    <div class="p-4 text-center text-gray-500">
                        <i class="ri-information-line text-xl mb-2 block"></i>
                        <p>בחר סקשן כדי לצפות בהגדרות שלו</p>
                    </div>
                    `;
                    document.getElementById('settings-panel-title').textContent = 'עריכת סקשן';
                }
            });

            
            document.getElementById('add-section-tab').addEventListener('click', () => {
                document.getElementById('page-settings-panel').classList.add('hidden');
                document.getElementById('section-editor-panel').classList.add('hidden');
                document.getElementById('add-section-panel').classList.remove('hidden');
                
                document.getElementById('page-settings-tab').classList.remove('bg-purple-100', 'text-purple-700');
                document.getElementById('page-settings-tab').classList.add('text-gray-600', 'hover:bg-gray-100');
                document.getElementById('section-editor-tab').classList.remove('bg-purple-100', 'text-purple-700');
                document.getElementById('section-editor-tab').classList.add('text-gray-600', 'hover:bg-gray-100');
                document.getElementById('add-section-tab').classList.remove('text-gray-600', 'hover:bg-gray-100');
                document.getElementById('add-section-tab').classList.add('bg-purple-100', 'text-purple-700');
                
                document.getElementById('settings-panel-title').textContent = 'הוספת סקשן';
            });
            
            // Back to list button
            document.getElementById('back-to-list-btn').addEventListener('click', () => {
                // Clear selection
                if (selectedSection) {
                    selectedSection.style.outline = 'none';
                    selectedSection = null;
                    activeSectionId = null;
                }
                
                // Update sidebar
                document.querySelectorAll('.section-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                // Show page settings
                document.getElementById('page-settings-panel').classList.remove('hidden');
                document.getElementById('section-editor-panel').classList.add('hidden');
                document.getElementById('add-section-panel').classList.add('hidden');
                
                document.getElementById('page-settings-tab').classList.remove('text-gray-600', 'hover:bg-gray-100');
                document.getElementById('page-settings-tab').classList.add('bg-purple-100', 'text-purple-700');
                document.getElementById('section-editor-tab').classList.remove('bg-purple-100', 'text-purple-700');
                document.getElementById('section-editor-tab').classList.add('text-gray-600', 'hover:bg-gray-100');
                document.getElementById('add-section-tab').classList.remove('bg-purple-100', 'text-purple-700');
                document.getElementById('add-section-tab').classList.add('text-gray-600', 'hover:bg-gray-100');
                
                document.getElementById('settings-panel-title').textContent = 'הגדרות כלליות';
                document.getElementById('back-to-list-btn').classList.add('hidden');
            });
            
            // Section buttons
            document.querySelectorAll('.section-button').forEach(button => {
                button.addEventListener('click', () => {
                    const sectionType = button.getAttribute('data-section');
                    addSection(sectionType);
                });
            });
            
            // Add section button in sidebar
            document.getElementById('add-section-btn').addEventListener('click', () => {
                // Show add section panel
                document.getElementById('page-settings-panel').classList.add('hidden');
                document.getElementById('section-editor-panel').classList.add('hidden');
                document.getElementById('add-section-panel').classList.remove('hidden');
                
                document.getElementById('page-settings-tab').classList.remove('bg-purple-100', 'text-purple-700');
                document.getElementById('page-settings-tab').classList.add('text-gray-600', 'hover:bg-gray-100');
                document.getElementById('section-editor-tab').classList.remove('bg-purple-100', 'text-purple-700');
                document.getElementById('section-editor-tab').classList.add('text-gray-600', 'hover:bg-gray-100');
                document.getElementById('add-section-tab').classList.remove('text-gray-600', 'hover:bg-gray-100');
                document.getElementById('add-section-tab').classList.add('bg-purple-100', 'text-purple-700');
                
                document.getElementById('settings-panel-title').textContent = 'הוספת סקשן';
            });
            
            // Section group filter
            document.getElementById('section-group-filter').addEventListener('change', function() {
                const selectedGroup = this.value;
                
                document.querySelectorAll('.section-group').forEach(group => {
                    if (!selectedGroup || group.getAttribute('data-group') === selectedGroup) {
                        group.style.display = 'block';
                    } else {
                        group.style.display = 'none';
                    }
                });
            });
            
            // Save button
            document.getElementById('save-button').addEventListener('click', () => {
                saveContent();
            });
            
            // Edit HTML button
            document.getElementById('edit-html-btn').addEventListener('click', () => {
                openHtmlEditor();
            });
            
            // HTML Editor modal buttons
            document.getElementById('close-html-editor').addEventListener('click', () => {
                closeHtmlEditor();
            });
            
            document.getElementById('cancel-html-edit').addEventListener('click', () => {
                closeHtmlEditor();
            });
            
            document.getElementById('apply-html-edit').addEventListener('click', () => {
                applyHtmlEdit();
            });
            
            // Remove section button
            document.getElementById('remove-section-btn').addEventListener('click', () => {
                removeSelectedSection();
            });
            
            // Color scheme selector
            document.getElementById('color-scheme').addEventListener('change', (e) => {
                applyColorScheme(e.target.value);
            });
            
            // Font selector
            document.getElementById('font-family').addEventListener('change', (e) => {
                applyFont(e.target.value);
            });
            
            // Color pickers
            document.getElementById('primary-color').addEventListener('input', (e) => {
                document.getElementById('primary-color-text').value = e.target.value;
                updateColors();
            });
            
            document.getElementById('primary-color-text').addEventListener('input', (e) => {
                if (/^#[0-9A-F]{6}$/i.test(e.target.value)) {
                    document.getElementById('primary-color').value = e.target.value;
                    updateColors();
                }
            });
            
            document.getElementById('secondary-color').addEventListener('input', (e) => {
                document.getElementById('secondary-color-text').value = e.target.value;
                updateColors();
            });
            
            document.getElementById('secondary-color-text').addEventListener('input', (e) => {
                if (/^#[0-9A-F]{6}$/i.test(e.target.value)) {
                    document.getElementById('secondary-color').value = e.target.value;
                    updateColors();
                }
            });
            
            // Initialize color scheme
            applyColorScheme('default');
            
            // Initialize font
            applyFont('Heebo, sans-serif');
        });
        
        // Update colors
        function updateColors() {
            const primaryColor = document.getElementById('primary-color').value;
            const secondaryColor = document.getElementById('secondary-color').value;
            
            const iframe = document.getElementById('preview-iframe');
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            
            const style = iframeDoc.head.querySelector('style#color-scheme');
            if (style) {
                style.textContent = style.textContent
                    .replace(/--primary-color: #[0-9a-f]{6}/i, `--primary-color: ${primaryColor}`)
                    .replace(/--secondary-color: #[0-9a-f]{6}/i, `--secondary-color: ${secondaryColor}`);
            }
            
            // Update current content
            currentContent = iframeDoc.documentElement.outerHTML;
        }
        
        // Add section to page
        function addSection(sectionType) {
            // Get section HTML from template
            fetch(`sections/${sectionType}/template.html`)
                .then(response => response.text())
                .then(html => {
                    // Insert into iframe content
                    const iframe = document.getElementById('preview-iframe');
                    const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                    
                    // Find where to insert the section (before the footer)
                    const footer = iframeDoc.querySelector('footer');
                    
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    const section = tempDiv.firstElementChild;
                    
                    // Add data attributes
                    section.setAttribute('data-section-type', sectionType);
                    section.setAttribute('data-title', sectionType.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase()));
                    section.id = `section-${Date.now()}`;
                    
                    if (footer) {
                        iframeDoc.body.insertBefore(section, footer);
                    } else {
                        iframeDoc.body.appendChild(section);
                    }
                    
                    // Update content
                    currentContent = iframeDoc.documentElement.outerHTML;
                    
                    // Refresh preview to add events
                    updatePreview();
                    
                    // Find and select the new section
                    setTimeout(() => {
                        const newSection = iframeDoc.querySelector(`[data-section-type="${sectionType}"]`);
                        if (newSection) {
                            selectSection(newSection, sectionsList.length - 1);
                        }
                    }, 500);
                })
                .catch(error => {
                    console.error('Error loading section template:', error);
                    alert('שגיאה בטעינת תבנית הסקשן');
                });
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
    
    // הגדרת מסגרת התצוגה המקדימה
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
        'html-editor-modal'
        // הוסף כאן עוד מודלים אם יש
    ];
    
    // הפעלת הפונקציה על כל מודל
    modals.forEach(modalId => {
        setupModalOverlayClose(modalId);
    });
});
    </script>
