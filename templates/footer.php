    </div> <!-- End main-content -->
    
    <!-- eBay Style Footer -->
    <footer class="ebay-footer">
        <div class="footer-container">
            <!-- Main Footer Links -->
            <div class="footer-main">
                <!-- Buy Section -->
                <div class="footer-column">
                    <h3>Buy</h3>
                    <ul>
                        <li><a href="/register.php">Registration</a></li>
                        <li><a href="/help/buying.php">Bidding & buying help</a></li>
                        <li><a href="/stores.php">Stores</a></li>
                        <li><a href="/collections.php">Creator Collections</a></li>
                        <li><a href="/charity.php">FezaMarket for Charity</a></li>
                        <li><a href="/charity-shop.php">Charity Shop</a></li>
                        <li><a href="/seasonal-sales.php">Seasonal Sales and events</a></li>
                        <li><a href="/gift-cards.php">FezaMarket Gift Cards</a></li>
                    </ul>
                </div>
                
                <!-- Sell Section -->
                <div class="footer-column">
                    <h3>Sell</h3>
                    <ul>
                        <li><a href="/sell.php">Start selling</a></li>
                        <li><a href="/help/selling.php">How to sell</a></li>
                        <li><a href="/business-sellers.php">Business sellers</a></li>
                        <li><a href="/affiliates.php">Affiliates</a></li>
                    </ul>
                    
                    <h4>Tools & apps</h4>
                    <ul>
                        <li><a href="/developers.php">Developers</a></li>
                        <li><a href="/security-center.php">Security center</a></li>
                        <li><a href="/sitemap.php">Site map</a></li>
                    </ul>
                </div>
                
                <!-- FezaMarket companies Section -->
                <div class="footer-column">
                    <h3>FezaMarket companies</h3>
                    <ul>
                        <li><a href="/tcgplayer.php">TCGplayer</a></li>
                    </ul>
                    
                    <h4>Stay connected</h4>
                    <ul class="social-links">
                        <li><a href="https://facebook.com/fezamarket" target="_blank"><i class="fab fa-facebook"></i> Facebook</a></li>
                        <li><a href="https://twitter.com/fezamarket" target="_blank"><i class="fab fa-twitter"></i> X (Twitter)</a></li>
                    </ul>
                </div>
                
                <!-- About FezaMarket Section -->
                <div class="footer-column">
                    <h3>About FezaMarket</h3>
                    <ul>
                        <li><a href="/company-info.php">Company info</a></li>
                        <li><a href="/news.php">News</a></li>
                        <li><a href="/investors.php">Investors</a></li>
                        <li><a href="/careers.php">Careers</a></li>
                        <li><a href="/diversity.php">Diversity & Inclusion</a></li>
                        <li><a href="/global-impact.php">Global Impact</a></li>
                        <li><a href="/government.php">Government relations</a></li>
                        <li><a href="/advertise.php">Advertise with us</a></li>
                        <li><a href="/policies.php">Policies</a></li>
                        <li><a href="/vero.php">Verified Rights Owner (VeRO) Program</a></li>
                        <li><a href="/eci-licenses.php">eCI Licenses</a></li>
                    </ul>
                </div>
                
                <!-- Help & Contact Section -->
                <div class="footer-column">
                    <h3>Help & Contact</h3>
                    <ul>
                        <li><a href="/seller-center.php">Seller Center</a></li>
                        <li><a href="/contact.php">Contact Us</a></li>
                        <li><a href="/returns.php">FezaMarket Returns</a></li>
                        <li><a href="/money-back.php">FezaMarket Money Back Guarantee</a></li>
                    </ul>
                    
                    <h4>Community</h4>
                    <ul>
                        <li><a href="/announcements.php">Announcements</a></li>
                        <li><a href="/community.php">FezaMarket Community</a></li>
                        <li><a href="/podcast.php">FezaMarket for Business Podcast</a></li>
                    </ul>
                    
                    <h4>FezaMarket Sites</h4>
                    <div class="country-selector">
                        <select class="country-dropdown">
                            <option value="US" selected>?? United States</option>
                            <option value="CA">?? Canada</option>
                            <option value="UK">?? United Kingdom</option>
                            <option value="AU">?? Australia</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Copyright and Legal Links -->
            <div class="footer-bottom">
                <div class="copyright-text">
                    Copyright � 1995-<?php echo date('Y'); ?> FezaMarket Inc. All Rights Reserved.
                </div>
                <div class="legal-links">
                    <a href="/accessibility.php">Accessibility</a>,
                    <a href="/user-agreement.php">User Agreement</a>,
                    <a href="/privacy.php">Privacy</a>,
                    <a href="/consumer-health-data.php">Consumer Health Data</a>,
                    <a href="/payments-terms.php">Payments Terms of Use</a>,
                    <a href="/cookies.php">Cookies</a>,
                    <a href="/ca-privacy-notice.php">CA Privacy Notice</a>,
                    <a href="/your-privacy-choices.php">Your Privacy Choices <i class="fas fa-shield-alt"></i></a> and
                    <a href="/adchoice.php">AdChoice <i class="fas fa-info-circle"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <style>
        .ebay-footer {
            background-color: #f7f7f7;
            border-top: 1px solid #e5e5e5;
            margin-top: 2rem;
            padding: 2rem 0 1rem;
            font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .footer-main {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-column h3 {
            color: #333;
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 1rem 0;
            border: none;
        }
        
        .footer-column h4 {
            color: #333;
            font-size: 14px;
            font-weight: 600;
            margin: 1.5rem 0 0.5rem 0;
            border: none;
        }
        
        .footer-column ul {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        
        .footer-column li {
            margin: 0 0 0.5rem 0;
        }
        
        .footer-column a {
            color: #666;
            text-decoration: none;
            font-size: 13px;
            line-height: 1.4;
            transition: color 0.2s;
        }
        
        .footer-column a:hover {
            color: #0654ba;
            text-decoration: underline;
        }
        
        .social-links a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .social-links i {
            font-size: 14px;
        }
        
        .country-selector {
            margin-top: 1rem;
        }
        
        .country-dropdown {
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 13px;
            color: #333;
            width: 100%;
            max-width: 200px;
            cursor: pointer;
        }
        
        .footer-bottom {
            border-top: 1px solid #e5e5e5;
            padding-top: 1rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .copyright-text {
            color: #666;
            font-size: 12px;
            font-weight: 400;
        }
        
        .legal-links {
            color: #666;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .legal-links a {
            color: #0654ba;
            text-decoration: none;
        }
        
        .legal-links a:hover {
            text-decoration: underline;
        }
        
        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .footer-main {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .footer-main {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .footer-bottom {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        
        @media (max-width: 480px) {
            .footer-main {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <!-- JavaScript Libraries -->
    <script src="/js/jquery-shim.js"></script>
    <script src="/js/fezamarket.js"></script>
    
    <script>
        $(document).ready(function() {
            // CSRF token for AJAX requests
            $.ajaxSetup({
                beforeSend: function(xhr, settings) {
                    if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type) && !this.crossDomain) {
                        const token = $('meta[name="csrf-token"]').attr('content');
                        if (token) {
                            xhr.setRequestHeader("X-CSRF-TOKEN", token);
                        }
                    }
                }
            });
            
            // Auto-hide flash messages
            $('.alert').each(function() {
                const alert = $(this);
                setTimeout(function() {
                    alert.fadeOut();
                }, 5000);
            });
            
            // Add active class to current page nav link
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.nav-links a');
            
            navLinks.forEach(link => {
                const linkPath = new URL(link.href).pathname;
                if (linkPath === currentPath || (currentPath.startsWith('/category') && link.href.includes('cat='))) {
                    link.classList.add('active');
                }
            });
        });
    </script>

</body>
</html>