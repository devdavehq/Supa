<?php

    // sanitizes data
    function Sanitize($data) {
        $data = trim($data);
        $data = htmlspecialchars($data);
        $data = htmlentities($data);

        return $data;
    }