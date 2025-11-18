<header class="header">
  <div class="container" style="display:flex;align-items:center;justify-content:space-between">
    <div class="logo"><a href="{{ route('home') }}"><img src="{{ asset('logo.svg') }}" alt="Brodero" style="height:50px" onerror="this.style.display='none';this.parentElement.textContent='Brodero'"></a></div>
    <nav class="nav">
      <a href="{{ route('home') }}">Acasă</a>
      <a href="{{ route('about') }}">Despre Noi</a>
      <a href="{{ route('shop.index') }}">Magazin</a>
      <a href="{{ route('contact') }}">Contact</a>
      <a href="{{ route('cart.index') }}" class="icon-link" title="Coș de cumpărături">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="20" cy="21" r="1"></circle>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        <span class="cart-badge" id="cartBadge">0</span>
      </a>
      @auth
        <a href="{{ route('account') }}" class="icon-link" title="Contul meu">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </a>
      @else
        <a href="{{ route('login') }}" class="icon-link" title="Autentificare">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </a>
      @endauth
    </nav>
  </div>
</header>
@auth
<script>
(async function updateCartBadge() {
  try {
    const res = await fetch('{{ route("cart.index") }}', { credentials: 'include', headers: {'Accept': 'application/json'} });
    if (res.ok) {
      const data = await res.json();
      const badge = document.getElementById('cartBadge');
      if (badge && data.items) {
        const count = data.items.reduce((sum, item) => sum + item.quantity, 0);
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-block' : 'none';
      }
    }
  } catch (e) {}
})();
</script>
@endauth
