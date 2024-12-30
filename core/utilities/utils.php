<?php

    // sanitizes data
    function Sanitize($data) {
        $data = trim($data);
        $data = htmlspecialchars($data);
        $data = htmlentities($data);

        return $data;
    }

    
    function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }