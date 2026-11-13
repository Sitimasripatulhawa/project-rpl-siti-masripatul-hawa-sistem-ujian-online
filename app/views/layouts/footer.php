    <!-- Application Footer -->
    <footer class="app-footer text-center text-md-start">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <div>
                &copy; <?= date('Y'); ?> <strong><?= APP_NAME; ?></strong>. Hak Cipta Dilindungi.
            </div>
            <div class="text-xs">
                Sistem Ujian Online & Pengawasan Cerdas (AI Assisted)
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5.3.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <!-- Custom Application JS -->
    <script src="<?= BASE_URL; ?>/public/js/app.js"></script>

    <!-- AI Chatbot Client Script -->
    <script src="<?= BASE_URL; ?>/public/js/chatbot.js"></script>
</body>
</html>
