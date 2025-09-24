<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'Seasonal Sales';
includeHeader($page_title);
?>

<div class="container">
    <div class="row justify-center">
        <div class="col-10">
            <div class="card mt-4">
                <div class="card-body">
                    <h1 class="card-title">Seasonal Sales</h1>
                    <p>Seasonal Sales and Events - Special offers, holiday deals, and promotional events.</p>
                    <p>This page is currently being developed. Please check back soon for more information.</p>
                    <p>For immediate assistance, please <a href="/contact.php">contact us</a>.</p>
                    <p><strong>Last Updated:</strong> <?php echo date('F d, Y'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php includeFooter(); ?>