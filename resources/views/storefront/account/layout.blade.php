@extends('layouts.storefront')

@section('content')
    @php
        $accountSections = [
            ['label' => 'Account settings', 'route' => 'account.settings', 'active' => request()->routeIs('account.settings')],
            ['label' => 'Previous orders', 'route' => 'account.orders', 'active' => request()->routeIs('account.orders')],
            ['label' => 'Purchases & licenses', 'route' => 'account.purchases', 'active' => request()->routeIs('account.purchases')],
            ['label' => 'Downloads', 'route' => 'account.downloads', 'active' => request()->routeIs('account.downloads')],
            ['label' => 'Wishlist', 'route' => 'account.wishlist', 'active' => request()->routeIs('account.wishlist')],
            ['label' => 'Invoices', 'route' => 'account.invoices.index', 'active' => request()->routeIs('account.invoices.*')],
            ['label' => 'Security & sessions', 'route' => 'account.security', 'active' => request()->routeIs('account.security*')],
            ['label' => 'Support requests', 'route' => 'account.support.index', 'active' => request()->routeIs('account.support.*')],
        ];
    @endphp

    <div class="mx-auto max-w-[1400px] px-5 py-12 lg:px-8">
        <div class="mb-8 flex items-center gap-2 lg:hidden" aria-label="Account navigation">
            <label for="account-mobile-section" class="sr-only">Account section</label>
            <select id="account-mobile-section" data-account-route-select class="form-input h-11 flex-1 py-0 font-bold">
                @foreach ($accountSections as $section)
                    <option value="{{ route($section['route']) }}" @selected($section['active'])>{{ $section['label'] }}</option>
                @endforeach
            </select>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="grid size-11 place-items-center rounded-md border border-zinc-300 text-zinc-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700" aria-label="Log out">
                    <x-heroicon-o-arrow-right-start-on-rectangle class="size-5" aria-hidden="true" />
                </button>
            </form>
        </div>
        <div class="flex flex-col gap-8 lg:flex-row">
            <aside class="hidden h-fit w-56 flex-shrink-0 self-start lg:sticky lg:top-24 lg:block" aria-label="Account navigation">
                @include('storefront.account.navigation')
            </aside>

            <div data-account-content class="min-w-0 flex-1">
                @yield('account-content')
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('change', (event) => {
            const routeSelect = event.target.closest('[data-account-route-select]');

            if (routeSelect?.value) {
                window.location.assign(routeSelect.value);
            }
        });
    </script>
@endsection
