<?php

namespace App\Support;

use App\Models\SiteSetting;

class WhatsApp
{
    /**
     * Bangun tautan wa.me dengan pesan terisi otomatis.
     *
     * @param  string|null  $message  Pesan yang akan diisi otomatis.
     * @param  string|null  $number  Nomor tujuan; default dari pengaturan situs.
     */
    public static function link(?string $message = null, ?string $number = null): string
    {
        $number ??= SiteSetting::current()->whatsapp_number;

        $digits = preg_replace('/\D+/', '', (string) $number) ?? '';

        $url = 'https://wa.me/'.$digits;

        if (filled($message)) {
            $url .= '?text='.rawurlencode($message);
        }

        return $url;
    }

    /**
     * Pesan umum yang dipakai tombol generik.
     */
    public static function defaultMessage(): string
    {
        return 'Halo Kolam Prima, saya ingin bertanya tentang layanan kolam renang.';
    }

    /**
     * Tautan dengan pesan umum.
     */
    public static function defaultLink(): string
    {
        return static::link(static::defaultMessage());
    }
}
