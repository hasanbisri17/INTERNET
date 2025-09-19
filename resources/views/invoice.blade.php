<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $payment->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .invoice-details {
            margin-bottom: 30px;
        }
        .customer-details {
            float: left;
            width: 50%;
        }
        .invoice-info {
            float: right;
            width: 50%;
            text-align: right;
        }
        .clear {
            clear: both;
        }
        .invoice-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .invoice-items th, .invoice-items td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        .invoice-items th {
            background-color: #f5f5f5;
        }
        .total {
            text-align: right;
            margin-top: 20px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
        }
        .notes {
            margin-top: 30px;
            font-size: 12px;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        @if(\App\Models\Setting::get('invoice_logo'))
            <div style="text-align: center; margin-bottom: 15px;">
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->path(\App\Models\Setting::get('invoice_logo')) }}" 
                     alt="Logo Perusahaan" 
                     style="max-height: 80px; max-width: 200px;" />
            </div>
        @endif
        <div class="invoice-title">INVOICE</div>
        <div>{{ config('app.name') }}</div>
    </div>

    <div class="invoice-details">
        <div class="customer-details">
            <strong>Ditagihkan kepada:</strong><br>
            {{ $payment->customer->name }}<br>
            {{ $payment->customer->address }}<br>
            {{ $payment->customer->phone }}
        </div>
        <div class="invoice-info">
            <strong>No. Invoice:</strong> {{ $payment->invoice_number }}<br>
            <strong>Tanggal:</strong> {{ $payment->created_at->format('d/m/Y') }}<br>
            <strong>Jatuh Tempo:</strong> {{ $payment->due_date->format('d/m/Y') }}<br>
            <strong>Status:</strong> {{ ucfirst($payment->status) }}
        </div>
        <div class="clear"></div>
    </div>

    <table class="invoice-items">
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $payment->internetPackage->name }} - Layanan Internet</td>
                <td style="text-align: right">Rp {{ number_format($payment->amount, 2) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <th style="text-align: right">Total</th>
                <th style="text-align: right">Rp {{ number_format($payment->amount, 2) }}</th>
            </tr>
        </tfoot>
    </table>

    @if(\App\Models\Setting::get('invoice_notes'))
    <div class="notes">
        <strong>Catatan:</strong><br>
        {!! \App\Models\Setting::get('invoice_notes') !!}
    </div>
    @endif

    <div class="footer">
        {!! \App\Models\Setting::get('invoice_footer', 'Terima kasih atas pembayaran Anda.') !!}
    </div>

    @if($payment->payment_date)
    <div class="payment-info">
        <strong>Informasi Pembayaran:</strong><br>
        Tanggal Pembayaran: {{ $payment->payment_date->format('d/m/Y') }}<br>
        Metode Pembayaran: {{ $payment->paymentMethod->name ?? '-' }}
    </div>
    @endif

    <div class="footer">
        Terima kasih atas kepercayaan Anda menggunakan layanan kami.
    </div>
</body>
</html>
