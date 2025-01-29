<?php
session_start(); 

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: admin.php");
  exit;
}

require_once './database/db_config.php';
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

// Fetch sale details if the ID is provided
if (isset($_GET['sale_id']) && is_numeric($_GET['sale_id'])) {
    $saleId = $_GET['sale_id'];

    // Fetch the sale details from the database
    $query = "SELECT * FROM sales WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $saleId);
    $stmt->execute();
    $result = $stmt->get_result();
    $sale = $result->fetch_assoc();

    if (!$sale) {
        $message = "Sale not found.";
        $messageType = "error";
    }
    $stmt->close();
} else {
    $message = "Invalid sale ID.";
    $messageType = "error";
}

// Handle form submission for updating sale
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update'])) {
  $quantity = filter_var(trim($_POST['quantity']), FILTER_VALIDATE_INT);
  $amount_paid = filter_var(trim($_POST['amount_paid']), FILTER_VALIDATE_FLOAT);
  $amount_owed = filter_var(trim($_POST['amount_owed']), FILTER_VALIDATE_FLOAT);
  $customer_name = htmlspecialchars(trim($_POST['customer_name']));
  $customer_phone = htmlspecialchars(trim($_POST['customer_phone']));
  $customer_address = htmlspecialchars(trim($_POST['customer_address']));

  // Additional checks for numeric values (e.g., non-negative values)
  if ($quantity === false || $quantity <= 0) {
    $message = "Please enter a valid quantity.";
    $messageType = "error";
  } elseif ($amount_paid === false || $amount_paid < 0) {
    $message = "Please enter a valid amount paid.";
    $messageType = "error";
  } elseif ($amount_owed === false || $amount_owed < 0) {
    $message = "Please enter a valid amount owed.";
    $messageType = "error";
  } elseif (!$customer_name || !$customer_phone || !$customer_address) {
    $message = "All fields must be filled.";
    $messageType = "error";
  } else {
    $updateQuery = "UPDATE sales SET quantity = ?, amount_paid = ?, amount_owed = ?, customer_name = ?, customer_phone = ?, customer_address = ? WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("iddsssi", $quantity, $amount_paid, $amount_owed, $customer_name, $customer_phone, $customer_address, $saleId);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Sale updated successfully!";
        $_SESSION['messageType'] = "success";
        header("Location: sales.php"); // Redirect to the sales list page after update
        exit();
    } else {
        $message = "Error updating sale: " . $stmt->error;
        $messageType = "error";
    }
    $stmt->close();
  }
}

// Handle sale deletion
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete'])) {
  // Check if the sale is complete (no amount owed)
  if ($sale['amount_owed'] == 0) {
      // Proceed with deletion if the sale is complete
      $deleteQuery = "DELETE FROM sales WHERE id = ?";
      $stmt = $conn->prepare($deleteQuery);
      $stmt->bind_param("i", $saleId);

      if ($stmt->execute()) {
          $_SESSION['message'] = "Sale deleted successfully!";
          $_SESSION['messageType'] = "success";
          header("Location: sales.php"); // Redirect after deletion
          exit();
      } else {
          $message = "Error deleting sale: " . $stmt->error;
          $messageType = "error";
      }
      $stmt->close();
  } else {
      // Sale is not complete, cannot delete
      $message = "Cannot delete sale. Amount owed is greater than 0.";
      $messageType = "error";
  }
}

$conn->close();

// Handle message display if any
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
                      <?php if (isset($sale)): ?>
                        <form method="POST" class="forms-sample">
                  <input type="hidden" name="product_id" value="<?= htmlspecialchars($sale['product_id']); ?>">
        <div class="form-group">
            <label for="item_name">Item Name</label>
            <input type="text" class="form-control" id="item_name" name="item_name" value="<?= htmlspecialchars($sale['item_name']); ?>" readonly>
        </div>
        <div class="form-group">
            <label for="item_price">Item Price</label>
            <input type="number" class="form-control" id="item_price" name="item_price" value="<?= htmlspecialchars($sale['item_price']); ?>" readonly>
        </div>
        <div class="form-group">
            <label for="quantity">Quantity</label>
            <input type="number" class="form-control" id="quantity" name="quantity" value="<?= htmlspecialchars($sale['quantity']); ?>" readonly>
        </div>
        <div class="form-group">
            <label for="amount_paid">Amount Paid</label>
            <input type="number" class="form-control" id="amount_paid" name="amount_paid" value="<?= htmlspecialchars($sale['amount_paid']); ?>" required>
        </div>
        <div class="form-group">
            <label for="amount_owed">Amount Owed</label>
            <input type="number" class="form-control" id="amount_owed" name="amount_owed" value="<?= htmlspecialchars($sale['amount_owed']); ?>" required>
        </div>
        <div class="form-group">
            <label for="customer_name">Customer Name</label>
            <input type="text" class="form-control" id="customer_name" name="customer_name" value="<?= htmlspecialchars($sale['customer_name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="customer_phone">Customer Phone</label>
            <input type="tel" class="form-control" id="customer_phone" name="customer_phone" value="<?= htmlspecialchars($sale['customer_phone']); ?>" required>
        </div>
        <div class="form-group">
            <label for="customer_address">Customer Address</label>
            <textarea class="form-control" id="customer_address" name="customer_address" rows="4" required><?= htmlspecialchars($sale['customer_address']); ?></textarea>
        </div>
        <button type="submit" name="update" class="btn btn-primary">Update Sale</button>
        <button type="submit" name="delete" class="btn btn-danger">Delete Sale</button>
    </form>
<?php else: ?>
    <p>No sale details available.</p>
<?php endif; ?>
   <script>
    // Function to update the amount owed
    function calculateAmountOwed() {
        // Get values from input fields
        const itemPrice = parseFloat(document.getElementById('item_price').value) || 0;
        const quantity = parseInt(document.getElementById('quantity').value) || 0;
        const amountPaid = parseFloat(document.getElementById('amount_paid').value) || 0;

        // Calculate total cost (item price * quantity)
        const totalCost = itemPrice * quantity;

        // Calculate amount owed
        const amountOwed = totalCost - amountPaid;

        // Update the amount owed field
        document.getElementById('amount_owed').value = amountOwed.toFixed(2); // Ensuring two decimal places
    }

    // Add event listeners to trigger calculation when any of the relevant fields change
    document.getElementById('item_price').addEventListener('input', calculateAmountOwed);
    document.getElementById('quantity').addEventListener('input', calculateAmountOwed);
    document.getElementById('amount_paid').addEventListener('input', calculateAmountOwed);

    // Initial calculation on page load in case there are already pre-filled values
    window.onload = calculateAmountOwed;
</script>
<script>
    // Store the initial values of the form fields
    const initialValues = {
        amount_paid: document.getElementById('amount_paid').value,
        amount_owed: document.getElementById('amount_owed').value,
        customer_name: document.getElementById('customer_name').value,
        customer_phone: document.getElementById('customer_phone').value,
        customer_address: document.getElementById('customer_address').value,
    };

    // Add an event listener to the form submission
    document.getElementById('saleForm').addEventListener('submit', function (event) {
        // Check if any values have changed
        const currentValues = {
            amount_paid: document.getElementById('amount_paid').value,
            amount_owed: document.getElementById('amount_owed').value,
            customer_name: document.getElementById('customer_name').value,
            customer_phone: document.getElementById('customer_phone').value,
            customer_address: document.getElementById('customer_address').value,
        };

        let dataChanged = false;
        for (const key in initialValues) {
            if (initialValues[key] !== currentValues[key]) {
                dataChanged = true;
                break;
            }
        }

        if (!dataChanged) {
            // Prevent form submission and show a message
            event.preventDefault();
            alert('No changes were detected in the form.');
        }
    });
</script>
    </div></div>

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