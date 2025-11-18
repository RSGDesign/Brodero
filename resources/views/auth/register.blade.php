<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Înregistrare — Brodero</title>
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
  <style>
    .auth-container { max-width:480px; margin:80px auto; padding:0 20px; }
    .auth-box { background:#fff; border:1px solid #dee2e6; border-radius:8px; padding:40px; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
    .auth-box h1 { margin-bottom:10px; font-size:1.8rem; }
    .auth-box p { color:#666; margin-bottom:30px; }
    .form-row { margin-bottom:20px; }
    .form-row label { display:block; margin-bottom:6px; font-weight:500; }
    .form-row input { width:100%; padding:12px; border:1px solid #ced4da; border-radius:4px; font-size:1rem; }
    .form-row input:focus { outline:none; border-color:var(--accent); }
    .btn-primary { width:100%; background:var(--accent); color:#fff; padding:14px; border:none; border-radius:4px; font-size:1.1rem; cursor:pointer; transition:background .3s; }
    .btn-primary:hover { background:var(--accent-dark); }
    .btn-primary:disabled { background:#ccc; cursor:not-allowed; }
    .auth-footer { text-align:center; margin-top:20px; }
    .auth-footer a { color:var(--accent); text-decoration:none; }
    .auth-footer a:hover { text-decoration:underline; }
    .error-msg { background:#f8d7da; color:#721c24; padding:12px; border-radius:4px; margin-bottom:20px; }
    .success-msg { background:#d4edda; color:#155724; padding:12px; border-radius:4px; margin-bottom:20px; }
  </style>
</head>
<body>
  @include('components.header')
  <main>
    <div class="auth-container">
      <div class="auth-box">
        <h1>Înregistrare</h1>
        <p>Creează-ți un cont pentru a accesa comenzile și descărcările tale.</p>
        @if ($errors->any())
        <div class="error-msg">
          @foreach ($errors->all() as $error)
            {{ $error }}<br>
          @endforeach
        </div>
        @endif
        <form method="POST" action="{{ route('register') }}">
          @csrf
          <div class="form-row">
            <label for="name">Nume complet</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Ion Popescu" required>
          </div>
          <div class="form-row">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="email@exemplu.ro" required>
          </div>
          <div class="form-row">
            <label for="password">Parolă</label>
            <input type="password" id="password" name="password" placeholder="Minim 8 caractere" required minlength="8">
          </div>
          <div class="form-row">
            <label for="password_confirmation">Confirmă parola</label>
            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Confirmă parola" required minlength="8">
          </div>
          <button type="submit" class="btn-primary">Înregistrează-te</button>
        </form>
        <div class="auth-footer">
          Ai deja cont? <a href="{{ route('login') }}">Autentifică-te</a>
        </div>
      </div>
    </div>
  </main>
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div>
          <h3>Mai multe despre Brodero</h3>
          <p style="color:var(--muted);font-size:14px">Brodero oferă designuri de broderie create cu pasiune și precizie.</p>
        </div>
        <div>
          <h3>Legal</h3>
          <ul>
            <li><a href="#">Termeni și Condiții</a></li>
            <li><a href="#">Politică de Confidențialitate</a></li>
          </ul>
        </div>
        <div>
          <h3>Magazin</h3>
          <ul>
            <li><a href="{{ route('shop.index') }}">Produse</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; 2022 - 2025 Toate drepturile rezervate. Brodero.</p>
      </div>
    </div>
  </footer>
</body>
</html>
