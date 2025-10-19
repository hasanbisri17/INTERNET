@extends('layouts.customer-portal')

@section('title', 'Dashboard Pelanggan')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Selamat Datang, {{ $customer->name }}</h5>
                    <p class="card-text">Paket Internet: <strong>{{ $customer->internetPackage->name }}</strong></p>
                    <p class="card-text">Status Layanan: 
                        @if($customer->is_active)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-danger">Tidak Aktif</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Tagihan Belum Dibayar</h5>
                </div>
                <div class="card-body">
                    @if($unpaidPayments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No. Invoice</th>
                                        <th>Tanggal Jatuh Tempo</th>
                                        <th>Jumlah</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($unpaidPayments as $payment)
                                    <tr>
                                        <td>{{ $payment->invoice_number }}</td>
                                        <td>{{ $payment->due_date->format('d M Y') }}</td>
                                        <td>Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                        <td>
                                            <a href="{{ route('customer.payment.detail', $payment->id) }}" class="btn btn-sm btn-info">Detail</a>
                                            <form action="{{ route('customer.payment.process') }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                                                <button type="submit" class="btn btn-sm btn-success">Bayar</button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-success">
                            Tidak ada tagihan yang belum dibayar.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Pembayaran Terakhir</h5>
                </div>
                <div class="card-body">
                    @if($paidPayments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No. Invoice</th>
                                        <th>Tanggal Bayar</th>
                                        <th>Jumlah</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paidPayments as $payment)
                                    <tr>
                                        <td>{{ $payment->invoice_number }}</td>
                                        <td>{{ $payment->paid_at->format('d M Y') }}</td>
                                        <td>Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                        <td>
                                            <a href="{{ route('customer.payment.detail', $payment->id) }}" class="btn btn-sm btn-info">Detail</a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            Belum ada riwayat pembayaran.
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <a href="{{ route('customer.payment.history') }}" class="btn btn-outline-primary">Lihat Semua Riwayat</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Informasi Akun</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nama:</strong> {{ $customer->name }}</p>
                            <p><strong>Email:</strong> {{ $customer->email }}</p>
                            <p><strong>Telepon:</strong> {{ $customer->phone }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Alamat:</strong> {{ $customer->address }}</p>
                            <p><strong>Tanggal Bergabung:</strong> {{ $customer->created_at->format('d M Y') }}</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('customer.profile') }}" class="btn btn-outline-primary">Edit Profil</a>
                    <a href="{{ route('customer.change-password') }}" class="btn btn-outline-secondary">Ganti Password</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection