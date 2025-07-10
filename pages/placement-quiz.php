<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Ensure user is logged in and hasn't taken the quiz
$stmt = $pdo->prepare("SELECT * FROM placement_quiz_attempts WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
if ($stmt->fetch()) {
    header("Location: dashboard.php");
    exit();
}

// Number of random questions you want
$num_questions = 3;

$stmt = $pdo->prepare("SELECT * FROM questions ORDER BY RAND() LIMIT ?");
$stmt->bindValue(1, $num_questions, PDO::PARAM_INT);
$stmt->execute();
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

function calculateScore($answers, $questions) {
    $score = 0;
    $maxPossibleScore = count($questions);

    foreach ($questions as $index => $question) {
        $answer_key = 'q' . $index;
        if (isset($answers[$answer_key]) && $answers[$answer_key] == $question['correct_option']) {
            $score++;
        }
    }

    $percentage = $maxPossibleScore > 0 ? ($score / $maxPossibleScore) * 100 : 0;
    return round($percentage);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Calculate score based on answers
    $score = calculateScore($_POST, $questions);
    
    // Save quiz attempt
    $stmt = $pdo->prepare("INSERT INTO placement_quiz_attempts (user_id, score) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $score]);
    
    // Redirect with success message
    $_SESSION['quiz_completed'] = true;
    header("Location: dashboard.php");
    exit();
}

// Remove the duplicate function and array from here
?>

<!DOCTYPE html>
<html>
<head>
    <title>Placement Quiz - CertifyIQ</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .quiz-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .question {
            margin-bottom: 20px;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .options label {
            display: block;
            margin: 10px 0;
            cursor: pointer;
        }
        .submit-btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="quiz-container">
        <h1>Placement Quiz</h1>
        <p>Complete this quiz to unlock course discounts!</p>
        
        <form method="POST">
            <?php foreach ($questions as $index => $q): ?>
            <div class="question">
                <h3><?php echo htmlspecialchars($q['question_text']); ?></h3>
                
                <div class="options">
                    <?php
                    $options = [
                        'A' => $q['option_a'],
                        'B' => $q['option_b'],
                        'C' => $q['option_c'],
                        'D' => $q['option_d'],
                    ];
                    foreach ($options as $key => $option_text):
                    ?>
                        <label>
                            <input type="radio" name="q<?php echo $index; ?>" value="<?php echo $key; ?>" required>
                            <?php echo htmlspecialchars($option_text); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <button type="submit" class="submit-btn">Submit Quiz</button>
        </form>
    </div>
</body>
</html>