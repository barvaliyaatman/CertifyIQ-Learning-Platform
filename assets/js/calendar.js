document.addEventListener('DOMContentLoaded', function() {
    // Event click handler
    document.querySelectorAll('.event').forEach(event => {
        event.addEventListener('click', function() {
            const eventId = this.dataset.eventId;
            const eventType = this.dataset.eventType;
            
            fetch('calendar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_event_details&event_id=${eventId}&event_type=${eventType}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showEventModal(data.data);
                }
            });
        });
    });

    // Navigation handlers
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = new URL(this.href);
            const month = url.searchParams.get('month');
            const year = url.searchParams.get('year');
            
            updateCalendar(month, year);
        });
    });
});

function showEventModal(eventData) {
    const modal = document.getElementById('eventModal');
    const content = document.getElementById('eventModalContent');
    
    // Populate modal with event details
    content.innerHTML = `
        <h3>${eventData.title}</h3>
        <p class="event-course">${eventData.course}</p>
        <p class="event-time">${eventData.time}</p>
        <p class="event-description">${eventData.description}</p>
    `;
    
    modal.style.display = 'block';
}

// Add these functions after your existing calendar.js code

function showAddEventModal() {
    const modal = document.getElementById('eventModal');
    modal.style.display = 'block';
}

document.querySelector('.close').onclick = function() {
    document.getElementById('eventModal').style.display = 'none';
}

document.getElementById('eventForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'add_event');
    
    fetch('calendar.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('eventModal').style.display = 'none';
            // Refresh calendar
            location.reload();
        } else {
            alert('Error adding event: ' + data.message);
        }
    });
}

// Add event deletion handler
function deleteEvent(eventId, eventType) {
    if (confirm('Are you sure you want to delete this event?')) {
        fetch('calendar.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_event&event_id=${eventId}&event_type=${eventType}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting event: ' + data.message);
            }
        });
    }
}