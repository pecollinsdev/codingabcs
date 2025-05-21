<?php
/**
 * API Client for making requests to the backend API
 */
class ApiClient {
    private $baseUrl;
    private $cookieName;

    public function __construct() {
        // Update the base URL to point to the correct API location
        $this->baseUrl = 'http://localhost/codingabcs/api/public';
        $this->cookieName = 'jwt_token';
    }

    /**
     * Make a GET request to the API
     * @param string $endpoint The API endpoint to call
     * @param array $params Query parameters
     * @return array The response data
     */
    public function get(string $endpoint, array $params = []): array {
        // Remove leading slash if present
        $endpoint = ltrim($endpoint, '/');
        
        // Build the URL
        $url = $this->baseUrl . '/' . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Requested-With: XMLHttpRequest'
        ];

        // Add JWT token to headers if it exists
        if (isset($_COOKIE[$this->cookieName])) {
            $token = $_COOKIE[$this->cookieName];
            $headers[] = 'Authorization: Bearer ' . $token;
        } else {
            return ['error' => 'Authentication required'];
        }

        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development only
        curl_setopt($ch, CURLOPT_VERBOSE, true); // Enable verbose output for debugging
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects

        // Create a temporary file to store the verbose output
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);

        // Execute the request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Get verbose debug information
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);

        // Check for cURL errors
        if (curl_errno($ch)) {
            curl_close($ch);
            fclose($verbose);
            return ['error' => 'API request failed: ' . curl_error($ch)];
        }

        curl_close($ch);
        fclose($verbose);

        // Parse the response
        $data = json_decode($response, true);
        
        // Check if the response is valid JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Invalid API response: ' . json_last_error_msg()];
        }

        // Check for HTTP errors
        if ($httpCode >= 400) {
            $errorMessage = isset($data['error']) ? $data['error'] : 'Unknown error';
            return ['error' => $errorMessage];
        }

        // Return the data array directly if it exists, otherwise return the full response
        return isset($data['data']) ? $data['data'] : $data;
    }

    /**
     * Make a POST request to the API
     * @param string $endpoint The API endpoint to call
     * @param array $data The data to send
     * @return array The response data
     */
    public function post($endpoint, $data = []) {
        $endpoint = ltrim($endpoint, '/');
        $url = $this->baseUrl . '/' . $endpoint;

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Requested-With: XMLHttpRequest'
        ];

        // Add JWT token to headers if it exists
        if (isset($_COOKIE[$this->cookieName])) {
            $token = $_COOKIE[$this->cookieName];
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development only
        curl_setopt($ch, CURLOPT_VERBOSE, true); // Enable verbose output for debugging
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects

        // Create a temporary file to store the verbose output
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Get verbose debug information
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);

        curl_close($ch);
        fclose($verbose);

        // Parse the response
        $data = json_decode($response, true);
        
        // Check if the response is valid JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Invalid API response: ' . json_last_error_msg()];
        }

        // Check for HTTP errors
        if ($httpCode >= 400) {
            $errorMessage = isset($data['error']) ? $data['error'] : 'Unknown error';
            return ['error' => $errorMessage];
        }


        // Return the data array directly if it exists, otherwise return the full response
        return isset($data['data']) ? $data['data'] : $data;
    }
} 