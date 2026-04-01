<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';

// Handle Exam Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_exam'])) {
    $course = isset($_POST['course']) ? $conn->real_escape_string($_POST['course']) : '';
    
    // Fetch answers from DB
    $answers_query = $conn->query("SELECT id, ans FROM questions WHERE course='$course'");
    $score = 0;
    $total = 0;
    
    if($answers_query && $answers_query->num_rows > 0) {
        $total = $answers_query->num_rows;
        while($row = $answers_query->fetch_assoc()) {
            $qid = $row['id'];
            if(isset($_POST['q'.$qid]) && $_POST['q'.$qid] == $row['ans']) {
                $score++;
            }
        }
        
        // Save Result
        $stmt = $conn->prepare("INSERT INTO results (user_id, course, score, total) VALUES (?, ?, ?, ?)");
        if($stmt) {
            $stmt->bind_param("isii", $user_id, $course, $score, $total);
            if($stmt->execute()){
                header("Location: result.php");
                exit();
            } else {
                $error = "Error saving result.";
            }
        } else {
            $error = "Database prepare statement failed.";
        }
    } else {
        $error = "Invalid exam submission.";
    }
}

// Check which course the user selected
$selected_course = isset($_GET['course']) ? $_GET['course'] : null;
$questions = [];

if ($selected_course) {
    $course_esc = $conn->real_escape_string($selected_course);
    $q_res = $conn->query("SELECT * FROM questions WHERE course='$course_esc'");
    if($q_res && $q_res->num_rows > 0) {
        while($row = $q_res->fetch_assoc()) {
            $questions[] = $row;
        }
    } else {
        $error = "No questions found for this subject.";
    }
}

// Available Subjects
$available_subjects = ['DBMS', 'DS', 'CN', 'OS'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Examination</title>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #e0e7ff;
            --bg-color: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --success: #10b981;
            --white: #ffffff;
            --border-color: #e2e8f0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .navbar h2 {
            font-size: 1.2rem;
            color: var(--primary);
            font-weight: 700;
            margin: 0;
        }

        .user-badge {
            background: var(--primary-light);
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Subject Selection */
        .page-title {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 800;
        }

        .page-subtitle {
            text-align: center;
            color: var(--text-muted);
            margin-bottom: 40px;
        }

        .subject-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }

        .subject-card {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 30px 20px;
            text-align: center;
            text-decoration: none;
            color: var(--text-main);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .subject-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary);
            transform: scaleX(0);
            transition: transform 0.3s ease;
            transform-origin: left;
        }

        .subject-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 25px -5px rgba(0, 0, 0, 0.1);
            border-color: transparent;
        }

        .subject-card:hover::before {
            transform: scaleX(1);
        }

        .subject-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            display: inline-block;
        }

        /* Exam Form */
        .exam-header {
            background: var(--white);
            padding: 20px 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 5px solid var(--primary);
        }

        .question-block {
            background: var(--white);
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            animation: fadeIn 0.5s ease-out backwards;
        }

        /* Staggered animation generated directly in style */
        .question-block:nth-child(1) { animation-delay: 0.1s; }
        .question-block:nth-child(2) { animation-delay: 0.2s; }
        .question-block:nth-child(3) { animation-delay: 0.3s; }
        .question-block:nth-child(4) { animation-delay: 0.4s; }
        .question-block:nth-child(5) { animation-delay: 0.5s; }
        .question-block:nth-child(6) { animation-delay: 0.6s; }
        .question-block:nth-child(k) { animation-delay: 0.1s; } /* Fallback */

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .question-text {
            font-size: 1.15rem;
            font-weight: 600;
            margin-bottom: 20px;
            line-height: 1.5;
            color: var(--text-main);
        }

        .question-number {
            color: var(--primary);
            margin-right: 5px;
        }

        .options {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .options label {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            background: var(--white);
        }

        .options input[type="radio"] {
            display: none;
        }

        .radio-custom {
            width: 20px;
            height: 20px;
            border: 2px solid #cbd5e1;
            border-radius: 50%;
            margin-right: 15px;
            position: relative;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .options input[type="radio"]:checked + .radio-custom {
            border-color: var(--primary);
        }

        .options input[type="radio"]:checked + .radio-custom::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 10px;
            height: 10px;
            background: var(--primary);
            border-radius: 50%;
        }

        .options input[type="radio"]:checked ~ .option-text {
            color: var(--primary);
            font-weight: 600;
        }

        .options label:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
        }

        .options label:has(input[type="radio"]:checked) {
            border-color: var(--primary);
            background: var(--primary-light);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.1);
        }

        .btn-submit {
            display: block;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), #818cf8);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);
            margin-top: 20px;
            margin-bottom: 40px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 20px -3px rgba(79, 70, 229, 0.5);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .alert-error {
            background: #fef2f2;
            color: #ef4444;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #ef4444;
            margin-bottom: 20px;
            text-align: center;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
            margin-bottom: 20px;
        }

        .back-link:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>

<div class="navbar">
    <h2>Exam Portal</h2>
    <div class="user-badge">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        <?php echo htmlspecialchars($_SESSION['user_name']); ?>
    </div>
</div>

<div class="container">
    <?php if($error) echo "<div class='alert-error'>$error</div>"; ?>

    <?php if(!$selected_course): ?>
        <h1 class="page-title">Choose Your Assessment</h1>
        <p class="page-subtitle">Select a subject below to begin your examination. You will be graded automatically.</p>
        
        <div class="subject-grid">
            <?php 
            $icons = ['DBMS' => '🗄️', 'DS' => '🧩', 'CN' => '🌐', 'OS' => '💻', 'Java' => '☕'];
            foreach($available_subjects as $sub): 
                $icon = isset($icons[$sub]) ? $icons[$sub] : '📚';
            ?>
                <a href="exam.php?course=<?php echo urlencode($sub); ?>" class="subject-card">
                    <span class="subject-icon"><?php echo $icon; ?></span>
                    <h3><?php echo htmlspecialchars($sub); ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        
        <a href="exam.php" class="back-link">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 5px;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Choose a different subject
        </a>

        <div class="exam-header">
            <div>
                <h2 style="margin: 0; color: var(--text-main); font-size: 1.5rem;"><?php echo htmlspecialchars($selected_course); ?> Examination</h2>
                <div style="color: var(--text-muted); font-size: 0.9rem; margin-top: 5px;">Complete all questions to submit.</div>
            </div>
            <div style="background: #e0e7ff; color: #4f46e5; padding: 10px 15px; border-radius: 10px; font-weight: bold;">
                <?php echo count($questions); ?> Questions
            </div>
        </div>

        <form method="POST" action="exam.php">
            <input type="hidden" name="course" value="<?php echo htmlspecialchars($selected_course); ?>">
            
            <?php 
            $qno = 1;
            foreach($questions as $index => $q): 
                // Add a small inline style for animation delay to ensure staggered loading even with large sets
                $delay = min($index * 0.1, 2) . 's';
            ?>
                <div class="question-block" style="animation-delay: <?php echo $delay; ?>;">
                    <div class="question-text"><span class="question-number">Q<?php echo $qno++; ?>.</span> <?php echo htmlspecialchars($q['question']); ?></div>
                    <ul class="options">
                        <li>
                            <label>
                                <input type="radio" name="q<?php echo $q['id']; ?>" value="1" required> 
                                <span class="radio-custom"></span>
                                <span class="option-text"><?php echo htmlspecialchars($q['opt1']); ?></span>
                            </label>
                        </li>
                        <li>
                            <label>
                                <input type="radio" name="q<?php echo $q['id']; ?>" value="2" required> 
                                <span class="radio-custom"></span>
                                <span class="option-text"><?php echo htmlspecialchars($q['opt2']); ?></span>
                            </label>
                        </li>
                        <li>
                            <label>
                                <input type="radio" name="q<?php echo $q['id']; ?>" value="3" required> 
                                <span class="radio-custom"></span>
                                <span class="option-text"><?php echo htmlspecialchars($q['opt3']); ?></span>
                            </label>
                        </li>
                        <li>
                            <label>
                                <input type="radio" name="q<?php echo $q['id']; ?>" value="4" required> 
                                <span class="radio-custom"></span>
                                <span class="option-text"><?php echo htmlspecialchars($q['opt4']); ?></span>
                            </label>
                        </li>
                    </ul>
                </div>
            <?php endforeach; ?>
            
            <button type="submit" name="submit_exam" class="btn-submit">Submit Assessment</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
