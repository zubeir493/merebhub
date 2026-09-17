<nav class="space-y-1" aria-label="Account sections">
    <ul class="m-0 grid list-none gap-1 p-0">
        <li>
            <a href="{{ route('account.settings') }}" class="{{ request()->routeIs('account.settings') ? 'bg-teal-50 text-teal-700' : 'text-zinc-700 hover:bg-zinc-50 hover:text-teal-700' }} flex items-center gap-3 rounded-md px-4 py-4 text-sm font-semibold transition-all" aria-current="{{ request()->routeIs('account.settings') ? 'page' : 'false' }}">
                <x-heroicon-o-user-circle class="size-5 shrink-0" aria-hidden="true" />
                <span>Account settings</span>
            </a>
        </li>
        <li>
            <a href="{{ route('account.orders') }}" class="{{ request()->routeIs('account.orders') ? 'bg-teal-50 text-teal-700' : 'text-zinc-700 hover:bg-zinc-50 hover:text-teal-700' }} flex items-center gap-3 rounded-md px-4 py-4 text-sm font-semibold transition-all" aria-current="{{ request()->routeIs('account.orders') ? 'page' : 'false' }}">
                <x-heroicon-o-gift class="size-5 shrink-0" aria-hidden="true" />
                <span>Previous orders</span>
            </a>
        </li>
        <li>
            <a href="{{ route('account.purchases') }}" class="{{ request()->routeIs('account.purchases') ? 'bg-teal-50 text-teal-700' : 'text-zinc-700 hover:bg-zinc-50 hover:text-teal-700' }} flex items-center gap-3 rounded-md px-4 py-4 text-sm font-semibold transition-all" aria-current="{{ request()->routeIs('account.purchases') ? 'page' : 'false' }}">
                <x-heroicon-o-key class="size-5 shrink-0" aria-hidden="true" />
                <span>Purchases &amp; licenses</span>
            </a>
        </li>
        <li>
            <a href="{{ route('account.wishlist') }}" class="{{ request()->routeIs('account.wishlist') ? 'bg-teal-50 text-teal-700' : 'text-zinc-700 hover:bg-zinc-50 hover:text-teal-700' }} flex items-center gap-3 rounded-md px-4 py-4 text-sm font-semibold transition-all" aria-current="{{ request()->routeIs('account.wishlist') ? 'page' : 'false' }}">
                <x-heroicon-o-heart class="size-5 shrink-0" aria-hidden="true" />
                <span>Wishlist</span>
            </a>
        </li>
        <li>
            <a href="{{ route('account.invoices.index') }}" class="{{ request()->routeIs('account.invoices.*') ? 'bg-teal-50 text-teal-700' : 'text-zinc-700 hover:bg-zinc-50 hover:text-teal-700' }} flex items-center gap-3 rounded-md px-4 py-4 text-sm font-semibold transition-all" aria-current="{{ request()->routeIs('account.invoices.*') ? 'page' : 'false' }}">
                <x-heroicon-o-document-text class="size-5 shrink-0" aria-hidden="true" />
                <span>Invoices</span>
            </a>
        </li>
        <li>
            <a href="{{ route('account.security') }}" class="{{ request()->routeIs('account.security*') ? 'bg-teal-50 text-teal-700' : 'text-zinc-700 hover:bg-zinc-50 hover:text-teal-700' }} flex items-center gap-3 rounded-md px-4 py-4 text-sm font-semibold transition-all" aria-current="{{ request()->routeIs('account.security*') ? 'page' : 'false' }}">
                <x-heroicon-o-shield-check class="size-5 shrink-0" aria-hidden="true" />
                <span>Security &amp; sessions</span>
            </a>
        </li>
        <li>
            <a href="{{ route('account.support.index') }}" class="{{ request()->routeIs('account.support.*') ? 'bg-teal-50 text-teal-700' : 'text-zinc-700 hover:bg-zinc-50 hover:text-teal-700' }} flex items-center gap-3 rounded-md px-4 py-4 text-sm font-semibold transition-all" aria-current="{{ request()->routeIs('account.support.*') ? 'page' : 'false' }}">
                <x-heroicon-o-chat-bubble-left-right class="size-5 shrink-0" aria-hidden="true" />
                <span>Support requests</span>
            </a>
        </li>
        <li>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="flex w-full items-center gap-3 rounded-md px-4 py-4 text-left text-sm font-semibold text-zinc-700 transition-all hover:bg-rose-50 hover:text-rose-700" aria-label="Log out">
                    <x-heroicon-o-arrow-right-start-on-rectangle class="size-5 shrink-0" aria-hidden="true" />
                    <span>Logout</span>
                </button>
            </form>
        </li>
    </ul>
</nav>
