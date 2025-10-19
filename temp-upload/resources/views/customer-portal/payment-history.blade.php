@extends('layouts.customer-portal')

@section('title', 'Riwayat Pembayaran')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Riwayat Pembayaran</h5>
                </div>
                <div class="card-body">
                    @if($payments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No. Invoice</th>
                                        <th>Tanggal Tagihan</th>
                                        <th>Jatuh Tempo</th>
                                        <th>Jumlah</th>
                                        <th>Status</th>
                                        <th>Tanggal Bayar</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payments as $payment)
                                    <tr>
                                        <td>{{ $payment->invoice_number }}</td>
                                        <td>{{ $payment->created_at->format('d M Y') }}</td>
                                        <td>{{ $payment->due_date->format('d M Y') }}</td>
                                        <td>Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                        <td>
                                            @if($payment->status == 'paid')
                                                <span class="badge bg-success">Lunas</span>
                                            @elseif($payment->status == 'unpaid')
                                                <span class="badge bg-warning">Belum Lunas</span>
                                            @elseif($payment->status == 'overdue')
                                                <span class="badge bg-danger">Terlambat</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $payment->status }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($payment->paid_at)
                                                {{ $payment->paid_at->format('d M Y') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('customer.payment.detail', $payment->id) }}" class="btn btn-sm btn-info">Detail</a>
                                            @if($payment->status == 'unpaid' || $payment->status == 'overdue')
                                                <form action="{{ route('customer.payment.process') }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                                                    <button type="submit" class="btn btn-sm btn-success">Bayar</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-4">
                            {{ $payments->links() }}
                        </div>
                    @else
                        <div class="alert alert-info">
                            Belum ada riwayat pembayaran.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection