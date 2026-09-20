<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeveloperApplicationRequest;
use App\Models\MerchantApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DeveloperController extends Controller
{
    public function index(): View
    {
        return view('storefront.developers', [
            'title' => 'Sell on MerebHub',
            'metaDescription' => 'Bring your software to MerebHub and reach customers looking for products from Ethiopian developers.',
        ]);
    }

    public function store(StoreDeveloperApplicationRequest $request): RedirectResponse
    {
        MerchantApplication::query()->create([
            'user_id' => $request->user()?->getAuthIdentifier(),
            'submitted_data' => $request->validated(),
            'submitted_at' => now(),
        ]);

        return redirect()
            ->to(route('developers.index').'#apply')
            ->with('status', 'Application received. We’ll review your product and get back to you by email.');
    }
}
