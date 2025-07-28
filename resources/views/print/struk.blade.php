<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Transaksi #{{ $transaction->code }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            display: flex;
            justify-content: center;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-size: 11px;
        }
        .receipt-container {
            width: 220px;
            padding: 0 0;
            line-height: 1.4;
            font-size: 11px;
            background-color: #fff;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .header h3 {
            margin: 0;
            font-size: 14px;
        }
        .header p {
            margin: 0;
            font-size: 11px;
        }
        .divider {
            border-bottom: 1px dashed #000;
            margin: 10px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .info-row .label {
            width: 70px;
            text-align: left;
        }
        .info-row .value {
            flex-grow: 1;
            text-align: left;
        }
        .item-list {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5mm;
        }
        .item-list th,
        .item-list td {
            padding: 1mm 0;
            text-align: left;
            vertical-align: top;
        }
        .item-list th {
            border-bottom: 1px dashed #000;
        }
        .item-list td:nth-child(1) {
            width: 40%;
        }
        .item-list td:nth-child(2) {
            width: 15%;
            text-align: center;
        }
        .item-list td:nth-child(3) {
            width: 25%;
            text-align: right;
        }
        .item-list td:nth-child(4) {
            width: 20%;
            text-align: right;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .item-row .description {
            flex-grow: 1;
            text-align: left;
        }
        .item-row .price {
            text-align: right;
            width: 80px;
            flex-shrink: 0;
        }
        .total-section {
            margin-top: 15px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .total-row .value-total {
            white-space: nowrap;
        }
        .grand-total {
            margin-top: 15px;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 11px;
        }
        .text-left{
            text-align: left;
        }
        @media print {
            body {
                background-color: #fff;
                margin: 0;
                padding: 0;
                display: block;
            }
            .receipt-container {
                width: 100%;
                max-width: 300px;
                box-shadow: none;
                border: none;
                padding: 5mm;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header">
            <h3>{{ ucwords(Auth::user()->branch->name) }}</h3>
            <p>{{ ucwords(Auth::user()->branch->address) }}</p>
            <p>Telp: {{ Auth::user()->branch->phone }}</p>
            <p>Email: {{ Auth::user()->branch->email }}</p>
        </div>

        <div class="divider"></div>

        <div class="info-row">
            <span class="label">No. Transaksi</span>
            <span class="value">: {{ $transaction->code }}</span>
        </div>
        <div class="info-row">
            <span class="label">Tanggal</span>
            <span class="value">: {{ $transaction->order_date->format('d/m/Y') }}</span>
        </div>
        <?php if($transaction->table_id != null) :?>
        <div class="info-row">
            <span class="label">No.Meja</span>
            <span class="value">: {{ $transaction->table->table_number ?? 'N/A' }}</span>
        </div>
        <?php endif;?>
        <div class="info-row">
            <span class="label">Pelanggan</span>
            <span class="value">: {{ ucwords($transaction->customer->name) }}</span>
        </div>
        <div class="info-row">
            <span class="label">Kasir</span>
            <span class="value">: {{ ucwords(Auth::user()->name) }}</span>
        </div>

        <div class="divider"></div>

        <table class="item-list">
            <thead>
                <tr>
                    <th>Menu</th>
                    <th>Qty</th>
                    <th>Harga</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transaction->transactionDetails as $detail)
                    <tr>
                        <td style="text-align: left;">{{ ucwords($detail->menus->name) }}</td>
                        <td style="text-align: center;">{{ $detail->quantity }}</td>
                        <td style="text-align: left;">{{ number_format($detail->unit_price, 0, ',', '.') }}</td>
                        <td style="text-align: left;">{{ number_format($detail->total_price, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="divider"></div>

        {{-- Perhitungan --}}
        @php
            $subTotal = 0;

            foreach ($transaction->transactionDetails as $detail) {
                $subTotal += $detail->total_price;
            }

        @endphp

        <div class="total-section">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="text-align: left; padding: 0;">Sub Total</td>
                    <td style="text-align: right; padding: 0;">
                        {{ number_format($subTotal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="text-align: left; padding: 0;">
                        Diskon
                        @if ($transaction->discount)
                            {{ $transaction->discount->type === 'percentage' ? $transaction->discount->value . '%' : '' }}
                        @endif
                    </td>
                    <td style="text-align: right; padding: 0;">
                        @php
                            $discountAmount = 0;
                            if ($transaction->discount) {
                                if ($transaction->discount->type === 'percentage') {
                                    $discountAmount = $subTotal * ($transaction->discount->value / 100);
                                } else {
                                    $discountAmount = $transaction->discount->value;
                                }
                            }
                        @endphp
                        {{ number_format($discountAmount, 0, ',', '.') }}
                    </td>
                </tr>
                <tr>
                    <td style="text-align: left; padding: 0;">Total</td>
                    <td style="text-align: right; padding: 0;">
                        {{ number_format($transaction->total, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="text-align: left; padding: 0;">{{ $transaction->paymentMethod->name ?? 'Tunai' }}</td>
                    <td style="text-align: right; padding: 0;">
                        {{ number_format($transaction->paid_amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="text-align: left; padding: 0;">Kembalian</td>
                    <td style="text-align: right; padding: 0;">
                        {{ number_format($transaction->change_amount, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            Terima Kasih Atas Kunjungan Anda
        </div>
    </div>
</body>

</html>
