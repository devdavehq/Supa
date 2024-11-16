<?php

function handleData($data) 
{
    // Check if data is not set or is empty
    if (empty($data)) {
        return 'Wrong Data';
    }

    $newdata = []; // Initialize the newdata array

    foreach ($data as $key => $value) 
    {
        $newdata[$key] = $value; 
    }

    return $newdata; 
}