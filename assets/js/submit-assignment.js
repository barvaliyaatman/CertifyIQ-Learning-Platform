document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('assignmentForm');
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('../api/submit_assignment.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Assignment submitted successfully!');
                location.reload();
            } else {
                alert(data.message || 'Failed to submit assignment');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to submit assignment');
        }
    });
});