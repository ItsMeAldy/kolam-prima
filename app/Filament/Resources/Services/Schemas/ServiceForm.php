<?php

namespace App\Filament\Resources\Services\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kartu Layanan')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul Layanan')
                            ->required()
                            ->maxLength(255),
                        FileUpload::make('image_path')
                            ->label('Gambar Ikon')
                            ->helperText('Bila diisi, gambar ini menggantikan ikon bawaan. Rasio persegi (1:1) disarankan.')
                            ->image()
                            ->disk('public')
                            ->directory('media/services')
                            ->visibility('public')
                            ->maxSize(2048),
                        TextInput::make('icon')
                            ->label('Ikon (nama Heroicon)')
                            ->helperText('Dipakai bila gambar ikon di atas kosong. Contoh: pencil-ruler, hammer, droplets')
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Deskripsi Singkat')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('wa_message')
                            ->label('Pesan WhatsApp')
                            ->helperText('Pesan otomatis saat pengunjung menekan tombol tanya di kartu ini.')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('sort_order')
                            ->label('Urutan Tampil')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),
            ]);
    }
}
