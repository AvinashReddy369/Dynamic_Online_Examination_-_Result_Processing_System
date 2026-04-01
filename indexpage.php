<?php
session_start();
// Suppress warnings in case setup_db hasn't been run yet gracefully
error_reporting(E_ALL & ~E_WARNING);
include 'db.php';

$error = '';
$success = '';
$show_register = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['register']) && isset($conn)) {
        $show_register = true;
        $name = $conn->real_escape_string($_POST['rname']);
        $email = $conn->real_escape_string($_POST['remail']);
        $password = password_hash($_POST['rpassword'], PASSWORD_DEFAULT);

        if(empty($name) || empty($email) || empty($_POST['rpassword'])){
            $error = "Please fill all fields for registration.";
        } else {
            $check = $conn->query("SELECT id FROM users WHERE email='$email'");
            if ($check && $check->num_rows > 0) {
                $error = "Email already registered!";
            } else {
                $sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$password')";
                if ($conn->query($sql) === TRUE) {
                    $success = "Registered Successfully! Now login.";
                    $show_register = false; 
                } else {
                    $error = "Error: database setup might be missing. Run setup_db.php";
                }
            }
        }
    } elseif (isset($_POST['login']) && isset($conn)) {
        $email = $conn->real_escape_string($_POST['email']);
        $password = $_POST['password'];

        if(empty($email) || empty($password)){
            $error = "Please fill all fields for login.";
        } else {
            $result = $conn->query("SELECT id, name, password FROM users WHERE email='$email'");
            if ($result && $result->num_rows == 1) {
                $row = $result->fetch_assoc();
                if (password_verify($password, $row['password'])) {
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['user_name'] = $row['name'];
                    header("Location: exam.php");
                    exit();
                } else {
                    $error = "Invalid Login (Incorrect Password).";
                }
            } else {
                $error = "Invalid Login (User not found) or DB not setup. Check setup_db.php";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Examination System</title>

    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --text-main: #1f2937;
            --text-light: #6b7280;
            --error: #ef4444;
            --success: #10b981;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        body {
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .main-container {
            display: flex;
            width: 100%;
            max-width: 850px;
            background: #ffffff;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0,0,0,0.2);
            min-height: 500px;
        }

        .left-panel {
            flex: 1;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.9), rgba(168, 85, 247, 0.9)), url('https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80') center/cover;
            color: white;
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .left-panel h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: 800;
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .left-panel p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }

        .right-panel {
            flex: 1;
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
            position: relative;
            background: #ffffff;
        }

        .tabs {
            display: flex;
            gap: 20px;
            margin-bottom: 40px;
            position: relative;
        }

        .tabs div {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-light);
            cursor: pointer;
            padding-bottom: 8px;
            transition: color 0.3s;
        }

        .tabs div:hover {
            color: var(--text-main);
        }

        .tabs .active {
            color: var(--primary);
        }

        .tabs .active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50%;
            height: 3px;
            background: var(--primary);
            border-radius: 3px;
            transition: transform 0.3s ease;
        }

        #registerTab.active ~ .indicator {
            transform: translateX(100%);
        }

        .form-container {
            position: relative;
            flex: 1;
            overflow: hidden;
            min-height: 400px;
        }

        .form {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.4s;
            opacity: 0;
            pointer-events: none;
            transform: translateX(20px);
        }

        .form.active {
            opacity: 1;
            pointer-events: auto;
            transform: translateX(0);
        }

        #registerForm {
            transform: translateX(-20px);
        }

        #registerForm.active {
            transform: translateX(0);
        }

        .input-group {
            position: relative;
            margin-bottom: 35px;
        }

        .input-group input {
            width: 100%;
            padding: 10px 0;
            font-size: 1.1rem;
            border: none;
            border-bottom: 2px solid #e2e8f0;
            outline: none;
            transition: all 0.3s;
            background: transparent;
            color: var(--text-main);
        }

        .input-group input:focus {
            border-bottom-color: var(--primary);
        }

        /* Underline animation effect */
        .input-group::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s ease;
        }

        .input-group:focus-within::after {
            width: 100%;
        }

        .input-group label {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            pointer-events: none;
            transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: transparent;
            font-size: 1.05rem;
        }

        .input-group input:focus ~ label,
        .input-group input:not(:placeholder-shown) ~ label {
            top: -12px;
            font-size: 0.85rem;
            color: var(--primary);
            font-weight: 700;
        }

        button.submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), #818cf8);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);
            margin-top: 20px;
        }

        button.submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 25px -5px rgba(79, 70, 229, 0.5);
            background: linear-gradient(135deg, var(--primary-hover), #6366f1);
        }

        button.submit-btn:active {
            transform: translateY(0);
            box-shadow: 0 5px 10px rgba(79, 70, 229, 0.3);
        }

        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
            display: flex;
            align-items: center;
        }

        @keyframes slideIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .alert.error { 
            background: #fef2f2; 
            color: var(--error);
            border-left: 4px solid var(--error);
        }
        
        .alert.success { 
            background: #ecfdf5; 
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            .left-panel {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>

<div class="main-container">

    <!-- LEFT PANEL -->
    <div class="left-panel">
        <h1>Unlock Your Potential</h1>
        <p>Join our secure and interactive online examination platform. Designed for a seamless and premium assessment experience.</p>
    </div>

    <!-- RIGHT PANEL -->
    <div class="right-panel">
        <div class="tabs">
            <div id="loginTab" onclick="switchTab('login')">Login</div>
            <div id="registerTab" onclick="switchTab('register')">Register</div>
            <div class="indicator" style="position:absolute; bottom:0; left:0; height:3px; background:var(--primary); transition:0.3s; border-radius:3px; width:45px;"></div>
        </div>

        <?php if($error): ?>
            <div class="alert error">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert success">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <!-- LOGIN -->
            <form id="loginForm" class="form <?php echo !$show_register ? 'active' : ''; ?>" method="POST" action="index.php">
                <div class="input-group">
                    <input type="email" name="email" id="login_email" placeholder=" " required>
                    <label for="login_email">Email Address</label>
                </div>
                <div class="input-group">
                    <input type="password" name="password" id="login_password" placeholder=" " required>
                    <label for="login_password">Password</label>
                </div>
                <button type="submit" name="login" class="submit-btn">Sign In</button>
            </form>

            <!-- REGISTER -->
            <form id="registerForm" class="form <?php echo $show_register ? 'active' : ''; ?>" method="POST" action="index.php">
                <div class="input-group">
                    <input type="text" name="rname" id="reg_name" placeholder=" " required>
                    <label for="reg_name">Full Name</label>
                </div>
                <div class="input-group">
                    <input type="email" name="remail" id="reg_email" placeholder=" " required>
                    <label for="reg_email">Email Address</label>
                </div>
                <div class="input-group">
                    <input type="password" name="rpassword" id="reg_password" placeholder=" " required>
                    <label for="reg_password">Password</label>
                </div>
                <button type="submit" name="register" class="submit-btn">Create Account</button>
            </form>
        </div>
    </div>
</div>

<script>
    function switchTab(type) {
        const loginForm = document.getElementById("loginForm");
        const registerForm = document.getElementById("registerForm");
        const loginTab = document.getElementById("loginTab");
        const registerTab = document.getElementById("registerTab");
        const indicator = document.querySelector(".indicator");

        if (type === 'login') {
            loginForm.classList.add("active");
            registerForm.classList.remove("active");
            
            loginTab.style.color = "var(--primary)";
            registerTab.style.color = "var(--text-light)";
            
            indicator.style.transform = "translateX(0)";
            indicator.style.width = loginTab.offsetWidth + "px";
        } else {
            registerForm.classList.add("active");
            loginForm.classList.remove("active");
            
            registerTab.style.color = "var(--primary)";
            loginTab.style.color = "var(--text-light)";
            
            indicator.style.transform = `translateX(${loginTab.offsetWidth + 20}px)`;
            indicator.style.width = registerTab.offsetWidth + "px";
        }
    }

    // Initialize tabs nicely
    window.addEventListener('DOMContentLoaded', () => {
        const initialType = '<?php echo $show_register ? "register" : "login"; ?>';
        // Need to wait slightly for fonts to render and elements width to compute
        setTimeout(() => {
            switchTab(initialType);
        }, 50);
    });
</script>

</body>
</html>
