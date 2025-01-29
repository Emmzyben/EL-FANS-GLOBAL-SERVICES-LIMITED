<?php
session_start(); // Ensure session is started

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: admin.php"); 
    exit;
}


require_once 'database/db_config.php'; 
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

$users = []; 

try {
    $query = "SELECT * FROM users" ;
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception("Error fetching users: " . $conn->error); // Error if the query fails
    }

    // Store fetched data in the $users array
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
} catch (Exception $e) {
    // Handle any errors by storing the error message
    $message = $e->getMessage();
    $messageType = "error"; // Type of message (error)
}

// Handle any session messages (success or error)
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
    <title>Account Settings</title>
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
    <!-- End layout styles -->
    <link rel="shortcut icon" href="assets/images/favicon.png" />
    <link rel="stylesheet" href="style.css">
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
              <h2 class="text-dark font-weight-bold mb-2">User Account Settings</h2>
             
            </div>
            <div class="row">
                <div class="col-12 grid-margin stretch-card">
                    <div class="card">
                      <div class="card-body">
                        <h4 class="card-title">Add new User</h4>
                        <form class="forms-sample" action="createUser.php" method="post" enctype="multipart/form-data">
  <div class="form-group">
    <label for="exampleInputName1">Name</label>
    <input type="text" class="form-control" id="exampleInputName1" name="fullName" placeholder="Name" required>
  </div>
  <div class="form-group">
    <label for="exampleInputEmail3">Email address</label>
    <input type="email" class="form-control" id="exampleInputEmail3" name="email" placeholder="Email" required>
  </div>
  
  <div class="form-group">
    <label for="exampleSelectGender">Gender</label>
    <select class="form-control" id="exampleSelectGender" name="gender" required>
      <option value="male">Male</option>
      <option value="female">Female</option>
    </select>
  </div>
  <div class="form-group">
    <label for="exampleTextarea1">Address</label>
    <textarea class="form-control" id="exampleTextarea1" name="address" rows="4" required></textarea>
  </div>
  <div class="form-group">
    <label for="exampleInputCity1">Position</label>
    <input type="text" class="form-control" id="exampleInputCity1" name="position" placeholder="Company role" required>
  </div>
  <div class="form-group">
    <label>Upload Picture</label>
    <div class="input-group col-xs-12">
      <input type="file" class="form-control file-upload-info" name="profilePicture" placeholder="Upload Image" accept="image/*" required>
      <span class="input-group-append">
        <button class="file-upload-browse btn btn-primary" type="button">Upload</button>
      </span>
    </div>
  </div>
  


  <div class="form-group">
    <label for="exampleInputEmail3">Username</label>
    <input type="text" class="form-control" id="exampleInputEmail3" name="username" placeholder="Username" required>
  </div>
  <div class="form-group">
    <label for="exampleInputPassword4">Password</label>
    <input type="password" class="form-control" id="exampleInputPassword4" name="password" placeholder="Password" required>
  </div>
  <button type="submit" class="btn btn-primary mr-2">Submit</button>
  <button class="btn btn-light">Cancel</button>
</form>

                      </div>
                    </div>
                  </div>
                  <div class="col-lg-12 stretch-card">
                    <div class="card">
                      <div class="card-body">
                        <h4 class="card-title">All users</h4>
                       </p>
                       <table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Profile Picture</th>
            <th>Name</th>
            <th>Email</th>
            <th>Username</th>
            <th>Position (Role)</th>
            <th>Details</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $index => $user): ?>
            <tr class="table-<?php echo ($index % 2 == 0) ? 'info' : 'warning'; ?>">
                <td><?php echo htmlspecialchars($user['id']); ?></td>
                <td><img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" style="width: 50px; height: 50px; border-radius: 50%"></td>
                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['position']); ?></td>
                <td>
                                <a href="user_details.php?id=<?= htmlspecialchars($user['id']); ?>">
                                    <button class="btn btn-secondary">View details</button>
                                </a>
                            </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
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