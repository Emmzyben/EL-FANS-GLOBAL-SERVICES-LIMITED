<?php
session_start(); // Start the session at the very top of the file
require_once './database/db_config.php';

// Initialize message variables
$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form inputs
    $emailOrUsername = trim($_POST['email-username']);
    $password = trim($_POST['password']);

    // Validate input fields
    if (empty($emailOrUsername) || empty($password)) {
        $message = "Email/Username and Password are required.";
        $messageType = "error";
    } else {
        // Check for the user in the database
        $query = "SELECT * FROM users WHERE email = ? OR username = ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("ss", $emailOrUsername, $emailOrUsername);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Store user data in session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['fullname'] = $user['fullname'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];

                    // Redirect to the previous page or default to the dashboard
                    if (isset($_SESSION['redirect_to'])) {
                        $redirect_to = $_SESSION['redirect_to'];
                        unset($_SESSION['redirect_to']); // Clear the redirect path
                        header("Location: $redirect_to");
                    } else {
                        header("Location: dashboard.php"); // Default page
                    }
                    exit;
                } else {
                    $message = "Invalid password. Please try again.";
                    $messageType = "error";
                }
            } else {
                $message = "No user found with the provided email/username.";
                $messageType = "error";
            }

            // Close the statement
            $stmt->close();
        } else {
            $message = "Database query error. Please try again later.";
            $messageType = "error";
        }
    }

    // Store message in the session for display
    $_SESSION['message'] = $message;
    $_SESSION['messageType'] = $messageType;

    // Redirect back to the login page
    header("Location: admin.php");
    exit;
}

// Retrieve and unset messages from the session (if any)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'];
    unset($_SESSION['message']);
    unset($_SESSION['messageType']);
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>EL-FANS GLOBAL SERVICES LIMITED</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="./assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="./assets/vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="./assets/vendors/css/vendor.bundle.base.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="stylesheet" href="./assets/css/style.css">
    <!-- End layout styles -->
    <link rel="shortcut icon" href="./assets/images/favicon.png" />
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <div class="container-scroller">
      <div class="container-fluid page-body-wrapper full-page-wrapper">
        <div class="content-wrapper d-flex align-items-center auth">
          <div class="row flex-grow">
            <div class="col-lg-4 mx-auto">
              <div class="auth-form-light text-center p-5">
                <div class="brand-logo">
                  <img src="./assets/images/logo.jpg" style="width:200px">
                </div>
                <h4>Admin Login</h4>
                <h6 class="font-weight-light">Log in to continue.</h6>
               
                <form class="pt-3" action="" method="post">
                  <div class="form-group">
                    <input type="text" name="email-username" class="form-control form-control-lg" id="exampleInputEmail1" placeholder="Username or Email">
                  </div>
                  <div class="form-group">
                    <input type="password" class="form-control form-control-lg" 
                     name="password"
                      placeholder="Password"
                    placeholder="Password">
                  </div>
                  <div class="mt-3">
                  <?php
                    if (!empty($message)) {
                        echo '<div id="notificationBar" class="notification-bar notification-' . $messageType . '">';
                        echo $message;
                        echo '<span class="close-btn" onclick="closeNotification()">&times;</span>';
                        echo '</div>';
                    }
                ?>
                    <button type="submit" class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn">SIGN IN</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        <!-- content-wrapper ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="./assets/vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="./assets/js/off-canvas.js"></script>
    <script src="./assets/js/hoverable-collapse.js"></script>
    <script src="./assets/js/misc.js"></script>
    <!-- endinject -->
     <script src="script.js"></script>
  </body>
</html>