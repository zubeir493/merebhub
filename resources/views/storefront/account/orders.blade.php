@extends('storefront.account.layout')

@section('account-content')
    <header class="account-page-header">
        <h1 class="account-page-title">Order history</h1>
        <p class="account-page-description">Review your completed purchases and open the details you need.</p>
    </header>

    @include('storefront.account.orders-table')
@endsection
