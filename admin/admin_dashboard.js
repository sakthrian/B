document.addEventListener('DOMContentLoaded', function() {
    // --------------- Navigation System ---------------
    const sidebarItems = document.querySelectorAll('.sidebar-menu li');
    const sections = document.querySelectorAll('.section');
    
    // Initialize sections (hide all by default)
    sections.forEach(section => {
        section.classList.remove('active');
        section.style.opacity = '0';
        section.style.transform = 'scale(0.9)';
    });
    
    // Function to activate a section
    function activateSection(sectionId) {
        // Hide all sections first
        sections.forEach(section => {
            section.classList.remove('active');
            section.style.opacity = '0';
            section.style.transform = 'scale(0.9)';
        });
        
        // Deactivate all sidebar items
        sidebarItems.forEach(item => {
            item.classList.remove('active');
        });
        
        // Find and activate the target section
        const targetSection = document.getElementById(sectionId);
        const targetSidebarItem = document.querySelector(`.sidebar-menu li[data-section="${sectionId}"]`);
        
        if (targetSection) {
            setTimeout(() => {
                targetSection.classList.add('active');
                targetSection.style.opacity = '1';
                targetSection.style.transform = 'scale(1)';
            }, 50);
        }
        
        if (targetSidebarItem) {
            targetSidebarItem.classList.add('active');
        }
    }
    
    // Handle navigation via URL hash
    function handleHashChange() {
        const hash = window.location.hash.substring(1);
        if (hash) {
            activateSection(hash);
        } else if (sections.length > 0) {
            // If no hash, activate the first section by default
            const firstSectionId = sections[0].id;
            window.location.hash = firstSectionId;
        }
    }
    
    // Apply navigation on page load and hash change
    window.addEventListener('hashchange', handleHashChange);
    handleHashChange();
    
    // Handle sidebar menu clicks
    sidebarItems.forEach(item => {
        item.addEventListener('click', function() {
            const sectionId = this.getAttribute('data-section');
            if (sectionId) {
                window.location.hash = sectionId;
            }
        });
    });
    
    // --------------- File Upload System ---------------
    const studentFileUpload = document.getElementById('student-file-upload');
    const uploadBtn = document.querySelector('.upload-btn');
    const uploadPreviewTable = document.getElementById('upload-preview-table')?.querySelector('tbody');
    const uploadPreview = document.querySelector('.upload-preview');
    const uploadActions = document.querySelector('.upload-actions');
    
    // Handle upload button click
    if (uploadBtn) {
        uploadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (studentFileUpload) {
                studentFileUpload.click();
            }
        });
    }
    
    // Handle file selection
    if (studentFileUpload) {
        studentFileUpload.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        studentFileUpload.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                if (uploadPreviewTable) {
                    uploadPreviewTable.innerHTML = '';
                }
                
                if (uploadPreview) {
                    uploadPreview.style.display = 'block';
                }
                
                if (uploadActions) {
                    uploadActions.style.display = 'flex';
                }
            }
        });
    }
    
    // Handle cancel upload button
    const cancelUploadBtn = document.querySelector('.cancel-upload');
    if (cancelUploadBtn) {
        cancelUploadBtn.addEventListener('click', function() {
            if (studentFileUpload) {
                studentFileUpload.value = '';
            }
            
            if (uploadPreview) {
                uploadPreview.style.display = 'none';
            }
            
            if (uploadActions) {
                uploadActions.style.display = 'none';
            }
        });
    }
    
    // Handle confirm upload button
    const confirmUploadBtn = document.querySelector('.confirm-upload');
    if (confirmUploadBtn) {
        confirmUploadBtn.addEventListener('click', function() {
            if (studentFileUpload) {
                studentFileUpload.value = '';
            }
            
            if (uploadPreviewTable) {
                uploadPreviewTable.innerHTML = '';
            }
            
            if (uploadPreview) {
                uploadPreview.style.display = 'none';
            }
            
            if (uploadActions) {
                uploadActions.style.display = 'none';
            }
        });
    }
    
    // --------------- Faculty Mapping System ---------------
    const facultySelect = document.getElementById('faculty-select');
    const courseSelect = document.getElementById('course-select');
    const semesterSelect = document.getElementById('semester-select');
    const mapFacultyBtn = document.querySelector('.btn-map');
    
    if (mapFacultyBtn) {
        mapFacultyBtn.addEventListener('click', function() {
            if (facultySelect && courseSelect && semesterSelect &&
                facultySelect.value && courseSelect.value && semesterSelect.value) {
                
                facultySelect.selectedIndex = 0;
                courseSelect.selectedIndex = 0;
                semesterSelect.selectedIndex = 0;
            } else {
                alert('Please select Faculty, Course, and Semester');
            }
        });
    }
});