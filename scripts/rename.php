<?php
$glob = dirname(__DIR__) . '/inedits/*.jpg';
foreach (glob($glob) as $file) {
    $dst = preg_replace('/_(\d\d\d\.jpg)/', "/$1", $file);
    echo "$file > $dst\n";
    $dir = dirname($dst);
    if (!is_dir($dir)) {
        mkdir($dir, 777, true);
    }
    rename($file, $dst);
}

