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

    $imageDirectory = 'uploads/';

    // Check if the folder exists, otherwise create it
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

                // ... Your existing code to save file name and default tag in the database ...

                // Azure Custom Vision integration
                include 'custom_vision_integration.php';  // Include the code for Azure Custom Vision
                perform_custom_vision_integration($targetFile);
                
                echo '<p>L\'image a été téléchargée avec succès.</p>';
            } else {
                echo '<p>Une erreur s\'est produite lors du téléchargement de l\'image.</p>';
            }
        } else {
            echo '<p>Seuls les fichiers JPG, JPEG, PNG et GIF sont autorisés.</p>';
        }
    }
    ?>

    <!-- Image upload form -->
    <form action="" method="post" enctype="multipart/form-data">
        <label for="image">Sélectionnez une image à télécharger :</label>
        <input type="file" name="image" id="image" accept="image/*" required>
        <button type="submit">Télécharger</button>
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
    $query = "SELECT TOP 1 Id, FileName, Tag FROM MaTable";
    $result = sqlsrv_query($conn, $query);

    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        echo '<p>ID: ' . $row['Id'] . ', FileName: ' . $row['FileName'] . ', Tag: ' . $row['Tag'] . '</p>';
    }
    ?>
</body>
</html>
