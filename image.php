<?php

    $url = $_GET['url'] or die('No URL specified');
    $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'twitter-avatar-' . sha1($url);

    if (!file_exists($file) || filemtime($file) < time() - 3600) {
        file_put_contents($file, file_get_contents($url));
    }

    echo file_get_contents($file);