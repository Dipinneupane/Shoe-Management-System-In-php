document.addEventListener("DOMContentLoaded", function() {
    var ratingInputs = document.querySelectorAll('.rating-stars input[type="radio"]');
    var ratingValue = document.getElementById('rating-value');
    var ratingHint = document.querySelector('.rating-hint');
    
    // Add hover effect using traditional for loop
    for (var i = 0; i < ratingInputs.length; i++) {
        (function() {
            var input = ratingInputs[i];
            var label = input.nextElementSibling;
            
            // Show hover effect
            label.addEventListener('mouseenter', function() {
                var value = this.getAttribute('title').split(' ')[0];
                this.style.transform = 'scale(1.3)';
                ratingHint.textContent = 'Rate this product ' + value + ' stars';
            });
            
            // Reset on mouse leave
            label.addEventListener('mouseleave', function() {
                this.style.transform = '';
                var checkedInput = document.querySelector('.rating-stars input[type="radio"]:checked');
                if (checkedInput) {
                    var value = checkedInput.nextElementSibling.getAttribute('title').split(' ')[0];
                    ratingValue.textContent = value + ' stars';
                    ratingHint.textContent = 'Click on a star to change your rating';
                } else {
                    ratingValue.textContent = 'Not rated yet';
                    ratingHint.textContent = 'Click on a star to rate this product';
                }
            });
            
            // Update on click
            input.addEventListener('change', function() {
                var value = this.nextElementSibling.getAttribute('title');
                ratingValue.textContent = value;
                ratingHint.textContent = 'Click on a star to change your rating';
                
                // Add a nice animation
                var star = this.nextElementSibling;
                star.style.animation = 'pulse 0.5s ease-in-out';
                setTimeout(function() {
                    star.style.animation = '';
                }, 500);
            });
        })();
    }
    
    // Initialize the rating display
    var checkedInput = document.querySelector('.rating-stars input[type="radio"]:checked');
    if (checkedInput) {
        var value = checkedInput.nextElementSibling.getAttribute('title');
        ratingValue.textContent = value;
        ratingHint.textContent = 'Click on a star to change your rating';
    }
});
