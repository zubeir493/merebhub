<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'MerebHub') }}</title>
    <meta name="description" content="{{ $metaDescription ?? 'Curated Ethiopian software with secure checkout and automatic license delivery.' }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-white font-sans text-zinc-900 antialiased">
    <header x-data="{ mobileOpen: false, accountOpen: false }" class="sticky top-0 z-40 border-b border-zinc-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-18 max-w-[1500px] items-center justify-between gap-5 px-5 lg:px-8">
            <button @click="mobileOpen = ! mobileOpen" class="grid size-10 place-items-center lg:hidden" aria-label="Toggle menu">
                <x-heroicon-o-bars-3 class="size-6" />
            </button>
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">
                <img src="/images/marketplace/logo.svg" alt="" width="32" height="32" class="h-8 w-8">
                <span class="text-lg font-extrabold">MerebHub</span>
            </a>
            <form action="{{ route('search') }}" role="search" class="relative ml-auto hidden max-w-sm flex-1 md:block">
                <button type="submit" class="absolute left-0 top-0 grid size-10 place-items-center text-zinc-400 transition-colors hover:text-teal-700" aria-label="Submit search">
                    <x-heroicon-o-magnifying-glass class="size-5" />
                </button>
                <input
                    id="search-input-desktop"
                    name="q"
                    value="{{ request('q') }}"
                    aria-label="Search MerebHub"
                    placeholder="Search..."
                    class="h-10 w-full rounded-lg border border-zinc-300 bg-zinc-50 pl-11 pr-12 text-sm outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10"
                >
                <span id="search-shortcut-desktop" role="button" tabindex="0" aria-label="Focus search (shortcut)" style="position: absolute;right: 10px;" class="top-1/2 -translate-y-1/2 rounded-sm bg-zinc-100 px-2 py-1 text-xs text-zinc-600 cursor-pointer select-none">Ctrl+K</span>
            </form>
            <div class="flex shrink-0 items-center gap-1 sm:gap-2">
                <a href="{{ route('wishlist.index') }}" class="relative grid size-10 place-items-center rounded-lg hover:bg-zinc-100" aria-label="Wishlist" title="Wishlist">
                    @if ($headerWishlistCount)
                        <x-heroicon-s-heart class="size-5" />
                    @else
                        <x-heroicon-o-heart class="size-5" />
                    @endif
                </a>
                <a href="{{ route('cart.index') }}" class="relative grid size-10 place-items-center rounded-lg hover:bg-zinc-100" aria-label="Cart" title="Cart">
                    <x-heroicon-o-shopping-cart class="size-5" />
                    @if ($headerCartCount)
                        <span class="absolute right-0 top-0 grid size-4 place-items-center rounded-full bg-teal-500 text-[9px] text-zinc-950" aria-label="{{ $headerCartCount }} {{ Str::plural('item type', $headerCartCount) }} in cart">{{ $headerCartCount }}</span>
                    @endif
                </a>
                @auth
                    <div class="relative">
                        <button @click="accountOpen = ! accountOpen" @click.outside="accountOpen = false" class="flex h-10 items-center gap-2 rounded-lg px-2 hover:bg-zinc-100">
                            <x-heroicon-o-user-circle class="size-5" />
                            <span class="hidden text-sm font-bold sm:inline">My Account</span>
                            <x-heroicon-o-chevron-down class="hidden size-3.5 sm:block" />
                        </button>
                        <div x-cloak x-show="accountOpen" x-transition.origin.top.right class="absolute right-0 mt-2 w-56 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-xl">
                            <div class="border-b border-zinc-100 px-4 py-3">
                                <p class="truncate text-sm font-extrabold">{{ auth()->user()->name }}</p>
                                <p class="truncate text-xs text-zinc-500">{{ auth()->user()->email }}</p>
                            </div>
                            <a href="{{ route('account.orders') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-bold hover:bg-zinc-50"><x-heroicon-o-receipt-percent class="size-4" /> Previous Orders</a>
                            <a href="{{ route('account.subscriptions') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-bold hover:bg-zinc-50"><x-heroicon-o-arrow-path-rounded-square class="size-4" /> Subscriptions</a>
                            <a href="{{ route('account.settings') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-bold hover:bg-zinc-50"><x-heroicon-o-cog-6-tooth class="size-4" /> Account settings</a>
                            <a href="{{ route('submissions.create') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-bold hover:bg-zinc-50"><x-heroicon-o-shopping-bag class="size-4" /> Sell software</a>
                            @if (auth()->user()->is_admin)
                                <a href="{{ url('/admin') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-bold hover:bg-zinc-50"><x-heroicon-o-adjustments-horizontal class="size-4" /> Submission admin</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}" class="border-t border-zinc-100">
                                @csrf
                                <button class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm font-bold text-rose-600 hover:bg-rose-50"><x-heroicon-o-arrow-right-start-on-rectangle class="size-4" /> Logout</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="flex h-10 items-center gap-2 rounded-lg px-2 hover:bg-zinc-100">
                        <x-heroicon-o-user-circle class="size-5" />
                        <span class="hidden text-sm font-bold sm:inline">Login</span>
                    </a>
                @endauth
            </div>
        </div>
        <div x-cloak x-show="mobileOpen" x-transition class="border-t border-zinc-200 bg-white px-5 py-4 lg:hidden">
            <form action="{{ route('search') }}" role="search" class="relative mb-4">
                <button type="submit" class="absolute left-0 top-0 grid size-11 place-items-center text-zinc-400 transition-colors hover:text-teal-700" aria-label="Submit search">
                    <x-heroicon-o-magnifying-glass class="size-5" />
                </button>
                <input id="search-input-mobile" name="q" value="{{ request('q') }}" aria-label="Search MerebHub" placeholder="Search software" class="h-11 w-full rounded-lg border border-zinc-300 pl-11 pr-12 text-sm">
                <span id="search-shortcut-mobile" role="button" tabindex="0" aria-label="Focus search (shortcut)" style="position: absolute;right: 10px;" class="top-1/2 -translate-y-1/2 rounded-md bg-zinc-100 px-2 py-1 text-xs text-zinc-600 cursor-pointer select-none">Ctrl+K</span>
            </form>
            <nav class="grid gap-1 text-sm font-bold">
                <a href="{{ route('home') }}" class="rounded-lg px-3 py-2 hover:bg-zinc-100">Discover</a>
                <a href="{{ route('store.index') }}" class="rounded-lg px-3 py-2 hover:bg-zinc-100">Store</a>
                <a href="{{ route('store.newarrivals') }}" class="rounded-lg px-3 py-2 hover:bg-zinc-100">New arrivals</a>
                <a href="{{ route('store.bestsellers') }}" class="rounded-lg px-3 py-2 hover:bg-zinc-100">Best sellers</a>
                <a href="{{ route('store.deals') }}" class="rounded-lg px-3 py-2 hover:bg-zinc-100">Deals</a>
                <a href="{{ route('vendors.index') }}" class="rounded-lg px-3 py-2 hover:bg-zinc-100">Developers</a>
                <a href="{{ route('submissions.create') }}" class="rounded-lg px-3 py-2 hover:bg-zinc-100">Sell software</a>
                <a href="{{ route('wishlist.index') }}" class="rounded-lg px-3 py-2 hover:bg-zinc-100">Wishlist</a>
                <a href="{{ route('cart.index') }}" class="rounded-lg px-3 py-2 hover:bg-zinc-100">Cart</a>
            </nav>
        </div>
    </header>

    @if (session('status') || $errors->any())
        <div class="pointer-events-none fixed right-4 top-20 z-50 flex w-[calc(100%-2rem)] max-w-sm flex-col gap-2 sm:right-6">
            @if (session('status'))
                <x-toast :message="session('status')" />
            @endif

            @if ($errors->any())
                <x-toast :message="$errors->first()" type="error" :duration="7000" />
            @endif
        </div>
    @endif

    <main>@yield('content')</main>

    <footer class="mt-16 border-t border-zinc-200 bg-zinc-950 text-zinc-300">
        <div class="mx-auto grid max-w-[1500px] gap-8 px-5 py-10 sm:grid-cols-2 lg:grid-cols-4 lg:px-8">
            <div>
                <div class="flex items-center gap-2 text-white">
                    <img src="/images/marketplace/logo.svg" alt="" width="32" height="32" class="h-8 w-8">
                    <strong>MerebHub</strong>
                </div>
                <p class="mt-4 text-sm leading-6 text-zinc-400">Independent Ethiopian digital products, reviewed and ready to use.</p>
            </div>
            <div><strong class="text-sm text-white">Marketplace</strong><div class="mt-3 grid gap-2 text-sm"><a href="{{ route('store.index') }}">Browse all</a><a href="{{ route('store.newarrivals') }}">New arrivals</a><a href="{{ route('store.deals') }}">Deals</a></div></div>
            <div><strong class="text-sm text-white">Developers</strong><div class="mt-3 grid gap-2 text-sm"><a href="{{ route('submissions.create') }}">Submit software</a><a href="{{ route('orders.lookup') }}">Order lookup</a></div></div>
            <div><strong class="text-sm text-white">Your account</strong><div class="mt-3 grid gap-2 text-sm"><a href="{{ route('account.orders') }}">Previous orders</a><a href="{{ route('wishlist.index') }}">Wishlist</a><a href="{{ route('cart.index') }}">Cart</a></div></div>
        </div>
    </footer>

    <script>
        (function () {
            const isMac = (typeof navigator !== 'undefined') && (/Mac|iPhone|iPad|iPod/.test(navigator.platform) || navigator.userAgent.includes('Macintosh'));
            const label = isMac ? '⌘+K' : 'Ctrl+K';

            const pairs = [
                { inputId: 'search-input-desktop', badgeId: 'search-shortcut-desktop' },
                { inputId: 'search-input-mobile', badgeId: 'search-shortcut-mobile' }
            ];

            pairs.forEach(({ inputId, badgeId }) => {
                const input = document.getElementById(inputId);
                const badge = document.getElementById(badgeId);
                if (!badge) return;
                badge.textContent = label;
                if (!input) return;
                badge.addEventListener('click', function (e) {
                    e.preventDefault();
                    input.focus();
                });
                badge.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        input.focus();
                    }
                });
            });

            document.addEventListener('keydown', function (e) {
                const key = (e.key || '').toLowerCase();
                if (key === 'k' && (isMac ? e.metaKey : e.ctrlKey)) {
                    // prevent browser default "find" / search behaviors
                    e.preventDefault();
                    const desktop = document.getElementById('search-input-desktop');
                    const mobile = document.getElementById('search-input-mobile');
                    (desktop || mobile)?.focus();
                }
            });
        })();
    </script>
    @livewireScripts
</body>
</html>
