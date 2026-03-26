<?php
header('Content-Type: application/json');

/*
 * Skrypt skanuje folder 'gallery/' i zwraca listę zdjęć jako JSON.
 * Obsługiwane formaty: jpg, jpeg, png, gif, webp.
 */

$directory = '../gallery';
$images = [];

if (is_dir($directory)) {
    // Skanujemy katalog, ignorując kropki i pliki ukryte
    $files = array_diff(scandir($directory), array('..', '.'));
    
    foreach ($files as $file) {
        $filePath = $directory . '/' . $file;
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            // Możemy ulepszyć 'alt' wycinając podkreślenia/myślniki z nazwy pliku
            $cleanName = str_replace(['_', '-'], ' ', pathinfo($file, PATHINFO_FILENAME));
            $images[] = [
                'src' => 'gallery/' . $file,
                'alt' => ucfirst($cleanName)
            ];
        }
    }
}

// Zwracamy pustą tablicę lub listę zdjęć
echo json_encode($images);
?>
