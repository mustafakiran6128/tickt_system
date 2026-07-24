<?php
// C:\laragon\www\destek_as\includes\footer.php
$base_url = '/destek_as';
?>
    <?php if (isLoggedIn()): ?>
        </main>
        <footer style="padding: 20px 40px; border-top: 1px solid var(--border-color); text-align: center; font-size: 12px; color: var(--text-muted); background: rgba(0,0,0,0.15);">
            &copy; <?php echo date('Y'); ?> Destek A.Ş. Kurumsal Destek ve Ticket Yönetim Sistemi. Tüm hakları saklıdır. Giresun Teknopark.
        </footer>
    </div> <!-- .main-wrapper -->
    <?php endif; ?>
</div> <!-- .app-container -->

<!-- Core JS Assets -->
<script src="<?php echo $base_url; ?>/assets/js/app.js"></script>
</body>
</html>
