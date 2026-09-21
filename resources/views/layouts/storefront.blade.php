<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'MerebHub') }}</title>
    <meta name="description" content="{{ $metaDescription ?? 'Curated Ethiopian software with secure checkout and automatic license delivery.' }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body data-public-shell class="flex min-h-screen flex-col bg-white font-sans text-zinc-900 antialiased">
    @php
        $publicNavigation = [
            ['label' => 'Store', 'route' => 'store.index', 'active' => request()->routeIs('store.*')],
            ['label' => 'Developers', 'route' => 'developers.index', 'active' => request()->routeIs('developers.*')],
            ['label' => 'Contact', 'route' => 'contact.index', 'active' => request()->routeIs('contact.*')],
        ];
    @endphp

    <header x-data="{ mobileOpen: false, accountOpen: false }" class="sticky top-0 z-40 border-b border-zinc-200 bg-white/95 backdrop-blur-sm">
        <div class="mx-auto flex h-18 max-w-[1400px] items-center justify-between gap-5 px-5 lg:px-8">
            <button @click="mobileOpen = ! mobileOpen" :aria-expanded="mobileOpen" class="grid size-10 place-items-center rounded-lg transition hover:bg-zinc-100 lg:hidden" aria-controls="mobile-navigation" aria-label="Toggle menu">
                <x-heroicon-o-bars-3 x-show="! mobileOpen" class="size-6" />
                <x-heroicon-o-x-mark x-cloak x-show="mobileOpen" class="size-6" />
            </button>
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">
                <img src="/images/marketplace/logo.svg" alt="" width="32" height="32" class="h-8 w-8">
                <span class="text-lg font-extrabold">MerebHub</span>
            </a>
            <nav data-desktop-public-navigation class="hidden items-center gap-1 lg:flex" aria-label="Primary navigation">
                @foreach ($publicNavigation as $item)
                    <a href="{{ route($item['route']) }}" @class([
                        'px-3 py-2 text-sm font-bold transition',
                        'text-teal-800' => $item['active'],
                        'text-zinc-600 hover:text-zinc-950' => ! $item['active'],
                    ]) @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endforeach
            </nav>
            <form action="{{ route('store.index') }}" role="search" class="relative ml-auto hidden max-w-xs flex-1 md:block xl:max-w-sm">
                <button type="submit" class="absolute left-0 top-0 grid size-10 place-items-center text-zinc-400 transition-colors hover:text-teal-700" aria-label="Submit search">
                    <x-heroicon-o-magnifying-glass class="size-5" />
                </button>
                <input
                    id="search-input-desktop"
                    name="q"
                    value="{{ request('q') }}"
                    aria-label="Search software, makers, or categories"
                    placeholder="Search software, makers, categories"
                    class="h-10 w-full rounded-md border border-zinc-300 bg-zinc-50 pl-11 pr-12 text-sm outline-none transition focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-500/10"
                >
                <span id="search-shortcut-desktop" role="button" tabindex="0" aria-label="Focus search (shortcut)" class="absolute right-2.5 top-1/2 -translate-y-1/2 cursor-pointer select-none rounded-sm bg-zinc-100 px-2 py-1 text-xs text-zinc-600">Ctrl+K</span>
            </form>
            <div class="flex shrink-0 items-center">
                <a href="{{ route('cart.index') }}" data-mini-cart-toggle class="relative grid size-10 place-items-center rounded-lg hover:bg-zinc-100" aria-label="Cart" aria-haspopup="dialog" aria-expanded="false" title="Cart">
                    <x-heroicon-o-shopping-cart class="size-5" />
                    @if ($headerCartCount)
                        <span data-cart-count class="absolute right-0 top-0 grid size-4 place-items-center rounded-full bg-teal-500 text-[9px] text-teal-950" aria-label="{{ $headerCartCount }} {{ Str::plural('item type', $headerCartCount) }} in cart">{{ $headerCartCount }}</span>
                    @else
                        <span data-cart-count class="absolute right-0 top-0 hidden size-4 place-items-center rounded-full bg-teal-500 text-[9px] text-teal-950"></span>
                    @endif
                </a>
                @auth
                    <div class="relative">
                        <button @click="accountOpen = ! accountOpen" @click.outside="accountOpen = false" class="flex h-10 items-center gap-2 rounded-lg px-2 hover:bg-zinc-100">
                            <x-heroicon-o-user-circle class="size-5" />
                            <span class="hidden text-sm font-bold sm:inline">My Account</span>
                        </button>
                        <div x-cloak x-show="accountOpen" x-transition.origin.top.right class="absolute right-0 mt-2 w-56 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-xl">
                            <div class="border-b border-zinc-100 px-4 py-3">
                                <p class="truncate text-sm font-extrabold">{{ auth()->user()->name }}</p>
                                <p class="truncate text-xs text-zinc-500">{{ auth()->user()->email }}</p>
                            </div>
                            <a href="{{ route('account.purchases') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-bold hover:bg-zinc-50"><x-heroicon-o-key class="size-4" /> Purchased Licenses</a>
                            <a href="{{ route('account.downloads') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-bold hover:bg-zinc-50"><x-heroicon-o-arrow-down-tray class="size-4" /> Downloads</a>
                            <a href="{{ route('account.wishlist') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-bold hover:bg-zinc-50"><x-heroicon-o-bookmark class="size-4" /> Wishlist</a>
                            <a href="{{ route('account.settings') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-bold hover:bg-zinc-50"><x-heroicon-o-cog-6-tooth class="size-4" /> Settings</a>
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
        <div data-mini-cart data-endpoint="{{ route('cart.mini') }}" hidden role="dialog" aria-label="Mini cart" aria-modal="false" class="fixed right-4 top-[4.75rem] z-50 w-[min(24rem,calc(100vw-2rem))] origin-top-right translate-y-2 scale-[.98] rounded-2xl border border-zinc-200 bg-white opacity-0 shadow-2xl shadow-zinc-950/15 transition-[opacity,transform] duration-200 ease-out sm:right-6">
            <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4">
                <div>
                    <h2 class="text-base font-extrabold text-zinc-950">Your cart</h2>
                    <p data-mini-cart-count-label class="mt-0.5 text-xs font-semibold text-zinc-500"></p>
                </div>
                <button type="button" data-mini-cart-close class="grid size-9 place-items-center rounded-lg text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-800" aria-label="Close cart">
                    <x-heroicon-o-x-mark class="size-5" />
                </button>
            </div>
            <div data-mini-cart-items class="max-h-[min(55vh,28rem)] overflow-y-auto"></div>
            <div class="border-t border-zinc-100 bg-zinc-50/70 p-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-semibold text-zinc-600">Total</span>
                    <strong data-mini-cart-total class="text-zinc-950"></strong>
                </div>
                <div class="mt-3 grid grid-cols-1 gap-2">
                    <a href="{{ route('cart.index') }}" class="flex items-center justify-center rounded-lg border border-zinc-300 px-3 py-2.5 text-xs font-extrabold text-zinc-700 transition hover:bg-white">View cart</a>
                    <form method="POST" action="{{ route('checkout.store') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center justify-center rounded-lg bg-zinc-950 px-3 py-2.5 text-xs font-extrabold text-white transition hover:bg-teal-700">Checkout</button>
                    </form>
                </div>
            </div>
        </div>
        <div id="mobile-navigation" x-cloak x-show="mobileOpen" x-transition class="border-t border-zinc-200 bg-white px-5 py-4 lg:hidden">
            <nav data-mobile-public-navigation class="grid gap-1 text-sm font-bold" aria-label="Mobile navigation">
                @foreach ($publicNavigation as $item)
                    <a href="{{ route($item['route']) }}" @click="mobileOpen = false" @class([
                        'px-3 py-2.5 transition',
                        'bg-teal-50 text-teal-800' => $item['active'],
                        'text-zinc-700 hover:bg-zinc-50 hover:text-zinc-950' => ! $item['active'],
                    ]) @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endforeach
            </nav>
            <form action="{{ route('store.index') }}" role="search" class="relative mt-4 border-t border-zinc-100 pt-4">
                <button type="submit" class="absolute left-0 top-4 grid size-11 place-items-center text-zinc-400 transition-colors hover:text-teal-700" aria-label="Submit search">
                    <x-heroicon-o-magnifying-glass class="size-5" />
                </button>
                <input id="search-input-mobile" name="q" value="{{ request('q') }}" aria-label="Search software, makers, or categories" placeholder="Search software, makers, categories" class="h-11 w-full rounded-lg border border-zinc-300 pl-11 pr-12 text-sm">
                <span id="search-shortcut-mobile" role="button" tabindex="0" aria-label="Focus search (shortcut)" class="absolute bottom-2 right-2.5 cursor-pointer select-none rounded-md bg-zinc-100 px-2 py-1 text-xs text-zinc-600">Ctrl+K</span>
            </form>
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

    <main class="flex-1">@yield('content')</main>

    <footer class="relative isolate mt-auto overflow-hidden border-t border-zinc-800 bg-zinc-950 text-zinc-300">
        <div class="mx-auto max-w-[1400px] px-5 lg:px-8">
            <div class="grid gap-10 border-b border-white/10 py-12 lg:grid-cols-[minmax(0,1fr)_minmax(22rem,.7fr)] lg:items-end lg:py-16">
                <div>
                    <div class="flex items-center gap-2.5 text-white">
                        <img src="/images/marketplace/logo.svg" alt="" width="32" height="32" class="size-8">
                        <strong class="text-lg">MerebHub</strong>
                    </div>
                    <p class="mt-8 max-w-[15ch] text-5xl font-extrabold leading-[.92] tracking-[-0.05em] text-white sm:text-6xl">Make room for better tools.</p>
                </div>
                <div class="lg:justify-self-end">
                    <p class="max-w-lg text-sm leading-7 text-zinc-400">A focused marketplace for discovering and owning independent Ethiopian software—with clear pricing, trusted publishers, and purchases kept in one place.</p>
                    <div class="mt-7 flex flex-wrap gap-3">
                        <a href="{{ route('store.index') }}" class="group inline-flex items-center gap-2 rounded-lg bg-teal-300 px-4 py-3 text-sm font-extrabold text-teal-950 transition hover:bg-teal-200">Browse marketplace <x-heroicon-o-arrow-up-right class="size-4 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" /></a>
                        <a href="{{ route('developers.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-white/15 px-4 py-3 text-sm font-extrabold text-white transition hover:border-teal-300/60 hover:text-teal-200">For developers</a>
                    </div>
                </div>
            </div>

            <div class="grid gap-10 border-b border-white/10 py-12 sm:grid-cols-2 lg:grid-cols-[1.35fr_.8fr_.8fr_.8fr]">
                <div class="max-w-xs">
                    <p class="text-sm leading-7 text-zinc-400">Find practical tools from local makers and teams building for work that happens here.</p>
                    <span class="mt-6 inline-flex items-center gap-2 text-xs font-bold text-zinc-500"><span class="size-1.5 rounded-full bg-teal-300"></span> Independent software · ETB checkout</span>
                </div>
                <div>
                    <strong class="text-sm text-white">Discover</strong>
                    <div class="mt-4 grid gap-3 text-sm text-zinc-400">
                        <a href="{{ route('store.index') }}" class="transition hover:text-white">Browse all</a>
                        <a href="{{ route('store.newarrivals') }}" class="transition hover:text-white">New arrivals</a>
                        <a href="{{ route('store.bestsellers') }}" class="transition hover:text-white">Best sellers</a>
                        <a href="{{ route('store.deals') }}" class="transition hover:text-white">Deals</a>
                    </div>
                </div>
                <div>
                    <strong class="text-sm text-white">Community</strong>
                    <div class="mt-4 grid gap-3 text-sm text-zinc-400">
                        <a href="{{ route('developers.index') }}" class="transition hover:text-white">Sell on MerebHub</a>
                        <a href="{{ route('vendors.index') }}" class="transition hover:text-white">Developer directory</a>
                        <a href="{{ route('contact.index') }}" class="transition hover:text-white">Contact</a>
                    </div>
                </div>
                <div>
                    <strong class="text-sm text-white">Your account</strong>
                    <div class="mt-4 grid gap-3 text-sm text-zinc-400">
                        <a href="{{ route('account.purchases') }}" class="transition hover:text-white">Purchases &amp; licenses</a>
                        <a href="{{ route('account.support.index') }}" class="transition hover:text-white">Support requests</a>
                        <a href="{{ route('account.settings') }}" class="transition hover:text-white">Settings</a>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3 py-6 text-xs font-medium text-zinc-500 sm:flex-row sm:items-center sm:justify-between">
                <span>&copy; {{ now()->year }} MerebHub. Built for Ethiopian software.</span>
                <span class="flex items-center gap-2"><span class="size-1.5 rounded-full bg-teal-400"></span>Checkout secured by Chapa</span>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
