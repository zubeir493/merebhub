@extends('layouts.storefront')

@section('content')
    <div class="mx-auto max-w-[1400px] px-5 py-12 lg:px-8">
        <div class="flex flex-col gap-8">
            <section>
                <p class="text-sm font-extrabold uppercase text-teal-700">Your account</p>
                <h1 class="mt-2 text-4xl font-extrabold tracking-tight">Account Options</h1>
                <p class="mt-2 text-zinc-600">Select what you'd like to manage</p>
            </section>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <a href="{{ route('account.orders') }}" class="group">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm transition-all hover:border-teal-200 hover:shadow-md">
                        <div class="flex items-center gap-4">
                            <div class="grid size-12 place-items-center rounded-xl bg-zinc-50 group-hover:bg-teal-50">
                                <x-heroicon-o-receipt-percent class="size-6 text-zinc-600 group-hover:text-teal-700" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-zinc-900 group-hover:text-teal-700">Previous Orders</h2>
                                <p class="mt-1 text-sm text-zinc-600">View your order history</p>
                            </div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('account.purchases') }}" class="group">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm transition-all hover:border-teal-200 hover:shadow-md">
                        <div class="flex items-center gap-4">
                            <div class="grid size-12 place-items-center rounded-xl bg-zinc-50 group-hover:bg-teal-50">
                                <x-heroicon-o-shopping-bag class="size-6 text-zinc-600 group-hover:text-teal-700" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-zinc-900 group-hover:text-teal-700">Purchases</h2>
                                <p class="mt-1 text-sm text-zinc-600">Access your purchased licenses and downloads</p>
                            </div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('account.subscriptions') }}" class="group">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm transition-all hover:border-teal-200 hover:shadow-md">
                        <div class="flex items-center gap-4">
                            <div class="grid size-12 place-items-center rounded-xl bg-zinc-50 group-hover:bg-teal-50">
                                <x-heroicon-o-arrow-path-rounded-square class="size-6 text-zinc-600 group-hover:text-teal-700" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-zinc-900 group-hover:text-teal-700">Subscriptions</h2>
                                <p class="mt-1 text-sm text-zinc-600">Manage your active subscriptions</p>
                            </div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('account.settings') }}" class="group">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm transition-all hover:border-teal-200 hover:shadow-md">
                        <div class="flex items-center gap-4">
                            <div class="grid size-12 place-items-center rounded-xl bg-zinc-50 group-hover:bg-teal-50">
                                <x-heroicon-o-user-circle class="size-6 text-zinc-600 group-hover:text-teal-700" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-zinc-900 group-hover:text-teal-700">Account Settings</h2>
                                <p class="mt-1 text-sm text-zinc-600">Edit your profile details</p>
                            </div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('wishlist.index') }}" class="group">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm transition-all hover:border-teal-200 hover:shadow-md">
                        <div class="flex items-center gap-4">
                            <div class="grid size-12 place-items-center rounded-xl bg-zinc-50 group-hover:bg-teal-50">
                                <x-heroicon-o-heart class="size-6 text-zinc-600 group-hover:text-teal-700" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-zinc-900 group-hover:text-teal-700">Wishlist</h2>
                                <p class="mt-1 text-sm text-zinc-600">View your saved items</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
@endsection