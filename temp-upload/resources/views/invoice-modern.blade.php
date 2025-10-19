<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $payment->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.5;
            padding: 20px;
        }
        
        /* Header */
        .header-table {
            width: 100%;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #5B6FFF;
        }
        
        .company-name {
            color: #5B6FFF;
            font-size: 24pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .company-info {
            font-size: 9pt;
            color: #666;
        }
        
        .invoice-title {
            text-align: right;
            font-size: 36pt;
            font-weight: bold;
            color: #5B6FFF;
        }
        
        .invoice-number {
            text-align: right;
            font-size: 11pt;
            color: #666;
            font-weight: bold;
        }
        
        /* Info Boxes */
        .info-table {
            width: 100%;
            margin-bottom: 30px;
        }
        
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #5B6FFF;
            vertical-align: top;
        }
        
        .info-title {
            color: #5B6FFF;
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .customer-name {
            font-size: 14pt;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .info-text {
            font-size: 9pt;
            color: #666;
            line-height: 1.6;
        }
        
        .info-label {
            color: #666;
            font-weight: normal;
        }
        
        .info-value {
            color: #333;
            font-weight: bold;
        }
        
        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table thead th {
            background: #5B6FFF;
            color: white;
            padding: 12px 10px;
            text-align: left;
            font-weight: bold;
            font-size: 10pt;
        }
        
        .items-table tbody td {
            padding: 12px 10px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 9pt;
        }
        
        .items-table tbody tr:last-child td {
            border-bottom: 2px solid #5B6FFF;
        }
        
        /* Summary */
        .summary-container {
            width: 100%;
            margin-bottom: 30px;
        }
        
        .summary-table {
            width: 350px;
            float: right;
            background: #f8f9fa;
            padding: 15px;
        }
        
        .summary-table td {
            padding: 8px 0;
            font-size: 10pt;
        }
        
        .summary-total {
            border-top: 2px solid #5B6FFF;
            padding-top: 10px;
            font-size: 14pt;
            font-weight: bold;
        }
        
        .summary-grand {
            background: #5B6FFF;
            color: white;
            padding: 10px;
            font-size: 16pt;
            font-weight: bold;
        }
        
        /* Status Badge */
        .status-badge {
            padding: 5px 15px;
            border-radius: 3px;
            font-size: 9pt;
            font-weight: bold;
            display: inline-block;
        }
        
        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-unpaid {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-overdue {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* Payment Info */
        .payment-box {
            clear: both;
            background: #f8f9fa;
            padding: 20px;
            border: 2px solid #5B6FFF;
            margin-bottom: 20px;
        }
        
        .payment-title {
            color: #5B6FFF;
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .payment-table {
            width: 100%;
        }
        
        .payment-table td {
            padding: 8px 10px;
            font-size: 9pt;
        }
        
        .payment-label {
            color: #666;
            width: 25%;
        }
        
        .payment-value {
            font-weight: bold;
            color: #333;
            width: 25%;
        }
        
        /* Paid Box */
        .paid-box {
            background: #d1fae5;
            padding: 15px;
            border-left: 4px solid #10b981;
            margin-bottom: 20px;
            color: #065f46;
        }
        
        .paid-title {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 5px;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            font-size: 8pt;
            color: #666;
            margin-top: 30px;
        }
        
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <div class="company-name">{{ \App\Models\Setting::get('company_name', config('app.name', 'Internet Provider')) }}</div>
                <div class="company-info">
                    {{ \App\Models\Setting::get('company_address', 'Alamat Perusahaan') }}<br>
                    Telp: {{ \App\Models\Setting::get('company_phone', '0123456789') }}<br>
                    Email: {{ \App\Models\Setting::get('company_email', 'info@company.com') }}
                </div>
            </td>
            <td style="width: 40%;">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">{{ $payment->invoice_number }}</div>
            </td>
        </tr>
    </table>

    <!-- Info Section -->
    <table class="info-table">
        <tr>
            <td class="info-box" style="width: 48%;">
                <div class="info-title">PELANGGAN</div>
                <div class="customer-name">{{ $payment->customer->name }}</div>
                <div class="info-text">
                    {{ $payment->customer->address }}<br>
                    Telp: {{ $payment->customer->phone }}
                    @if($payment->customer->internetPackage)
                        <br>Paket: {{ $payment->customer->internetPackage->name }}
                    @endif
                </div>
            </td>
            <td style="width: 4%;"></td>
            <td class="info-box" style="width: 48%;">
                <div class="info-title">DETAIL INVOICE</div>
                <div class="info-text">
                    <span class="info-label">Tanggal:</span> 
                    <span class="info-value">{{ $payment->created_at->format('d F Y') }}</span><br>
                    
                    <span class="info-label">Periode:</span> 
                    <span class="info-value">{{ \Carbon\Carbon::parse($payment->billing_period)->format('F Y') }}</span><br>
                    
                    <span class="info-label">Jatuh Tempo:</span> 
                    <span class="info-value">{{ $payment->due_date->format('d F Y') }}</span><br><br>
                    
                    @if($payment->status === 'paid')
                        <span class="status-badge status-paid">LUNAS</span>
                    @elseif($payment->due_date->isPast() && $payment->status !== 'paid')
                        <span class="status-badge status-overdue">TERLAMBAT</span>
                    @else
                        <span class="status-badge status-unpaid">BELUM LUNAS</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">NO</th>
                <th style="width: 50%;">DESKRIPSI</th>
                <th style="width: 20%; text-align: center;">PERIODE</th>
                <th style="width: 10%; text-align: center;">QTY</th>
                <th style="width: 15%; text-align: right;">JUMLAH</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center;">1</td>
                <td>
                    <strong>{{ $payment->customer->internetPackage->name ?? 'Paket Internet' }}</strong><br>
                    <small style="color: #666;">Biaya berlangganan internet bulanan</small>
                </td>
                <td style="text-align: center;">
                    {{ \Carbon\Carbon::parse($payment->billing_period)->format('M Y') }}
                </td>
                <td style="text-align: center;">1</td>
                <td style="text-align: right;">
                    <strong>Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Summary -->
    <div class="summary-container">
        <table class="summary-table">
            <tr>
                <td>Subtotal:</td>
                <td style="text-align: right;">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>PPN (0%):</td>
                <td style="text-align: right;">Rp 0</td>
            </tr>
            <tr class="summary-total">
                <td>TOTAL:</td>
                <td style="text-align: right;">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="2" style="height: 10px;"></td>
            </tr>
            <tr class="summary-grand">
                <td>GRAND TOTAL:</td>
                <td style="text-align: right;">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
            </tr>
        </table>
        <div style="clear: both;"></div>
    </div>

    <!-- Payment Info -->
    <div class="payment-box">
        <div class="payment-title">INFORMASI PEMBAYARAN</div>
        <table class="payment-table">
            <tr>
                <td class="payment-label">Bank:</td>
                <td class="payment-value">{{ \App\Models\Setting::get('bank_name', 'Bank BCA') }}</td>
                <td class="payment-label">No. Rekening:</td>
                <td class="payment-value">{{ \App\Models\Setting::get('bank_account', '1234567890') }}</td>
            </tr>
            <tr>
                <td class="payment-label">Atas Nama:</td>
                <td class="payment-value" colspan="3">{{ \App\Models\Setting::get('bank_account_name', 'PT. Company Name') }}</td>
            </tr>
        </table>
        @php
            $paymentNotes = \App\Models\Setting::get('payment_notes');
        @endphp
        @if($paymentNotes)
        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0; font-size: 9pt; color: #666; line-height: 1.6;">
            {!! nl2br(e($paymentNotes)) !!}
        </div>
        @endif
    </div>

    <!-- Paid Status -->
    @if($payment->status === 'paid')
    <div class="paid-box">
        <div class="paid-title">&#x2713; INVOICE TELAH LUNAS</div>
        <div style="font-size: 9pt;">
            Dibayar pada: {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d F Y') : '-' }}
            @if($payment->paymentMethod)
                <br>Metode Pembayaran: {{ $payment->paymentMethod->name }}
            @endif
            @if($payment->notes)
                <br>Catatan: {{ $payment->notes }}
            @endif
        </div>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>{{ \App\Models\Setting::get('invoice_footer', 'Terima kasih atas kepercayaan Anda menggunakan layanan kami.') }}</p>
        <p>Invoice ini dicetak secara otomatis dan sah tanpa tanda tangan.</p>
        <p style="margin-top: 10px; font-weight: bold;">{{ \App\Models\Setting::get('company_name', config('app.name', 'Internet Provider')) }}</p>
    </div>
</body>
</html>
