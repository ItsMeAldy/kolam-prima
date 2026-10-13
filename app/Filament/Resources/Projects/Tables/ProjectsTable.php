<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Models\Project;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('cover')
                    ->label('Gambar')
                    ->disk('public')
                    ->height(48)
                    ->width(64)
                    ->getStateUsing(fn (Project $record): ?string => $record->images->first()?->path),
                TextColumn::make('title')
                    ->label('Nama Proyek')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location')
                    ->label('Lokasi')
                    ->searchable(),
                TextColumn::make('property_type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'rumah' => 'Rumah',
                        'villa' => 'Villa',
                        'hotel' => 'Hotel',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'rumah' => 'info',
                        'villa' => 'success',
                        'hotel' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('images_count')
                    ->label('Jumlah Gambar')
                    ->counts('images')
                    ->alignCenter(),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable()
                    ->alignCenter(),
                ToggleColumn::make('is_published')
                    ->label('Tampil'),
            ])
            ->filters([
                SelectFilter::make('property_type')
                    ->label('Jenis Properti')
                    ->options([
                        'rumah' => 'Rumah',
                        'villa' => 'Villa',
                        'hotel' => 'Hotel',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
