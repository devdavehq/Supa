<?php

// session_start();
    //  links to all necessary files NOTE: add this to the index file at he rootpage i.e index.php
    //  instantiate all classes here

    // require 'vendor/autoload.php';
    

    // Autoload all PHP files in the utilities directory
    foreach (glob(__DIR__ . '/utilities/*.php') as $file) {
        require_once $file;
    }
   

