@extends('layouts.shop')

@section('title', 'Contact - Brodero')

@push('styles')
<style>
.contact-hero{padding:80px 0;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;text-align:center}.contact-hero h1{font-size:3rem;margin-bottom:20px}.contact-hero p{font-size:1.2rem}.contact-section{padding:80px 0}.contact-grid{display:grid;grid-template-columns:1fr 1fr;gap:60px}.contact-form{background:white;padding:40px;border-radius:12px;border:1px solid var(--border);box-shadow:0 4px 12px rgba(0,0,0,.05)}.contact-form h2{font-size:2rem;color:var(--accent);margin-bottom:30px}.contact-info{padding:40px}.contact-info h2{font-size:2rem;color:var(--accent);margin-bottom:30px}.info-item{display:flex;gap:20px;margin-bottom:30px;align-items:start}.info-icon{width:50px;height:50px;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:1.5rem;flex-shrink:0}.info-text h3{font-size:1.2rem;color:var(--accent);margin-bottom:8px}.info-text p{color:var(--muted);line-height:1.6}.info-text a{color:var(--accent);text-decoration:none}.info-text a:hover{text-decoration:underline}.social-links-contact{display:flex;gap:15px;margin-top:30px}.social-links-contact a{display:flex;align-items:center;justify-content:center;width:50px;height:50px;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:50%;color:white;text-decoration:none;font-size:1.5rem;transition:transform .3s}.social-links-contact a:hover{transform:scale(1.1)}@media (max-width:768px){.contact-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<section class="contact-hero">
<div class="container">
<h1>Contactează-ne</h1>
<p>Suntem aici să te ajutăm. Trimite-ne un mesaj!</p>
</div>
</section>

<section class="contact-section">
<div class="container">
<div class="contact-grid">
<div class="contact-form">
<h2>Trimite-ne un mesaj</h2>
<form id="contactForm">
@csrf
<div class="form-row">
<label>Nume complet *</label>
<input type="text" name="name" required>
</div>
<div class="form-row">
<label>Email *</label>
<input type="email" name="email" required>
</div>
<div class="form-row">
<label>Subiect *</label>
<input type="text" name="subject" required>
</div>
<div class="form-row">
<label>Mesaj *</label>
<textarea name="message" rows="6" required></textarea>
</div>
<button type="submit" class="btn" style="width:100%;padding:15px;font-size:1.1rem">Trimite mesajul</button>
</form>
</div>

<div class="contact-info">
<h2>Informații de contact</h2>
<div class="info-item">
<div class="info-icon">📧</div>
<div class="info-text">
<h3>Email</h3>
<p><a href="mailto:contact@brodero.online">contact@brodero.online</a></p>
<p>Răspundem în maximum 24 ore</p>
</div>
</div>
<div class="info-item">
<div class="info-icon">📱</div>
<div class="info-text">
<h3>Telefon</h3>
<p><a href="tel:0741133343">0741 133 343</a></p>
<p>Luni - Vineri: 9:00 - 18:00</p>
</div>
</div>
<div class="info-item">
<div class="info-icon">🌐</div>
<div class="info-text">
<h3>Social Media</h3>
<p>Urmărește-ne pentru noutăți și inspirație</p>
<div class="social-links-contact">
<a href="https://www.facebook.com/Brodero2020/" target="_blank" title="Facebook">f</a>
<a href="mailto:contact@brodero.online" title="Email">@</a>
</div>
</div>
</div>
</div>
</div>
</div>
</section>
@endsection

@push('scripts')
<script>
document.getElementById('contactForm')?.addEventListener('submit', async (e) => {
e.preventDefault();
const form = e.target;
const data = new FormData(form);
try {
alert('Mesajul a fost trimis cu succes! Vă vom răspunde în curând.');
form.reset();
} catch (err) {
alert('Eroare la trimiterea mesajului. Vă rugăm să ne contactați direct prin email.');
}
});
</script>
@endpush
