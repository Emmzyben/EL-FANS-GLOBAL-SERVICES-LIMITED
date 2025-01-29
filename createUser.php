<?php
session_start();
require_once './database/db_config.php'; // Include database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Retrieve and sanitize form data
        $fullName = htmlspecialchars(trim($_POST['fullName']));
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $username = htmlspecialchars(trim($_POST['username']));
        $password = htmlspecialchars(trim($_POST['password']));
        $gender = htmlspecialchars(trim($_POST['gender']));
        $position = htmlspecialchars(trim($_POST['position']));
        $address = htmlspecialchars(trim($_POST['address']));

        // Check if email or username already exists
        $query = "SELECT * FROM users WHERE email = ? OR username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            throw new Exception("Email or username already exists.");
        }

        // Handle profile picture upload
        $profilePicturePath = null; // Default null if no file uploaded
        if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] == 0) {
            $uploadDir = './profileImage/';
            $fileTmpPath = $_FILES['profilePicture']['tmp_name'];
            $fileName = $_FILES['profilePicture']['name'];
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
                throw new Exception("Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.");
            }

            $uniqueFileName = uniqid() . '.' . $fileExtension;
            $profilePicturePath = $uploadDir . $uniqueFileName;

            if (!move_uploaded_file($fileTmpPath, $profilePicturePath)) {
                throw new Exception("Failed to upload the profile picture.");
            }
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Insert user into the database
        $insertQuery = "INSERT INTO users (full_name, email, username, password, gender, profile_picture, position, address) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param(
            "ssssssss",
            $fullName,
            $email,
            $username,
            $hashedPassword,
            $gender,
            $profilePicturePath,
            $position,
            $address
        );

        if ($stmt->execute()) {
            $_SESSION['message'] = "User created successfully!";
            $_SESSION['messageType'] = "success";
        } else {
            throw new Exception("Failed to create user.");
        }
    } catch (Exception $e) {
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['messageType'] = "error";
    }

    // Redirect back to the form page
    header("Location: settings.php");
    exit;
}
?>
