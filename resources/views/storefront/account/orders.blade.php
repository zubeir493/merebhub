@extends('storefront.account.layout')

@section('account-content')
    <header class="account-page-header">
        <h1 class="account-page-title">Previous orders</h1>
        <p class="account-page-description">View and manage your completed orders.</p>
    </header>

    @include('storefront.account.orders-table')
@endsection
