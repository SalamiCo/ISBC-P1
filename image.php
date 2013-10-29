<?php

    $url = $_GET['url'] or die('No URL specified');
    $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'twitter-avatar-' . sha1($url);

    // If the avatar is not already loaded, do it now
    if (!file_exists($file) || filemtime($file) < time() - 3600) {
        file_put_contents($file, file_get_contents($url));
    }

    if (isset($_GET['marker'])) {
        // Place the avatar in a marker

        $avatar = imagecreatefromjpeg($file);
        $img = imagecreatefrompng('img/marker-' . $_GET['marker'] . '.png');
        imagealphablending($img, true);
        imagesavealpha($img, true);

        imagecopyresized($img, $avatar, 2, 2, 0, 0, 20, 20, imagesx($avatar), imagesy($avatar));

        header('Content-Type: image/png');
        imagepng($img);

    } else {
        // Directly echo the avatar as-is
        header('Content-Type: image/jpeg');
        echo file_get_contents($file);
    }