</main>
<!-- /.main content -->

<footer style="background:var(--navy);padding:36px 0 20px;margin-top:60px;">
    <div style="max-width:1240px;margin:0 auto;padding:0 28px;">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,var(--green),var(--green-dark));display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-plus-square-fill" style="color:#fff;font-size:15px;"></i>
                </div>
                <span style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700;color:#fff;">Med<span style="color:var(--green);">Chain</span></span>
            </div>
            <div style="display:flex;gap:20px;">
                <a href="/midchaine/index1.php?office=front&controller=objet&action=list"
                   style="color:rgba(255,255,255,.6);font-size:13.5px;transition:color .2s;"
                   onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.6)'">
                    Catalogue
                </a>
                <a href="/midchaine/index1.php?office=front&controller=pret&action=myLoans"
                   style="color:rgba(255,255,255,.6);font-size:13.5px;transition:color .2s;"
                   onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.6)'">
                    Mes prêts
                </a>
                <a href="/midchaine/views/frontoffice/auth/profile.php"
                   style="color:rgba(255,255,255,.6);font-size:13.5px;transition:color .2s;"
                   onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.6)'">
                    Mon profil
                </a>
            </div>
        </div>
        <div style="border-top:1px solid rgba(255,255,255,.08);padding-top:16px;text-align:center;color:rgba(255,255,255,.4);font-size:13px;">
            © <?php echo date('Y'); ?> <strong style="color:var(--green);">MedChain</strong>. Tous droits réservés.
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
