<?php

namespace App\Filament\Admin\Resources\Orders\Tables;

use App\Models\Order;
use App\Services\OrderService;
use App\Services\TransactionService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\Service;
use App\Models\Product;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class OrdersTable
{

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'DESC')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode Pesanan')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->label('Nama Pelanggan')
                    ->searchable(),
                TextColumn::make('customer.email')
                    ->label('Email Pelanggan')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'PENDING' => 'Menunggu',
                            'CONFIRMED' => 'Dikonfirmasi',
                            'PICKED_UP' => 'Diambil',
                            'IN_PROCESS' => 'Dalam Proses',
                            'READY' => 'Siap',
                            'OUT_FOR_DELIVERY' => 'Sedang Dikirim',
                            'DELIVERED' => 'Terkirim',
                            'COMPLETED' => 'Selesai',
                            'CANCELLED' => 'Dibatalkan',
                            default => $state,
                        };
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'CONFIRMED' => 'info',
                        'PICKED_UP' => 'primary',
                        'IN_PROCESS' => 'warning',
                        'READY' => 'success',
                        'OUT_FOR_DELIVERY' => 'info',
                        'DELIVERED' => 'success',
                        'COMPLETED' => 'success',
                        'CANCELLED' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('payment_status')
                    ->label('Status Pembayaran')
                    ->badge()
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'UNPAID' => 'Belum Dibayar',
                            'PAID' => 'Sudah Dibayar',
                            'EXPIRED' => 'Kedaluwarsa',
                            default => $state,
                        };
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'UNPAID' => 'danger',
                        'PAID' => 'success',
                        'EXPIRED' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([

                ViewAction::make()
                    ->label('Lihat'),
                // alur dimulai
                Action::make("Konfirmasi")
                    ->visible(fn(Order $record) => $record->status == "PENDING")
                    ->requiresConfirmation()
                    ->schema([
                        TextInput::make('customer_name')
                            ->label('Nama Pelanggan')
                            ->disabled()
                            ->default(fn(Order $record) => $record->customer->name),
                        TextInput::make('customer_email')
                            ->label('Email Pelanggan')
                            ->disabled()
                            ->default(fn(Order $record) => $record->customer->email),
                        Textarea::make('pickup_address')
                            ->label('Alamat Penjemputan')
                            ->disabled()
                            ->rows(3)
                            ->default(fn(Order $record) => $record->pickupAddress ?
                                "{$record->pickupAddress->label}\n{$record->pickupAddress->recipient_name} - {$record->pickupAddress->recipient_phone}\n{$record->pickupAddress->full_address}" :
                                'Tidak ada alamat penjemputan'
                            ),
                        Textarea::make('delivery_address')
                            ->label('Alamat Pengantaran')
                            ->disabled()
                            ->rows(3)
                            ->default(fn(Order $record) => $record->deliveryAddress ?
                                "{$record->deliveryAddress->label}\n{$record->deliveryAddress->recipient_name} - {$record->deliveryAddress->recipient_phone}\n{$record->deliveryAddress->full_address}" :
                                'Tidak ada alamat pengantaran'
                            ),
                        Textarea::make('notes')
                            ->label('Catatan Pesanan')
                            ->disabled()
                            ->rows(2)
                            ->default(fn(Order $record) => $record->notes ?? 'Tidak ada catatan'),
                    ])
                    ->action(fn(Order $record) => resolve(OrderService::class)->confirm($record))
                    ->color("success")
                    ->icon('heroicon-o-check-circle'),
                Action::make("Batalkan")
                    ->visible(fn(Order $record) => $record->status == "PENDING" || $record->status == "CONFIRMED")
                    ->requiresConfirmation()
                    ->schema([
                        TextInput::make('customer_name')
                            ->label('Nama Pelanggan')
                            ->disabled()
                            ->default(fn(Order $record) => $record->customer->name),
                        TextInput::make('customer_email')
                            ->label('Email Pelanggan')
                            ->disabled()
                            ->default(fn(Order $record) => $record->customer->email),
                        Textarea::make('pickup_address')
                            ->label('Alamat Penjemputan')
                            ->disabled()
                            ->rows(3)
                            ->default(fn(Order $record) => $record->pickupAddress ?
                                "{$record->pickupAddress->label}\n{$record->pickupAddress->recipient_name} - {$record->pickupAddress->recipient_phone}\n{$record->pickupAddress->full_address}" :
                                'Tidak ada alamat penjemputan'
                            ),
                        Textarea::make('delivery_address')
                            ->label('Alamat Pengantaran')
                            ->disabled()
                            ->rows(3)
                            ->default(fn(Order $record) => $record->deliveryAddress ?
                                "{$record->deliveryAddress->label}\n{$record->deliveryAddress->recipient_name} - {$record->deliveryAddress->recipient_phone}\n{$record->deliveryAddress->full_address}" :
                                'Tidak ada alamat pengantaran'
                            ),
                        Textarea::make('notes')
                            ->label('Catatan Pesanan')
                            ->disabled()
                            ->rows(2)
                            ->default(fn(Order $record) => $record->notes ?? 'Tidak ada catatan'),
                        Textarea::make('reason')
                            ->label('Alasan Pembatalan')
                            ->required()
                    ])
                    ->action(fn(array $data, Order $record) => resolve(OrderService::class)->cancel($record, $data["reason"]))
                    ->color("danger")
                    ->icon('heroicon-o-x-circle'),

                // mengambil laundry ke customer
                Action::make("Ambil Laundry")
                    ->visible(fn(Order $record) => $record->status == "CONFIRMED")
                    ->requiresConfirmation()
                    ->schema([
                        TextInput::make('customer_name')
                            ->label('Nama Pelanggan')
                            ->disabled()
                            ->default(fn(Order $record) => $record->customer->name),
                        TextInput::make('customer_email')
                            ->label('Email Pelanggan')
                            ->disabled()
                            ->default(fn(Order $record) => $record->customer->email),
                        Textarea::make('pickup_address')
                            ->label('Alamat Penjemputan')
                            ->disabled()
                            ->rows(3)
                            ->default(fn(Order $record) => $record->pickupAddress ?
                                "{$record->pickupAddress->label}\n{$record->pickupAddress->recipient_name} - Telepon : {$record->pickupAddress->recipient_phone}\n{$record->pickupAddress->full_address}" :
                                'Tidak ada alamat penjemputan'
                            ),
                    ])
                    ->color("info")
                    ->action(fn(array $data, Order $record) => resolve(OrderService::class)->pickedUp($record))
                    ->icon('heroicon-o-truck'),

                // penimbangan
                // pengisian paket
                // pembayaran laundry
                Action::make("Transaction")
                    ->visible(fn(Order $record) => $record->status == "PICKED_UP" && $record->payment_status == "UNPAID" && $record->orderItems->isEmpty())
                    ->label("Buat Transaksi")
                    ->requiresConfirmation()
                    ->schema([
                        Section::make('Informasi Pelanggan')
                            ->schema([
                                TextInput::make('customer_name')
                                    ->label('Nama Pelanggan')
                                    ->disabled()
                                    ->default(fn(Order $record) => $record->customer->name),
                                TextInput::make('order_code')
                                    ->label('Kode Pesanan')
                                    ->disabled()
                                    ->default(fn(Order $record) => $record->code),
                            ])->columns(2),

                        Section::make('Item Laundry')
                            ->schema([
                                Repeater::make('order_items')
                                    ->label('Item Pesanan')
                                    ->schema([
                                        Select::make('service_id')
                                            ->label('Jenis Layanan')
                                            ->options(Service::all()->pluck('name', 'id'))
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $service = Service::find($state);
                                                if ($service) {
                                                    $set('price', $service->price_per_unit);
                                                    $set('unit', $service->unit);
                                                }
                                            }),

                                        TextInput::make('unit')
                                            ->label('Satuan')
                                            ->disabled()
                                            ->default(''),

                                        TextInput::make('qty')
                                            ->label('Jumlah')
                                            ->numeric()
                                            ->step(0.1)
                                            ->default(0)
                                            ->reactive(),

                                        TextInput::make('price')
                                            ->label('Harga per Satuan')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->disabled(),

                                    ])
                                    ->columns(2)
                                    ->defaultItems(1)
                                    ->addActionLabel('Tambah Item')
                                    ->deleteAction(
                                        fn($action) => $action->requiresConfirmation()
                                    ),
                            ]),

                        Section::make("Biaya Tambahan")
                            ->schema([
                                TextInput::make("delivery_fee")
                                    ->default(0)
                                    ->numeric()
                                    ->label('Biaya Antar')
                            ])->columns(2)

                    ])
                    ->action(fn(array $data, Order $record) => resolve(OrderService::class)->setOrderItem($record, $data))
                    ->icon('heroicon-o-credit-card')
                    ->color("info"),

                EditAction::make()
                    ->visible(fn(Order $record) => $record->status == "PICKED_UP" && $record->payment_status == "UNPAID" && !$record->orderItems->isEmpty())
                    ->label('Lihat Paket'),

                // Action::make("Lihat Transaksi")
                //     ->visible(fn(Order $record) => $record->status == "PICKED_UP" && $record->payment_status == "UNPAID" && !$record->orderItems->isEmpty())
                //     ->label("Lihat Transaksi")
                //     ->action(function (Order $record) {
                //         // Redirect ke halaman invoice untuk melihat transaksi
                //         return redirect()->route('invoice', $record->code);
                //     })
                //     ->icon('heroicon-o-eye')
                //     ->color("primary"),

                // Action::make("Bayar paket")
                //     ->visible(fn(Order $record) => $record->status == "PICKED_UP" && $record->payment_status == "UNPAID" && !$record->orderItems->isEmpty())
                //     ->url(fn(Order $record) => url('payment/' . $record->code))
                //     ->openUrlInNewTab(false)
                //     ->icon('heroicon-o-credit-card')
                //     ->color("success"),

                // proses laundry
                Action::make("Proses Laundry")
                    ->visible(fn(Order $record) => $record->status == "PICKED_UP" && $record->payment_status == "PAID")
                    ->requiresConfirmation()
                    ->action(fn(Order $record) => resolve(OrderService::class)->processing($record))
                    ->color("warning")
                    ->icon('heroicon-o-cog-6-tooth'),


                // Laundry selesai
                Action::make("Laundry Selesai")
                    ->visible(fn(Order $record) => $record->status == "IN_PROCESS" && $record->payment_status == "PAID")
                    ->requiresConfirmation()
                    ->action(fn(Order $record) => resolve(OrderService::class)->processingDone($record))
                    ->color("success")
                    ->icon('heroicon-o-shield-check'),

                // Mengirim laundry
                Action::make("Kirim Laundry")
                    ->visible(fn(Order $record) => $record->status == "READY" && $record->payment_status == "PAID")
                    ->requiresConfirmation()
                    ->action(fn(array $data, Order $record) => resolve(OrderService::class)->outOfDelivery($record))
                    ->color("info")
                    ->icon('heroicon-o-paper-airplane'),

                // laundry sampai
                Action::make("Laundry Sudah Sampai")
                    ->visible(fn(Order $record) => $record->status == "OUT_FOR_DELIVERY" && $record->payment_status == "PAID")
                    ->requiresConfirmation()
                    ->action(fn(array $data, Order $record) => resolve(OrderService::class)->delivered($record))
                    ->color("success")
                    ->icon('heroicon-o-gift'),

                ActionGroup::make([
                    EditAction::make()
                        ->label('Edit'),
                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->label('Hapus'),
                ])
            ]);
    }
}
