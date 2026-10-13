<?php

namespace App\Filament\Resources\Videos\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VideoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Video')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul Video')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('sort_order')
                            ->label('Urutan Tampil')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),

                Section::make('Berkas Video')
                    ->schema([
                        FileUpload::make('video_path')
                            ->label('Video (MP4)')
                            ->disk('public')
                            ->directory('media/videos')
                            ->visibility('public')
                            ->acceptedFileTypes(['video/mp4'])
                            ->maxSize(51200)
                            ->required()
                            ->columnSpanFull(),
                        FileUpload::make('thumbnail_path')
                            ->label('Poster / Thumbnail')
                            ->helperText('Gambar pratinjau sebelum video diputar. Disarankan rasio tegak (9:16).')
                            ->image()
                            ->disk('public')
                            ->directory('media/thumbs')
                            ->visibility('public')
                            ->maxSize(5120)
                            ->columnSpanFull(),
                    ]),

                Section::make('Pengaturan Tampilan')
                    ->schema([
                        Toggle::make('is_published')
                            ->label('Tampilkan di Website')
                            ->default(true),
                    ]),
            ]);
    }
}
