@extends('storefront.account.layout')

@section('account-content')
    <header class="mb-8">
        <h1 class="text-4xl font-extrabold tracking-tight">Previous Orders</h1>
        <p class="mt-2 text-zinc-600">View and manage your completed orders.</p>
    </header>

    @include('storefront.account.orders-table')
@endsection