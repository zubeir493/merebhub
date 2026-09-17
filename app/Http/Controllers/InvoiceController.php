<?php

namespace App\Http\Controllers;

use App\Domain\Billing\Actions\EnsureInvoiceSnapshotAction;
use App\Models\InvoiceSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Lunar\Core\Models\Order;

class InvoiceController extends Controller
{
    public function index(Request $request, EnsureInvoiceSnapshotAction $invoiceSnapshots): View
    {
        $orders = $request->user()->orders()
            ->whereNotNull('placed_at')
            ->with(['billingAddress.country', 'currency', 'lines', 'user'])
            ->latest('placed_at')
            ->paginate(12)
            ->withQueryString();

        $invoices = $orders->getCollection()
            ->map(function (Order $order) use ($invoiceSnapshots): InvoiceSnapshot {
                $invoice = $invoiceSnapshots->handle($order);
                $invoice->setRelation('order', $order);

                return $invoice;
            });

        $orders->setCollection($invoices);

        return view('storefront.account.invoices', [
            'invoices' => $orders,
        ]);
    }

    public function show(
        Request $request,
        string $invoiceOrder,
        EnsureInvoiceSnapshotAction $invoiceSnapshots,
    ): View {
        $order = Order::query()
            ->where('public_id', $invoiceOrder)
            ->whereNotNull('placed_at')
            ->firstOrFail();
        abort_unless((int) $order->user_id === (int) $request->user()->getKey(), 404);

        $invoice = $invoiceSnapshots->handle($order);
        abort_unless(Gate::forUser($request->user())->allows('view', $invoice), 404);
        $invoice->setRelation('order', $order);

        return view('storefront.account.invoice', ['invoice' => $invoice]);
    }
}
