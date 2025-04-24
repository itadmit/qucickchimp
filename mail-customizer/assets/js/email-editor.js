import sectionSettings from './sections.js';
import TemplateManager from './templates.js';

class EmailEditor {
    constructor() {
        this.sections = [];
        this.selectedSection = null;
        
        // קישור לאלמנטים בדף
        this.sectionsList = document.getElementById('sectionsList');
        this.previewContainer = document.getElementById('preview');
        this.settingsContainer = document.getElementById('settings');
        
        // אלמנטים של דיאלוג שמירה
        this.saveButton = document.getElementById('saveBtn');
        this.saveDialog = document.getElementById('saveTemplateDialog');
        this.overlay = document.getElementById('saveTemplateOverlay');
        this.templateName = document.getElementById('templateName');
        this.confirmSave = document.getElementById('confirmSaveTemplate');
        this.cancelSave = document.getElementById('cancelSaveTemplate');
        
        // אלמנטים של דיאלוג הוספת סקשן
        this.addSectionDialog = document.getElementById('addSectionDialog');
        this.addSectionOverlay = document.getElementById('addSectionOverlay');
        this.cancelAddSection = document.getElementById('cancelAddSection');
        
        // יצירת מנהל התבניות
        this.templateManager = new TemplateManager(this);
        
        this.init();
    }
    
    init() {
        // לא ניצור סקשנים אוטומטית יותר
        /* 
        if (this.sections.length === 0) {
            this.sections.push({
                id: Date.now(),
                type: 'heading',
                settings: this.getDefaultSettings('heading')
            });
            
            this.sections.push({
                id: Date.now() + 1,
                type: 'text',
                settings: this.getDefaultSettings('text')
            });
        }
        */
        
        this.renderPreview(); // רנדור ראשוני של התצוגה המקדימה
        this.renderSectionsList();
        this.setupEventListeners();
        this.setupSaveDialog();
        this.setupAddSectionDialog();
    }
    
    setupAddSectionDialog() {
        // הוספת כפתור "הוסף סקשן" בראש הרשימה
        const addButton = document.createElement('button');
        addButton.className = 'add-section-btn mb-4';
        addButton.innerHTML = '<i class="ri-add-line"></i> הוסף סקשן';
        addButton.addEventListener('click', () => {
            this.addSectionDialog.classList.remove('hidden');
            this.addSectionOverlay.classList.remove('hidden');
        });
        
        // הוספת הכפתור בתחילת הרשימה
        this.sectionsList.insertBefore(addButton, this.sectionsList.firstChild);
        
        // סגירת המודל
        this.cancelAddSection.addEventListener('click', () => {
            this.addSectionDialog.classList.add('hidden');
            this.addSectionOverlay.classList.add('hidden');
        });
        
        this.addSectionOverlay.addEventListener('click', () => {
            this.addSectionDialog.classList.add('hidden');
            this.addSectionOverlay.classList.add('hidden');
        });
        
        // הוספת אירועים לכפתורי הסקשנים
        document.querySelectorAll('.section-button').forEach(button => {
            button.addEventListener('click', () => {
                const type = button.dataset.type;
                this.addSection(type);
            });
        });
    }
    
    setupSaveDialog() {
        this.saveButton.addEventListener('click', () => {
            this.saveDialog.classList.remove('hidden');
            this.overlay.classList.remove('hidden');
        });
        
        this.cancelSave.addEventListener('click', () => {
            this.saveDialog.classList.add('hidden');
            this.overlay.classList.add('hidden');
        });
        
        this.confirmSave.addEventListener('click', () => {
            this.saveTemplate();
        });
        
        this.overlay.addEventListener('click', () => {
            this.saveDialog.classList.add('hidden');
            this.overlay.classList.add('hidden');
        });
    }
    
    async saveTemplate() {
        const name = this.templateName.value.trim();
        if (!name) {
            alert('נא להזין שם לתבנית');
            return;
        }
        
        // בדיקה שיש סקשנים לשמירה
        if (this.sections.length === 0) {
            alert('לא ניתן לשמור תבנית ריקה. הוסף לפחות סקשן אחד');
            return;
        }
        
        const html = this.getHTML();
        
        try {
            // הצגת הודעת טעינה
            this.confirmSave.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> שומר...';
            this.confirmSave.disabled = true;
            
            // שמירה בשרת
            const response = await fetch('save-template.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    name,
                    html,
                    // שימוש ב-user_id מהמשתמש המחובר או ערך ברירת מחדל 1
                    user_id: window.currentUserId || 1,
                    // אם יש campaign_id, שלח גם אותו
                    campaign_id: window.currentCampaignId || null
                })
            });
            
            if (!response.ok) {
                throw new Error(`שגיאת HTTP: ${response.status}`);
            }
            
            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                throw new Error(`התגובה אינה בפורמט JSON (${contentType})`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                // הצגת הודעת הצלחה
                this.confirmSave.innerHTML = '<i class="ri-check-line"></i> נשמר!';
                
                setTimeout(() => {
                    // סגירת החלון והצגת הודעת הצלחה
                    this.saveDialog.classList.add('hidden');
                    this.overlay.classList.add('hidden');
                    this.confirmSave.innerHTML = 'שמירה';
                    this.confirmSave.disabled = false;
                    
                    // איפוס שם התבנית
                    this.templateName.value = '';
                    
                    // הצגת הודעה למשתמש
                    const successMessage = document.createElement('div');
                    successMessage.className = 'fixed bottom-4 left-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50 animate-fade-in';
                    successMessage.innerHTML = `
                        <div class="flex items-center gap-2">
                            <i class="ri-check-line"></i>
                            <span>התבנית נשמרה בהצלחה!</span>
                        </div>
                    `;
                    document.body.appendChild(successMessage);
                    
                    // מחיקת ההודעה אחרי 3 שניות
                    setTimeout(() => {
                        successMessage.classList.add('animate-fade-out');
                        setTimeout(() => {
                            document.body.removeChild(successMessage);
                        }, 500);
                    }, 3000);
                    
                }, 1000);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            // איפוס כפתור השמירה
            this.confirmSave.innerHTML = 'שמירה';
            this.confirmSave.disabled = false;
            
            // הצגת הודעת שגיאה
            console.error('שגיאה בשמירת התבנית:', error);
            alert('שגיאה בשמירת התבנית: ' + error.message);
        }
    }
    
    renderSectionsList() {
        // ניקוי הרשימה, אבל שומר על כפתור ההוספה
        const addButton = this.sectionsList.querySelector('button');
        this.sectionsList.innerHTML = '';
        if (addButton) {
            this.sectionsList.appendChild(addButton);
        }
        
        // הוספת הסקשנים הקיימים
        this.sections.forEach((section, index) => {
            const div = document.createElement('div');
            div.className = 'sidebar-item'; // הסרנו את group כי אנחנו משתמשים ב-hover ישירות
            
            // נוסיף אייקון בהתאם לסוג הסקשן
            const iconClass = sectionSettings[section.type].icon || 'ri-file-list-line';
            
            div.innerHTML = `
                <div class="flex items-center gap-2 flex-1">
                    <i class="${iconClass}"></i>
                    <span>${sectionSettings[section.type].name}</span>
                </div>
                <div class="section-actions">
                    ${index > 0 ? `<button class="section-action-btn up-btn" title="הזז למעלה">
                        <i class="ri-arrow-up-s-line"></i>
                    </button>` : ''}
                    ${index < this.sections.length - 1 ? `<button class="section-action-btn down-btn" title="הזז למטה">
                        <i class="ri-arrow-down-s-line"></i>
                    </button>` : ''}
                    <button class="section-action-btn delete-btn" title="מחק סקשן">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
            `;
            
            div.dataset.id = section.id;
            
            // הוספת אירוע לחיצה לבחירת הסקשן
            div.addEventListener('click', (e) => {
                // רק אם הלחיצה לא הייתה על אחד הכפתורים
                if (!e.target.closest('.section-action-btn')) {
                    this.selectSection(section.id);
                }
            });
            
            this.sectionsList.appendChild(div);
            
            // הוספת אירועים לכפתורים
            const deleteBtn = div.querySelector('.delete-btn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.deleteSection(section.id);
                });
            }
            
            const upBtn = div.querySelector('.up-btn');
            if (upBtn) {
                upBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.moveSection(section.id, 'up');
                });
            }
            
            const downBtn = div.querySelector('.down-btn');
            if (downBtn) {
                downBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.moveSection(section.id, 'down');
                });
            }
        });
        
        if (this.sections.length === 0) {
            this.sectionsList.innerHTML += '<div class="text-gray-500 text-center py-4 text-sm px-2">אין סקשנים במייל. לחץ על "הוסף סקשן" או בחר תבנית מוכנה</div>';
        }
    }
    
    deleteSection(id) {
        if (confirm('האם אתה בטוח שברצונך למחוק סקשן זה?')) {
            this.sections = this.sections.filter(section => section.id !== id);
            this.renderPreview();
            this.renderSectionsList();
            
            if (this.selectedSection && this.selectedSection.id === id) {
                this.selectedSection = null;
                this.renderSettings();
            }
        }
    }
    
    moveSection(id, direction) {
        const index = this.sections.findIndex(section => section.id === id);
        if (index === -1) return;
        
        if (direction === 'up' && index > 0) {
            // החלפת מיקום עם הסקשן שמעליו
            [this.sections[index], this.sections[index - 1]] = [this.sections[index - 1], this.sections[index]];
        } else if (direction === 'down' && index < this.sections.length - 1) {
            // החלפת מיקום עם הסקשן שמתחתיו
            [this.sections[index], this.sections[index + 1]] = [this.sections[index + 1], this.sections[index]];
        }
        
        this.renderPreview();
        this.renderSectionsList();
        this.updateActiveStates();
    }
    
    setupEventListeners() {
        this.previewContainer.addEventListener('click', (e) => {
            const sectionElement = e.target.closest('.section');
            if (sectionElement) {
                this.selectSection(sectionElement.dataset.id);
            }
        });
        
        // הוספת אירועים לכפתורים בתצוגה הריקה
        this.previewContainer.querySelector('#choose-template-btn')?.addEventListener('click', () => {
            this.templateManager.loadTemplateDialog.classList.remove('hidden');
            this.templateManager.loadTemplateOverlay.classList.remove('hidden');
            this.templateManager.loadTemplates();
        });
        
        this.previewContainer.querySelector('#start-empty-btn')?.addEventListener('click', () => {
            this.addSectionDialog.classList.remove('hidden');
            this.addSectionOverlay.classList.remove('hidden');
        });
        
        // כפתור תצוגה מקדימה
        const previewBtn = document.getElementById('previewBtn');
        if (previewBtn) {
            previewBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.showEmailPreview();
            });
        }
    }
    
    showEmailPreview() {
        if (this.sections.length === 0) {
            alert('אין תוכן להציג בתצוגה מקדימה. הוסף סקשנים תחילה.');
            return;
        }
        
        // יצירת חלון חדש עם תצוגה מקדימה של המייל
        const previewWindow = window.open('', '_blank');
        
        // השג את ה-HTML המלא מוכן לשליחה
        const html = this.getHTML();
        
        // הוסף כותרת לתצוגה המקדימה
        const previewHtml = html.replace('<body>', `<body>
            <div style="text-align: center; padding: 10px; background-color: #f5f5f5; margin-bottom: 20px; border-radius: 4px;">
                <h2>תצוגה מקדימה של המייל</h2>
                <p>כך ייראה המייל בתיבת הדואר של הנמען</p>
            </div>`
        ).replace('</body>', `
            <div style="text-align: center; padding: 10px; background-color: #f5f5f5; margin-top: 20px; font-size: 0.8rem; color: #666; border-radius: 4px;">
                <p>זוהי תצוגה מקדימה בלבד. ייתכנו הבדלים בהצגת המייל בתוכנות דואר שונות.</p>
            </div>
        </body>`);
        
        previewWindow.document.open();
        previewWindow.document.write(previewHtml);
        previewWindow.document.close();
    }
    
    addSection(type) {
        const section = {
            id: Date.now(),
            type,
            settings: this.getDefaultSettings(type)
        };
        
        this.sections.push(section);
        this.renderPreview();
        this.renderSectionsList();
        this.selectSection(section.id);
        
        // סגירת המודל
        this.addSectionDialog.classList.add('hidden');
        this.addSectionOverlay.classList.add('hidden');
    }
    
    getDefaultSettings(type) {
        const settings = {};
        const sectionConfig = sectionSettings[type];
        
        Object.entries(sectionConfig.settings).forEach(([key, setting]) => {
            if (setting.type === 'accordion') {
                settings[key] = {};
                Object.entries(setting.settings).forEach(([subKey, subSetting]) => {
                    settings[key][subKey] = subSetting.default !== undefined ? subSetting.default : 
                        (subKey === 'textAlign' ? 'center' : '');
                });
            } else {
                settings[key] = setting.default !== undefined ? setting.default : '';
            }
        });
        
        // וידוא שהכפתור מקבל יישור טקסט ברירת מחדל
        if (type === 'button' && settings.design && settings.design.textAlign === undefined) {
            settings.design.textAlign = 'center';
        }
        
        // אם זה סקשן תמונה, נוסיף תמונה חינמית כברירת מחדל
        if (type === 'image') {
            // מערך של תמונות חינמיות מ-Unsplash
            const defaultImages = [
                'https://images.unsplash.com/photo-1579547621869-0ddb5f237392',
                'https://images.unsplash.com/photo-1504711434969-e33886168f5c',
                'https://images.unsplash.com/photo-1557804506-669a67965ba0',
                'https://images.unsplash.com/photo-1607083206968-13611e3d76db',
                'https://images.unsplash.com/photo-1516321318423-f06f85e504b3'
            ];
            
            // בחירת תמונה אקראית מהמערך
            const randomIndex = Math.floor(Math.random() * defaultImages.length);
            settings.src = defaultImages[randomIndex];
            settings.alt = 'תמונה לדוגמה';
        }
        
        return settings;
    }
    
    selectSection(id) {
        this.selectedSection = this.sections.find(s => s.id === parseInt(id));
        this.renderSettings();
        this.updateActiveStates();
    }
    
    updateActiveStates() {
        // עדכון הסקשן הנבחר ברשימה
        document.querySelectorAll('.sidebar-item').forEach(item => {
            if (item.dataset.id === this.selectedSection?.id.toString()) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
        
        // עדכון הסקשן הנבחר בתצוגה המקדימה
        document.querySelectorAll('.section').forEach(section => {
            if (section.dataset.id === this.selectedSection?.id.toString()) {
                section.classList.add('border-primary', 'border-2');
                section.classList.remove('border-transparent');
            } else {
                section.classList.remove('border-primary', 'border-2');
                section.classList.add('border-transparent');
            }
        });
    }
    
    renderPreview() {
        this.previewContainer.innerHTML = '';
        
        if (this.sections.length === 0) {
            // אם אין סקשנים, הצג הודעה למשתמש
            const emptyMessage = document.createElement('div');
            emptyMessage.className = 'text-center py-10 px-4';
            emptyMessage.innerHTML = `
                <i class="ri-mail-line text-5xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-600 mb-2">אין סקשנים במייל</h3>
                <p class="text-gray-500 mb-6">בחר תבנית קיימת או התחל מתבנית ריקה</p>
                <div class="flex gap-3 justify-center">
                    <button id="choose-template-btn" class="btn-primary-sm">
                        <i class="ri-folder-open-line"></i>
                        בחר תבנית
                    </button>
                    <button id="start-empty-btn" class="btn-secondary-sm">
                        <i class="ri-add-line"></i>
                        התחל מתבנית ריקה
                    </button>
                </div>
            `;
            this.previewContainer.appendChild(emptyMessage);
            
            // הוסף אירועים לכפתורים
            const chooseTemplateBtn = this.previewContainer.querySelector('#choose-template-btn');
            if (chooseTemplateBtn) {
                chooseTemplateBtn.addEventListener('click', () => {
                    this.templateManager.loadTemplateDialog.classList.remove('hidden');
                    this.templateManager.loadTemplateOverlay.classList.remove('hidden');
                    this.templateManager.loadTemplates();
                });
            }
            
            const startEmptyBtn = this.previewContainer.querySelector('#start-empty-btn');
            if (startEmptyBtn) {
                startEmptyBtn.addEventListener('click', () => {
                    this.addSectionDialog.classList.remove('hidden');
                    this.addSectionOverlay.classList.remove('hidden');
                });
            }
            
            return;
        }
        
        // יצירת מיכל פנימי שיכיל את כל הסקשנים
        const emailContainer = document.createElement('div');
        emailContainer.className = 'email-content-container';
        this.previewContainer.appendChild(emailContainer);
        
        // אם יש סקשנים, הצג אותם כרגיל
        this.sections.forEach(section => {
            const sectionElement = this.createSectionElement(section);
            emailContainer.appendChild(sectionElement);
        });
    }
    
    createSectionElement(section) {
        const div = document.createElement('div');
        div.className = 'section border-2 border-transparent p-3 mb-4 hover:border-gray-200 relative rounded-md transition-all group';
        div.dataset.id = section.id;
        
        // הוספת כפתורי עריכה ומחיקה מעל הסקשן
        const actionsDiv = document.createElement('div');
        actionsDiv.className = 'section-actions absolute top-2 left-2 opacity-0 group-hover:opacity-100 flex gap-1 bg-white/80 p-1 rounded z-10';
        
        // החלץ את האינדקס של הסקשן
        const index = this.sections.findIndex(s => s.id === section.id);
        
        // מוסיף כפתורים בהתאם למיקום הסקשן
        let actionsHtml = '';
        
        if (index > 0) {
            actionsHtml += `
                <button class="section-action-btn up-btn" title="הזז למעלה">
                    <i class="ri-arrow-up-s-line text-sm"></i>
                </button>
            `;
        }
        
        if (index < this.sections.length - 1) {
            actionsHtml += `
                <button class="section-action-btn down-btn" title="הזז למטה">
                    <i class="ri-arrow-down-s-line text-sm"></i>
                </button>
            `;
        }
        
        actionsHtml += `
            <button class="section-action-btn delete-btn" title="מחק סקשן">
                <i class="ri-delete-bin-line text-sm"></i>
            </button>
        `;
        
        actionsDiv.innerHTML = actionsHtml;
        
        const config = sectionSettings[section.type];
        const content = this.renderSectionContent(section);
        
        div.innerHTML = `
            <div class="section-content">
                ${content}
            </div>
        `;
        
        div.appendChild(actionsDiv);
        
        // הוספת אירועים לכפתורים
        const deleteBtn = actionsDiv.querySelector('.delete-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.deleteSection(section.id);
            });
        }
        
        const upBtn = actionsDiv.querySelector('.up-btn');
        if (upBtn) {
            upBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.moveSection(section.id, 'up');
            });
        }
        
        const downBtn = actionsDiv.querySelector('.down-btn');
        if (downBtn) {
            downBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.moveSection(section.id, 'down');
            });
        }
        
        return div;
    }
    
    renderSectionContent(section) {
        const settings = section.settings;
        
        switch (section.type) {
            case 'heading':
                return `<h2 style="
                    font-family: ${settings.typography.fontFamily || 'Noto Sans Hebrew'};
                    font-size: ${settings.typography.fontSize || 24}px;
                    font-weight: ${settings.typography.fontWeight || 'bold'};
                    text-decoration: ${settings.typography.textDecoration || 'none'};
                    color: ${settings.design.textColor || '#000000'};
                    background-color: ${settings.design.backgroundColor || '#ffffff'};
                    text-align: ${settings.design.textAlign || 'right'};
                ">${settings.content}</h2>`;
                
            case 'text':
                return `<p style="
                    font-family: ${settings.typography.fontFamily || 'Noto Sans Hebrew'};
                    font-size: ${settings.typography.fontSize || 16}px;
                    font-weight: ${settings.typography.fontWeight || 'normal'};
                    text-decoration: ${settings.typography.textDecoration || 'none'};
                    color: ${settings.design.textColor || '#000000'};
                    background-color: ${settings.design.backgroundColor || '#ffffff'};
                    text-align: ${settings.design.textAlign || 'right'};
                ">${settings.content}</p>`;
                
            case 'image':
                return `<div style="
                    background-color: ${settings.design.backgroundColor || '#ffffff'};
                    text-align: ${settings.design.align || 'center'};
                ">
                    <img src="${settings.src}" alt="${settings.alt}" style="
                        width: ${settings.design.width || '100%'};
                        max-width: 100%;
                    ">
                </div>`;
                
            case 'button':
                const buttonTextAlign = settings.design.textAlign || 'center';
                return `<div style="text-align: ${buttonTextAlign};">
                    <button style="
                        font-family: ${settings.typography.fontFamily || 'Noto Sans Hebrew'};
                        font-size: ${settings.typography.fontSize || 16}px;
                        font-weight: ${settings.typography.fontWeight || 'bold'};
                        text-decoration: ${settings.typography.textDecoration || 'none'};
                        color: ${settings.design.textColor || '#ffffff'};
                        background-color: ${settings.design.backgroundColor || '#007bff'};
                        width: ${settings.design.fullWidth ? '100%' : 'auto'};
                        padding: 10px 20px;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                    ">${settings.content}</button>
                </div>`;
                
            case 'divider':
                return `<hr style="
                    border: none;
                    border-top: 1px solid ${settings.design.color};
                    width: ${settings.design.width};
                    margin: 20px auto;
                ">`;
                
            case 'social':
                const socialLinks = [];
                if (settings.facebook) socialLinks.push(`<a href="${settings.facebook}"><i class="ri-facebook-fill"></i></a>`);
                if (settings.instagram) socialLinks.push(`<a href="${settings.instagram}"><i class="ri-instagram-fill"></i></a>`);
                if (settings.twitter) socialLinks.push(`<a href="${settings.twitter}"><i class="ri-twitter-fill"></i></a>`);
                if (settings.tiktok) socialLinks.push(`<a href="${settings.tiktok}"><i class="ri-tiktok-fill"></i></a>`);
                if (settings.email) socialLinks.push(`<a href="mailto:${settings.email}"><i class="ri-mail-fill"></i></a>`);
                if (settings.whatsapp) socialLinks.push(`<a href="${settings.whatsapp}"><i class="ri-whatsapp-fill"></i></a>`);
                
                return `<div style="
                    text-align: ${settings.design.textAlign};
                    background-color: ${settings.design.backgroundColor};
                ">
                    ${socialLinks.join(' ')}
                </div>`;
                
            default:
                return '';
        }
    }
    
    renderSettings() {
        if (!this.selectedSection) {
            // אם אין סקשן נבחר, בדוק אם יש בכלל סקשנים
            if (this.sections.length === 0) {
                this.settingsContainer.innerHTML = `
                    <div class="text-center py-6 px-4">
                        <i class="ri-settings-4-line text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500 mb-2">אין סקשנים במייל</p>
                        <p class="text-gray-400 text-sm">התחל מתבנית קיימת או הוסף סקשן חדש</p>
                    </div>
                `;
            } else {
                this.settingsContainer.innerHTML = '<p class="text-gray-500 text-center py-4">בחר סקשן לעריכה</p>';
            }
            return;
        }
        
        const section = this.selectedSection;
        const config = sectionSettings[section.type];
        const settings = section.settings;
        
        let html = `<div class="flex items-center gap-2 text-primary mb-4">
            <i class="${config.icon || 'ri-file-list-line'}"></i>
            <h3 class="text-base font-medium">${config.name}</h3>
        </div>`;
        
        Object.entries(config.settings).forEach(([key, setting]) => {
            if (setting.type === 'accordion') {
                html += this.renderAccordion(key, setting, settings[key]);
            } else {
                html += this.renderSetting(key, setting, settings[key]);
            }
        });
        
        this.settingsContainer.innerHTML = html;
        this.setupSettingsEventListeners();
    }
    
    renderAccordion(key, setting, values) {
        return `
            <div class="accordion mb-3">
                <button type="button" class="accordion-header w-full flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="${setting.icon || 'ri-settings-3-line'}"></i>
                        <span>${setting.label}</span>
                    </div>
                    <i class="ri-arrow-down-s-line"></i>
                </button>
                <div class="accordion-content" style="display: none;">
                    ${Object.entries(setting.settings).map(([subKey, subSetting]) => 
                        this.renderSetting(subKey, subSetting, values[subKey], key)
                    ).join('')}
                </div>
            </div>
        `;
    }
    
    renderSetting(key, setting, value, parentKey = null) {
        const settingKey = parentKey ? `${parentKey}.${key}` : key;
        
        switch (setting.type) {
            case 'input':
                return `
                    <div class="mb-4">
                        <label class="text-sm font-medium text-gray-700 mb-1">${setting.label}</label>
                        <input type="text" class="w-full border border-gray-300 rounded-md p-2 text-sm" data-setting="${settingKey}" value="${value}">
                    </div>
                `;
                
            case 'textarea':
                return `
                    <div class="mb-4">
                        <label class="text-sm font-medium text-gray-700 mb-1">${setting.label}</label>
                        <textarea class="w-full border border-gray-300 rounded-md p-2 text-sm" data-setting="${settingKey}">${value}</textarea>
                    </div>
                `;
                
            case 'select':
                return `
                    <div class="mb-4">
                        <label class="text-sm font-medium text-gray-700 mb-1">${setting.label}</label>
                        <select class="w-full border border-gray-300 rounded-md p-2 text-sm" data-setting="${settingKey}">
                            ${setting.options.map(option => 
                                `<option value="${option}" ${option === value ? 'selected' : ''}>${option}</option>`
                            ).join('')}
                        </select>
                    </div>
                `;
                
            case 'color':
                return `
                    <div class="mb-4">
                        <label class="text-sm font-medium text-gray-700 mb-1">${setting.label}</label>
                        <input type="color" class="w-full h-10 rounded-md border border-gray-200" data-setting="${settingKey}" value="${value}">
                    </div>
                `;
                
            case 'number':
                return `
                    <div class="mb-4">
                        <label class="text-sm font-medium text-gray-700 mb-1">${setting.label}</label>
                        <input type="number" class="w-full border border-gray-300 rounded-md p-2 text-sm" data-setting="${settingKey}" value="${value}">
                    </div>
                `;
                
            case 'checkbox':
                return `
                    <div class="mb-4">
                        <label class="flex items-center text-sm font-medium text-gray-700">
                            <input type="checkbox" class="w-4 h-4 mr-2 rounded border-gray-300 text-primary focus:ring-primary" data-setting="${settingKey}" ${value ? 'checked' : ''}>
                            ${setting.label}
                        </label>
                    </div>
                `;
                
            default:
                return '';
        }
    }
    
    setupSettingsEventListeners() {
        // Accordion toggles
        const accordionToggles = document.querySelectorAll('.accordion-header');
        
        // פתיחת האקורדיון הראשון באופן אוטומטי
        if (accordionToggles.length > 0) {
            const firstAccordion = accordionToggles[0];
            const content = firstAccordion.nextElementSibling;
            const icon = firstAccordion.querySelector('i:last-child');
            
            content.style.display = 'block';
            icon.className = 'ri-arrow-up-s-line';
        }
        
        // הוספת אירועי לחיצה לאקורדיונים
        accordionToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const content = this.nextElementSibling;
                const icon = this.querySelector('i:last-child');
                
                if (content.style.display === 'none' || content.style.display === '') {
                    content.style.display = 'block';
                    icon.className = 'ri-arrow-up-s-line';
                } else {
                    content.style.display = 'none';
                    icon.className = 'ri-arrow-down-s-line';
                }
            });
        });
        
        // Setting changes
        document.querySelectorAll('[data-setting]').forEach(element => {
            element.addEventListener('input', (e) => {
                const setting = e.target.dataset.setting;
                const value = e.target.type === 'checkbox' ? e.target.checked : e.target.value;
                this.updateSetting(setting, value);
            });
            
            element.addEventListener('change', (e) => {
                const setting = e.target.dataset.setting;
                const value = e.target.type === 'checkbox' ? e.target.checked : e.target.value;
                this.updateSetting(setting, value);
            });
        });
    }
    
    updateSetting(setting, value) {
        if (!this.selectedSection) return;
        
        const keys = setting.split('.');
        let current = this.selectedSection.settings;
        
        for (let i = 0; i < keys.length - 1; i++) {
            current = current[keys[i]];
        }
        
        current[keys[keys.length - 1]] = value;
        
        this.renderPreview();
    }
    
    getHTML() {
        // כאן אנחנו יוצרים HTML מאורגן ונקי שמוכן לשליחה במייל
        // במקום להשתמש ב-innerHTML של מיכל התצוגה המקדימה שכולל אלמנטים של ממשק המשתמש
        
        if (this.sections.length === 0) {
            return '';
        }
        
        // יצירת קוד HTML נקי
        let htmlContent = '';
        
        // עבור על כל הסקשנים והוסף את ה-HTML שלהם
        this.sections.forEach(section => {
            // קח את התוכן של הסקשן בלבד, ללא אלמנטים של הממשק
            const sectionContent = this.renderSectionContent(section);
            htmlContent += `<section>${sectionContent}</section>\n`;
        });
        
        // תבנית HTML מלאה
        const fullHTML = `<!DOCTYPE html>
<html dir="rtl" lang="he">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Hebrew:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Noto Sans Hebrew', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        section {
            margin-bottom: 20px;
        }
        img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
${htmlContent}
</body>
</html>`;
        
        return fullHTML;
    }
}

// Initialize the editor
const editor = new EmailEditor(); 