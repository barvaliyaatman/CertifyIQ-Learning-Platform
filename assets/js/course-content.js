document.addEventListener('DOMContentLoaded', function() {
    // Initialize sortable functionality
    initSortable();

    const urlParams = new URLSearchParams(window.location.search);
    const courseId = urlParams.get('course_id');

    // --- MODAL CONTROLS ---
    window.addSection = () => {
        document.getElementById('sectionModal').style.display = 'block';
        document.querySelector('#sectionModal h2').textContent = 'Add Section';
        document.getElementById('sectionForm').reset();
        document.getElementById('sectionForm').setAttribute('data-mode', 'add');
    };
    
    window.addLesson = () => {
        document.getElementById('lessonModal').style.display = 'block';
        document.querySelector('#lessonModal h2').textContent = 'Add Lesson';
        document.getElementById('lessonForm').reset();
        document.getElementById('lessonForm').setAttribute('data-mode', 'add');
    };
    
    window.closeModal = (modalId) => document.getElementById(modalId).style.display = 'none';

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    };

    // --- FORM SUBMISSIONS ---
    const sectionForm = document.getElementById('sectionForm');
    if (sectionForm) {
        sectionForm.addEventListener('submit', handleSectionFormSubmit);
    }

    const lessonForm = document.getElementById('lessonForm');
    if (lessonForm) {
        lessonForm.addEventListener('submit', handleLessonFormSubmit);
    }

    // --- DYNAMIC ACTIONS (Edit/Delete) ---
    window.editSection = (sectionId) => {
        fetch(`../../ajax/get_section.php?id=${sectionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const section = data.section;
                document.querySelector('#sectionModal h2').textContent = 'Edit Section';
                document.getElementById('sectionForm').setAttribute('data-mode', 'edit');
                document.getElementById('sectionForm').setAttribute('data-section-id', sectionId);
                
                // Fill the form with existing data
                document.querySelector('#sectionForm input[name="title"]').value = section.title;
                document.querySelector('#sectionForm input[name="order"]').value = section.order_number;
                
                document.getElementById('sectionModal').style.display = 'block';
            } else {
                alert(data.message || 'Failed to load section data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading section data');
        });
    };

    window.deleteSection = (sectionId) => {
        if (confirm('Are you sure you want to delete this section and all its lessons?')) {
            fetch('../../api/manage_section.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ section_id: sectionId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to delete section');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An unexpected error occurred while deleting the section.');
            });
        }
    };
    
    window.editLesson = (lessonId) => {
        fetch(`../../ajax/get_lesson.php?id=${lessonId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const lesson = data.lesson;
                document.querySelector('#lessonModal h2').textContent = 'Edit Lesson';
                document.getElementById('lessonForm').setAttribute('data-mode', 'edit');
                document.getElementById('lessonForm').setAttribute('data-lesson-id', lessonId);
                
                // Fill the form with existing data
                document.querySelector('#lessonForm select[name="section_id"]').value = lesson.section_id;
                document.querySelector('#lessonForm input[name="title"]').value = lesson.title;
                document.querySelector('#lessonForm select[name="type"]').value = lesson.type;
                document.querySelector('#lessonForm textarea[name="content"]').value = lesson.content || '';
                document.querySelector('#lessonForm input[name="order"]').value = lesson.order_number;
                
                document.getElementById('lessonModal').style.display = 'block';
            } else {
                alert(data.message || 'Failed to load lesson data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading lesson data');
        });
    };

    window.deleteLesson = (lessonId) => {
        if (confirm('Are you sure you want to delete this lesson?')) {
            fetch('../../ajax/delete_lesson.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `lesson_id=${lessonId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to delete lesson');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An unexpected error occurred while deleting the lesson.');
            });
        }
    };

    // --- HELPERS ---
    function initSortable() {
        const sectionsContainer = document.querySelector('.sections-list');
        if (sectionsContainer) {
            new Sortable(sectionsContainer, {
                animation: 150,
                handle: '.drag-handle',
                onEnd: function(evt) {
                    // Handle order update
                }
            });
        }
    }
    
    async function handleSectionFormSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const submitButton = form.querySelector('button[type="submit"]');
        const mode = form.getAttribute('data-mode');
        const sectionId = form.getAttribute('data-section-id');

        toggleButtonState(submitButton, true, 'Saving...');

        try {
            let response;
            if (mode === 'edit') {
                // Edit existing section
                const formData = {
                    section_id: sectionId,
                    title: form.querySelector('input[name="title"]').value,
                    order: form.querySelector('input[name="order"]').value
                };
                
                response = await fetch('../../api/manage_section.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
            } else {
                // Add new section
                const formData = new FormData(form);
                response = await fetch('../../api/manage_section.php', {
                    method: 'POST',
                    body: formData
                });
            }
            
            const data = await response.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Failed to save section');
                toggleButtonState(submitButton, false, 'Save Section');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An unexpected error occurred.');
            toggleButtonState(submitButton, false, 'Save Section');
        }
    }

    async function handleLessonFormSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const submitButton = form.querySelector('button[type="submit"]');
        const mode = form.getAttribute('data-mode');
        const lessonId = form.getAttribute('data-lesson-id');

        toggleButtonState(submitButton, true, 'Saving...');

        try {
            let response;
            if (mode === 'edit') {
                // Edit existing lesson
                const formData = {
                    lesson_id: lessonId,
                    title: form.querySelector('input[name="title"]').value,
                    type: form.querySelector('select[name="type"]').value,
                    content: form.querySelector('textarea[name="content"]').value,
                    order: form.querySelector('input[name="order"]').value
                };
                
                response = await fetch('../../api/manage_lesson.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
            } else {
                // Add new lesson
                const formData = new FormData(form);
                response = await fetch('../../api/manage_lesson.php', {
                    method: 'POST',
                    body: formData
                });
            }
            
            const data = await response.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Failed to save lesson');
                toggleButtonState(submitButton, false, 'Save Lesson');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An unexpected error occurred.');
            toggleButtonState(submitButton, false, 'Save Lesson');
        }
    }

    function toggleButtonState(button, disabled, text) {
        button.disabled = disabled;
        button.textContent = text;
    }
});