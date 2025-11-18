<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Autentificare — Brodero</title>
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
        <h1>Autentificare</h1>
        <p>Intră în cont pentru a accesa comenzile și descărcările tale.</p>
        @if (session('status'))
        <div class="success-msg">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
        <div class="error-msg">
          @foreach ($errors->all() as $error)
            {{ $error }}
          @endforeach
        </div>
        @endif
        <form method="POST" action="{{ route('login') }}">
          @csrf
          <div class="form-row">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="email@exemplu.ro" required autofocus>
          </div>
          <div class="form-row">
            <label for="password">Parolă</label>
            <input type="password" id="password" name="password" placeholder="Parola ta" required>
          </div>
          <button type="submit" class="btn-primary">Autentifică-te</button>
        </form>
        <div class="auth-footer">
          Nu ai cont? <a href="{{ route('register') }}">Înregistrează-te</a>
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
