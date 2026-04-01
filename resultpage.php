<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT course, score, total FROM results WHERE user_id=$user_id ORDER BY exam_date DESC LIMIT 1";
$result_query = $conn->query($sql);

$has_result = false;
$course = '';
$score = 0;
$total = 0;
$percentage = 0;
$status = '';
$status_class = '';

if ($result_query && $result_query->num_rows > 0) {
    $row = $result_query->fetch_assoc();
    $course = $row['course'];
    $score = $row['score'];
    $total = $row['total'];
    if($total > 0){
        $percentage = round(($score / $total) * 100, 2);
    }
    
    if($percentage >= 50) {
        $status = "✅ PASS";
        $status_class = "pass";
    } else {
        $status = "❌ FAIL";
        $status_class = "fail";
    }
    $has_result = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Results</title>
    <style>
        :root {
            --primary: #4f46e5;
            --bg-color: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --pass: #10b981;
            --fail: #ef4444;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 20px 20px;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .result-card {
            background: var(--white);
            width: 100%;
            max-width: 480px;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
            animation: popIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
            transform: scale(0.9);
        }

        @keyframes popIn {
            to { opacity: 1; transform: scale(1); }
        }

        .result-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: <?php echo ($status_class == 'pass') ? 'var(--pass)' : 'var(--fail)'; ?>;
        }

        .icon-container {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: <?php echo ($status_class == 'pass') ? '#d1fae5' : '#fee2e2'; ?>;
            color: <?php echo ($status_class == 'pass') ? 'var(--pass)' : 'var(--fail)'; ?>;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px;
            font-size: 40px;
            animation: scaleIn 0.5s 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
            transform: scale(0.5);
        }

        @keyframes scaleIn {
            to { opacity: 1; transform: scale(1); }
        }

        h1 {
            font-size: 2rem;
            color: var(--text-main);
            margin-bottom: 5px;
            font-weight: 800;
        }

        .course-badge {
            display: inline-block;
            background: #f1f5f9;
            color: var(--text-muted);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 30px;
        }

        .score-box {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
        }

        /* SVG Circle Progress */
        .circular-chart {
            display: block;
            margin: 0 auto 15px;
            max-width: 120px;
            max-height: 120px;
        }
        .circle-bg {
            fill: none;
            stroke: #e2e8f0;
            stroke-width: 3.8;
        }
        .circle {
            fill: none;
            stroke-width: 3.8;
            stroke-linecap: round;
            stroke: <?php echo ($status_class == 'pass') ? 'var(--pass)' : 'var(--fail)'; ?>;
            transition: stroke-dasharray 1s ease-out;
            animation: progress 1s ease-out forwards;
        }
        @keyframes progress {
            from { stroke-dasharray: 0, 100; }
            to { stroke-dasharray: <?php echo $percentage; ?>, 100; }
        }
        .percentage-text {
            fill: var(--text-main);
            font-family: 'Inter', sans-serif;
            font-size: 0.5rem;
            font-weight: 800;
            text-anchor: middle;
            transform: translateY(15%);
        }

        .fraction {
            font-size: 1.1rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            flex: 1;
            padding: 14px 20px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
        }

        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: var(--text-main);
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        /* Confetti */
        .confetti-container {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none;
            z-index: 10;
        }

    </style>
</head>
<body>

<?php if($has_result && $status_class == 'pass'): ?>
<div class="confetti-container" id="confetti"></div>
<script>
    function createConfetti() {
        const container = document.getElementById('confetti');
        if (!container) return;
        
        const colors = ['#4f46e5', '#10b981', '#f59e0b', '#ec4899', '#3b82f6'];
        
        for (let i = 0; i < 70; i++) {
            const confetti = document.createElement('div');
            confetti.style.position = 'absolute';
            confetti.style.width = Math.random() * 8 + 4 + 'px';
            confetti.style.height = Math.random() * 8 + 4 + 'px';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.left = Math.random() * 100 + 'vw';
            confetti.style.top = -10 + 'px';
            confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
            confetti.style.opacity = Math.random() + 0.5;
            confetti.style.transform = `rotate(${Math.random() * 360}deg)`;
            
            const animationDuration = Math.random() * 3 + 2;
            confetti.style.animation = `fall ${animationDuration}s linear forwards`;
            
            container.appendChild(confetti);
            
            setTimeout(() => {
                if(confetti.parentNode) confetti.remove();
            }, animationDuration * 1000);
        }
    }

    const style = document.createElement('style');
    style.innerHTML = `
        @keyframes fall {
            to {
                transform: translateY(100vh) rotate(720deg);
            }
        }
    `;
    document.head.appendChild(style);

    window.onload = () => setTimeout(createConfetti, 300);
</script>
<?php endif; ?>

<div class="result-card">
    <?php if($has_result): ?>
        <div class="icon-container">
            <?php echo ($status_class == 'pass') ? '🏆' : '💔'; ?>
        </div>
        
        <h1><?php echo ($status_class == 'pass') ? 'Congratulations!' : 'Keep Trying!'; ?></h1>
        <div class="course-badge"><?php echo htmlspecialchars($course); ?> Assessment completed</div>
        
        <div class="score-box">
            <svg viewBox="0 0 36 36" class="circular-chart">
                <path class="circle-bg"
                    d="M18 2.0845
                    a 15.9155 15.9155 0 0 1 0 31.831
                    a 15.9155 15.9155 0 0 1 0 -31.831"
                />
                <path class="circle"
                    stroke-dasharray="0, 100"
                    d="M18 2.0845
                    a 15.9155 15.9155 0 0 1 0 31.831
                    a 15.9155 15.9155 0 0 1 0 -31.831"
                />
                <text x="18" y="20.35" class="percentage-text"><?php echo $percentage; ?>%</text>
            </svg>
            <div class="fraction">You scored <strong><?php echo $score; ?></strong> out of <?php echo $total; ?></div>
        </div>
    <?php else: ?>
        <div class="icon-container" style="background: #f1f5f9; color: #64748b;">!</div>
        <h1>No Result Found</h1>
        <p style="color: var(--text-muted); margin-bottom: 30px;">You haven't completed any assessments yet.</p>
    <?php endif; ?>

    <div class="action-buttons">
        <a href="exam.php" class="btn btn-primary">New Exam</a>
        <a href="result.php?logout=true" class="btn btn-secondary">Logout</a>
    </div>
</div>

</body>
</html>
