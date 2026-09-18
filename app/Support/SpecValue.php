<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * სპეციფიკაციის მნიშვნელობების ნორმალიზაცია.
 *
 * პროდუქტები სხვადასხვა მომწოდებლისგან მოდის და ერთსა და იმავე მნიშვნელობას
 * თავისებურად წერენ:
 *
 *   30-40 მ²  |  30–40 მ²  |  35 - 40 მ²     ← სხვადასხვა ტირე და ინტერვალი
 *   12000     |  12000 BTU                   ← ერთეულით და მის გარეშე
 *
 * ამის გამო ფილტრში ერთი მნიშვნელობა 3-4 ვარიანტად ჩნდებოდა.
 */
class SpecValue
{
    /** ერთეულები, რომლებიც რიცხვს მოსდევს — გრძელი ჯერ, რომ „კვტ" „ვტ"-ზე ადრე დაიჭიროს */
    private const UNITS = [
        'მ²', 'მ2', 'კვ.მ', 'კვ მ', 'კვმ',
        'კვტ', 'ვტ', 'კგ', 'სმ', 'მმ', 'დბ', 'ლ', 'გ', 'მ', 'ჰც',
        'kwh', 'btu', 'db', 'kw', 'cm', 'mm', 'kg', 'hz', 'w', 'l', 'g',
    ];

    /** ლათინური ერთეულების სასურველი ჩაწერა — მომხმარებელს „btu" კი არა, „BTU" უნდა დაუწეროს */
    private const UNIT_DISPLAY = [
        'btu' => 'BTU', 'kwh' => 'kWh', 'kw' => 'kW', 'w' => 'W',
        'l' => 'L', 'kg' => 'kg', 'g' => 'g', 'cm' => 'cm', 'mm' => 'mm', 'hz' => 'Hz', 'db' => 'dB',
    ];

    /** ტირეს ყველა ვარიანტი — en dash, em dash, მინუსი, ზოლი */
    private const DASHES = ['–', '—', '−', '‒', '―', '~', '〜'];

    /**
     * ნედლი მნიშვნელობის დაშლა: რიცხვები, ერთეული, დარჩენილი ტექსტი.
     */
    public static function parse(string $raw): array
    {
        $s = trim($raw);
        $s = str_replace(self::DASHES, '-', $s);
        $s = preg_replace('/\s+/u', ' ', $s);
        $s = str_replace(',', '.', $s);

        // ერთეული ბოლოში
        $unit = null;
        foreach (self::UNITS as $u) {
            if (preg_match('/(\d)\s*' . preg_quote($u, '/') . '\.?$/ui', $s)) {
                $unit = $u;
                $s    = preg_replace('/\s*' . preg_quote($u, '/') . '\.?$/ui', '', $s);
                break;
            }
        }

        preg_match_all('/(?<![\d.])-?\d+(?:\.\d+)?/', $s, $m);
        $numbers = array_map('floatval', $m[0] ?? []);

        // ტექსტური ნაწილი — რიცხვების გარეშე რა რჩება
        $text = trim(preg_replace('/(?<![\d.])-?\d+(?:\.\d+)?/', '', $s));
        $text = trim($text, " \t\n\r\0\x0B-/");

        return [
            'numbers' => $numbers,
            'unit'    => $unit,
            'text'    => $text,
            'clean'   => trim($s),
        ];
    }

    /**
     * ერთი რიცხვი დალაგებისა და დიაპაზონის ფილტრისთვის.
     * „30-40 მ²" → 35 (შუა წერტილი), რომ დიაპაზონში სამართლიანად მოხვდეს.
     */
    public static function toNumber(string $raw): ?float
    {
        $p = self::parse($raw);

        if (empty($p['numbers'])) {
            return null;
        }

        if (count($p['numbers']) >= 2) {
            return (min($p['numbers']) + max($p['numbers'])) / 2;
        }

        return $p['numbers'][0];
    }

    /**
     * სპეციფიკაცია რიცხვითია თუ არა — მნიშვნელობების უმრავლესობით ვწყვეტთ.
     * რიცხვითზე დიაპაზონის სლაიდერი გვინდა, არა checkbox-ების სია.
     */
    public static function isNumericSpec(Collection $rawValues): bool
    {
        if ($rawValues->isEmpty()) {
            return false;
        }

        $numeric = $rawValues->filter(fn ($v) => self::toNumber((string) $v) !== null)->count();

        return ($numeric / $rawValues->count()) >= 0.8;
    }

    public static function displayUnit(string $unit): string
    {
        return self::UNIT_DISPLAY[mb_strtolower($unit)] ?? $unit;
    }

    /**
     * ჯგუფის დომინანტური ერთეული — რომ „12000" და „12000 BTU" ერთ ვარიანტად იქცეს.
     */
    public static function dominantUnit(Collection $rawValues): ?string
    {
        $units = $rawValues
            ->map(fn ($v) => self::parse((string) $v)['unit'])
            ->filter()
            ->countBy();

        return $units->isEmpty() ? null : $units->sortDesc()->keys()->first();
    }

    /**
     * საჩვენებელი, კანონიკური სახე — ჯგუფის ერთეულით.
     *
     *   „12000"      + ერთეული BTU → „12000 BTU"
     *   „35 - 40 მ²" + ერთეული მ²  → „35-40 მ²"
     */
    public static function canonical(string $raw, ?string $unit = null): string
    {
        $p = self::parse($raw);

        if (empty($p['numbers'])) {
            return $p['clean'] !== '' ? $p['clean'] : trim($raw);
        }

        $nums = array_map(
            fn ($n) => rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.'),
            $p['numbers']
        );

        $label = count($nums) >= 2
            ? min($nums) . '-' . max($nums)
            : $nums[0];

        // ტექსტი რიცხვის გვერდით (მაგ. „12000 ინვერტორი") არ დავკარგოთ
        if ($p['text'] !== '') {
            $label .= ' ' . $p['text'];
        }

        $unit = $unit ?: $p['unit'];

        return $unit ? $label . ' ' . self::displayUnit($unit) : $label;
    }
}
