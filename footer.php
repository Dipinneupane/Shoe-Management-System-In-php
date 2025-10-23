<footer class="footer">
    <div class="footer-top">
        <div class="container">
            <div class="footer-content">
                <div class="footer-about">
                    <div class="footer-logo">
                        <i class="fas fa-shoe-prints"></i>
                        <h2>Happy Fit</h2>
                    </div>
                    <p class="footer-desc">Premium quality shoes for every occasion. Step into comfort and style with our exclusive collection.</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>

                <div class="footer-links">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="home.php"><i class="fas fa-angle-right"></i> Home</a></li>
                        <li><a href="shop.php"><i class="fas fa-angle-right"></i> Shop</a></li>
                        <li><a href="about.php"><i class="fas fa-angle-right"></i> About Us</a></li>
                        <li><a href="contact.php"><i class="fas fa-angle-right"></i> Contact</a></li>
                    </ul>
                </div>

                <div class="footer-links">
                    <h3>My Account</h3>
                    <ul>
                        <li><a href="login.php"><i class="fas fa-angle-right"></i> Login</a></li>
                        <li><a href="register.php"><i class="fas fa-angle-right"></i> Register</a></li>
                        <li><a href="cart.php"><i class="fas fa-angle-right"></i> My Cart</a></li>
                        <li><a href="orders.php"><i class="fas fa-angle-right"></i> My Orders</a></li>
                    </ul>
                </div>

                <div class="footer-contact">
                    <h3>Contact Us</h3>
                    <ul class="contact-info">
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <p>Kathmandu, Nepal - 44600</p>
                        </li>
                        <li>
                            <i class="fas fa-phone"></i>
                            <p>+977 9863517314</p>
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <p>dipin.neupane2016@gmail.com</p>
                        </li>
                        <li>
                            <i class="fas fa-clock"></i>
                            <p>Mon - Sat: 9:00 AM - 6:00 PM</p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div class="container">
            <div class="footer-bottom-content">
                <p class="copyright">&copy; <?php echo date('Y'); ?> <span>Happy Fit</span>. All Rights Reserved.</p>
                <div class="payment-methods">
                    <img src="images/payment-methods.png" alt="Payment Methods" onerror="this.style.display='none'">
                </div>
            </div>
        </div>
    </div>
    
    <a href="#" class="scroll-top" id="scroll-top">
        <i class="fas fa-arrow-up"></i>
    </a>
</footer>

<script>
    // Mobile menu toggle
    let menu = document.querySelector('#menu-btn');
    let navbar = document.querySelector('.navbar');
    
    menu.onclick = () => {
        menu.classList.toggle('active');
        navbar.classList.toggle('active');
    }
    
    // User box toggle
    let userBtn = document.querySelector('#user-btn');
    let userBox = document.querySelector('.user-box');
    
    userBtn.onclick = () => {
        userBox.classList.toggle('active');
    }
    
    // Scroll to top button
    let scrollBtn = document.querySelector('#scroll-top');
    
    window.onscroll = () => {
        menu.classList.remove('active');
        navbar.classList.remove('active');
        userBox.classList.remove('active');
        
        if(window.scrollY > 300){
            scrollBtn.classList.add('active');
        } else {
            scrollBtn.classList.remove('active');
        }
    }
    
    scrollBtn.onclick = () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
        return false;
    }
</script>