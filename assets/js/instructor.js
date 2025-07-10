function editCourse(courseId) {
    fetch(`../../api/get_course.php?id=${courseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = `edit_course.php?id=${courseId}`;
            } else {
                alert('Error accessing course. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
}