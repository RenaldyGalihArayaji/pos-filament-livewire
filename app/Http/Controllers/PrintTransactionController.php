<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PrintTransactionController extends Controller
{
   public function printStruk(Transaction $transaction)
    {
        $transaction->load(['transactionDetails.menus', 'customer', 'paymentMethod','discount']);
        $html = view('print.struk', compact('transaction'))->render();
        $pdf = Pdf::loadHtml($html);
        $pdf->setPaper([0, 0, 226.77, $pdf->getDomPDF()->getCanvas()->get_height()], 'portrait');
        return $pdf->download('struk-transaksi-' . $transaction->code . '.pdf');
    }
}
