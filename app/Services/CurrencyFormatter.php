<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CurrencyFormatter
{
    private function settings(): array
    {
        return Cache::remember('hope.erp.currency', 300, function (): array {
            if (! DB::getSchemaBuilder()->hasTable('erp_settings')) {
                return ['code' => 'XOF', 'symbol' => 'FCFA', 'decimal_places' => 0];
            }
            $row = DB::table('erp_settings')->where('key', 'currency')->value('value');
            $settings = is_string($row) ? json_decode($row, true) : null;
            return array_merge(
                ['code' => 'XOF', 'symbol' => 'FCFA', 'decimal_places' => 0],
                is_array($settings) ? $settings : []
            );
        });
    }

    public function format(int|float|string|null $amount, ?string $currency = null): string
    {
        $settings = $this->settings();
        $decimals = max(0, min(4, (int) ($settings['decimal_places'] ?? 0)));
        $symbol = $currency && $currency !== ($settings['code'] ?? 'XOF')
            ? $currency
            : ($settings['symbol'] ?? $settings['code'] ?? 'XOF');
        return number_format((float) $amount, $decimals, ',', ' ') . ' ' . $symbol;
    }

    public function clearCache(): void
    {
        Cache::forget('hope.erp.currency');
    }
}
