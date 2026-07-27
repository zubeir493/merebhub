@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-[1400px] px-5 py-12 lg:px-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <aside class="hidden lg:block w-56 flex-shrink-0 self-start h-fit lg:sticky lg:top-24" aria-label="Account navigation">
                <nav class="space-y-1" aria-label="Account sections">
                    <a href="{{ route('account.settings') }}"
                       class="{{ request()->routeIs('account.settings') ? 'bg-teal-50 text-teal-700' : 'text-zinc-700 hover:bg-zinc-50 hover:text-teal-700' }}
                           flex items-center gap-3 rounded-md px-4 py-4 text-sm font-semibold transition-all"
                       aria-current="{{ request()->routeIs('account.settings') ? 'page' : 'false' }}">
                        <x-heroicon-o-user-circle class="size-5 shrink-0" aria-hidden="true" />
                        <span>Account Settings</span>
                    </a>
                    <a href="{{ route('wishlist.index') }}"
                       class="{{ request()->routeIs('wishlist.index') ? 'bg-teal-50 text-teal-700' : 'text-zinc-700 hover:bg-zinc-50 hover:text-teal-700' }}
                           flex items-center gap-3 rounded-md px-4 py-4 text-sm font-semibold transition-all"
                       aria-current="{{ request()->routeIs('wishlist.index') ? 'page' : 'false' }}">
                        <x-heroicon-o-heart class="size-5 shrink-0" aria-hidden="true" />
                        <span>Wishlist</span>
                    </a>
                    <a href="{{ route('account.orders') }}"
                       class="{{ request()->routeIs('account.orders') ? 'bg-teal-50 text-teal-700' : 'text-zinc-700 hover:bg-zinc-50 hover:text-teal-700' }}
                           flex items-center gap-3 rounded-md px-4 py-4 text-sm font-semibold transition-all"
                       aria-current="{{ request()->routeIs('account.orders') ? 'page' : 'false' }}">
                        <x-heroicon-o-gift class="size-5 shrink-0" aria-hidden="true" />
                        <span>Orders</span>
                    </a>
                    <a href="{{ route('account.subscriptions') }}"
                       class="{{ request()->routeIs('account.subscriptions') ? 'bg-teal-50 text-teal-700' : 'text-zinc-700 hover:bg-zinc-50 hover:text-teal-700' }}
                           flex items-center gap-3 rounded-md px-4 py-4 text-sm font-semibold transition-all"
                       aria-current="{{ request()->routeIs('account.subscriptions') ? 'page' : 'false' }}">
                        <x-heroicon-o-arrow-path-rounded-square class="size-5 shrink-0" aria-hidden="true" />
                        <span>Subscriptions</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        <button 
                        class="flex items-center gap-3 rounded-md px-4 py-4 text-sm font-semibold transition-all w-full hover:bg-rose-50 hover:text-rose-700"
                        aria-current="{{ request()->routeIs('account.subscriptions') ? 'page' : 'false' }}">
                            <x-heroicon-o-arrow-right-start-on-rectangle class="size-5 shrink-0" aria-hidden="true" />
                            <span>Logout</span>
                        </button>
                    </form>
                </nav>
            </aside>

            <main class="flex-1 min-w-0">
                @yield('account-content')
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('accountSidebar', () => ({
                mobileOpen: false,
                activeTab: '{{ request()->route()->getName() }}',
            }));
        });
    </script>
@endsection