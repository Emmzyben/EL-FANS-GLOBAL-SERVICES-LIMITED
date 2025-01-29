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

// Fetch products for the form dropdown
$products = [];
try {
    $query = "SELECT id, item_name, item_price, quantity FROM products";
    $result = $conn->query($query);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    } else {
        throw new Exception("Error fetching products: " . $conn->error);
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>{$e->getMessage()}</div>";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get and sanitize form inputs
    $item_id = intval($_POST['item_id']);
    $item_name = htmlspecialchars(trim($_POST['item_name']));
    $item_price = filter_var(trim($_POST['item_price']), FILTER_VALIDATE_FLOAT);
    $quantity_bought = filter_var(trim($_POST['quantity']), FILTER_VALIDATE_INT);
    $amount_paid = filter_var(trim($_POST['amount_paid']), FILTER_VALIDATE_FLOAT);
    $amount_owed = filter_var(trim($_POST['amount_owed']), FILTER_VALIDATE_FLOAT);
    $customer_name = htmlspecialchars(trim($_POST['customer_name']));
    $customer_phone = htmlspecialchars(trim($_POST['customer_phone']));
    $customer_address = htmlspecialchars(trim($_POST['customer_address']));
    
    
    // Validate required fields
    if ($item_id && $item_name && $item_price !== false && $quantity_bought !== false && $amount_paid !== false) {
        // Fetch current quantity of the product
        $productQuery = "SELECT quantity FROM products WHERE id = ?";
        $stmt = $conn->prepare($productQuery);
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $stmt->bind_result($current_quantity);
        $stmt->fetch();
        $stmt->close();

        if ($quantity_bought > $current_quantity) {
            $message = "Error: Quantity bought exceeds available stock.";
            $messageType = "error";
        } else {
            // Insert sale record into the sales table
            $saleQuery = "INSERT INTO sales (item_name, item_price, quantity, amount_paid, amount_owed, customer_name, customer_phone, customer_address, product_id) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($saleQuery);

            if ($stmt) {
                $stmt->bind_param(
                    "sdiddsssi",
                    $item_name,
                    $item_price,
                    $quantity_bought,
                    $amount_paid,
                    $amount_owed,
                    $customer_name,
                    $customer_phone,
                    $customer_address,
                    $item_id
                );

                if ($stmt->execute()) {
                    // Update the products table to reflect the new quantity
                    $new_quantity = $current_quantity - $quantity_bought;
                    $updateQuery = "UPDATE products SET quantity = ? WHERE id = ?";
                    $updateStmt = $conn->prepare($updateQuery);
                    $updateStmt->bind_param("ii", $new_quantity, $item_id);

                    if ($updateStmt->execute()) {
                        $message = "Sale recorded successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error updating product quantity: " . $updateStmt->error;
                        $messageType = "error";
                    }
                    $updateStmt->close();
                } else {
                    $message = "Error recording sale: " . $stmt->error;
                    $messageType = "error";
                }
                $stmt->close();
            } else {
                $message = "Error preparing query: " . $conn->error;
                $messageType = "error";
            }
        }
    } else {
        $message = "Invalid input data. Please check your entries.";
        $messageType = "error";
    }

    $_SESSION['message'] = $message;
    $_SESSION['messageType'] = $messageType;
    header("Location: add_sale.php");
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
    <title>Add New sale</title>
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
                 
                </span>
              </div>
            </div>
           
            <div class="row">
                <div class="col-12 grid-margin stretch-card">
                    <div class="card">
                      <div class="card-body">
                        <h4 class="card-title">New Sale</h4>
                        <form class="forms-sample" method="POST" action="">
    <input type="hidden" name="item_id" value="PRODUCT_ID">

    <div class="form-group">
        <label for="item_select">Item</label>
        <select class="form-control" id="item_select" name="item_id" required>
            <option value="">-- Select an Item --</option>
            <?php foreach ($products as $product): ?>
                <option value="<?= $product['id']; ?>" 
                        data-name="<?= htmlspecialchars($product['item_name']); ?>" 
                        data-price="<?= $product['item_price']; ?>" 
                        data-quantity="<?= $product['quantity']; ?>">
                    <?= htmlspecialchars($product['item_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <input type="hidden" id="item_name" name="item_name" >

    <div class="form-group">
        <label for="item_price">Item Price</label>
        <input type="number" class="form-control" id="item_price" name="item_price" placeholder="Price" readonly required>
    </div>
    <div class="form-group">
        <label for="quantity_in_stock">Quantity (In Stock)</label>
        <input type="number" class="form-control" id="quantity_in_stock" placeholder="Qty in Stock" readonly required>
    </div>
    <div class="form-group">
        <label for="quantity_bought">Quantity (Bought)</label>
        <input type="number" class="form-control" id="quantity_bought" name="quantity" placeholder="Qty Bought" required>
    </div>
    <div class="form-group">
        <label for="amount_paid">Amount Paid</label>
        <input type="number" class="form-control" id="amount_paid" name="amount_paid" placeholder="Amount Paid" required>
    </div>
    <div class="form-group">
        <label for="amount_owed">Amount Owed</label>
        <input type="number" class="form-control" id="amount_owed" name="amount_owed" placeholder="Amount Owed" readonly required>
    </div>
    <div class="form-group">
        <label for="customer_name">Customer Name</label>
        <input type="text" class="form-control" id="customer_name" name="customer_name" placeholder="Customer Name" required>
    </div>
    <div class="form-group">
        <label for="customer_phone">Customer Phone</label>
        <input type="tel" class="form-control" id="customer_phone" name="customer_phone" placeholder="Customer Phone" required>
    </div>
    <div class="form-group">
        <label for="customer_address">Customer Address</label>
        <textarea class="form-control" id="customer_address" name="customer_address" rows="4" placeholder="Customer Address" required></textarea>
    </div>
    <button type="submit" class="btn btn-primary mr-2" style="background-color:green;border:none">Submit</button>
    <button type="reset" class="btn btn-light">Cancel</button>
</form>

<script>
    // Handle updates when the item is selected
    document.getElementById('item_select').addEventListener('change', function () {
        const selectedItem = this.options[this.selectedIndex];
        const name = selectedItem.getAttribute('data-name'); // Get the item name
        const price = selectedItem.getAttribute('data-price'); // Get the item price
        const quantity = selectedItem.getAttribute('data-quantity'); // Get the quantity in stock

        // Update the hidden input for item name
        document.getElementById('item_name').value = name || '';

        // Update the price and quantity fields
        document.getElementById('item_price').value = price || '';
        document.getElementById('quantity_in_stock').value = quantity || '';
    });

    // Handle updates when the quantity bought is changed
    document.getElementById('quantity_bought').addEventListener('input', function () {
        const quantityInStock = parseFloat(document.getElementById('quantity_in_stock').value) || 0;
        const quantityBought = parseFloat(this.value) || 0;
        const itemPrice = parseFloat(document.getElementById('item_price').value) || 0;
        const amountPaid = parseFloat(document.getElementById('amount_paid').value) || 0;

        // Ensure quantity bought does not exceed quantity in stock
        if (quantityBought > quantityInStock) {
            alert("Quantity bought cannot exceed quantity in stock.");
            this.value = '';
            return;
        }

        // Calculate the amount owed
        const amountOwed = itemPrice * quantityBought - amountPaid;
        document.getElementById('amount_owed').value = amountOwed.toFixed(2);
    });

    // Handle updates when the amount paid is changed
    document.getElementById('amount_paid').addEventListener('input', function () {
        const quantityBought = parseFloat(document.getElementById('quantity_bought').value) || 0;
        const itemPrice = parseFloat(document.getElementById('item_price').value) || 0;
        const amountPaid = parseFloat(this.value) || 0;

        // Calculate the amount owed
        const amountOwed = itemPrice * quantityBought - amountPaid;
        document.getElementById('amount_owed').value = amountOwed.toFixed(2);
    });
</script>


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