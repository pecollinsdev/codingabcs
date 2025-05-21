<?php
/**
 * Card Builder Utility
 * 
 * A utility class for generating consistent card components across the application.
 * Usage:
 * $card = new CardBuilder();
 * echo $card->build([
 *     'title' => 'Card Title',
 *     'content' => 'Card content here',
 *     'icon' => 'fas fa-icon',
 *     'classes' => 'additional-classes',
 *     'footer' => 'Card footer content'
 * ]);
 */

class CardBuilder {
    private $defaults = [
        'title' => '',
        'content' => '',
        'icon' => '',
        'classes' => '',
        'footer' => '',
        'shadow' => true,
        'border' => false,
        'hover' => true
    ];

    public function build($options = []): string {
        $options = array_merge($this->defaults, $options);
        
        $cardClasses = 'card';
        if ($options['shadow']) $cardClasses .= ' shadow-lg';
        if (!$options['border']) $cardClasses .= ' border-0';
        if ($options['hover'] === true) $cardClasses .= ' hover-card';
        if ($options['classes']) $cardClasses .= ' ' . $options['classes'];

        $html = '<div class="' . $cardClasses . '" style="transition: all 0.3s ease-in-out;">';
        
        if ($options['icon'] || $options['title']) {
            $html .= '<div class="card-header bg-transparent border-0">';
            if ($options['icon']) {
                if (strpos($options['icon'], '<i') === 0) {
                    $html .= '<div class="feature-icon mb-3">' . $options['icon'] . '</div>';
                } else {
                    $html .= '<div class="feature-icon mb-3"><i class="' . $options['icon'] . '"></i></div>';
                }
            }
            if ($options['title']) {
                $html .= '<h3 class="card-title" style="color: var(--text-color)">' . $options['title'] . '</h3>';
            }
            $html .= '</div>';
        }

        $html .= '<div class="card-body">' . $options['content'] . '</div>';

        if ($options['footer']) {
            $html .= '<div class="card-footer bg-transparent border-0">' . $options['footer'] . '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Build a card with centered content
     * @return string
     */
    public function buildCentered($options = []): string {
        $options['classes'] = isset($options['classes']) ? 
            $options['classes'] . ' text-center' : 
            'text-center';
        return $this->build($options);
    }

    /**
     * Build a card with a specific background color
     */
    public function buildWithBg($options = [], $bgColor = 'var(--card-bg)') {
        $options['classes'] = isset($options['classes']) ? 
            $options['classes'] . ' custom-bg' : 
            'custom-bg';
        $card = $this->build($options);
        return str_replace('class="card', 'class="card" style="background-color: ' . $bgColor . ';"', $card);
    }

    /**
     * Generate a primary button with hover effects
     */
    public function primaryButton($text, $href = '#', $icon = '', $classes = '') {
        $iconHtml = $icon ? '<i class="' . $icon . '"></i>' : '';
        return '<a href="' . $href . '" class="btn btn-primary ' . $classes . '">' . 
               $iconHtml . $text . '</a>';
    }

    /**
     * Generate an outline button with hover effects
     */
    public function outlineButton($text, $href = '#', $icon = '', $classes = '') {
        $iconHtml = $icon ? '<i class="' . $icon . '"></i>' : '';
        return '<a href="' . $href . '" class="btn btn-outline-primary ' . $classes . '">' . 
               $iconHtml . $text . '</a>';
    }

    /**
     * Add button styles to the page
     */
    public function addButtonStyles() {
        return '<link rel="stylesheet" href="/codingabcs/client/assets/css/buttons.css">';
    }
} 