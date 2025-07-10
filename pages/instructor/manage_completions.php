<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Ensure user is logged in and is an instructor
require_login();
if ($_SESSION['user_role'] !== 'instructor') {
    header("Location: /pages/dashboard.php");
    exit();
}

$instructor_id = $_SESSION['user_id'];

// Fetch course completions awaiting approval for courses taught by this instructor
$stmt = $pdo->prepare("
    SELECT 
        cc.id,
        u.name as student_name,
        c.title as course_title,
        cc.completion_date
    FROM course_completions cc
    JOIN users u ON cc.user_id = u.id
    JOIN courses c ON cc.course_id = c.id
    WHERE c.instructor_id = ? AND cc.status = 'pending'
    ORDER BY cc.completion_date ASC
");
$stmt->execute([$instructor_id]);
$completions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Manage Completions";
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $page_title; ?> - LMS</title>
    <link rel="stylesheet" href="../../assets/css/instructor.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .completions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }
        .completion-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.07);
            padding: 20px;
            border-left: 5px solid #667eea;
            transition: all 0.3s ease;
        }
        .completion-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }
        .completion-card h3 {
            font-size: 1.2em;
            margin-top: 0;
            margin-bottom: 5px;
        }
        .completion-card .student-name {
            font-weight: bold;
            color: #333;
        }
        .completion-card .course-title {
            color: #555;
            font-size: 1em;
            margin-bottom: 15px;
        }
        .completion-card .completion-date {
            font-size: 0.9em;
            color: #777;
            margin-bottom: 20px;
        }
        .completion-card .actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .no-completions {
            text-align: center;
            padding: 50px;
            background: #fff;
            border-radius: 8px;
            border: 2px dashed #e0e0e0;
        }
        .no-completions i {
            font-size: 3.5em;
            color: #ccc;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="instructor-container">
        <?php include '../../includes/instructor_sidebar.php'; ?>

        <main class="instructor-content">
            <header class="instructor-header">
                <h1><i class="fas fa-tasks"></i> Course Completion Approvals</h1>
            </header>

            <div class="card">
                <div class="card-header">
                    <h2>Pending Approvals</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($completions)): ?>
                        <div class="no-completions">
                            <i class="fas fa-check-double"></i>
                            <p>No pending course completion approvals at this time.</p>
                        </div>
                    <?php else: ?>
                        <div class="completions-grid">
                            <?php foreach ($completions as $completion): ?>
                                <div class="completion-card" id="completion-row-<?php echo $completion['id']; ?>">
                                    <h3>
                                        <span class="student-name"><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($completion['student_name']); ?></span>
                                    </h3>
                                    <p class="course-title"><i class="fas fa-book-open"></i> <?php echo htmlspecialchars($completion['course_title']); ?></p>
                                    <p class="completion-date">
                                        <i class="fas fa-calendar-check"></i> Completed on: <?php echo date('M d, Y', strtotime($completion['completion_date'])); ?>
                                    </p>
                                    <div class="actions">
                                        <button class="btn btn-success approve-btn" data-id="<?php echo $completion['id']; ?>">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button class="btn btn-danger reject-btn" data-id="<?php echo $completion['id']; ?>">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectionModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal('rejectionModal')">&times;</span>
            <h2>Reason for Rejection</h2>
            <form id="rejectionForm">
                <input type="hidden" name="completion_id" id="rejectionCompletionId">
                <textarea name="rejection_reason" id="rejectionReason" rows="5" placeholder="Provide a reason..." required></textarea>
                <div class="form-actions">
                    <button type="button" onclick="closeModal('rejectionModal')" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-danger">Submit Rejection</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    // Function to close modal
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    $(document).ready(function() {
        // Handle Approve
        $('.approve-btn').on('click', function() {
            var completionId = $(this).data('id');
            var button = $(this);

            if (confirm('Are you sure you want to approve this completion?')) {
                $.ajax({
                    url: '../../api/approve_completion.php',
                    type: 'POST',
                    data: { completion_id: completionId },
                    dataType: 'json',
                    beforeSend: function() {
                        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Approving...');
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#completion-row-' + completionId).fadeOut(500, function() {
                                $(this).remove();
                                if ($('.completions-grid .completion-card').length === 0) {
                                    $('.card-body').html('<div class="no-completions"><i class="fas fa-check-double"></i><p>No pending course completion approvals at this time.</p></div>');
                                }
                            });
                        } else {
                            alert('Error: ' + response.message);
                            button.prop('disabled', false).html('<i class="fas fa-check"></i> Approve');
                        }
                    },
                    error: function() {
                        alert('An unexpected error occurred. Please try again.');
                        button.prop('disabled', false).html('<i class="fas fa-check"></i> Approve');
                    }
                });
            }
        });

        // Handle Reject button click
        $('.reject-btn').on('click', function() {
            var completionId = $(this).data('id');
            $('#rejectionCompletionId').val(completionId);
            $('#rejectionModal').show();
        });

        // Handle Rejection Form Submission
        $('#rejectionForm').on('submit', function(e) {
            e.preventDefault();
            var completionId = $('#rejectionCompletionId').val();
            var reason = $('#rejectionReason').val();
            var form = $(this);

            $.ajax({
                url: '../../api/reject_completion.php',
                type: 'POST',
                data: {
                    completion_id: completionId,
                    rejection_reason: reason
                },
                dataType: 'json',
                beforeSend: function() {
                    form.find('button[type="submit"]').prop('disabled', true).text('Submitting...');
                },
                success: function(response) {
                    if (response.success) {
                        closeModal('rejectionModal');
                        $('#completion-row-' + completionId).fadeOut(500, function() { 
                            $(this).remove();
                            if ($('.completions-grid .completion-card').length === 0) {
                                $('.card-body').html('<div class="no-completions"><i class="fas fa-check-double"></i><p>No pending course completion approvals at this time.</p></div>');
                            }
                        });
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An unexpected error occurred.');
                },
                complete: function() {
                    form.find('button[type="submit"]').prop('disabled', false).text('Submit Rejection');
                }
            });
        });
    });
    </script>
</body>
</html> 