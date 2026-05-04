    </div><!-- /.container-xl -->
</main><!-- /.mc-main -->

<!-- ═══ FOOTER ══════════════════════════════════════════════════════════════ -->
<footer class="mc-footer">
    <p>© <?php echo date('Y'); ?> <strong>MedChain</strong> — Tous droits réservés.</p>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Auto-dismiss alerts
document.querySelectorAll('.mc-alert').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity .5s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 500);
    }, 4500);
});
</script>

<?php echo $extraJs ?? ''; ?>
</body>
</html>
