<?php

class JokeController
{
    public function index()
    {
        // Default category
        $category = isset($_GET['category']) ? $_GET['category'] : 'Any';

        // Build API URL
        $url = "https://v2.jokeapi.dev/joke/" . $category;

        // Call API
        $response = file_get_contents($url);

        // Convert JSON to PHP array
        $jokeData = json_decode($response, true);

        // Send data to view
        require_once BASE_PATH . '/views/front/joke/index.php';
    }
}