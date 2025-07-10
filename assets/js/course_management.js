document.addEventListener('DOMContentLoaded', function() {
    // Tab switching functionality
    window.showTab = function(tabName) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        
        document.getElementById(tabName + '-tab').classList.add('active');
        document.querySelector(`[onclick="showTab('${tabName}')"]`).classList.add('active');
    };

    // Modal functionality
    const modal = document.getElementById('lessonModal');
    const closeBtn = document.querySelector('.close');
    
    window.showAddLessonModal = function() {
        document.getElementById('lessonForm').reset();
        document.getElementById('lessonId').value = '';
        document.querySelector('#lessonModal h2').textContent = 'Add New Lesson';
        modal.style.display = 'block';
    };

    closeBtn.onclick = function() {
        modal.style.display = 'none';
    };

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    };

    // Lesson form submission
    document.getElementById('lessonForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('../../ajax/manage_lesson.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        });
    });

    // Edit lesson
    window.editLesson = function(lessonId) {
        fetch(`../../ajax/get_lesson.php?id=${lessonId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('lessonId').value = data.lesson.id;
                document.getElementById('lessonTitle').value = data.lesson.title;
                document.getElementById('lessonDescription').value = data.lesson.description;
                document.getElementById('lessonContent').value = data.lesson.content;
                document.querySelector('#lessonModal h2').textContent = 'Edit Lesson';
                modal.style.display = 'block';
            }
        });
    };

    // Delete lesson
    window.deleteLesson = function(lessonId) {
        if (confirm('Are you sure you want to delete this lesson?')) {
            fetch('../../ajax/delete_lesson.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `lesson_id=${lessonId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        }
    };
});