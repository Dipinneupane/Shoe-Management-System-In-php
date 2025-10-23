// Product Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    initTabs();
    
    // Quantity selector
    initQuantitySelector();
    
    // Initialize size selection
    initSizeSelection();
    
    // Initialize star rating
    initStarRating();
});

// Initialize tabs
function initTabs() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active class from all buttons and panels
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanels.forEach(p => p.classList.remove('active'));
            
            // Add active class to clicked button and corresponding panel
            btn.classList.add('active');
            const panelId = btn.getAttribute('data-tab') + '-panel';
            const panel = document.getElementById(panelId);
            if (panel) panel.classList.add('active');
        });
    });
}

// Initialize quantity selector
function initQuantitySelector() {
    const minusBtn = document.querySelector('.minus-btn');
    const plusBtn = document.querySelector('.plus-btn');
    const quantityInput = document.querySelector('.quantity-input');
    
    if (!minusBtn || !plusBtn || !quantityInput) return;
    
    minusBtn.addEventListener('click', () => {
        let currentValue = parseInt(quantityInput.value);
        if(currentValue > 1) {
            quantityInput.value = currentValue - 1;
        }
    });
    
    plusBtn.addEventListener('click', () => {
        let currentValue = parseInt(quantityInput.value);
        if(currentValue < 10) {
            quantityInput.value = currentValue + 1;
        }
    });
}

// Initialize size selection
function initSizeSelection() {
    const sizeOptions = document.querySelectorAll('.size-option');
    
    sizeOptions.forEach(option => {
        const radio = option.querySelector('input[type="radio"]');
        const label = option.querySelector('label');
        
        // Highlight selected size
        if (radio && label) {
            label.addEventListener('click', () => {
                // Remove active class from all options
                sizeOptions.forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                // Add active class to selected option
                option.classList.add('selected');
            });
            
            // Check if this option is pre-selected
            if (radio.checked) {
                option.classList.add('selected');
            }
        }
    });
}

// Initialize star rating
function initStarRating() {
    const stars = document.querySelectorAll('.star-rating .star');
    
    if (stars.length === 0) return;
    
    stars.forEach((star, index) => {
        star.addEventListener('click', () => {
            // Set the rating value
            const ratingInput = document.querySelector('input[name="rating"]');
            if (ratingInput) {
                ratingInput.value = index + 1;
            }
            
            // Update star display
            stars.forEach((s, i) => {
                if (i <= index) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
        });
    });
}

// Helper function to show notifications
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Auto-remove notification after 5 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(-20px)';
        
        // Remove from DOM after animation
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
    
    // Add styles if not already present
    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 25px;
                border-radius: 5px;
                color: white;
                font-weight: 500;
                z-index: 1000;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                transform: translateY(0);
                opacity: 1;
                transition: all 0.3s ease;
            }
            
            .notification.success {
                background-color: #4CAF50;
            }
            
            .notification.error {
                background-color: #F44336;
            }
            
            .notification.warning {
                background-color: #FF9800;
            }
            
            .notification.info {
                background-color: #2196F3;
            }
        `;
        document.head.appendChild(style);
    }
}
