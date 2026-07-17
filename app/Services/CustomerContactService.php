<?php

namespace App\Services;

class CustomerContactService
{
    public static function phoneUri(string $phoneNumber): string
    {
        return preg_replace('/[^0-9+]/', '', $phoneNumber) ?: '';
    }

    public static function whatsAppNumber(string $phoneNumber): string
    {
        $number = preg_replace('/\D+/', '', $phoneNumber) ?: '';

        if (str_starts_with($number, '00')) {
            return substr($number, 2);
        }

        if (str_starts_with($number, '0')) {
            return '92'.substr($number, 1);
        }

        return $number;
    }

    public static function whatsAppUrl(string $phoneNumber, string $message): string
    {
        return 'https://wa.me/'.self::whatsAppNumber($phoneNumber).'?text='.rawurlencode($message);
    }
}
