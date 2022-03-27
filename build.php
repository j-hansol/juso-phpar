<?php
try {
    $phar_path = __DIR__ . "/build/juso.phar";

    if( !file_exists(__DIR__ . '/build') ) mkdir( __DIR__ . '/build' );
    if (file_exists($phar_path)) unlink($phar_path);
    if (file_exists($phar_path . '.gz')) unlink($phar_path . '.gz');

    $phar = new Phar($phar_path);
    $phar->startBuffering();

    $defaultStub = $phar->createDefaultStub('main.php');
    $phar->buildFromDirectory(__DIR__ . '/app');
    $stub = "#!/usr/bin/env php \n" . $defaultStub;
    $phar->setStub($stub);
    $phar->stopBuffering();

    $phar->compressFiles(Phar::GZ);
    echo "$phar_path successfully created" . PHP_EOL;
} catch (Exception $e ) {
    echo $e->getMessage();
}
