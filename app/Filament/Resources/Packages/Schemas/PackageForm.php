<?php

namespace App\Filament\Resources\Packages\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Paket')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Paket')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('tagline')
                            ->label('Tagline')
                            ->maxLength(255),
                        TextInput::make('price')
                            ->label('Harga')
                            ->helperText('Kosongkan agar tampil "Hubungi kami".')
                            ->maxLength(255),
                        TextInput::make('sort_order')
                            ->label('Urutan Tampil')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),

                Section::make('Fitur Paket')
                    ->schema([
                        Repeater::make('features')
                            ->label('Daftar Fitur')
                            ->simple(
                                TextInput::make('feature')
                                    ->label('Fitur')
                                    ->required(),
                            )
                            ->addActionLabel('Tambah Fitur')
                            ->defaultItems(1),
                    ]),

                Section::make('Pengaturan Tampilan')
                    ->schema([
                        Toggle::make('is_popular')
                            ->label('Tandai sebagai "Paling Populer"')
                            ->default(false),
                    ]),
            ]);
    }
}
