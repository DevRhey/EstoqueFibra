</main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<?php $appJsVersion = @filemtime(__DIR__ . '/../../assets/js/app.js') ?: time(); ?>
<script src="assets/js/app.js?v=<?php echo (int) $appJsVersion; ?>"></script>
</body>
</html>
