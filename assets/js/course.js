// Add lesson click handler
document.addEventListener('DOMContentLoaded', function() {
    const lessonItems = document.querySelectorAll('.lesson-item');
    
    lessonItems.forEach(item => {
        item.addEventListener('click', function() {
            // Close other open lessons
            lessonItems.forEach(otherItem => {
                if (otherItem !== item && otherItem.classList.contains('active')) {
                    otherItem.classList.remove('active');
                }
            });
            
            // Toggle current lesson
            this.classList.toggle('active');
        });
    });

    // Mark course as completed
    const completeBtn = document.getElementById('completeBtn');
    if (completeBtn) {
        completeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const courseId = this.getAttribute('data-course');
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Completing...';
            fetch('../api/complete_course.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ course_id: courseId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.error || 'Failed to mark course as completed.');
                    completeBtn.disabled = false;
                    completeBtn.innerHTML = '<i class="fas fa-flag-checkered"></i> Mark as Completed';
                }
            })
            .catch(() => {
                alert('Failed to mark course as completed.');
                completeBtn.disabled = false;
                completeBtn.innerHTML = '<i class="fas fa-flag-checkered"></i> Mark as Completed';
            });
        });
    }
});