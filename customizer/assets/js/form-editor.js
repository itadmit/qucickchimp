/**
 * Form Editor for QuickSite Landing Page Customizer
 * This script handles dynamic form editing functionality
 */

// Form field types
const FIELD_TYPES = {
    text: 'טקסט',
    email: 'אימייל',
    tel: 'טלפון',
    number: 'מספר',
    textarea: 'טקסט ארוך',
    select: 'רשימה נפתחת',
    checkbox: 'תיבת סימון',
    radio: 'כפתורי רדיו',
    date: 'תאריך',
    hidden: 'שדה מוסתר'
};

// Global variables
let currentForm = null;
let currentFormFields = [];
let formIframeDoc = null;

// משתנה לשמירת מצביע למחוון נוכחי
let currentDropIndicator = null;

// יצירת מחוון ההשמטה
function createFieldDropIndicator() {
    const indicator = document.createElement('div');
    indicator.className = 'drop-indicator';
    indicator.innerHTML = `<div class="drop-indicator-line"></div>`;
    return indicator;
}

/**
 * Initialize the form editor
 * @param {HTMLElement} section - The section element containing the form
 * @param {Document} iframeDoc - The iframe document
 */
function initFormEditor(section, iframeDoc) {
    // Store references
    formIframeDoc = iframeDoc;
    currentForm = section.querySelector('form');
    
    if (!currentForm) {
        console.error('No form found in the section');
        return;
    }
    
    // Load existing form fields
    loadExistingFormFields();
    
    // Load existing form attributes
    loadExistingFormAttributes();
    
    // Setup event listeners
    setupFormEditorEventListeners();
    
    // Setup redirect checkbox
    setupRedirectCheckbox();
}

/**
 * Load existing form fields into the UI
 */
async function loadExistingFormFields() {
    // וודא שהמודאל נפתח ומוכן לפני חיפוש האלמנטים
    setTimeout(() => {
        // שינוי מזהה האלמנט שמחפשים - יש להשתמש ב-modal-form-fields-container
        const formFieldsContainer = document.getElementById('modal-form-fields-container');
        if (!formFieldsContainer) {
            console.error('Form fields container not found.');
            return;
        }
    
        const existingFields = extractExistingFormFields();
        if (existingFields.length === 0) {
            console.log('No existing fields found.');
            return;
        }
    
        console.log('Loading existing fields:', existingFields);
    
        // Clear existing fields
        formFieldsContainer.innerHTML = '';
        
        // אתחול משתנה גלובלי לשמירת מידע על האלמנט הנגרר
        window._draggedFieldItem = null;
        
        // ניקוי אירועי גרירה קודמים מהקונטיינר
        formFieldsContainer.removeEventListener('dragover', handleFieldDragOver);
        formFieldsContainer.removeEventListener('dragenter', handleFieldDragEnter);
        formFieldsContainer.removeEventListener('dragleave', handleFieldDragLeave);
        formFieldsContainer.removeEventListener('drop', handleFieldDrop);
        document.removeEventListener('dragend', handleFieldDragEnd);
        
        // הוספת אירועי גרירה חדשים
        formFieldsContainer.addEventListener('dragover', handleFieldDragOver);
        formFieldsContainer.addEventListener('dragenter', handleFieldDragEnter);
        formFieldsContainer.addEventListener('dragleave', handleFieldDragLeave);
        formFieldsContainer.addEventListener('drop', handleFieldDrop);
        document.addEventListener('dragend', handleFieldDragEnd);
        
        // הצגת השדות הקיימים
        existingFields.forEach((field, index) => {
            const fieldElement = createFieldUI(field, index);
            formFieldsContainer.appendChild(fieldElement);
            
            // הוספת אירועי גרירה לאלמנט
            const dragHandle = fieldElement.querySelector('.handle');
            if (dragHandle) {
                dragHandle.setAttribute('draggable', 'true');
                dragHandle.addEventListener('dragstart', handleFieldDragStart);
            }
        });
        
        // עדכון ההצגה
        const formFieldsSection = document.getElementById('modal-form-fields-section');
        const noFieldsMessage = document.getElementById('modal-no-fields-message');
        
        if (formFieldsSection && currentFormFields.length > 0) {
            formFieldsSection.classList.remove('hidden');
        }
        
        if (noFieldsMessage) {
            if (currentFormFields.length > 0) {
                noFieldsMessage.classList.add('hidden');
            } else {
                noFieldsMessage.classList.remove('hidden');
            }
        }
        
        // עדכון מספר השדות
        const fieldsCountElement = document.getElementById('modal-fields-count');
        if (fieldsCountElement) {
            fieldsCountElement.textContent = currentFormFields.length;
        }
        
    }, 100); // תן למודאל זמן להיטען לפני חיפוש האלמנטים
}

/**
 * חילוץ שדות קיימים מהטופס
 */
function extractExistingFormFields() {
    // וודא שיש טופס
    if (!currentForm) return;
    
    // איפוס המערך של השדות
    currentFormFields = [];
    
    // קבל את כל הילדים של הטופס
    const formChildren = Array.from(currentForm.children);
    
    formChildren.forEach((container, index) => {
        // בדיקה האם זה מיכל של שדה (בד"כ div עם mb-4)
        if (container.tagName.toLowerCase() !== 'div' || !container.querySelector('input, textarea, select')) {
            return; // דלג על אלמנטים שאינם שדות
        }
        
        // חפש את אלמנט השדה בתוך המיכל
        const element = container.querySelector('input, textarea, select');
        
        // דלג על כפתורי שליחה או שדות מוסתרים שאינם שדות מותאמים
        if (element.type === 'submit' || (element.type === 'hidden' && element.name !== 'custom_field')) {
            return;
        }
        
        // קבע את סוג השדה
        let fieldType = element.type;
        if (element.tagName.toLowerCase() === 'textarea') {
            fieldType = 'textarea';
        } else if (element.tagName.toLowerCase() === 'select') {
            fieldType = 'select';
        }
        
        // חלץ את תווית השדה
        let labelText = '';
        const label = container.querySelector('label');
        if (label) {
            labelText = label.textContent;
            // הסר כוכבית של שדה חובה אם קיימת
            labelText = labelText.replace(/\s*\*\s*$/, '');
        }
        
        // יצירת אובייקט השדה
        const field = {
            id: element.id || `field-${index}`,
            name: element.name || element.id || `field-${index}`,
            type: fieldType,
            label: labelText,
            required: element.hasAttribute('required'),
            placeholder: element.getAttribute('placeholder') || '',
            value: element.value || '',
            options: []
        };
        
        // טיפול באפשרויות לשדות select
        if (fieldType === 'select') {
            const options = element.querySelectorAll('option');
            options.forEach(option => {
                field.options.push({
                    value: option.value,
                    text: option.textContent
                });
            });
        }
        
        // ניסיון לחלץ מידע נוסף משדה מוסתר
        const hiddenField = container.querySelector('input[type="hidden"][name="custom_field"]');
        if (hiddenField) {
            try {
                const customData = JSON.parse(hiddenField.value);
                if (customData.options && customData.options.length > 0) {
                    field.options = customData.options;
                }
            } catch (e) {
                console.warn('Failed to parse custom field data', e);
            }
        }
        
        // הוסף את השדה למערך
        currentFormFields.push(field);
    });
    
    console.log('Extracted', currentFormFields.length, 'fields from form');
    return currentFormFields;
}

/**
 * Set up drag and drop functionality for form fields
 */
function setupFieldDragDrop() {
    const container = document.getElementById('form-fields-container');
    
    // Initialize Sortable if it exists
    if (typeof Sortable !== 'undefined') {
        new Sortable(container, {
            animation: 150,
            handle: '.handle', // Use the drag handle
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onEnd: function(evt) {
                // Update the fields array based on the new order
                const newOrder = Array.from(container.children).map(element => 
                    parseInt(element.getAttribute('data-field-index'), 10)
                );
                
                // Rearrange the array based on new order
                const reorderedFields = [];
                newOrder.forEach(oldIndex => {
                    reorderedFields.push(currentFormFields[oldIndex]);
                });
                
                // Update the current fields array
                currentFormFields = reorderedFields;
                
                // Reload fields UI with updated indexes
                loadExistingFormFields();
                
                // Update the form in the iframe
                updateFormInIframe();
            }
        });
    }
}

/**
 * Create UI for a form field
 * @param {Object} field - The field data
 * @param {number} index - The field index
 * @return {HTMLElement} - The created field UI element
 */
function createFieldUI(field, index) {
    const fieldElement = document.createElement('div');
    fieldElement.id = `field-item-${index}`;
    fieldElement.className = 'field-item bg-white shadow rounded-lg p-3 mb-2 hover:shadow-md transition-all duration-200';
    fieldElement.setAttribute('data-field-index', index);
    
    // Add drag handle and flex container for better layout
    fieldElement.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-start flex-col">
                <div class="flex items-center w-full">
                    <div class="handle mr-2 text-gray-400 cursor-move">
                        <i class="ri-drag-move-2-line"></i>
                    </div>
                    <h3 class="text-md font-semibold text-gray-800 truncate max-w-[160px]">${field.label || 'שדה ללא תווית'}</h3>
                    ${field.required ? '<span class="field-required-indicator mr-1"><i class="ri-asterisk"></i></span>' : ''}
                </div>
                <div class="text-sm text-gray-500 flex items-center mr-6 mt-1">
                    <span class="field-type-icon mr-1">
                        ${getFieldTypeIcon(field.type)}
                    </span>
                    <span class="field-type">${getFieldTypeName(field.type)}</span>
                </div>
            </div>
            <div class="field-actions flex">
                <button class="edit-field-btn text-gray-600 hover:text-purple-700 p-1 rounded-md hover:bg-purple-100 transition-colors" title="ערוך שדה">
                    <i class="ri-edit-line"></i>
                </button>
                <button class="duplicate-field-btn text-gray-600 hover:text-green-700 p-1 rounded-md hover:bg-green-100 transition-colors" title="שכפל שדה">
                    <i class="ri-file-copy-line"></i>
                </button>
                <button class="delete-field-btn text-gray-600 hover:text-red-700 p-1 rounded-md hover:bg-red-100 transition-colors" title="מחק שדה">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `;
    
    // Add edit button event listener
    const editButton = fieldElement.querySelector('.edit-field-btn');
    editButton.addEventListener('click', (e) => {
        e.stopPropagation();
        showFieldEditModal(index);
    });
    
    // Add delete button event listener
    const deleteButton = fieldElement.querySelector('.delete-field-btn');
    deleteButton.addEventListener('click', (e) => {
        e.stopPropagation();
        if (confirm('האם אתה בטוח שברצונך למחוק שדה זה?')) {
            deleteFormField(index);
        }
    });
    
    // Add duplicate button event listener
    const duplicateButton = fieldElement.querySelector('.duplicate-field-btn');
    duplicateButton.addEventListener('click', (e) => {
        e.stopPropagation();
        const duplicatedField = JSON.parse(JSON.stringify(field));
        duplicatedField.id = `${field.id}-copy-${Date.now()}`;
        duplicatedField.name = `${field.name}-copy-${Date.now()}`;
        duplicatedField.label = `${field.label} (העתק)`;
        currentFormFields.splice(index + 1, 0, duplicatedField);
        updateFormInIframe();
        loadExistingFormFields();
    });

    // Make entire field element clickable for editing
    fieldElement.addEventListener('click', (e) => {
        if (!e.target.closest('button') && !e.target.closest('.handle')) {
            showFieldEditModal(index);
        }
    });
    
    return fieldElement;
}

/**
 * Helper function to get field type display name
 * @param {string} type - The field type
 * @return {string} - The display name
 */
function getFieldTypeName(type) {
    const types = {
        'text': 'טקסט',
        'email': 'אימייל',
        'number': 'מספר',
        'tel': 'טלפון',
        'textarea': 'אזור טקסט',
        'select': 'בחירה מרשימה',
        'checkbox': 'תיבת סימון',
        'radio': 'כפתורי רדיו',
        'date': 'תאריך',
        'time': 'שעה',
        'url': 'כתובת אתר',
        'password': 'סיסמה',
        'file': 'קובץ'
    };
    
    return types[type] || type;
}

/**
 * Helper function to get field type icon
 * @param {string} type - The field type
 * @return {string} - The icon HTML
 */
function getFieldTypeIcon(type) {
    const icons = {
        'text': '<i class="ri-text-wrap"></i>',
        'email': '<i class="ri-mail-line"></i>',
        'number': '<i class="ri-hashtag"></i>',
        'tel': '<i class="ri-phone-line"></i>',
        'textarea': '<i class="ri-file-text-line"></i>',
        'select': '<i class="ri-list-check"></i>',
        'checkbox': '<i class="ri-checkbox-line"></i>',
        'radio': '<i class="ri-radio-button-line"></i>',
        'date': '<i class="ri-calendar-line"></i>',
        'time': '<i class="ri-time-line"></i>',
        'url': '<i class="ri-link"></i>',
        'password': '<i class="ri-lock-line"></i>',
        'file': '<i class="ri-file-upload-line"></i>'
    };
    
    return icons[type] || '<i class="ri-text-wrap"></i>';
}

/**
 * Setup form editor event listeners
 */
function setupFormEditorEventListeners() {
    // Add new field button
    const addFieldBtn = document.getElementById('modal-add-form-field-btn');
    if (addFieldBtn) {
        addFieldBtn.addEventListener('click', () => {
            showFieldEditModal();
        });
    }
    
    // Form list select
    const formListSelect = document.getElementById('modal-form-list-select');
    if (formListSelect) {
        formListSelect.addEventListener('change', () => {
            updateFormAttributes();
        });
    }
    
    // Form tag input
    const formTagInput = document.getElementById('modal-form-tag-input');
    if (formTagInput) {
        // שימוש באירוע input במקום change כדי לתפוס שינויים מיידיים
        formTagInput.addEventListener('input', () => {
            updateFormAttributes();
        });
        
        // תפיסת אירוע change גם כן למקרה של שינויים באמצעים אחרים
        formTagInput.addEventListener('change', () => {
            updateFormAttributes();
        });
        
        // הוסף אירוע blur לשמירה כשהמשתמש עוזב את השדה
        formTagInput.addEventListener('blur', () => {
            updateFormAttributes();
            
            // עדכן את הטופס באייפריים כדי לוודא שהתגיות נשמרות
            updateFormInIframe();
        });
    }
}

/**
 * Setup redirect checkbox functionality
 */
function setupRedirectCheckbox() {
    // שימוש במזהים הנכונים של האלמנטים במודל
    const redirectCheckbox = document.getElementById('modal-redirect-checkbox');
    const redirectUrlContainer = document.getElementById('modal-redirect-url-container');
    const redirectUrlInput = document.getElementById('modal-redirect-url-input');
    
    // בדיקה שכל האלמנטים קיימים
    if (!redirectCheckbox || !redirectUrlContainer || !redirectUrlInput) {
        console.warn('Missing form redirect elements in setupRedirectCheckbox');
        return;
    }
    
    // Check if the form has a redirect URL set
    const redirectUrl = currentForm.getAttribute('data-redirect');
    if (redirectUrl) {
        redirectCheckbox.checked = true;
        redirectUrlContainer.classList.remove('hidden');
        redirectUrlInput.value = redirectUrl;
    }
    
    // Toggle redirect URL input visibility
    redirectCheckbox.addEventListener('change', () => {
        if (redirectCheckbox.checked) {
            redirectUrlContainer.classList.remove('hidden');
        } else {
            redirectUrlContainer.classList.add('hidden');
            redirectUrlInput.value = '';
            updateFormAttributes();
        }
    });
    
    // Update redirect URL
    redirectUrlInput.addEventListener('change', () => {
        updateFormAttributes();
    });
}

/**
 * Show modal for editing a field
 * @param {number} fieldIndex - The field index (undefined for new field)
 */
function showFieldEditModal(fieldIndex) {
    // Create modal if it doesn't exist
    let modalElement = document.getElementById('field-edit-modal');
    if (!modalElement) {
        modalElement = document.createElement('div');
        modalElement.id = 'field-edit-modal';
        modalElement.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50';
        document.body.appendChild(modalElement);
    }
    
    // Determine if we're editing or creating
    const isEditing = fieldIndex !== undefined;
    const field = isEditing ? currentFormFields[fieldIndex] : {
        id: `field-${Date.now()}`,
        name: '',
        type: 'text',
        label: '',
        required: false,
        placeholder: '',
        value: '',
        options: []
    };
    
    // Set modal content
    modalElement.innerHTML = `
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-medium">${isEditing ? 'ערוך שדה' : 'הוסף שדה חדש'}</h3>
                <button id="close-field-modal" class="text-gray-500 hover:text-gray-700">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>
            
            <div class="p-4 space-y-4 max-h-[70vh] overflow-y-auto">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">סוג שדה</label>
                    <select id="field-type" class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm">
                        ${Object.entries(FIELD_TYPES).map(([value, label]) => `
                            <option value="${value}" ${field.type === value ? 'selected' : ''}>
                                ${label}
                            </option>
                        `).join('')}
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">תווית</label>
                    <input type="text" id="field-label" class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm" 
                           value="${field.label}" placeholder="תווית השדה">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">שם השדה (ID)</label>
                    <input type="text" id="field-name" dir="ltr" class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm" 
                           value="${field.name}" placeholder="name_field">
                    <p class="text-xs text-gray-500 mt-1">שם השדה ישמש גם כ-ID בדף וכשם הפרמטר בטופס.</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Placeholder</label>
                    <input type="text" id="field-placeholder" class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm" 
                           value="${field.placeholder}" placeholder="טקסט לדוגמה שיוצג בשדה">
                </div>
                
                <div id="field-options-container" class="${field.type === 'select' || field.type === 'radio' ? '' : 'hidden'}">
                    <label class="block text-sm font-medium text-gray-700 mb-1">אפשרויות</label>
                    <div id="options-list" class="space-y-2 mb-2">
                        ${field.options.map((option, i) => `
                            <div class="option-item flex">
                                <input type="text" class="option-value block w-1/3 py-2 px-3 border border-gray-300 bg-white rounded-r-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm" 
                                       placeholder="value" value="${option.value}" dir="ltr">
                                <input type="text" class="option-text block w-2/3 py-2 px-3 border border-l-0 border-gray-300 bg-white rounded-l-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm" 
                                       placeholder="טקסט להצגה" value="${option.text}">
                            </div>
                        `).join('') || `
                            <div class="option-item flex">
                                <input type="text" class="option-value block w-1/3 py-2 px-3 border border-gray-300 bg-white rounded-r-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm" 
                                       placeholder="value" value="" dir="ltr">
                                <input type="text" class="option-text block w-2/3 py-2 px-3 border border-l-0 border-gray-300 bg-white rounded-l-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm" 
                                       placeholder="טקסט להצגה" value="">
                            </div>
                        `}
                    </div>
                    <button type="button" id="add-option-btn" class="text-sm text-purple-600 hover:text-purple-800">
                        <i class="ri-add-line ml-1"></i> הוסף אפשרות
                    </button>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ערך ברירת מחדל</label>
                    <input type="text" id="field-value" class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm" 
                           value="${field.value}" placeholder="ערך התחלתי">
                </div>
                
                <div class="flex items-center">
                    <input id="field-required" type="checkbox" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded" ${field.required ? 'checked' : ''}>
                    <label for="field-required" class="mr-2 block text-sm text-gray-700">שדה חובה</label>
                </div>
            </div>
            
            <div class="p-4 border-t flex justify-end">
                <button id="cancel-field-btn" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                    ביטול
                </button>
                <button id="save-field-btn" class="mr-3 px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none">
                    ${isEditing ? 'עדכן' : 'הוסף'} שדה
                </button>
            </div>
        </div>
    `;
    
    modalElement.classList.remove('hidden');
    
    // Setup event listeners
    setupFieldModalEventListeners(fieldIndex);
}

/**
 * Setup event listeners for field edit modal
 * @param {number} fieldIndex - The field index (undefined for new field)
 */
function setupFieldModalEventListeners(fieldIndex) {
    const closeBtn = document.getElementById('close-field-modal');
    const cancelBtn = document.getElementById('cancel-field-btn');
    const saveBtn = document.getElementById('save-field-btn');
    const fieldTypeSelect = document.getElementById('field-type');
    const addOptionBtn = document.getElementById('add-option-btn');
    
    // Close modal
    const closeModal = () => {
        const modalElement = document.getElementById('field-edit-modal');
        if (modalElement) {
            modalElement.remove();
        }
    };
    
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    
    // Toggle options container visibility based on field type
    fieldTypeSelect.addEventListener('change', () => {
        const optionsContainer = document.getElementById('field-options-container');
        const fieldType = fieldTypeSelect.value;
        
        if (fieldType === 'select' || fieldType === 'radio') {
            optionsContainer.classList.remove('hidden');
        } else {
            optionsContainer.classList.add('hidden');
        }
    });
    
    // Add option button
    addOptionBtn.addEventListener('click', () => {
        const optionsList = document.getElementById('options-list');
        const newOption = document.createElement('div');
        newOption.className = 'option-item flex';
        newOption.innerHTML = `
            <input type="text" class="option-value block w-1/3 py-2 px-3 border border-gray-300 bg-white rounded-r-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm" 
                   placeholder="value" value="" dir="ltr">
            <input type="text" class="option-text block w-2/3 py-2 px-3 border border-l-0 border-gray-300 bg-white rounded-l-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm" 
                   placeholder="טקסט להצגה" value="">
        `;
        optionsList.appendChild(newOption);
    });
    
    // Save field
    saveBtn.addEventListener('click', () => {
        // Get field data
        const fieldData = {
            id: document.getElementById('field-name').value || `field-${Date.now()}`,
            name: document.getElementById('field-name').value || `field-${Date.now()}`,
            type: document.getElementById('field-type').value,
            label: document.getElementById('field-label').value,
            required: document.getElementById('field-required').checked,
            placeholder: document.getElementById('field-placeholder').value,
            value: document.getElementById('field-value').value,
            options: []
        };
        
        // Get options if applicable
        if (fieldData.type === 'select' || fieldData.type === 'radio') {
            const optionItems = document.querySelectorAll('.option-item');
            optionItems.forEach(item => {
                const valueInput = item.querySelector('.option-value');
                const textInput = item.querySelector('.option-text');
                
                if (valueInput.value || textInput.value) {
                    fieldData.options.push({
                        value: valueInput.value || textInput.value,
                        text: textInput.value || valueInput.value
                    });
                }
            });
        }
        
        // Validate field
        if (!fieldData.label) {
            alert('נא להזין תווית לשדה');
            return;
        }
        
        if (!fieldData.name) {
            alert('נא להזין שם לשדה');
            return;
        }
        
        if ((fieldData.type === 'select' || fieldData.type === 'radio') && fieldData.options.length === 0) {
            alert('נא להוסיף לפחות אפשרות אחת');
            return;
        }
        
        // Update or add field
        if (fieldIndex !== undefined) {
            // Update existing field
            currentFormFields[fieldIndex] = fieldData;
        } else {
            // Add new field
            currentFormFields.push(fieldData);
        }
        
        // Update form in iframe
        updateFormInIframe();
        
        // Update form fields UI
        loadExistingFormFields();
        
        // Close modal
        closeModal();
    });
}

/**
 * Delete a form field
 * @param {number} index - The field index
 */
function deleteFormField(index) {
    currentFormFields.splice(index, 1);
    updateFormInIframe();
    loadExistingFormFields();
}

/**
 * Update form in the iframe with the current fields
 */
function updateFormInIframe() {
    // שמור את השדות המקוריים לפני ניקוי וביצוע שינויים
    const originalFields = [...currentFormFields];
    
    // קבל את האייפריים ואת המסמך שלו
    const iframe = document.getElementById('preview-iframe');
    if (!iframe || !iframe.contentDocument) return;
    
    const iframeDoc = iframe.contentDocument;
    
    // קבל את הטופס מהאייפריים
    const previewForm = currentForm;
    if (!previewForm) return;
    
    // שמור את המאפיינים (data attributes) של הטופס לפני ניקוי
    const formDataAttributes = {
        tags: previewForm.getAttribute('data-tags'),
        list: previewForm.getAttribute('data-list'),
        redirect: previewForm.getAttribute('data-redirect')
    };
    
    // נקה את הטופס הנוכחי (שמור על כפתור שליחה אם קיים)
    let submitButton = null;
    // שמור על כפתור השליחה אם קיים
    Array.from(previewForm.children).forEach(child => {
        if (child.querySelector('input[type="submit"]')) {
            submitButton = child.cloneNode(true);
        }
    });
    
    // נקה את הטופס
    previewForm.innerHTML = '';
    
    // שחזר את המאפיינים של הטופס
    if (formDataAttributes.tags) {
        previewForm.setAttribute('data-tags', formDataAttributes.tags);
    }
    if (formDataAttributes.list) {
        previewForm.setAttribute('data-list', formDataAttributes.list);
    }
    if (formDataAttributes.redirect) {
        previewForm.setAttribute('data-redirect', formDataAttributes.redirect);
    }
    
    // הוסף את כל השדות לטופס
    originalFields.forEach(field => {
        const fieldContainer = document.createElement('div');
        fieldContainer.className = 'mb-4';
        
        // הוסף תווית
        if (field.label) {
            const label = document.createElement('label');
            label.setAttribute('for', field.id);
            label.className = 'block text-gray-700 text-sm font-bold mb-2';
            label.innerHTML = field.label + (field.required ? ' <span class="text-red-500">*</span>' : '');
            fieldContainer.appendChild(label);
        }
        
        // יצירת אלמנט השדה בהתאם לסוג
        let element;
        
        switch (field.type) {
            case 'textarea':
                element = document.createElement('textarea');
                element.className = 'shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline';
                element.rows = 4;
                break;
                
            case 'select':
                element = document.createElement('select');
                element.className = 'shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline';
                
                // הוסף את האפשרויות
                field.options.forEach(option => {
                    const optElement = document.createElement('option');
                    optElement.value = option.value;
                    optElement.textContent = option.text;
                    element.appendChild(optElement);
                });
                break;
                
            case 'checkbox':
                const checkboxContainer = document.createElement('div');
                checkboxContainer.className = 'flex items-center';
                
                element = document.createElement('input');
                element.className = 'mr-2';
                element.type = 'checkbox';
                
                const checkboxLabel = document.createElement('span');
                checkboxLabel.className = 'text-gray-700';
                checkboxLabel.textContent = field.label || '';
                
                checkboxContainer.appendChild(element);
                checkboxContainer.appendChild(checkboxLabel);
                fieldContainer.appendChild(checkboxContainer);
                break;
                
            case 'radio':
                // TODO: Implement radio buttons
                element = document.createElement('input');
                element.type = 'text';
                element.className = 'shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline';
                break;
                
            default:
                element = document.createElement('input');
                element.type = field.type || 'text';
                element.className = 'shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline';
                break;
        }
        
        // הגדר את המאפיינים המשותפים לכל השדות
        element.id = field.id;
        element.name = field.name;
        
        if (field.placeholder) {
            element.setAttribute('placeholder', field.placeholder);
        }
        
        if (field.value) {
            if (field.type === 'checkbox') {
                element.checked = field.value === 'true' || field.value === true;
            } else {
                element.value = field.value;
            }
        }
        
        if (field.required) {
            element.setAttribute('required', 'required');
        }
        
        // הוסף שדה נסתר למידע נוסף אם נדרש
        const hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = 'custom_field';
        hiddenField.value = JSON.stringify({
            fieldType: field.type,
            required: field.required,
            options: field.options
        });
        
        // הוסף את האלמנטים למיכל אם עוד לא נוספו
        if (field.type !== 'checkbox') {
            fieldContainer.appendChild(element);
        }
        
        fieldContainer.appendChild(hiddenField);
        previewForm.appendChild(fieldContainer);
    });
    
    // הוסף שדה נסתר לתגיות אם יש
    const formTags = previewForm.getAttribute('data-tags');
    if (formTags) {
        const tagsField = document.createElement('input');
        tagsField.type = 'hidden';
        tagsField.name = 'tags';
        tagsField.value = formTags;
        previewForm.appendChild(tagsField);
    }
    
    // הוסף בחזרה את כפתור השליחה אם היה קיים
    if (submitButton) {
        previewForm.appendChild(submitButton);
    } else {
        // צור כפתור שליחה חדש אם לא היה קיים
        const submitContainer = document.createElement('div');
        submitContainer.className = 'flex items-center justify-between';
        
        const submitBtn = document.createElement('input');
        submitBtn.type = 'submit';
        submitBtn.value = 'שלח';
        submitBtn.className = 'bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline';
        
        submitContainer.appendChild(submitBtn);
        previewForm.appendChild(submitContainer);
    }
    
    // עדכן את לוגיקת התצוגה של השדות במודל עורך הטפסים
    loadExistingFormFields();
}

/**
 * Update form attributes
 */
function updateFormAttributes() {
    if (!currentForm) return;
    
    // Set form action and method
    currentForm.setAttribute('action', '#');
    currentForm.setAttribute('method', 'post');
    
    // Set data attributes for list and tags
    const formListSelect = document.getElementById('modal-form-list-select');
    if (formListSelect && formListSelect.value) {
        currentForm.setAttribute('data-list-id', formListSelect.value);
    } else {
        currentForm.removeAttribute('data-list-id');
    }
    
    const formTagInput = document.getElementById('modal-form-tag-input');
    if (formTagInput && formTagInput.value) {
        currentForm.setAttribute('data-tags', formTagInput.value);
    } else {
        currentForm.removeAttribute('data-tags');
    }
    
    // Set redirect URL
    const redirectCheckbox = document.getElementById('modal-redirect-checkbox');
    const redirectUrlInput = document.getElementById('modal-redirect-url-input');
    if (redirectCheckbox && redirectCheckbox.checked && redirectUrlInput && redirectUrlInput.value) {
        currentForm.setAttribute('data-redirect', redirectUrlInput.value);
    } else {
        currentForm.removeAttribute('data-redirect');
    }
    
    // Update content in parent iframe
    if (formIframeDoc) {
        window.parent.currentContent = formIframeDoc.documentElement.outerHTML;
    }
}

/**
 * Load existing form attributes (tags, redirect, etc.)
 */
function loadExistingFormAttributes() {
    if (!currentForm) return;
    
    // Load tags if exist
    const formTags = currentForm.getAttribute('data-tags');
    const formTagInput = document.getElementById('modal-form-tag-input');
    if (formTagInput && formTags) {
        formTagInput.value = formTags;
    }
    
    // Load list selection if exists
    const formList = currentForm.getAttribute('data-list-id');
    const formListSelect = document.getElementById('modal-form-list-select');
    if (formListSelect && formList) {
        formListSelect.value = formList;
    }
}

// הוסף אירוע לכפתור השמירה בדף העורך
document.addEventListener('DOMContentLoaded', function() {
    // פנה לחלון האב שהוא העורך הראשי
    if (window.parent && window.parent.document) {
        const saveButton = window.parent.document.querySelector('#save-page-button, .save-page-button');
        if (saveButton) {
            saveButton.addEventListener('click', function() {
                // וודא שהתגיות מעודכנות לפני השמירה
                if (currentForm) {
                    updateFormAttributes();
                    updateFormInIframe();
                    
                    console.log('Form attributes updated before save:', {
                        tags: currentForm.getAttribute('data-tags'),
                        list: currentForm.getAttribute('data-list'),
                        redirect: currentForm.getAttribute('data-redirect')
                    });
                }
            });
        }
    }
});

// טיפול באירוע dragover
function handleFieldDragOver(e) {
    e.preventDefault(); // הכרחי כדי לאפשר השמטה
    e.dataTransfer.dropEffect = 'move';
    
    // מצא את האלמנט הנגרר
    const draggedItem = window._draggedFieldItem;
    
    // עדכן את מיקום מחוון ההשמטה
    if (draggedItem) {
        const formFieldsContainer = document.getElementById('modal-form-fields-container');
        
        // אם המחוון לא קיים, צור אותו
        if (!currentDropIndicator) {
            currentDropIndicator = createFieldDropIndicator();
            formFieldsContainer.appendChild(currentDropIndicator);
        }
        
        // מצא את כל השדות במיכל, למעט השדה הנגרר
        const fieldItems = Array.from(formFieldsContainer.querySelectorAll('.field-item:not(.being-dragged)'));
        
        // בדיקה אם העכבר נמצא מעל החלק העליון ביותר של המיכל
        const containerRect = formFieldsContainer.getBoundingClientRect();
        const topPadding = 20; // אזור גריפה בחלק העליון
        
        // אם העכבר נמצא בחלק העליון של המיכל (מעל האלמנט הראשון)
        if (e.clientY < containerRect.top + topPadding || 
            (fieldItems.length > 0 && e.clientY < fieldItems[0].getBoundingClientRect().top)) {
            formFieldsContainer.insertBefore(currentDropIndicator, formFieldsContainer.firstChild);
            return;
        }
        
        // מצא את האלמנט הקרוב ביותר למיקום העכבר
        let closestItem = null;
        let closestDistance = Number.MAX_VALUE;
        let insertBefore = true;
        
        fieldItems.forEach((item) => {
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
                formFieldsContainer.insertBefore(currentDropIndicator, closestItem);
            } else if (closestItem.nextSibling) {
                // שמה אחרי האלמנט הקרוב ביותר
                formFieldsContainer.insertBefore(currentDropIndicator, closestItem.nextSibling);
            } else {
                // אם זה האלמנט האחרון, הוסף בסוף
                formFieldsContainer.appendChild(currentDropIndicator);
            }
        } else if (fieldItems.length === 0) {
            // אם אין פריטים אחרים, הוסף בתחילת הרשימה
            if (formFieldsContainer.firstChild) {
                formFieldsContainer.insertBefore(currentDropIndicator, formFieldsContainer.firstChild);
            } else {
                formFieldsContainer.appendChild(currentDropIndicator);
            }
        }
    }
    
    return false;
}

// טיפול באירוע dragenter
function handleFieldDragEnter(e) {
    e.preventDefault();
    // לא צריך לעשות כלום נוסף כאן, כי handleFieldDragOver ייצור את המחוון
}

// טיפול באירוע dragleave
function handleFieldDragLeave(e) {
    const formFieldsContainer = document.getElementById('modal-form-fields-container');
    // הסר את המחוון רק אם עזבנו את אזור הרשימה לגמרי
    if (!formFieldsContainer.contains(e.relatedTarget)) {
        removeFieldDropIndicator();
    }
}

// טיפול באירוע drop
function handleFieldDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    
    // מצא את האלמנט הנגרר
    const draggedItem = window._draggedFieldItem;
    const formFieldsContainer = document.getElementById('modal-form-fields-container');
    
    if (draggedItem && formFieldsContainer) {
        // בדוק אם ה-dropIndicator קיים ונשתמש במיקום שלו
        if (currentDropIndicator && currentDropIndicator.parentNode) {
            // הכנס את האלמנט הנגרר במיקום של אינדיקטור ההשמטה
            formFieldsContainer.insertBefore(draggedItem, currentDropIndicator);
            
            // הסרת מחוון ההשמטה אחרי השימוש בו
            removeFieldDropIndicator();
            
            // עדכון סדר השדות בטופס
            updateFieldsOrder();
            
            // ניקוי המשתנה הגלובלי
            window._draggedFieldItem = null;
            return;
        }
        
        // גישה חלופית אם המחוון לא קיים
        // מצא את המיקום להכנסה בהתאם למיקום העכבר
        const allItems = Array.from(formFieldsContainer.querySelectorAll('.field-item'));
        const mouseY = e.clientY;
        const containerRect = formFieldsContainer.getBoundingClientRect();
        
        // בדוק אם העכבר בחלק העליון של המיכל
        if (mouseY < containerRect.top + 20 || 
            (allItems.length > 0 && mouseY < allItems[0].getBoundingClientRect().top)) {
            formFieldsContainer.insertBefore(draggedItem, formFieldsContainer.firstChild);
            updateFieldsOrder();
            window._draggedFieldItem = null;
            return;
        }
        
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
                formFieldsContainer.insertBefore(draggedItem, targetItem);
            } else {
                formFieldsContainer.insertBefore(draggedItem, targetItem.nextSibling);
            }
        } else if (!targetItem && allItems.length > 0) {
            // אם לא נמצא אלמנט יעד, הוסף בסוף
            formFieldsContainer.appendChild(draggedItem);
        }
        
        // עדכון סדר השדות בטופס
        updateFieldsOrder();
        
        // ניקוי המשתנה הגלובלי
        window._draggedFieldItem = null;
    }
    
    // הסרת מחוון ההשמטה בכל מקרה
    removeFieldDropIndicator();
    
    return false;
}

// טיפול באירוע dragend
function handleFieldDragEnd(e) {
    // הסרת סגנון הגרירה
    const draggedItem = window._draggedFieldItem;
    if (draggedItem) {
        draggedItem.classList.remove('being-dragged');
        window._draggedFieldItem = null;
    }
    
    // הסרת מחוון ההשמטה
    removeFieldDropIndicator();
}

// פונקציה להסרת מחוון ההשמטה
function removeFieldDropIndicator() {
    if (currentDropIndicator && currentDropIndicator.parentNode) {
        currentDropIndicator.parentNode.removeChild(currentDropIndicator);
        currentDropIndicator = null;
    }
}

// עדכון סדר השדות בטופס
function updateFieldsOrder() {
    const formFieldsContainer = document.getElementById('modal-form-fields-container');
    if (!formFieldsContainer) return;
    
    // קבלת הסדר החדש מרשימת השדות
    const newOrder = Array.from(formFieldsContainer.querySelectorAll('.field-item')).map(item => {
        return parseInt(item.getAttribute('data-field-index'), 10);
    }).filter(index => !isNaN(index));
    
    // יצירת מערך של שדות מסודרים מחדש
    const reorderedFields = [];
    newOrder.forEach(oldIndex => {
        if (currentFormFields[oldIndex]) {
            reorderedFields.push(currentFormFields[oldIndex]);
        }
    });
    
    // עדכון מערך השדות הנוכחי
    if (reorderedFields.length > 0) {
        currentFormFields = reorderedFields;
        
        // עדכון הטופס באיפריים
        updateFormInIframe();
    }
}

/**
 * מאפשר גרירה ושחרור לשדות הטופס
 */
function setupDragAndDrop() {
    const fieldsContainer = document.querySelector('.fields-container');
    if (!fieldsContainer) {
        console.warn('לא נמצא מיכל שדות עבור פונקציית גרירה ושחרור');
        return;
    }
    
    let draggedItem = null;
    let dropIndicator = null;
    
    // יצירת אינדיקטור השמטה
    function createDropIndicator() {
        const indicator = document.createElement('div');
        indicator.className = 'drop-indicator';
        const line = document.createElement('div');
        line.className = 'drop-indicator-line';
        indicator.appendChild(line);
        return indicator;
    }
    
    // עדכון מיקום האינדיקטור
    function updateDropIndicatorPosition(e, container) {
        if (!dropIndicator || !container) return;
        
        const containerRect = container.getBoundingClientRect();
        const fields = Array.from(container.querySelectorAll('.field-item:not(.being-dragged)'));
        
        // מיקום בראש הרשימה אם אין שדות או אם העכבר מעל החלק העליון של המיכל
        if (fields.length === 0 || e.clientY < fields[0].getBoundingClientRect().top) {
            container.insertBefore(dropIndicator, container.firstChild);
            return;
        }
        
        // מציאת השדה שמתחתיו יש להציב את האינדיקטור
        for (let i = 0; i < fields.length; i++) {
            const field = fields[i];
            const fieldRect = field.getBoundingClientRect();
            const fieldMiddle = fieldRect.top + fieldRect.height / 2;
            
            if (e.clientY < fieldMiddle) {
                container.insertBefore(dropIndicator, field);
                return;
            }
            
            // אם זה השדה האחרון והעכבר מתחתיו
            if (i === fields.length - 1) {
                container.insertBefore(dropIndicator, field.nextSibling);
                return;
            }
        }
    }
    
    // הוספת מאזינים לכל השדות הקיימים
    function addDragListenersToFields() {
        const fields = document.querySelectorAll('.field-item');
        fields.forEach(field => {
            // ודא שאנחנו לא מוסיפים מאזינים פעמיים
            field.removeEventListener('dragstart', handleDragStart);
            field.removeEventListener('dragend', handleDragEnd);
            
            field.setAttribute('draggable', 'true');
            field.addEventListener('dragstart', handleDragStart);
            field.addEventListener('dragend', handleDragEnd);
        });
    }
    
    // טיפול בהתחלת גרירה
    function handleDragStart(e) {
        draggedItem = this;
        setTimeout(() => {
            this.classList.add('being-dragged');
        }, 0);
        
        // יצירת אינדיקטור השמטה אם לא קיים
        if (!dropIndicator) {
            dropIndicator = createDropIndicator();
        }
        
        // הגדרת נתוני העברה
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', this.id || 'field-item');
    }
    
    // טיפול בסיום גרירה
    function handleDragEnd() {
        this.classList.remove('being-dragged');
        draggedItem = null;
        
        // הסרת אינדיקטור ההשמטה
        if (dropIndicator && dropIndicator.parentNode) {
            dropIndicator.parentNode.removeChild(dropIndicator);
        }
        dropIndicator = null;
        
        // עדכון סדר השדות בשרת
        updateFieldOrder();
    }
    
    // הוספת מאזינים למיכל השדות
    fieldsContainer.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        updateDropIndicatorPosition(e, this);
    });
    
    fieldsContainer.addEventListener('drop', function(e) {
        e.preventDefault();
        if (!draggedItem || !dropIndicator) return;
        
        // מציב את השדה הנגרר במיקום של אינדיקטור ההשמטה
        this.insertBefore(draggedItem, dropIndicator.nextSibling);
    });
    
    // עדכון סדר השדות בשרת
    function updateFieldOrder() {
        const fields = Array.from(fieldsContainer.querySelectorAll('.field-item'));
        const fieldOrder = fields.map(field => field.dataset.fieldId).filter(id => id);
        
        if (fieldOrder.length === 0) return;
        
        const formId = document.querySelector('#form-editor').dataset.formId;
        if (!formId) {
            console.warn('לא נמצא ID של הטופס לעדכון סדר השדות');
            return;
        }
        
        // שליחת נתונים לשרת
        fetch(formEditorData.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: 'update_field_order',
                form_id: formId,
                field_order: JSON.stringify(fieldOrder),
                security: formEditorData.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('שגיאה בעדכון סדר השדות:', data.data);
            }
        })
        .catch(error => {
            console.error('שגיאה בעדכון סדר השדות:', error);
        });
    }
    
    // הוספת מאזינים לשדות קיימים
    addDragListenersToFields();
    
    // הגדרת הפונקציה להוספת מאזינים לשדות חדשים
    window.addDragListenersToNewField = function(fieldElement) {
        if (!fieldElement) return;
        
        fieldElement.setAttribute('draggable', 'true');
        fieldElement.addEventListener('dragstart', handleDragStart);
        fieldElement.addEventListener('dragend', handleDragEnd);
    };
}

// הפעלת פונקציונליות גרירה ושחרור כשהדף טעון
document.addEventListener('DOMContentLoaded', function() {
    // ... existing code ...
    
    setupDragAndDrop();
    
    // ... existing code ...
});

// טיפול באירוע dragstart
function handleFieldDragStart(e) {
    // הוספת קלאס למיכל השדות 
    const formFieldsContainer = document.getElementById('modal-form-fields-container');
    if (formFieldsContainer) {
        formFieldsContainer.classList.add('drag-active');
    }
    
    const fieldItem = e.target.closest('.field-item') || e.target;
    if (fieldItem) {
        e.dataTransfer.setData('text/plain', fieldItem.getAttribute('data-field-index'));
        
        // שמירת הפריט הנגרר במשתנה גלובלי
        window._draggedFieldItem = fieldItem;
        
        // הוספת סגנון לאלמנט הנגרר
        setTimeout(() => {
            fieldItem.classList.add('being-dragged');
        }, 0);
    }
}

// טיפול באירוע dragend
function handleFieldDragEnd(e) {
    // הסרת קלאס מהמיכל
    const formFieldsContainer = document.getElementById('modal-form-fields-container');
    if (formFieldsContainer) {
        formFieldsContainer.classList.remove('drag-active');
    }
    
    // הסרת סגנון הגרירה
    const draggedItem = window._draggedFieldItem;
    if (draggedItem) {
        draggedItem.classList.remove('being-dragged');
        window._draggedFieldItem = null;
    }
    
    // הסרת מחוון ההשמטה
    removeFieldDropIndicator();
} 