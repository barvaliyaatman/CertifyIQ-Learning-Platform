document.addEventListener('DOMContentLoaded', function() {
    const assignmentForm = document.getElementById('assignmentForm');
    if (assignmentForm) {
        assignmentForm.addEventListener('submit', handleAssignmentSubmit);
    }
});

function addAssignment() {
    document.getElementById('assignmentModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

async function handleAssignmentSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('../../api/manage_assignment.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error saving assignment');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to save assignment');
    }
}

async function deleteAssignment(id) {
    if (confirm('Are you sure you want to delete this assignment?')) {
        try {
            const response = await fetch('../../api/delete_assignment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            });
            
            const data = await response.json();
            if (data.success) {
                location.reload();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
}

async function editAssignment(id) {
    try {
        const response = await fetch(`../../api/get_assignment.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const assignment = data.assignment;
            document.querySelector('#assignmentModal h2').textContent = 'Edit Assignment';
            const form = document.getElementById('assignmentForm');
            form.querySelector('[name="title"]').value = assignment.title;
            form.querySelector('[name="description"]').value = assignment.description;
            form.querySelector('[name="max_score"]').value = assignment.max_score;
            form.querySelector('[name="due_date"]').value = assignment.due_date;
            document.getElementById('assignmentModal').style.display = 'block';
        }
    } catch (error) {
        console.error('Error:', error);
    }
}