document.addEventListener('DOMContentLoaded', function() {
    // Add any necessary JavaScript functionality here
    // For example, you might want to add animations or handle the display of explanations
    
    // Example: Add smooth scroll to top when page loads
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });

    // Example: Add animation to question cards as they appear
    const questionCards = document.querySelectorAll('.question-card');
    questionCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
