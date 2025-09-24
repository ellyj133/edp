    </main>
    
    <footer class="fezamarket-footer">
        <div class="container">
            <!-- Newsletter Signup Section -->
            <div class="footer-newsletter">
                <div class="newsletter-content">
                    <div class="newsletter-info">
                        <h3>Stay in the loop</h3>
                        <p>Get the latest deals, new products, and trending items delivered to your inbox.</p>
                    </div>
                    <div class="newsletter-form">
                        <form class="signup-form" action="/newsletter/signup.php" method="POST">
                            <input type="email" placeholder="Enter your email" class="email-input" required>
                            <button type="submit" class="signup-btn">
                                <i class="fas fa-paper-plane"></i> Subscribe
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="footer-main">
                <!-- Shop Section -->
                <div class="footer-section">
                    <h3>
                        <i class="fas fa-shopping-bag"></i>
                        Shop
                    </h3>
                    <ul>
                        <li><a href="/deals.php"><i class="fas fa-fire"></i> Daily Deals</a></li>
                        <li><a href="/register.php"><i class="fas fa-user-plus"></i> Create Account</a></li>
                        <li><a href="/stores.php"><i class="fas fa-store"></i> Browse Stores</a></li>
                        <li><a href="/gift-cards.php"><i class="fas fa-gift"></i> Gift Cards</a></li>
                        <li><a href="/charity.php"><i class="fas fa-heart"></i> Charity Shop</a></li>
                        <li><a href="/seasonal-sales.php"><i class="fas fa-calendar-alt"></i> Seasonal Sales</a></li>
                    </ul>
                </div>
                
                <!-- Sell Section -->
                <div class="footer-section">
                    <h3>
                        <i class="fas fa-handshake"></i>
                        Sell
                    </h3>
                    <ul>
                        <li><a href="/sell/start.php"><i class="fas fa-rocket"></i> Start Selling</a></li>
                        <li><a href="/sell/how-to.php"><i class="fas fa-question-circle"></i> How to Sell</a></li>
                        <li><a href="/sell/business.php"><i class="fas fa-building"></i> Business Sellers</a></li>
                        <li><a href="/seller-center.php"><i class="fas fa-tachometer-alt"></i> Seller Center</a></li>
                        <li><a href="/sell/affiliates.php"><i class="fas fa-users"></i> Affiliate Program</a></li>
                    </ul>
                </div>
                
                <!-- Support Section -->
                <div class="footer-section">
                    <h3>
                        <i class="fas fa-life-ring"></i>
                        Support
                    </h3>
                    <ul>
                        <li><a href="/help.php"><i class="fas fa-question"></i> Help Center</a></li>
                        <li><a href="/contact.php"><i class="fas fa-envelope"></i> Contact Us</a></li>
                        <li><a href="/returns.php"><i class="fas fa-undo"></i> Returns & Refunds</a></li>
                        <li><a href="/money-back.php"><i class="fas fa-shield-alt"></i> Money Back Guarantee</a></li>
                        <li><a href="/security.php"><i class="fas fa-lock"></i> Security Center</a></li>
                    </ul>
                </div>
                
                <!-- Company Section -->
                <div class="footer-section">
                    <h3>
                        <i class="fas fa-building"></i>
                        Company
                    </h3>
                    <ul>
                        <li><a href="/about/company.php"><i class="fas fa-info-circle"></i> About Us</a></li>
                        <li><a href="/careers.php"><i class="fas fa-briefcase"></i> Careers</a></li>
                        <li><a href="/news.php"><i class="fas fa-newspaper"></i> Press & News</a></li>
                        <li><a href="/investors.php"><i class="fas fa-chart-line"></i> Investor Relations</a></li>
                        <li><a href="/advertise.php"><i class="fas fa-bullhorn"></i> Advertise</a></li>
                    </ul>
                </div>

                <!-- Connect Section -->
                <div class="footer-section">
                    <h3>
                        <i class="fas fa-share-alt"></i>
                        Connect
                    </h3>
                    <div class="social-links">
                        <a href="#" class="social-link facebook" target="_blank">
                            <i class="fab fa-facebook-f"></i>
                            <span>Facebook</span>
                        </a>
                        <a href="#" class="social-link twitter" target="_blank">
                            <i class="fab fa-twitter"></i>
                            <span>Twitter</span>
                        </a>
                        <a href="#" class="social-link instagram" target="_blank">
                            <i class="fab fa-instagram"></i>
                            <span>Instagram</span>
                        </a>
                        <a href="#" class="social-link youtube" target="_blank">
                            <i class="fab fa-youtube"></i>
                            <span>YouTube</span>
                        </a>
                    </div>
                    
                    <div class="mobile-apps">
                        <h4>Get our app</h4>
                        <div class="app-links">
                            <a href="#" class="app-link">
                                <i class="fab fa-apple"></i>
                                <div>
                                    <span>Download on the</span>
                                    <strong>App Store</strong>
                                </div>
                            </a>
                            <a href="#" class="app-link">
                                <i class="fab fa-google-play"></i>
                                <div>
                                    <span>Get it on</span>
                                    <strong>Google Play</strong>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <div class="footer-bottom-left">
                        <div class="footer-logo">
                            <span class="logo-f">f</span><span class="logo-e">e</span><span class="logo-z">z</span><span class="logo-a">a</span><span class="logo-market">Market</span>
                        </div>
                        <p class="copyright">
                            © <?php echo date('Y'); ?> FezaMarket Inc. All rights reserved.
                        </p>
                    </div>
                    <div class="footer-bottom-right">
                        <div class="legal-links">
                            <a href="/privacy.php">Privacy Policy</a>
                            <a href="/user-agreement.php">Terms of Service</a>
                            <a href="/cookies.php">Cookie Policy</a>
                            <a href="/accessibility.php">Accessibility</a>
                            <a href="/ca-privacy.php">CA Privacy</a>
                        </div>
                        <div class="region-selector">
                            <select class="region-select">
                                <option value="us" selected>🇺🇸 United States - English</option>
                                <option value="uk">🇬🇧 United Kingdom - English</option>
                                <option value="ca">🇨🇦 Canada - English</option>
                                <option value="au">🇦🇺 Australia - English</option>
                                <option value="de">🇩🇪 Deutschland - Deutsch</option>
                                <option value="fr">🇫🇷 France - Français</option>
                                <option value="es">🇪🇸 España - Español</option>
                                <option value="it">🇮🇹 Italia - Italiano</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Include additional JavaScript if needed -->
    <script>
        // Initialize tooltips and other interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Add any page-specific JavaScript here
        });
    </script>
</body>
</html>