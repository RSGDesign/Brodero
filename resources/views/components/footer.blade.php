<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <h3>Mai multe despre Brodero</h3>
        <p style="color:var(--muted);font-size:14px">Brodero oferă designuri de broderie create cu pasiune și precizie. Inspirăm creatori să transforme fiecare idee într-o lucrare unică.</p>
        <div class="social-links">
          <a href="https://www.facebook.com/Brodero2020/" target="_blank" title="Facebook">f</a>
          <a href="mailto:contact@brodero.online" title="Email">@</a>
          <a href="tel:0741133343" title="Telefon">☎</a>
        </div>
      </div>
      <div>
        <h3>Legal</h3>
        <ul>
          <li><a href="#">Termeni și Condiții</a></li>
          <li><a href="#">Politică de Confidențialitate</a></li>
          <li><a href="#">Politica Cookie</a></li>
          <li><a href="#">Politica de Retur</a></li>
        </ul>
      </div>
      <div>
        <h3>Magazin</h3>
        <ul>
          <li><a href="{{ route('shop.index') }}">Produse</a></li>
          <li><a href="#">Retur</a></li>
        </ul>
      </div>
      <div>
        <h3>Newsletter</h3>
        <p style="color:var(--muted);font-size:14px;margin-bottom:10px">Inspiră-te, descarcă, coase – într-un singur newsletter.</p>
        <form id="subscribeForm">
          <div class="form-row"><input type="email" id="subEmail" placeholder="Adresa de email" required></div>
          <button class="btn" type="submit">Abonează-te</button>
        </form>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2022 - 2025 Toate drepturile rezervate. Brodero.</p>
    </div>
  </div>
</footer>
<script>
  // Newsletter form
  document.getElementById('subscribeForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('subEmail').value.trim();
    if (!email) return alert('Email invalid');
    try {
      const res = await fetch('/api/subscribe', {
        method:'POST',
        headers:{'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
        body:JSON.stringify({email})
      });
      if (res.ok) alert('Mulțumim pentru abonare!');
      else alert('Eroare la abonare');
    } catch { alert('Eroare de rețea'); }
  });
</script>
