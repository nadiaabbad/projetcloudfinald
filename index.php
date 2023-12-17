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
    require_once 'config.php';  // Inclure le fichier de configuration

    // Répertoire où les images seront stockées
    $imageDirectory = 'uploads/';

    // Vérifie si le dossier existe, sinon le crée
    if (!file_exists($imageDirectory)) {
        mkdir($imageDirectory, 0777, true);
    }

    // Traitement du téléchargement de l'image
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
        $targetFile = $imageDirectory . basename($_FILES['image']['name']);
        
        // Vérifie si le fichier est une image
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif');

        if (in_array($imageFileType, $allowedExtensions)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                // Enregistrez le chemin de l'image et les tags dans la base de données
                $fileName = $_FILES['image']['name'];

                // Call Custom Vision for image classification
                $imagePath = $imageDirectory . $fileName;
                $customVisionEndpoint = "https://predictiongitapp.cognitiveservices.azure.com/"; // Replace with your Custom Vision endpoint
                $customVisionPredictionKey = "8b6ad715cd9344a8b1583de97f24c1ae"; // Replace with your Custom Vision prediction key
                $customVisionIterationId = "057147e0-d1b2-4ed1-83a7-a798ecd772e2"; // Replace with your Custom Vision iteration ID

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

                echo '<p>L\'image a été téléchargée et classifiée avec succès.</p>';
            } else {
                echo '<p>Une erreur s\'est produite lors du téléchargement de l\'image.</p>';
            }
        } else {
            echo '<p>Seuls les fichiers JPG, JPEG, PNG et GIF sont autorisés.</p>';
        }
    }
    ?>

    <!-- Formulaire pour télécharger une image -->
    <form action="" method="post" enctype="multipart/form-data">
        <label for="image">Sélectionnez une image à télécharger :</label>
        <input type="file" name="image" id="image" accept="image/*" required>
        <button type="submit">Télécharger</button>
    </form>

    <!-- Affichage de l'album -->
    <h2>Album</h2>
    <div>
        <?php
        // Affiche toutes les images dans le répertoire
        $images = glob($imageDirectory . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        foreach ($images as $image) {
            echo '<img src="' . $image . '" alt="Album Image">';
        }
        ?>
    </div>

    <!-- Test de la connexion à la base de données -->
    <?php
    // Requête de test
    $query = "SELECT TOP 1 ImageID, ImageName, Class FROM Images";
    $result = sqlsrv_query($conn, $query);

    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    // Affichage des résultats de test
    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        echo '<p>ImageID: ' . $row['ImageID'] . ', ImageName: ' . $row['ImageName'] . ', Class: ' . $row['Class'] . '</p>';
    }
    ?>
</body>
</html>
