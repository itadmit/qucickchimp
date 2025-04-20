// בדיקה אם הקוד כבר נטען
if (typeof window.imageUploader === 'undefined') {
    // Configuration
    const IMAGE_UPLOAD_ENDPOINT = '../upload.php';

    // פונקציות העלאת תמונות...
    function uploadImage(file, landingPageId) {
        return new Promise((resolve, reject) => {
            // יצירת FormData
            const formData = new FormData();
            formData.append('file', file);
            formData.append('landing_page_id', landingPageId);
            
            // יצירת בקשת XHR
            const xhr = new XMLHttpRequest();
            
            // טיפול בהתקדמות
            xhr.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable) {
                    const percentComplete = Math.round((event.loaded / event.total) * 100);
                    console.log(`Upload progress: ${percentComplete}%`);
                }
            });
            
            // טיפול בסיום
            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        resolve(response);
                    } catch (e) {
                        reject(new Error('Invalid server response'));
                    }
                } else {
                    reject(new Error(`Upload failed with status ${xhr.status}`));
                }
            });
            
            // טיפול בשגיאות
            xhr.addEventListener('error', () => {
                reject(new Error('Network error occurred'));
            });
            
            xhr.addEventListener('abort', () => {
                reject(new Error('Upload aborted'));
            });
            
            // התחלת ההעלאה
            xhr.open('POST', IMAGE_UPLOAD_ENDPOINT);
            xhr.send(formData);
        });
    }

    function selectImage(options = {}) {
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = options.accept || 'image/jpeg,image/png,image/gif,image/webp';
        fileInput.style.display = 'none';
        
        fileInput.addEventListener('change', () => {
            if (fileInput.files && fileInput.files[0]) {
                if (typeof options.onSelect === 'function') {
                    options.onSelect(fileInput.files[0]);
                }
            }
            
            // ניקוי
            document.body.removeChild(fileInput);
        });
        
        document.body.appendChild(fileInput);
        fileInput.click();
    }

    function replaceImage(imgElement, landingPageId, onComplete) {
        selectImage({
            onSelect: (file) => {
                // וידוא סוג הקובץ
                if (!['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)) {
                    alert('סוג הקובץ אינו נתמך. רק קבצי תמונה מסוג JPG, PNG, GIF או WebP מותרים');
                    if (typeof onComplete === 'function') onComplete(false);
                    return;
                }
                
                // וידוא גודל הקובץ
                if (file.size > 5 * 1024 * 1024) {
                    alert('הקובץ גדול מדי. גודל מקסימלי הוא 5MB');
                    if (typeof onComplete === 'function') onComplete(false);
                    return;
                }
                
                // הצגת מצב טעינה
                const originalSrc = imgElement.src;
                imgElement.style.opacity = "0.5";
                
                // יצירת ספינר טעינה
                const spinner = document.createElement('div');
                spinner.className = 'absolute inset-0 flex items-center justify-center bg-black bg-opacity-20';
                spinner.innerHTML = '<div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-indigo-500"></div>';
                
                if (imgElement.parentNode) {
                    if (getComputedStyle(imgElement.parentNode).position === 'static') {
                        imgElement.parentNode.style.position = 'relative';
                    }
                    imgElement.parentNode.appendChild(spinner);
                }
                
                // העלאת הקובץ
                uploadImage(file, landingPageId)
                    .then(response => {
                        // הסרת מצב הטעינה
                        imgElement.style.opacity = "";
                        if (spinner.parentNode) spinner.parentNode.removeChild(spinner);
                        
                        if (response.success) {
                            // עדכון מקור התמונה
                            imgElement.src = response.file_url;
                            
                            console.log('התמונה הוחלפה בהצלחה');
                            if (typeof onComplete === 'function') onComplete(true);
                        } else {
                            // שחזור התמונה המקורית והצגת שגיאה
                            imgElement.src = originalSrc;
                            alert(response.message || 'שגיאה בהעלאת התמונה');
                            if (typeof onComplete === 'function') onComplete(false);
                        }
                    })
                    .catch(error => {
                        // הסרת מצב הטעינה
                        imgElement.style.opacity = "";
                        if (spinner.parentNode) spinner.parentNode.removeChild(spinner);
                        
                        // שחזור התמונה המקורית והצגת שגיאה
                        imgElement.src = originalSrc;
                        alert('שגיאה בהעלאת התמונה: ' + error.message);
                        if (typeof onComplete === 'function') onComplete(false);
                    });
            }
        });
    }

    // ייצוא הפונקציות לשימוש בקסטומייזר
    window.imageUploader = {
        upload: function(landingPageId, onSuccess, onError) {
            selectImage({
                onSelect: (file) => {
                    // וידוא סוג הקובץ
                    if (!['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)) {
                        if (typeof onError === 'function') {
                            onError('סוג הקובץ אינו נתמך. רק קבצי תמונה מסוג JPG, PNG, GIF או WebP מותרים');
                        }
                        return;
                    }
                    
                    // וידוא גודל הקובץ
                    if (file.size > 5 * 1024 * 1024) {
                        if (typeof onError === 'function') {
                            onError('הקובץ גדול מדי. גודל מקסימלי הוא 5MB');
                        }
                        return;
                    }
                    
                    // העלאת הקובץ
                    uploadImage(file, landingPageId)
                        .then(response => {
                            if (response.success) {
                                if (typeof onSuccess === 'function') {
                                    onSuccess(response.file_url);
                                }
                            } else {
                                if (typeof onError === 'function') {
                                    onError(response.message || 'שגיאה בהעלאת הקובץ');
                                }
                            }
                        })
                        .catch(error => {
                            if (typeof onError === 'function') {
                                onError(error.message);
                            }
                        });
                }
            });
        },
        replaceImage: replaceImage
    };
}