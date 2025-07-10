async function submitGrade(event, submissionId) {
    event.preventDefault();
    const form = event.target;
    const score = form.querySelector('input[name="score"]').value;
    const feedback = form.querySelector('textarea[name="feedback"]').value;

    try {
        const response = await fetch('../../api/grade_submission.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                submission_id: submissionId,
                score: score,
                feedback: feedback
            })
        });

        const data = await response.json();
        if (data.success) {
            alert('Grade saved successfully!');
            // Optionally refresh the page or update the UI
            location.reload();
        } else {
            alert(data.message || 'Failed to save grade');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to save grade');
    }
}