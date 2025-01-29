<?php
session_start(); // Ensure session is started
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header("Location: admin.php");
    exit;
}

require_once './database/db_config.php'; // Include your database configuration
$userId = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    $message = "User not found.";
    $messageType = "error";
}
$stmt->close();



$message = "";
$messageType = "";

// Fetch user details if the ID is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $userId = $_GET['id'];
    
    // Fetch the user details from the database
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_assoc();
    
    if (!$users) {
        $message = "User not found.";
        $messageType = "error";
    }
    $stmt->close();
} else {
    $message = "Invalid user ID.";
    $messageType = "error";
}

// Handle form submission for updating user details
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update'])) {
    // Sanitize and validate user input
    $fullName = htmlspecialchars(trim($_POST['full_name']));
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $username = htmlspecialchars(trim($_POST['username']));
    $password = htmlspecialchars(trim($_POST['password']));
    $position = htmlspecialchars(trim($_POST['position']));
    $address = htmlspecialchars(trim($_POST['address']));

    if ($fullName && $email && $username && $position && $address) {
        // Hash the password if it's provided
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $updateQuery = "UPDATE users SET full_name = ?, email = ?, username = ?, password = ?, position = ?, address = ? WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("ssssssi", $fullName, $email, $username, $hashedPassword, $position, $address, $userId);
        } else {
            $updateQuery = "UPDATE users SET full_name = ?, email = ?, username = ?, position = ?, address = ? WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("sssssi", $fullName, $email, $username, $position, $address, $userId);
        }

        if ($stmt->execute()) {
            $message = "User updated successfully!";
            $messageType = "success";
            header("Location: settings.php"); // Redirect to the settings page after update
            exit();
        } else {
            $message = "Error updating user: " . $stmt->error;
            $messageType = "error";
        }
        $stmt->close();
    } else {
        $message = "Invalid input data. Please check your entries.";
        $messageType = "error";
    }
}

// Handle user deletion
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete'])) {
    $deleteQuery = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        $message = "User deleted successfully!";
        $messageType = "success";
        header("Location: settings.php"); // Redirect to the settings page after deletion
        exit();
    } else {
        $message = "Error deleting user: " . $stmt->error;
        $messageType = "error";
    }
    $stmt->close();
}

$conn->close();

// Display session message if any
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
    <title>Details</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="style.css">
    <!-- End layout styles -->
    <link rel="shortcut icon" href="assets/images/favicon.png" />
  </head>
  <body>
    <div class="container-scroller">
      <!-- partial:partials/_navbar.php -->
      <nav class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
        <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
          <a class="navbar-brand brand-logo" href="#"><img src="assets/images/logo.jpg" alt="logo" /></a>
          <a class="navbar-brand brand-logo-mini" href="#"><img src="assets/images/logo.jpg" alt="logo" /></a>
        </div>
        <div class="navbar-menu-wrapper d-flex align-items-stretch">
          <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
            <span class="mdi mdi-menu"></span>
          </button>
          
          <ul class="navbar-nav navbar-nav-right">
          
            <li class="nav-item nav-profile dropdown">
              <a class="nav-link dropdown-toggle" id="profileDropdown" href="#" data-toggle="dropdown" aria-expanded="false">
                <div class="nav-profile-img">
                  <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="image">
                </div>
                <div class="nav-profile-text">
                  <p class="mb-1 text-black"><?php echo htmlspecialchars($user['full_name']); ?></p>
                </div>
              </a>
              <div class="dropdown-menu navbar-dropdown dropdown-menu-right p-0 border-0 font-size-sm" aria-labelledby="profileDropdown" data-x-placement="bottom-end">
                <div class="p-3 text-center bg-primary">
                  <img class="img-avatar img-avatar48 img-avatar-thumb" src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="">
                </div>
                <div class="p-2">
                  <h5 class="dropdown-header text-uppercase pl-2 text-dark">User Options</h5>
                 
                  <a class="dropdown-item py-1 d-flex align-items-center justify-content-between" href="profile.php">
                    <span>Profile</span>
                    <span class="p-0">
                      <i class="mdi mdi-account-outline ml-1"></i>
                    </span>
                  </a>
                  <a class="dropdown-item py-1 d-flex align-items-center justify-content-between" href="settings.php">
                    <span>Settings</span>
                    <i class="mdi mdi-settings"></i>
                  </a>
                  <div role="separator" class="dropdown-divider"></div>
                  <h5 class="dropdown-header text-uppercase  pl-2 text-dark mt-2">Action</h5>
                 <a class="dropdown-item py-1 d-flex align-items-center justify-content-between" href="logout.php">
                    <span>Log Out</span>
                    <i class="mdi mdi-logout ml-1"></i>
                  </a>
                </div>
              </div>
            </li>
          
          </ul>
          <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
            <span class="mdi mdi-menu"></span>
          </button>
        </div>
      </nav>
      <!-- partial -->
      <div class="container-fluid page-body-wrapper">
        <!-- partial:partials/_sidebar.php -->
        <nav class="sidebar sidebar-offcanvas" id="sidebar">
          <ul class="nav">
            <li class="nav-item nav-category">Main</li>
            <li class="nav-item">
              <a class="nav-link" href="dashboard.php">
                <span class="icon-bg"><i class="mdi mdi-cube menu-icon"></i></span>
                <span class="menu-title">Dashboard</span>
              </a>
            </li>
 <li class="nav-item">
              <a class="nav-link"  href="all_products.php" >
                <span class="icon-bg"><i class="mdi mdi-crosshairs-gps menu-icon"></i></span>
                <span class="menu-title">All Products</span>
              </a>
            
            </li>
            <li class="nav-item">
              <a class="nav-link" href="sales.php">
                <span class="icon-bg"><i class="mdi mdi-chart-bar menu-icon"></i></span>
                <span class="menu-title">Sales</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="debts.php">
                <span class="icon-bg"><i class="mdi mdi-format-list-bulleted menu-icon"></i></span>
                <span class="menu-title">Debts</span>
              </a>
            </li>
           

            <li class="nav-item sidebar-user-actions">
              <div class="sidebar-user-menu">
                <a href="settings.php" class="nav-link"><i class="mdi mdi-settings menu-icon"></i>
                  <span class="menu-title">Account Settings</span>
                </a>
              </div>
            </li>
          
            <li class="nav-item sidebar-user-actions">
              <div class="sidebar-user-menu">
                <a href="logout.php" class="nav-link"><i class="mdi mdi-logout menu-icon"></i>
                  <span class="menu-title">Log Out</span></a>
              </div>
            </li>
          </ul>
        </nav>
        <!-- partial -->
        <div class="main-panel">
          <div class="content-wrapper">
          <?php
                    if (!empty($message)) {
                        echo '<div id="notificationBar" class="notification-bar notification-' . $messageType . '">';
                        echo $message;
                        echo '<span class="close-btn" onclick="closeNotification()">&times;</span>';
                        echo '</div>';
                    }
                ?>
            <div class="row" id="proBanner">
              <div class="col-12">
                <span >
                  <a href="https://github.com/BootstrapDash/ConnectPlusAdmin-Free-Bootstrap-Admin-Template" target="_blank" class="btn ml-auto download-button"></a>
                  <a href="http://www.bootstrapdash.com/demo/connect-plus/jquery/template/" target="_blank" class="btn purchase-button"></a>
                  <i id="bannerClose"></i>
                </span>
              </div>
            </div>
            <div class="d-xl-flex justify-content-between align-items-start">
              <h2 class="text-dark font-weight-bold mb-2">Details</h2>
             
            </div>
            <div class="row">
            <div class=" grid-margin stretch-card" style="width: 100%;">
                    <div class="card">
                      <div class="card-body">
                      <?php if (isset($users)): ?>
        <form method="POST" class="forms-sample">
        <div class="form-group">
            <label for="full_name">Full Name:</label>
            <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($users['full_name']); ?>" required><br>
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" class="form-control" name="email" value="<?php echo htmlspecialchars($users['email']); ?>" required><br>
         </div>
       <div class="form-group">
            <label for="position">Position:</label>
            <input type="text" id="position" class="form-control" name="position" value="<?php echo htmlspecialchars($users['position']); ?>" required><br>
       </div>
       <div class="form-group">
            <label for="address">Address:</label>
            <textarea id="address" class="form-control" name="address" required><?php echo htmlspecialchars($users['address']); ?></textarea><br>
       </div>
       <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($users['username']); ?>" required><br>
       </div>
       <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" class="form-control" name="password"><br>
       </div>
            <button type="submit" name="update" class="btn btn-primary">Update User</button>
            <button type="submit" name="delete" class="btn btn-danger">Delete User</button>
        </form>
    <?php else: ?>
        <p>No user details to display.</p>
    <?php endif; ?>

                      </div>
                      </div>
                      </div>
            </div>
          </div>
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.php -->
          <footer class="footer">
            <div class="footer-inner-wraper">
              <div class="d-sm-flex justify-content-center justify-content-sm-between">
                <span class="text-muted d-block text-center text-sm-left d-sm-inline-block">Copyright © EL-FANS GLOBAL SERVICES LIMITED 2025</span>
             </div>
            </div>
          </footer>
          <!-- partial -->
        </div>
        <!-- main-panel ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="assets/vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="assets/vendors/chart.js/Chart.min.js"></script>
    <script src="assets/vendors/jquery-circle-progress/js/circle-progress.min.js"></script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="assets/js/off-canvas.js"></script>
    <script src="assets/js/hoverable-collapse.js"></script>
    <script src="assets/js/misc.js"></script>
    <!-- endinject -->
    <!-- Custom js for this page -->
    <script src="assets/js/dashboard.js"></script>
    <!-- End custom js for this page -->
    <script src="script.js"></script>
  </body>
</html>