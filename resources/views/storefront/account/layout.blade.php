@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-[1400px] px-5 py-12 lg:px-8">
        <div class="mh-account-mobile-nav lg:hidden" aria-label="Account navigation">
            @include('storefront.account.navigation')
        </div>
        <div class="flex flex-col lg:flex-row gap-8">
            <aside class="hidden h-fit w-56 flex-shrink-0 self-start lg:sticky lg:top-24 lg:block" aria-label="Account navigation">
                @include('storefront.account.navigation')
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
