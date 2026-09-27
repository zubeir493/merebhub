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
            'title' => 'Bring your software to market',
            'metaDescription' => 'List your Ethiopian software on MerebHub and reach customers who are ready to discover and buy useful tools.',
        ]);
    }

    public function docs(): View
    {
        return view('storefront.developer-docs', [
            'title' => 'MerebHub integration guide',
            'metaDescription' => 'A practical guide to listing licensed software on MerebHub, from application and review to activation and offline support.',
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
            ->with('status', 'Application received. We’ll review your product and reply by email with the next step.');
    }
}
