<?php

namespace App\Filament\U\Resources\Orders\Tables;

use App\Models\Order;
use App\Services\FeedbackService;
use App\Services\OrderService;
use App\Services\TransactionService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->where('user_id', auth()->id()))
            ->defaultSort('created_at', 'DESC')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode Laundry')
                    ->searchable(),
                TextColumn::make('pickupAddress.label')
                    ->label('Alamat Penjemputan')
                    ->searchable(),
                TextColumn::make('deliveryAddress.label')
                    ->label('Alamat Pengiriman')
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
                    ->color(fn(string $state): string => match ($state) {
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
                    }),
                TextColumn::make('subtotal_amount')
                    ->label('Subtotal')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('delivery_fee')
                    ->label('Biaya Pengiriman')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('completed_date')
                    ->label('Tanggal Selesai')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make("Bayar paket")
                    ->visible(fn(Order $record) => $record->payment_status == 'UNPAID' && $record->status == 'PICKED_UP')
                    ->url(fn(Order $record) => route('invoice', $record->code))
                    ->openUrlInNewTab(false)
                    ->color("info")
                    ->icon(Heroicon::CreditCard),
                Action::make("Konfirmasi Laundry")
                    ->visible(fn(Order $order) => $order->status == "DELIVERED")
                    ->action(fn(array $data, Order $record) => resolve(OrderService::class)->userConfirmed($record))
                    ->color("success")
                    ->icon(Heroicon::CheckCircle),
                Action::make("Ulasan")
                    ->visible(fn(Order $order) => $order->status == "COMPLETED")
                    ->action(fn(array $data) => resolve(FeedbackService::class)->create($data))
                    ->color("warning")
                    ->icon(Heroicon::ChatBubbleLeft),

            ]);
    }
}
