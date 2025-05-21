<?php 
  // page.php
  $page       = 'home';
  $showFooter = true;
  
  require_once '../assets/php/card_builder.php';
  $cardBuilder = new CardBuilder();
  
  // Add button styles
  echo $cardBuilder->addButtonStyles();
?>


<section class="hero-section">
  <div class="container hero-container d-flex flex-column flex-lg-row align-items-center justify-content-between">
    <div class="hero-content text-center text-lg-start mb-5 mb-lg-0 col-lg-6">
      <h1 class="display-5 fw-bold mb-3">
        Welcome to <span class="text-primary">Coding ABCs</span>
      </h1>
      <p class="lead mb-4">
        Master coding fundamentals with interactive, beginner‑friendly quizzes built to make learning fun.
      </p>
      <div class="hero-buttons">
        <?php
        echo $cardBuilder->primaryButton('Continue', url('login'), 'fas fa-sign-in-alt');
        echo $cardBuilder->outlineButton('Sign Up', url('register'), 'fas fa-user-plus');
        ?>
      </div>
    </div>
    <div class="hero-image-wrapper col-lg-6 text-center">
      <img src="../assets/images/code-thinking.svg"
           alt="Coding Illustration"
           class="img-fluid hero-image">
    </div>
  </div>
</section>

<section class="cards-section">
  <div class="container">
    <h2 class="section-title text-center mb-5">Why Choose Coding ABCs?</h2>
    <div class="row g-4">
      <?php
      $cards = [
        [
          'title' => 'Interactive Quizzes',
          'content' => 'Engaging quizzes designed to test and reinforce core programming concepts.',
          'icon' => '<i class="fas fa-laptop-code"></i>',
          'classes' => 'text-center'
        ],
        [
          'title' => 'Track Your Progress',
          'content' => 'Monitor your learning over time with personalized analytics and insights.',
          'icon' => '<i class="fas fa-chart-line"></i>',
          'classes' => 'text-center'
        ],
        [
          'title' => 'Learn at Your Own Pace',
          'content' => 'Access all lessons anytime, from any device — your learning, your schedule.',
          'icon' => '<i class="fas fa-clock"></i>',
          'classes' => 'text-center'
        ]
      ];

      foreach ($cards as $card) {
        echo '<div class="col-md-4">';
        echo $cardBuilder->build($card);
        echo '</div>';
      }
      ?>
    </div>
  </div>
</section>


