<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Album PHP</title>
</head>
<body>
    <h1>Mon Album</h1>

    <?php
    require_once 'config.php';  // Include the configuration file

    // Directory where the images will be stored
    $imageDirectory = 'uploads/';

    // Check if the directory exists, create it if not
    if (!file_exists($imageDirectory)) {
        mkdir($imageDirectory, 0777, true);
    }

    // Image upload processing
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
        $targetFile = $imageDirectory . basename($_FILES['image']['name']);
        
        // Check if the file is an image
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif');

        if (in_array($imageFileType, $allowedExtensions)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                // Save the image path and tags in the database
                $fileName = $_FILES['image']['name'];

                // Call Custom Vision for image classification
                // ... (previous code for Custom Vision)

                // Continue with the database insertion
                $insertQuery = "INSERT INTO Images (ImageName, Class) VALUES (?, ?)";
                $params = array($fileName, $tags);
                $stmt = sqlsrv_prepare($conn, $insertQuery, $params);

                if (sqlsrv_execute($stmt) === false) {
                    die(print_r(sqlsrv_errors(), true));
                }

                echo '<p>The image was uploaded and classified successfully.</p>';
            } else {
                echo '<p>An error occurred while uploading the image.</p>';
            }
        } else {
            echo '<p>Only JPG, JPEG, PNG, and GIF files are allowed.</p>';
        }
    }
    ?>

    <!-- Form for uploading an image -->
    <form action="" method="post" enctype="multipart/form-data">
        <label for="image">Select an image to upload:</label>
        <input type="file" name="image" id="image" accept="image/*" required>
        <button type="submit">Upload</button>
    </form>

    <!-- Display the album -->
    <h2>Album</h2>
    <div>
        <?php
        // Display all images in the directory
        $images = glob($imageDirectory . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        foreach ($images as $image) {
            echo '<img src="' . $image . '" alt="Album Image">';
        }
        ?>
    </div>

    <!-- Test the database connection -->
    <?php
    // Test query
    $query = "SELECT TOP 10 ImageID, ImageName, Class FROM Images";
    $result = sqlsrv_query($conn, $query);

    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    // Display test results
    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        echo '<p>ImageID: ' . $row['ImageID'] . ', ImageName: ' . $row['ImageName'] . ', Class: ' . $row['Class'] . '</p>';
    }
    ?>
</body>
</html>
