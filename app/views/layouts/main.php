<?php
/**
 * Main Layout Template
 * AI Online Exam & Proctoring System
 */

require_once __DIR__ . '/header.php';
?>

<div class="app-wrapper">
    <!-- Sidebar Navigation -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Container -->
    <div class="app-main">
        <!-- Top Navbar -->
        <?php require_once __DIR__ . '/navbar.php'; ?>

        <!-- Content Area -->
        <main class="app-content">
            <?php 
            if (isset($contentView) && file_exists($contentView)) {
                require_once $contentView;
            } elseif (isset($viewContent)) {
                echo $viewContent;
            }
            ?>
        </main>

        <!-- Footer -->
        <?php require_once __DIR__ . '/footer.php'; ?>
    </div>
</div>
