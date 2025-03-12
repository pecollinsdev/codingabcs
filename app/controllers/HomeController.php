<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;

/**
 * HomeController handles the public home page.
 *
 * This controller is responsible for displaying the public home page
 * when users visit the site without being logged in. It redirects
 * logged-in users to the dashboard.
 */
class HomeController extends Controller {
    // Display the public home page
    public function index() {
        // If user is logged in, redirect to dashboard
        if (Session::has('user_id')) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        // Show the public welcome page
        $this->view('home');
    }
}
