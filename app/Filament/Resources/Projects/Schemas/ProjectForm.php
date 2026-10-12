<?php

namespace App\Filament\Resources\Projects\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Proyek')
                    ->schema([
                        TextInput::make('title')
                            ->label('Nama Proyek')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('location')
                            ->label('Lokasi')
                            ->required()
                            ->maxLength(255),
                        Select::make('property_type')
                            ->label('Jenis Properti')
                            ->options([
                                'rumah' => 'Rumah',
                                'villa' => 'Villa',
                                'hotel' => 'Hotel',
                            ])
                            ->required()
                            ->native(false),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Gambar Proyek')
                    ->schema([
                        Repeater::make('images')
                            ->label('Gambar')
                            ->relationship()
                            ->schema([
                                TextInput::make('image_url')
                                    ->label('URL Gambar')
                                    ->required()
                                    ->url()
                                    ->maxLength(2048),
                                TextInput::make('sort_order')
                                    ->label('Urutan')
                                    ->numeric()
                                    ->default(0),
                            ])
                            ->columns(2)
                            ->reorderable(false)
                            ->addActionLabel('Tambah Gambar')
                            ->defaultItems(0),
                    ]),

                Section::make('Pengaturan Tampilan')
                    ->schema([
                        TextInput::make('sort_order')
                            ->label('Urutan Tampil')
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_published')
                            ->label('Tampilkan di Website')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
