/**
 * ספריית המדיה - מאפשרת לבחור תמונות קיימות ולהעלות חדשות
 */

// משתנה לשמירת קולבק בעת בחירת תמונה
let mediaSelectionCallback = null;
let currentLandingPageId = null;
let selectedImage = null;
let isUploading = false; // דגל למניעת העלאות כפולות

// פתיחת המודל של ספריית המדיה
function openMediaLibrary(landingPageId, onSelect) {
    // שמירת המזהה של דף הנחיתה והקולבק
    currentLandingPageId = landingPageId;
    mediaSelectionCallback = onSelect;
    
    // איפוס בחירת תמונה קודמת
    selectedImage = null;
    document.querySelectorAll('.media-item.selected').forEach(item => {
        item.classList.remove('selected');
    });
    
    // הצגת המודל
    const modal = document.getElementById('media-library-modal');
    modal.classList.remove('hidden');
    
    // טעינת תמונות קיימות
    loadExistingMedia(landingPageId);
}

// טעינת תמונות קיימות מהשרת
function loadExistingMedia(landingPageId) {
    // איפוס גריד המדיה
    const mediaGrid = document.getElementById('media-grid');
    if (!mediaGrid) {
        console.error("Media grid not found");
        return;
    }
    
    mediaGrid.innerHTML = `
        <div class="text-center py-10 text-gray-500 col-span-4">
            <i class="ri-loader-4-line animate-spin text-3xl mb-2"></i>
            <p>טוען תמונות...</p>
        </div>
    `;
    
    console.log("Loading media for landing page ID:", landingPageId);
    
    // טעינת התמונות מהשרת
    fetch(`/get_media.php?landing_page_id=${landingPageId}`)
        .then(response => {
            console.log("Response status:", response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                console.log("Raw API response:", text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error("JSON parsing error:", e);
                    throw new Error("Invalid JSON response");
                }
            });
        })
        .then(data => {
            console.log("Parsed API data:", data);
            if (data.success && data.files && Array.isArray(data.files)) {
                console.log("Will render", data.files.length, "images");
                renderMediaGrid(data.files);
            } else {
                console.log("API returned error or no files:", data.message);
                mediaGrid.innerHTML = `
                    <div class="text-center py-10 text-gray-500 col-span-4">
                        <i class="ri-error-warning-line text-3xl mb-2"></i>
                        <p>${data.message || 'שגיאה בטעינת תמונות'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error("Error loading media:", error);
            mediaGrid.innerHTML = `
                <div class="text-center py-10 text-gray-500 col-span-4">
                    <i class="ri-error-warning-line text-3xl mb-2"></i>
                    <p>שגיאה בטעינת תמונות: ${error.message}</p>
                </div>
            `;
        });
}

// הצגת התמונות בגריד
// החלף את הפונקציה הקיימת renderMediaGrid בקובץ media-library.js
function renderMediaGrid(files) {
    console.log("renderMediaGrid called with", files.length, "files");
    
    const mediaGrid = document.getElementById('media-grid');
    if (!mediaGrid) {
        console.error("Media grid container not found");
        return;
    }
    
    // נקה את תוכן הגריד הנוכחי
    console.log("Clearing media grid");
    mediaGrid.innerHTML = '';
    
    if (files.length === 0) {
        console.log("No files to display");
        mediaGrid.innerHTML = `
            <div class="text-center py-10 text-gray-500 col-span-4">
                <i class="ri-image-line text-3xl mb-2"></i>
                <p>אין תמונות בספרייה. העלה תמונות חדשות כדי להתחיל.</p>
            </div>
        `;
        return;
    }
    
    // לולאה שעוברת על כל התמונות
    console.log("Creating media item elements");
    files.forEach((file, index) => {
        console.log(`Processing file ${index+1}/${files.length}:`, file.filename);
        
        // Convert HTTP to HTTPS if needed
        const secureUrl = file.url.replace('http://', 'https://');
        
        // יצירת אלמנט עבור תמונה
        const mediaItem = document.createElement('div');
        mediaItem.className = 'media-item border rounded-lg overflow-hidden cursor-pointer hover:shadow-md';
        mediaItem.setAttribute('data-url', secureUrl);
        
        // הגדרת התוכן של האלמנט
        mediaItem.innerHTML = `
            <div style="height: 150px; background-color: #f9f9f9; overflow: hidden;">
                <img src="${secureUrl}" alt="${file.filename}" class="object-cover w-full h-full">
            </div>
            <div class="p-2 text-sm truncate text-center">${file.filename}</div>
        `;
        
        // הוספת אירוע לחיצה
        mediaItem.addEventListener('click', () => {
            console.log("Media item clicked:", secureUrl);
            
            // הסרת סימון מכל התמונות
            document.querySelectorAll('.media-item.selected').forEach(item => {
                item.classList.remove('selected', 'ring-2', 'ring-indigo-500');
            });
            
            // הוספת סימון לתמונה הנבחרת
            mediaItem.classList.add('selected', 'ring-2', 'ring-indigo-500');
            
            // שמירת התמונה הנבחרת
            selectedImage = secureUrl;
            
            // אופציונלי: לחיצה כפולה בוחרת ושולחת
            mediaItem.addEventListener('dblclick', () => {
                if (mediaSelectionCallback) {
                    console.log("Double click - selecting image:", secureUrl);
                    mediaSelectionCallback(secureUrl);
                    closeMediaLibrary();
                }
            });
        });
        
        // הוספת האלמנט לגריד
        mediaGrid.appendChild(mediaItem);
    });
    
    console.log("Media grid rendered with", mediaGrid.children.length, "items");
    
    // עדכון כפתור ההעלאה לכפתור בחירה אם יש תמונות
    const uploadButton = document.getElementById('upload-media-button');
if (uploadButton) {
    uploadButton.addEventListener('click', () => {
        console.log("Upload/select button clicked");
        const fileInput = document.getElementById('media-upload-input');
        
        if (fileInput.files.length > 0) {
            // העלאת קובץ חדש
            console.log("Uploading new file");
            uploadMedia(fileInput.files[0]);
        } else if (selectedImage) {
            // בחירת תמונה קיימת
            console.log("Selecting existing image:", selectedImage);
            if (typeof mediaSelectionCallback === 'function') {
                mediaSelectionCallback(selectedImage);
                closeMediaLibrary();
            } else {
                console.error("No callback function available for image selection");
                alert('שגיאה: לא ניתן לבחור תמונה');
            }
        } else {
            console.log("No file selected and no image chosen");
            alert('אנא בחר תמונה מהגלריה או העלה תמונה חדשה');
        }
    });
}

}

// אתחול אירועים במודל המדיה
// אתחול אירועים במודל המדיה
function initMediaLibrary() {
    console.log("Initializing media library events");
    
    // סגירת המודל
    const closeBtn = document.getElementById('close-media-library');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            console.log("Close button clicked");
            closeMediaLibrary();
        });
    }
    
    // לחצן העלאה/בחירה
    const uploadBtn = document.getElementById('upload-media-button');
    if (uploadBtn) {
        uploadBtn.addEventListener('click', () => {
            console.log("Upload/select button clicked");
            const fileInput = document.getElementById('media-upload-input');
            
            if (fileInput.files.length > 0) {
                // העלאת קובץ חדש
                console.log("Uploading new file");
                uploadMedia(fileInput.files[0]);
            } else if (selectedImage) {
                // בחירת תמונה קיימת
                console.log("Selected image:", selectedImage);
                if (mediaSelectionCallback) {
                    mediaSelectionCallback(selectedImage);
                    closeMediaLibrary();
                }
            } else {
                // לא נבחר כלום
                console.log("No image selected");
                alert('אנא בחר תמונה או העלה תמונה חדשה');
            }
        });
    }
    
    // אתחול דרופ זון
    const uploadZone = document.querySelector('.border-dashed');
    const uploadInput = document.getElementById('media-upload-input');
    
    if (uploadZone && uploadInput) {
        // לחיצה על אזור הגרירה
        uploadZone.addEventListener('click', () => {
            uploadInput.click();
        });
        
        // בחירת קובץ
        uploadInput.addEventListener('change', () => {
            if (uploadInput.files.length > 0) {
                handleFileSelection(uploadInput.files[0]);
            }
        });
    }
    
    // כפתור רענון
    const refreshBtn = document.getElementById('refresh-media');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
            if (currentLandingPageId) {
                loadExistingMedia(currentLandingPageId);
            }
        });
    }
}

// טיפול בבחירת קובץ
function handleFileSelection(file) {
    const uploadPreview = document.getElementById('upload-preview');
    const uploadPlaceholder = document.getElementById('upload-placeholder');
    const uploadButton = document.getElementById('upload-media-button');
    
    // בדיקת סוג הקובץ
    if (!['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)) {
        alert('סוג הקובץ אינו נתמך. רק קבצי תמונה מסוג JPG, PNG, GIF או WebP מותרים');
        return;
    }
    
    // בדיקת גודל הקובץ
    if (file.size > 5 * 1024 * 1024) {
        alert('הקובץ גדול מדי. גודל מקסימלי הוא 5MB');
        return;
    }
    
    // הצגת תצוגה מקדימה
    const reader = new FileReader();
    reader.onload = (e) => {
        uploadPreview.querySelector('img').src = e.target.result;
        uploadPreview.classList.remove('hidden');
        uploadPlaceholder.classList.add('hidden');
    };
    reader.readAsDataURL(file);
    
    // הפעלת כפתור העלאה
    uploadButton.disabled = false;
}

function closeMediaLibrary() {
    console.log("Closing media library");
    const mediaModal = document.getElementById('media-library-modal');
    if (mediaModal) {
        mediaModal.classList.add('hidden');
    }
    isMediaLibraryOpening = false;
}

// העלאת קובץ לשרת
function uploadMedia(file) {
    if (isUploading) {
        console.log("Upload already in progress, ignoring request");
        return;
    }
    
    const uploadButton = document.getElementById('upload-media-button');
    
    // יצירת FormData
    const formData = new FormData();
    formData.append('file', file);
    formData.append('landing_page_id', currentLandingPageId);
    
    // שינוי טקסט הכפתור
    uploadButton.disabled = true;
    uploadButton.innerHTML = '<i class="ri-loader-4-line animate-spin mr-1"></i> מעלה...';
    isUploading = true;
    
    // שליחת הקובץ
    fetch('/upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log("Parsed API data:", data);
        if (data.success) {
            // רענון הגריד
            loadExistingMedia(currentLandingPageId);
        } else {
            throw new Error(data.message || 'שגיאה בהעלאת התמונה');
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        alert('שגיאה בהעלאת התמונה: ' + error.message);
    })
    .finally(() => {
        // איפוס הכפתור והדגל
        uploadButton.disabled = false;
        uploadButton.innerHTML = 'העלאת תמונה';
        isUploading = false;
    });
}

// חשיפת פונקציות לשימוש גלובלי
window.mediaLibrary = {
    open: openMediaLibrary,
    loadExistingMedia: loadExistingMedia  // הוסף את זה אם חסר
};

// אתחול ספריית המדיה כאשר המסמך נטען
document.addEventListener('DOMContentLoaded', initMediaLibrary);