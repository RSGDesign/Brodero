<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Brodero')</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    @stack('styles')
</head>
<body>
    @include('components.header')

    <main>
        @yield('content')
    </main>

    @include('components.footer')

    @stack('scripts')
    <script>
        // Update cart badge
        (function() {
            async function updateCartBadge() {
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
                } catch (e) {
                    // Badge stays at 0 if not authenticated or error
                }
            }
            @auth
                updateCartBadge();
            @endauth
            window.updateCartBadge = updateCartBadge;
        })();
        
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
</body>
</html>
