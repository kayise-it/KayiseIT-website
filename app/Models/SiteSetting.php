<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $table = 'site_settings';

    protected $fillable = [
        'show_whatsapp_floating',
        'whatsapp_e164',
        'show_chatbot_floating',
        'lmis_enabled',
        'lmis_base_url',
        'lmis_api_token',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'show_whatsapp_floating' => 'boolean',
        'show_chatbot_floating' => 'boolean',
        'lmis_enabled' => 'boolean',
        'lmis_api_token' => 'encrypted',
    ];

    public static function current(): self
    {
        $row = static::query()->first();
        if ($row === null) {
            $row = static::query()->create([
                'show_whatsapp_floating' => true,
                'whatsapp_e164' => '27693907862',
                'show_chatbot_floating' => true,
                'lmis_enabled' => false,
                'lmis_base_url' => 'http://localhost:3010',
            ]);
        }

        return $row;
    }

    /**
     * Digits for wa.me, or null when the stored value is missing or not 10–15 digits.
     */
    public function whatsappDigits(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->whatsapp_e164);
        if (! is_string($digits) || $digits === '' || strlen($digits) < 10 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }

    /**
     * Human-readable number, for example +27 69 390 7862.
     */
    public function whatsappDisplay(): ?string
    {
        $digits = $this->whatsappDigits();
        if ($digits === null) {
            return null;
        }

        if (str_starts_with($digits, '27') && strlen($digits) === 11) {
            return sprintf(
                '+27 %s %s %s',
                substr($digits, 2, 2),
                substr($digits, 4, 3),
                substr($digits, 7, 4)
            );
        }

        return '+'.$digits;
    }

    public static function displayedWhatsappNumber(): ?string
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable((new static)->getTable())) {
                return null;
            }

            return static::current()->whatsappDisplay();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
