/**
 * מודול לניהול תבניות מייל
 */

class TemplateManager {
    constructor(editor) {
        this.editor = editor;
        this.loadTemplateButton = document.getElementById('loadTemplateBtn');
        this.loadTemplateDialog = document.getElementById('loadTemplateDialog');
        this.loadTemplateOverlay = document.getElementById('loadTemplateOverlay');
        this.cancelLoadTemplate = document.getElementById('cancelLoadTemplate');
        this.templateContainer = document.getElementById('templatesContainer');
        
        this.setupLoadTemplateDialog();
    }
    
    setupLoadTemplateDialog() {
        if (!this.loadTemplateButton) return;
        
        this.loadTemplateButton.addEventListener('click', () => {
            this.loadTemplateDialog.classList.remove('hidden');
            this.loadTemplateOverlay.classList.remove('hidden');
            this.loadTemplates();
        });
        
        this.cancelLoadTemplate.addEventListener('click', () => {
            this.loadTemplateDialog.classList.add('hidden');
            this.loadTemplateOverlay.classList.add('hidden');
        });
        
        this.loadTemplateOverlay.addEventListener('click', () => {
            this.loadTemplateDialog.classList.add('hidden');
            this.loadTemplateOverlay.classList.add('hidden');
        });
    }
    
    async loadTemplates() {
        if (!this.templateContainer) return;
        
        try {
            this.templateContainer.innerHTML = '<div class="text-center p-4"><i class="ri-loader-4-line animate-spin text-2xl"></i></div>';
            
            // שימוש בתבניות מוכנות מראש
            const templates = this.getDefaultTemplates();
            this.renderTemplates(templates);
            
        } catch (error) {
            this.templateContainer.innerHTML = `<div class="text-center p-4 text-red-500">שגיאה: ${error.message}</div>`;
        }
    }
    
    getDefaultTemplates() {
        return [
            {
                id: 'welcome',
                name: 'תבנית ברוכים הבאים',
                created_at: new Date().toISOString(),
                html: this.getWelcomeTemplate()
            },
            {
                id: 'newsletter',
                name: 'תבנית עדכון חודשי',
                created_at: new Date().toISOString(),
                html: this.getNewsletterTemplate()
            },
            {
                id: 'promotion',
                name: 'תבנית קידום מכירות',
                created_at: new Date().toISOString(),
                html: this.getPromotionTemplate()
            },
            {
                id: 'blank',
                name: 'תבנית ריקה',
                created_at: new Date().toISOString(),
                html: this.getBlankTemplate()
            }
        ];
    }
    
    getWelcomeTemplate() {
        return `
            <section>
                <h2 style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 24px; font-weight: bold; color: #333; text-align: center;">ברוכים הבאים!</h2>
            </section>
            <section>
                <div style="text-align: center; margin: 15px 0;">
                    <i class="ri-user-follow-line" style="font-size: 48px; color: #6366f1; display: inline-block;"></i>
                </div>
            </section>
            <section>
                <p style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; color: #333; text-align: right;">שמחים לראות אותך איתנו! תודה שהצטרפת לקהילה שלנו.</p>
                <p style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; color: #333; text-align: right;">אנו מקווים שתיהנה מהשירותים שלנו ותמצא ערך רב בתכנים שאנו מציעים. צוות התמיכה שלנו זמין עבורך בכל שאלה או בקשה.</p>
            </section>
            <section>
                <img src="https://images.unsplash.com/photo-1557804506-669a67965ba0" style="width: 100%; max-width: 600px; display: block; margin: 20px auto;" alt="תמונת קהילה">
            </section>
            <section>
                <h2 style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 20px; font-weight: bold; color: #333; text-align: right;">היתרונות שלנו</h2>
                <div style="display: flex; align-items: center; margin: 15px 0;">
                    <i class="ri-check-line" style="font-size: 24px; color: #10b981; margin-left: 10px;"></i>
                    <p style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; color: #333; margin: 0;">שירות לקוחות מעולה</p>
                </div>
                <div style="display: flex; align-items: center; margin: 15px 0;">
                    <i class="ri-check-line" style="font-size: 24px; color: #10b981; margin-left: 10px;"></i>
                    <p style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; color: #333; margin: 0;">תכנים איכותיים</p>
                </div>
                <div style="display: flex; align-items: center; margin: 15px 0;">
                    <i class="ri-check-line" style="font-size: 24px; color: #10b981; margin-left: 10px;"></i>
                    <p style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; color: #333; margin: 0;">קהילה תומכת</p>
                </div>
            </section>
            <section>
                <button style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; font-weight: bold; color: white; background-color: #6366f1; padding: 12px 24px; border: none; border-radius: 4px; display: block; margin: 30px auto;">
                    <i class="ri-arrow-right-line" style="margin-left: 8px;"></i>התחל עכשיו
                </button>
            </section>
            <section>
                <hr style="border: none; border-top: 1px solid #e0e0e0; width: 80%; margin: 30px auto;">
            </section>
            <section>
                <div style="background-color: #f9fafb; padding: 20px; border-radius: 8px; margin-top: 20px;">
                    <h3 style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 18px; font-weight: bold; color: #333; text-align: right;">מה הצעד הבא?</h3>
                    <p style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; color: #333; text-align: right;">השלם את הפרופיל שלך ותתחיל ליהנות מהשירותים שלנו. אם יש לך שאלות, אל תהסס לפנות אלינו!</p>
                </div>
            </section>
        `;
    }
    
    getNewsletterTemplate() {
        return `
            <section>
                <h2 style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 24px; font-weight: bold; color: #333; text-align: center;">עדכון חודשי</h2>
            </section>
            <section>
                <div style="text-align: center; margin: 15px 0;">
                    <i class="ri-mail-send-line" style="font-size: 48px; color: #3b82f6; display: inline-block;"></i>
                </div>
            </section>
            <section>
                <p style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; color: #333; text-align: right;">שלום לכל המנויים שלנו!</p>
                <p style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; color: #333; text-align: right;">אנו שמחים לשתף אתכם בעדכונים האחרונים שלנו לחודש זה. היו הרבה התפתחויות מרגשות והחלטנו לתמצת אותן עבורכם.</p>
            </section>
            <section>
                <h2 style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 20px; font-weight: bold; color: #333; text-align: right; display: flex; align-items: center;">
                    <i class="ri-newspaper-line" style="margin-left: 10px; color: #3b82f6; font-size: 24px;"></i>
                    החדשות האחרונות
                </h2>
                <img src="https://images.unsplash.com/photo-1504711434969-e33886168f5c" style="width: 100%; max-width: 600px; display: block; margin: 20px auto;" alt="חדשות">
                <p style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; color: #333; text-align: right;">תוכן המדור הראשון יופיע כאן. ניתן לערוך את התוכן לפי הצורך. שינויים רבים התרחשו החודש, והמשכנו להתפתח ולהרחיב את הפעילות שלנו בכל התחומים.</p>
            </section>
            <section>
                <h2 style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 20px; font-weight: bold; color: #333; text-align: right; display: flex; align-items: center;">
                    <i class="ri-article-line" style="margin-left: 10px; color: #3b82f6; font-size: 24px;"></i>
                    מאמר החודש
                </h2>
                <p style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; color: #333; text-align: right;">תוכן המדור השני יופיע כאן. ניתן לערוך את התוכן לפי הצורך. המאמר החודש עוסק בנושאים רלוונטיים לתחום שלנו ומביא תובנות חדשות ומעניינות. קריאה מהנה!</p>
            </section>
            <section>
                <div style="background-color: #eff6ff; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 18px; font-weight: bold; color: #333; text-align: right;">טיפ החודש</h3>
                    <div style="display: flex; align-items: flex-start;">
                        <i class="ri-lightbulb-line" style="font-size: 24px; color: #f59e0b; margin-left: 10px; margin-top: 3px;"></i>
                        <p style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; color: #333; text-align: right; margin: 0;">שימו לב לעדכונים חשובים שאנו שולחים מדי פעם, הם מכילים מידע חיוני לשימוש מיטבי במערכת.</p>
                    </div>
                </div>
            </section>
            <section>
                <button style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; font-weight: bold; color: white; background-color: #3b82f6; padding: 12px 24px; border: none; border-radius: 4px; display: block; margin: 30px auto;">
                    <i class="ri-book-open-line" style="margin-left: 8px;"></i>קרא עוד
                </button>
            </section>
            <section>
                <hr style="border: none; border-top: 1px solid #e0e0e0; width: 80%; margin: 30px auto;">
            </section>
            <section>
                <div style="display: flex; justify-content: center; gap: 20px; margin: 20px 0;">
                    <a href="#" style="text-decoration: none;">
                        <i class="ri-facebook-fill" style="font-size: 24px; color: #3b82f6;"></i>
                    </a>
                    <a href="#" style="text-decoration: none;">
                        <i class="ri-instagram-line" style="font-size: 24px; color: #3b82f6;"></i>
                    </a>
                    <a href="#" style="text-decoration: none;">
                        <i class="ri-twitter-fill" style="font-size: 24px; color: #3b82f6;"></i>
                    </a>
                    <a href="#" style="text-decoration: none;">
                        <i class="ri-linkedin-fill" style="font-size: 24px; color: #3b82f6;"></i>
                    </a>
                </div>
            </section>
        `;
    }
    
    getPromotionTemplate() {
        return `
            <section>
                <h2 style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 24px; font-weight: bold; color: #333; text-align: center;">מבצע מיוחד!</h2>
            </section>
            <section>
                <div style="text-align: center; margin: 15px 0;">
                    <i class="ri-price-tag-3-line" style="font-size: 48px; color: #e53e3e; display: inline-block;"></i>
                </div>
            </section>
            <section>
                <img src="https://images.unsplash.com/photo-1607083206968-13611e3d76db" style="width: 100%; max-width: 600px; display: block; margin: 20px auto;" alt="מבצע מיוחד">
            </section>
            <section>
                <p style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; color: #333; text-align: center;">לזמן מוגבל בלבד</p>
                <h2 style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 36px; font-weight: bold; color: #e53e3e; text-align: center;">הנחה של 20%</h2>
                <p style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; color: #333; text-align: center;">על כל המוצרים באתר</p>
            </section>
            <section>
                <div style="background-color: #fef2f2; border: 1px solid #fee2e2; border-radius: 8px; padding: 20px; margin: 20px 0;">
                    <h3 style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 18px; font-weight: bold; color: #333; text-align: right; display: flex; align-items: center;">
                        <i class="ri-information-line" style="margin-left: 10px; color: #e53e3e; font-size: 24px;"></i>
                        פרטי המבצע
                    </h3>
                    <ul style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; color: #333; text-align: right; padding-right: 20px;">
                        <li style="margin-bottom: 10px;">ההנחה תקפה לכל המוצרים באתר</li>
                        <li style="margin-bottom: 10px;">אין כפל מבצעים והנחות</li>
                        <li style="margin-bottom: 10px;">המבצע בתוקף עד לתאריך 31.12.2023</li>
                        <li style="margin-bottom: 10px;">המלאי מוגבל - כל הקודם זוכה!</li>
                    </ul>
                </div>
            </section>
            <section>
                <h3 style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 20px; font-weight: bold; color: #333; text-align: center; margin: 20px 0;">איך לנצל את ההנחה?</h3>
                <div style="display: flex; align-items: center; margin: 15px 0;">
                    <div style="background-color: #e53e3e; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; justify-content: center; align-items: center; margin-left: 15px; font-family: 'Noto Sans Hebrew', sans-serif;">1</div>
                    <p style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; color: #333; margin: 0;">בחר את המוצרים הרצויים והוסף אותם לסל הקניות</p>
                </div>
                <div style="display: flex; align-items: center; margin: 15px 0;">
                    <div style="background-color: #e53e3e; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; justify-content: center; align-items: center; margin-left: 15px; font-family: 'Noto Sans Hebrew', sans-serif;">2</div>
                    <p style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; color: #333; margin: 0;">הזן את קוד הקופון: SALE20 בעת התשלום</p>
                </div>
                <div style="display: flex; align-items: center; margin: 15px 0;">
                    <div style="background-color: #e53e3e; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; justify-content: center; align-items: center; margin-left: 15px; font-family: 'Noto Sans Hebrew', sans-serif;">3</div>
                    <p style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; color: #333; margin: 0;">ההנחה תתווסף אוטומטית לחשבונך</p>
                </div>
            </section>
            <section>
                <button style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 18px; font-weight: bold; color: white; background-color: #e53e3e; padding: 15px 30px; border: none; border-radius: 4px; display: block; margin: 30px auto;">
                    <i class="ri-shopping-cart-line" style="margin-left: 8px;"></i>קנה עכשיו
                </button>
            </section>
            <section>
                <hr style="border: none; border-top: 1px solid #e0e0e0; width: 80%; margin: 20px auto;">
            </section>
            <section>
                <p style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 14px; color: #666; text-align: center;">* המבצע בתוקף עד לתאריך 31.12.2023. בכפוף לתקנון.</p>
            </section>
        `;
    }
    
    getBlankTemplate() {
        return `
            <section>
                <h2 style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 24px; font-weight: bold; color: #333; text-align: right;">כותרת ראשית</h2>
            </section>
            <section>
                <p style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; color: #333; text-align: right;">תוכן המייל שלך יופיע כאן. לחץ כדי לערוך ולהתאים לצרכים שלך.</p>
            </section>
            <section>
                <div style="text-align: center; margin: 15px 0;">
                    <i class="ri-image-line" style="font-size: 48px; color: #9ca3af; display: inline-block;"></i>
                </div>
            </section>
            <section>
                <img src="https://via.placeholder.com/600x300" style="width: 100%; max-width: 600px; display: block; margin: 20px auto;" alt="תמונה לדוגמה">
            </section>
            <section>
                <h3 style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 20px; font-weight: bold; color: #333; text-align: right;">כותרת משנית</h3>
                <p style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; color: #333; text-align: right;">פסקה נוספת שתופיע במייל שלך. הוסף מידע רלוונטי כאן.</p>
            </section>
            <section>
                <button style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 16px; font-weight: bold; color: white; background-color: #6366f1; padding: 10px 20px; border: none; border-radius: 4px; display: block; margin: 20px auto;">
                    <i class="ri-cursor-line" style="margin-left: 8px;"></i>כפתור לדוגמה
                </button>
            </section>
            <section>
                <hr style="border: none; border-top: 1px solid #e0e0e0; width: 80%; margin: 20px auto;">
            </section>
            <section>
                <div style="background-color: #f9fafb; padding: 15px; border-radius: 4px; margin-top: 20px;">
                    <p style="font-family: 'Noto Sans Hebrew', sans-serif; font-size: 14px; color: #666; text-align: center;">חלק תחתון של המייל עם מידע נוסף.</p>
                </div>
            </section>
        `;
    }
    
    renderTemplates(templates) {
        if (templates.length === 0) {
            this.templateContainer.innerHTML = '<div class="text-center p-4 text-gray-500">לא נמצאו תבניות</div>';
            return;
        }
        
        let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4">';
        
        templates.forEach(template => {
            const thumbnailClass = template.id === 'blank' ? 'text-gray-300' : 'text-primary';
            
            html += `
                <div class="template-item border rounded-lg overflow-hidden hover:shadow-md transition-all cursor-pointer" data-id="${template.id}">
                    <div class="p-4 border-b">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="font-bold">${template.name}</h3>
                            <i class="ri-mail-line text-2xl ${thumbnailClass}"></i>
                        </div>
                        <p class="text-gray-500 text-sm">תבנית מוכנה לשימוש</p>
                    </div>
                    <div class="p-3 flex gap-2 justify-end bg-gray-50">
                        <button class="edit-template-btn px-3 py-1 text-sm bg-primary text-white rounded" data-id="${template.id}">
                            <i class="ri-edit-line"></i> השתמש בתבנית
                        </button>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        this.templateContainer.innerHTML = html;
        
        // הוספת אירועים לכפתורי עריכה
        document.querySelectorAll('.edit-template-btn').forEach(button => {
            button.addEventListener('click', () => {
                const templateId = button.dataset.id;
                this.loadTemplate(templateId, templates);
            });
        });
    }
    
    loadTemplate(templateId, templates) {
        const template = templates.find(t => t.id == templateId);
        
        if (!template) {
            alert('התבנית לא נמצאה');
            return;
        }
        
        // שמירת השם להמשך
        this.editor.currentTemplateName = template.name;
        
        // הפיכת ה-HTML לרכיבים בעורך
        const parser = new DOMParser();
        const doc = parser.parseFromString(template.html, 'text/html');
        
        // איפוס הסקשנים הקיימים
        this.editor.sections = [];
        
        // עבור על כל הסקשנים בתבנית
        doc.querySelectorAll('section').forEach((section, index) => {
            let type = '';
            let settings = {};
            
            // זיהוי סוג הסקשן לפי התוכן
            if (section.querySelector('h1, h2, h3')) {
                // סקשן מסוג כותרת
                type = 'heading';
                const heading = section.querySelector('h1, h2, h3');
                settings = this.editor.getDefaultSettings('heading');
                
                // פענוח הסגנון מה-CSS
                const style = heading.style;
                settings.content = heading.textContent || 'כותרת';
                
                if (style.fontFamily) settings.typography.fontFamily = style.fontFamily.split(',')[0].trim().replace(/['"]/g, '');
                if (style.fontSize) settings.typography.fontSize = parseInt(style.fontSize) || 24;
                if (style.fontWeight) settings.typography.fontWeight = style.fontWeight;
                if (style.textDecoration) settings.typography.textDecoration = style.textDecoration;
                if (style.color) settings.design.textColor = style.color;
                if (style.backgroundColor) settings.design.backgroundColor = style.backgroundColor;
                if (style.textAlign) settings.design.textAlign = style.textAlign;
                
            } else if (section.querySelector('p')) {
                // סקשן מסוג טקסט
                type = 'text';
                const paragraph = section.querySelector('p');
                settings = this.editor.getDefaultSettings('text');
                
                // שילוב כל פסקאות הטקסט בסקשן
                let content = '';
                section.querySelectorAll('p').forEach(p => {
                    content += p.textContent + '\n\n';
                });
                settings.content = content.trim() || 'טקסט';
                
                // פענוח הסגנון מה-CSS
                const style = paragraph.style;
                if (style.fontFamily) settings.typography.fontFamily = style.fontFamily.split(',')[0].trim().replace(/['"]/g, '');
                if (style.fontSize) settings.typography.fontSize = parseInt(style.fontSize) || 16;
                if (style.fontWeight) settings.typography.fontWeight = style.fontWeight;
                if (style.textDecoration) settings.typography.textDecoration = style.textDecoration;
                if (style.color) settings.design.textColor = style.color;
                if (style.backgroundColor) settings.design.backgroundColor = style.backgroundColor;
                if (style.textAlign) settings.design.textAlign = style.textAlign;
                
            } else if (section.querySelector('img')) {
                // סקשן מסוג תמונה
                type = 'image';
                const img = section.querySelector('img');
                settings = this.editor.getDefaultSettings('image');
                
                settings.src = img.src || '';
                settings.alt = img.alt || '';
                
                // פענוח הסגנון מה-CSS
                const style = img.style;
                if (style.width) settings.design.width = style.width;
                
                // פענוח הסגנון של המיכל של התמונה
                const parentStyle = section.style;
                if (parentStyle.backgroundColor) settings.design.backgroundColor = parentStyle.backgroundColor;
                if (parentStyle.textAlign) settings.design.align = parentStyle.textAlign;
                
            } else if (section.querySelector('button, .button, a.btn')) {
                // סקשן מסוג כפתור
                type = 'button';
                const button = section.querySelector('button, .button, a.btn');
                settings = this.editor.getDefaultSettings('button');
                
                settings.content = button.textContent || 'כפתור';
                
                // פענוח הסגנון מה-CSS
                const style = button.style;
                if (style.fontFamily) settings.typography.fontFamily = style.fontFamily.split(',')[0].trim().replace(/['"]/g, '');
                if (style.fontSize) settings.typography.fontSize = parseInt(style.fontSize) || 16;
                if (style.fontWeight) settings.typography.fontWeight = style.fontWeight;
                if (style.textDecoration) settings.typography.textDecoration = style.textDecoration;
                if (style.color) settings.design.textColor = style.color;
                if (style.backgroundColor) settings.design.backgroundColor = style.backgroundColor;
                if (style.width === '100%') settings.design.fullWidth = true;
                
                // פענוח הסגנון של המיכל של הכפתור
                const containerStyle = section.style;
                if (containerStyle.textAlign) settings.design.textAlign = containerStyle.textAlign;
                
            } else if (section.querySelector('hr')) {
                // סקשן מסוג קו מפריד
                type = 'divider';
                const hr = section.querySelector('hr');
                settings = this.editor.getDefaultSettings('divider');
                
                // פענוח הסגנון מה-CSS
                const style = hr.style;
                if (style.borderColor) settings.design.color = style.borderColor;
                if (style.width) settings.design.width = style.width;
                
            } else {
                // סקשן מסוג אחר או לא מזוהה, נשתמש בטקסט כברירת מחדל
                type = 'text';
                settings = this.editor.getDefaultSettings('text');
                settings.content = section.textContent || 'טקסט';
            }
            
            this.editor.sections.push({
                id: Date.now() + index,
                type,
                settings
            });
        });
        
        // אם אין סקשנים, יצור אחד ברירת מחדל
        if (this.editor.sections.length === 0) {
            this.editor.sections.push({
                id: Date.now(),
                type: 'heading',
                settings: this.editor.getDefaultSettings('heading')
            });
            
            this.editor.sections.push({
                id: Date.now() + 1,
                type: 'text',
                settings: this.editor.getDefaultSettings('text')
            });
        }
        
        // סגירת חלון בחירת התבנית
        this.loadTemplateDialog.classList.add('hidden');
        this.loadTemplateOverlay.classList.add('hidden');
        
        // עדכון העורך
        this.editor.renderPreview();
        this.editor.renderSectionsList();
        this.editor.selectSection(this.editor.sections[0].id); // בחירת הסקשן הראשון אוטומטית
        
        // הגדרת השם של התבנית
        this.editor.templateName.value = template.name;
    }
}

export default TemplateManager; 