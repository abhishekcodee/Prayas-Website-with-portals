<?php
session_start();

// Redirect all admin login requests to the unified single portal login page
header("Location: ../login.php");
exit;
?>

/*
|--------------------------------------------------------------------------
| Admin Login
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $error = "Please enter email and password.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Fetch Admin
            |--------------------------------------------------------------------------
            |
            | Expected table:
            |
            | admin
            | --------------------------------
            | id
            | name
            | email
            | password
            |
            */

           $stmt = $pdo->prepare("
    SELECT
        id,
        name,
        email,
        password,
        role,
        status
    FROM admin
    WHERE email = ?
    LIMIT 1
");

$stmt->execute([$email]);

$admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin) {

                /*
                |--------------------------------------------------------------------------
                | Password Verification
                |--------------------------------------------------------------------------
                |
                | Supports:
                | 1. password_hash()
                | 2. Temporary plain-text passwords during local development
                |
                */

                $passwordValid = false;

                if (
                    isset($admin["password"]) &&
                    password_verify($password, $admin["password"])
                ) {

                    $passwordValid = true;

                } elseif (
                    isset($admin["password"]) &&
                    hash_equals(
                        (string)$admin["password"],
                        (string)$password
                    )
                ) {

                    // Remove plain-text support once passwords are hashed.
                    $passwordValid = true;
                }

                if ($passwordValid) {

                    session_regenerate_id(true);

                    $_SESSION["admin_id"] = $admin["id"];

                    $_SESSION["admin_name"] =
                        $admin["name"] ?? "Administrator";

                    $_SESSION["admin_email"] =
                        $admin["email"] ?? $email;

                    header("Location: dashboard.php");
                    exit;

                } else {

                    $error = "Invalid email or password.";
                }

            } else {

                $error = "Invalid email or password.";
            }

        } catch (PDOException $e) {

            /*
             * Local development message.
             * In production don't expose database errors.
             */

            $error = "Database error: " . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Admin Login | PRAYAS</title>

<style>

* {

    margin: 0;
    padding: 0;
    box-sizing: border-box;

}

body {

    min-height: 100vh;

    font-family:
        Inter,
        "Segoe UI",
        Arial,
        sans-serif;

    background:

        radial-gradient(
            circle at top left,
            rgba(239,107,107,.15),
            transparent 32%
        ),

        radial-gradient(
            circle at bottom right,
            rgba(37,99,235,.12),
            transparent 35%
        ),

        #f8fafc;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 30px;

    color: #0f172a;

}

/* =========================================================
   LOGIN WRAPPER
========================================================= */

.login-wrapper {

    width: 100%;

    max-width: 1080px;

    min-height: 620px;

    display: grid;

    grid-template-columns:
        1.05fr
        .95fr;

    background: #ffffff;

    border-radius: 28px;

    overflow: hidden;

    box-shadow:

        0 30px 70px
        rgba(15,23,42,.13);

    border:

        1px solid
        rgba(148,163,184,.22);

}

/* =========================================================
   LEFT BRAND PANEL
========================================================= */

.brand-panel {

    position: relative;

    padding: 65px 60px;

    background:

        linear-gradient(
            145deg,
            #0f172a 0%,
            #172554 55%,
            #1e3a8a 100%
        );

    color: white;

    display: flex;

    flex-direction: column;

    justify-content: space-between;

    overflow: hidden;

}

.brand-panel::before {

    content: "";

    position: absolute;

    width: 350px;
    height: 350px;

    border-radius: 50%;

    background:
        rgba(239,107,107,.16);

    top: -130px;
    right: -130px;

}

.brand-panel::after {

    content: "";

    position: absolute;

    width: 260px;
    height: 260px;

    border:

        1px solid
        rgba(255,255,255,.10);

    transform: rotate(45deg);

    bottom: -120px;
    left: -100px;

}

.brand-content {

    position: relative;
    z-index: 2;

}

.logo-box {

    display: inline-flex;

    align-items: center;

    gap: 13px;

    margin-bottom: 70px;

}

.logo-mark {

    width: 48px;
    height: 48px;

    border-radius: 14px;

    background:

        linear-gradient(
            135deg,
            #ef6b6b,
            #e25555
        );

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 24px;

    font-weight: 800;

    transform: rotate(45deg);

    box-shadow:

        0 10px 30px
        rgba(239,107,107,.30);

}

.logo-mark span {

    transform: rotate(-45deg);

}

.brand-name {

    font-size: 25px;

    font-weight: 800;

    letter-spacing: .4px;

}

.brand-sub {

    font-size: 11px;

    color: #cbd5e1;

    margin-top: 2px;

    letter-spacing: 1px;

    text-transform: uppercase;

}

.brand-panel h1 {

    font-size: 43px;

    line-height: 1.15;

    margin-bottom: 22px;

    max-width: 430px;

}

.brand-panel h1 span {

    color: #ef6b6b;

}

.brand-description {

    color: #cbd5e1;

    font-size: 16px;

    line-height: 1.8;

    max-width: 430px;

}

.brand-footer {

    position: relative;

    z-index: 2;

    color: #94a3b8;

    font-size: 13px;

}

/* =========================================================
   LOGIN PANEL
========================================================= */

.login-panel {

    padding: 65px 65px;

    display: flex;

    align-items: center;

}

.login-box {

    width: 100%;

}

.login-heading {

    margin-bottom: 38px;

}

.login-heading .tag {

    display: inline-block;

    padding: 7px 13px;

    background: #fff1f2;

    color: #e25555;

    border-radius: 30px;

    font-size: 12px;

    font-weight: 700;

    letter-spacing: .6px;

    text-transform: uppercase;

    margin-bottom: 18px;

}

.login-heading h2 {

    font-size: 34px;

    color: #0f172a;

    margin-bottom: 10px;

}

.login-heading p {

    color: #64748b;

    line-height: 1.6;

}

/* =========================================================
   FORM
========================================================= */

.form-group {

    margin-bottom: 22px;

}

.form-group label {

    display: block;

    font-size: 13px;

    font-weight: 700;

    color: #334155;

    margin-bottom: 9px;

}

.input-wrapper {

    position: relative;

}

.input-icon {

    position: absolute;

    left: 16px;

    top: 50%;

    transform:
        translateY(-50%);

    color: #94a3b8;

    font-size: 18px;

}

.form-control {

    width: 100%;

    height: 54px;

    border:

        1px solid
        #dbe3ee;

    border-radius: 12px;

    padding:

        0 45px
        0 48px;

    font-size: 15px;

    color: #0f172a;

    background: #f8fafc;

    outline: none;

    transition: .2s ease;

}

.form-control:focus {

    background: white;

    border-color: #ef6b6b;

    box-shadow:

        0 0 0 4px
        rgba(239,107,107,.10);

}

.password-toggle {

    position: absolute;

    right: 15px;

    top: 50%;

    transform:
        translateY(-50%);

    border: none;

    background: transparent;

    cursor: pointer;

    color: #64748b;

    font-size: 16px;

}

/* =========================================================
   ERROR
========================================================= */

.alert {

    padding: 13px 15px;

    border-radius: 10px;

    margin-bottom: 22px;

    font-size: 14px;

}

.alert-error {

    background: #fff1f2;

    border:

        1px solid
        #fecdd3;

    color: #be123c;

}

/* =========================================================
   BUTTON
========================================================= */

.login-btn {

    width: 100%;

    height: 55px;

    border: none;

    border-radius: 12px;

    background:

        linear-gradient(
            135deg,
            #ef6b6b,
            #e25555
        );

    color: white;

    font-size: 15px;

    font-weight: 700;

    cursor: pointer;

    margin-top: 7px;

    transition: .25s;

    box-shadow:

        0 12px 25px
        rgba(239,107,107,.25);

}

.login-btn:hover {

    transform:
        translateY(-2px);

    box-shadow:

        0 16px 32px
        rgba(239,107,107,.32);

}

.security-note {

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    color: #94a3b8;

    font-size: 12px;

    margin-top: 24px;

}

.logo-box{
    margin-bottom:70px;
}

.brand-logo{
    width:150px;
    height:auto;
    display:block;
    object-fit:contain;
}
/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:850px) {

    body {

        padding: 15px;

    }

    .login-wrapper {

        grid-template-columns: 1fr;

    }

    .brand-panel {

        padding: 40px;

        min-height: 330px;

    }

    .logo-box {

        margin-bottom: 45px;

    }

    .brand-panel h1 {

        font-size: 34px;

    }

    .brand-footer {

        margin-top: 35px;

    }

    .login-panel {

        padding: 45px 35px;

    }

}

@media(max-width:480px) {

    .brand-panel {

        padding: 30px 25px;

    }

    .brand-panel h1 {

        font-size: 29px;

    }

    .login-panel {

        padding: 38px 24px;

    }

    .login-heading h2 {

        font-size: 28px;

    }

}

</style>

</head>

<body>

<div class="login-wrapper">

    <!-- ================================================
         LEFT BRAND PANEL
    ================================================= -->

    <section class="brand-panel">

        <div class="brand-content">

       <div class="logo-box">
    <img
        src="http://localhost/wordpress/wp-content/uploads/2026/02/Prayas-Logo-final.png"
        alt="PRAYAS Logo"
        class="brand-logo"
    >
</div>

            <h1>

                Recruitment
                <span>Admin Portal</span>

            </h1>

            <p class="brand-description">

                Manage students, assessments, and recruitment in one secure platform.

            </p>

        </div>

        <div class="brand-footer">

            Student Recruitment Portal
            &nbsp;•&nbsp;
            Authorized Access Only

        </div>

    </section>


    <!-- ================================================
         LOGIN FORM
    ================================================= -->

    <section class="login-panel">

        <div class="login-box">

            <div class="login-heading">

                <div class="tag">
                    Admin Access
                </div>

                <h2>
                    Welcome Back
                </h2>

                <p>
                    Enter your administrator credentials
                    to continue to the dashboard.
                </p>

            </div>

            <?php if ($error !== ""): ?>

                <div class="alert alert-error">

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                autocomplete="off"
            >

                <div class="form-group">

                    <label>
                        Email Address
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">
                            ✉
                        </span>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            placeholder="admin@example.com"
                            autocomplete="off"
                            required
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label>
                        Password
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">
                            🔒
                        </span>

                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control"
                            placeholder="Enter your password"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword()"
                            aria-label="Show or hide password"
                        >

                            👁

                        </button>

                    </div>

                </div>


                <button
                    type="submit"
                    class="login-btn"
                >

                    Sign In to Admin Portal

                </button>

            </form>


            <div class="security-note">

                🔐 Secure administrator authentication

            </div>

        </div>

    </section>

</div>


<script>

function togglePassword() {

    const password =
        document.getElementById("password");

    password.type =
        password.type === "password"
        ? "text"
        : "password";

}

</script>

</body>

</html>
