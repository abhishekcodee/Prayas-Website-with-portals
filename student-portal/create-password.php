<?php
session_start();
require_once "db.php";

$message = "";

if(!isset($_SESSION['create_password_email'])){
    header("Location: register.php");
    exit;
}

$email = $_SESSION['create_password_email'];

if(isset($_POST['save'])){

    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if(empty($password) || empty($confirm)){

        $message = "All fields are required.";

    }elseif($password != $confirm){

        $message = "Passwords do not match.";

    }elseif(strlen($password) < 6){

        $message = "Password must be at least 6 characters.";

    }else{

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $update = $pdo->prepare("
    UPDATE users
    SET
        password=?,
        first_login=0,
        current_step=1,
        registration_completed=0
    WHERE email=?
");
        if($update->execute([$hash,$email])){

            $user = $pdo->prepare("
                SELECT id,full_name
                FROM users
                WHERE email=?
            ");

            $user->execute([$email]);

            $row = $user->fetch(PDO::FETCH_ASSOC);

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['full_name'];

            unset($_SESSION['create_password_email']);

            header("Location: student/resume.php");
            exit;

        }else{

            $message = "Something went wrong.";

        }

    }

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Password | PRAYAS Student Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>

<?php require_once __DIR__ . '/includes/nav_header.php'; ?>

<main class="portal-main-wrapper">
    <div class="container">
        <div class="card-icon-badge">
            <i class="fa-solid fa-key"></i>
        </div>

        <h2>Create Password</h2>
        <p class="card-subtitle">Set up a secure password for your candidate login account</p>

        <?php if(!empty($message)): ?>
            <div class="alert-message">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="form-group-icon password-box">
                <i class="fa-solid fa-lock input-icon"></i>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="New Password (min. 6 characters)"
                    required
                    autofocus>
                <span class="toggle-password" onclick="togglePassword('password',this)" title="Toggle Password Visibility">
                    <i class="fa-solid fa-eye"></i>
                </span>
            </div>

            <div class="form-group-icon password-box">
                <i class="fa-solid fa-lock input-icon"></i>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Confirm New Password"
                    required>
                <span class="toggle-password" onclick="togglePassword('confirm_password',this)" title="Toggle Password Visibility">
                    <i class="fa-solid fa-eye"></i>
                </span>
            </div>

            <button type="submit" name="save" class="btn-portal-primary">
                <span>Save Password &amp; Continue</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>
    </div>
</main>

<script>

function togglePassword(id,element){

    let input=document.getElementById(id);

    let icon=element.querySelector("i");

    if(input.type==="password"){

        input.type="text";

        icon.classList.remove("fa-eye");

        icon.classList.add("fa-eye-slash");

    }else{

        input.type="password";

        icon.classList.remove("fa-eye-slash");

        icon.classList.add("fa-eye");

    }

}

</script>

</body>

</html>