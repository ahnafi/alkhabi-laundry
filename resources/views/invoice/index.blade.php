<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Invoice - {{ $order->code }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body {
                background: white;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Invoice Container -->
        <div class="bg-white rounded-lg shadow-lg max-w-4xl mx-auto">
            
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-8 rounded-t-lg">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-4xl font-bold">ALKHABI LAUNDRY</h1>
                        <p class="text-blue-100 mt-1">Layanan Laundry Profesional</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-blue-100">Invoice</p>
                        <p class="text-2xl font-bold">{{ $order->code }}</p>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="p-8">
                
                <!-- Order Info Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <!-- Dari Perusahaan -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-600 mb-3 uppercase">Dari</h3>
                        <div class="text-gray-800">
                            <p class="font-semibold">Alkhabi Laundry</p>
                            <p class="text-sm text-gray-600">Jl. Laundry No. 123</p>
                            <p class="text-sm text-gray-600">Kota Laundry, 12345</p>
                            <p class="text-sm text-gray-600 mt-2">Hubungi: +62 812 3456 7890</p>
                        </div>
                    </div>

                    <!-- Ke Pelanggan -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-600 mb-3 uppercase">Ke</h3>
                        <div class="text-gray-800">
                            <p class="font-semibold">{{ $order->customer->name }}</p>
                            <p class="text-sm text-gray-600">{{ $order->customer->email }}</p>
                            <p class="text-sm text-gray-600">{{ $order->customer->phone ?? 'N/A' }}</p>
                            @if($order->pickupAddress)
                                <p class="text-sm text-gray-600 mt-2">
                                    <strong>Alamat Penjemputan:</strong><br>
                                    {{ $order->pickupAddress->label }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Invoice Details Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 pb-8 border-b-2 border-gray-200">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-600 mb-3 uppercase">Detail Invoice</h3>
                        <table class="text-sm w-full">
                            <tbody>
                                <tr>
                                    <td class="text-gray-600 py-1">Nomor Invoice:</td>
                                    <td class="font-semibold">{{ $order->code }}</td>
                                </tr>
                                <tr>
                                    <td class="text-gray-600 py-1">Tanggal Invoice:</td>
                                    <td class="font-semibold">{{ $order->created_at->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-gray-600 py-1">Status Laundry:</td>
                                    <td>
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold
                                            {{ $order->status === 'PICKED_UP' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ match($order->status) {
                                                'PENDING' => 'Menunggu',
                                                'CONFIRMED' => 'Dikonfirmasi',
                                                'PICKED_UP' => 'Diambil',
                                                'IN_PROCESS' => 'Dalam Proses',
                                                'READY' => 'Siap',
                                                'OUT_FOR_DELIVERY' => 'Sedang Dikirim',
                                                'DELIVERED' => 'Terkirim',
                                                'COMPLETED' => 'Selesai',
                                                'CANCELLED' => 'Dibatalkan',
                                                default => $order->status,
                                            } }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-600 mb-3 uppercase">Rincian Pengiriman</h3>
                        <table class="text-sm w-full">
                            <tbody>
                                <tr>
                                    <td class="text-gray-600 py-1">Alamat Pengiriman:</td>
                                </tr>
                                <tr>
                                    <td class="text-gray-700 py-1">
                                        @if($order->deliveryAddress)
                                            <span class="font-semibold">{{ $order->deliveryAddress->label }}</span>
                                        @else
                                            <span class="text-gray-500">Belum ditentukan</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-gray-600 py-1 pt-3">Total Qty:</td>
                                    <td class="font-semibold">{{ $order->total_qty ?? 0 }} item</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="mb-8">
                    <h3 class="text-sm font-semibold text-gray-600 mb-4 uppercase">Rincian Layanan</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 border-b-2 border-gray-300">
                                <tr>
                                    <th class="px-4 py-3 text-left text-gray-700 font-semibold">Layanan</th>
                                    <th class="px-4 py-3 text-center text-gray-700 font-semibold">Qty</th>
                                    <th class="px-4 py-3 text-right text-gray-700 font-semibold">Harga Satuan</th>
                                    <th class="px-4 py-3 text-right text-gray-700 font-semibold">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($order->orderItems as $item)
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="px-4 py-3 text-gray-800">
                                            {{ $item->service->name ?? 'Layanan' }}
                                        </td>
                                        <td class="px-4 py-3 text-center text-gray-800">
                                            {{ $item->qty }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-800">
                                            Rp {{ number_format($item->service->price_per_unit ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-800 font-semibold">
                                            Rp {{ number_format($item->subtotal ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                            Tidak ada item dalam pesanan ini
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Totals Section -->
                <div class="flex justify-end mb-8">
                    <div class="w-full md:w-80">
                        <div class="bg-gray-50 rounded-lg p-6 space-y-3">
                            <div class="flex justify-between text-gray-700">
                                <span>Subtotal:</span>
                                <span class="font-semibold">Rp {{ number_format($order->subtotal_amount ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-gray-700">
                                <span>Biaya Pengiriman:</span>
                                <span class="font-semibold">Rp {{ number_format($order->delivery_fee ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div class="border-t-2 border-gray-300 pt-3 flex justify-between text-lg font-bold text-gray-900">
                                <span>Total Pembayaran:</span>
                                <span class="text-blue-600">Rp {{ number_format($order->total_amount ?? 0, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Status -->
                <div class="mb-8 p-4 rounded-lg {{ $order->payment_status === 'PAID' ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200' }}">
                    <p class="text-sm font-semibold {{ $order->payment_status === 'PAID' ? 'text-green-800' : 'text-yellow-800' }}">
                        Status Pembayaran: 
                        <span class="inline-block px-3 py-1 rounded text-xs font-bold mt-1
                            {{ $order->payment_status === 'PAID' ? 'bg-green-200 text-green-900' : 'bg-yellow-200 text-yellow-900' }}">
                            {{ $order->payment_status === 'PAID' ? 'SUDAH DIBAYAR' : 'BELUM DIBAYAR' }}
                        </span>
                    </p>
                </div>

                <!-- Payment Button -->
                @if($order->payment_status !== 'PAID')
                    <div id="payment-section" class="bg-blue-50 border-2 border-blue-200 rounded-lg p-6 text-center">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Lakukan Pembayaran</h3>
                        <p class="text-gray-600 mb-6">
                            Klik tombol di bawah untuk melakukan pembayaran melalui Midtrans
                        </p>
                        <button id="pay-button" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition duration-200 w-full md:w-auto">
                            💳 Bayar Sekarang: Rp {{ number_format($order->total_amount ?? 0, 0, ',', '.') }}
                        </button>
                    </div>
                @else
                    <div class="bg-green-50 border-2 border-green-200 rounded-lg p-6 text-center">
                        <h3 class="text-lg font-semibold text-green-800 mb-2">✓ Pembayaran Berhasil</h3>
                        <p class="text-gray-600 mb-4">
                            Terima kasih telah melakukan pembayaran. Pesanan Anda sedang diproses.
                        </p>
                        <a href="{{ url('/u/orders') }}" class="inline-block bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg transition duration-200">
                            Kembali ke Pesanan
                        </a>
                    </div>
                @endif

                <!-- Notes -->
                @if($order->notes)
                    <div class="mt-8 pt-8 border-t-2 border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-600 mb-2 uppercase">Catatan</h3>
                        <p class="text-gray-700">{{ $order->notes }}</p>
                    </div>
                @endif

                <!-- Footer Info -->
                <div class="mt-8 pt-8 border-t-2 border-gray-200 text-center text-xs text-gray-500 space-y-1">
                    <p>Terima kasih telah memilih Alkhabi Laundry</p>
                    <p>Hubungi customer service kami untuk bantuan lebih lanjut: support@alkhabi-laundry.com</p>
                </div>

            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-8 flex justify-center gap-4 no-print">
            <button onclick="window.print()" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded-lg transition duration-200">
                🖨️ Cetak Invoice
            </button>
            <a href="{{ url('/u/orders') }}" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-6 rounded-lg transition duration-200">
                ← Kembali
            </a>
        </div>
    </div>

    <!-- Midtrans Snap Script -->
    <script src="https://app.{{ config('midtrans.is_production') ? '' : 'sandbox.' }}midtrans.com/snap/snap.js" 
            data-client-key="{{ $clientKey }}"></script>
    
    <script type="text/javascript">
        document.getElementById('pay-button')?.addEventListener('click', function () {
            snap.pay("{{ $snapToken }}", {
                onSuccess: function (result) {
                    console.log('Payment Success:', result);
                    
                    // Send payment success to backend untuk update order status
                    fetch('{{ route("payment.success") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify({
                            order_id: '{{ $order->id }}',
                            transaction_id: result.transaction_id,
                            status: result.transaction_status
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Backend response:', data);
                        // Redirect ke halaman orders setelah pembayaran berhasil
                        setTimeout(function() {
                            window.location.href = "{{ url('/u/orders') }}";
                        }, 1500);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Tetap redirect meski error di backend
                        setTimeout(function() {
                            window.location.href = "{{ url('/u/orders') }}";
                        }, 2000);
                    });
                },
                onPending: function (result) {
                    console.log('Payment Pending:', result);
                    // Tetap di halaman invoice jika pending
                    alert('Pembayaran sedang diproses, harap tunggu...');
                },
                onError: function (result) {
                    console.log('Payment Error:', result);
                    alert('Pembayaran gagal, silakan coba lagi!');
                },
                onClose: function () {
                    console.log('Customer closed the popup without finishing the payment');
                }
            });
        });
    </script>
</body>
</html>