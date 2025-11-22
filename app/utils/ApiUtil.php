<?php

namespace app\utils;

class ApiUtil {

    /**
     * Perform a GET request to the specified URL and return the decoded JSON response.
     *
     * @param string $baseUrl The URL to send the GET request to.
     * @return array|null The decoded JSON response as an associative array, or null on failure.
     */
    public function get(string $baseUrl): ?array {

        $ch = curl_init();

        $curlOptions = [
            CURLOPT_URL => $baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ];
        curl_setopt_array($ch, $curlOptions);
        
        $result = curl_exec($ch);
        
        return json_decode($result, true);
    }
}

