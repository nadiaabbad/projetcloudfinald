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
                $imagePath = $imageDirectory . $fileName;
                $customVisionEndpoint = "https://porjectpredictionfinal.cognitiveservices.azure.com/"; // Replace with your Custom Vision endpoint
                $customVisionPredictionKey = "0881faa437b44587849fb6f0374932be"; // Replace with your Custom Vision prediction key
                $customVisionIterationId = "044e4e9b-23e5-44c3-9cf0-86b754c966f2"; // Replace with your Custom Vision iteration ID

                // Create a POST request to the Custom Vision prediction endpoint
                $ch = curl_init($customVisionEndpoint . "/customvision/v3.0/Prediction/$customVisionIterationId/classify/iterations/Iteration1/image");
                $imageData = file_get_contents($imagePath);

                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $imageData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/octet-stream',
                    'Prediction-Key: ' . $customVisionPredictionKey
                ));

                $result = curl_exec($ch);
                $decodedResult = json_decode($result, true);

                if ($decodedResult && isset($decodedResult['predictions'][0]['tagName'])) {
                    $predictedClass = $decodedResult['predictions'][0]['tagName'];
                    echo "<p>Classified as: $predictedClass</p>";
                    $tags = $predictedClass;
                } else {
                    echo "<p>Unable to classify the image or prediction result is invalid.</p>";
                    $tags = 'Unknown'; // Set a default value for $tags
                }

                curl_close($ch);

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
