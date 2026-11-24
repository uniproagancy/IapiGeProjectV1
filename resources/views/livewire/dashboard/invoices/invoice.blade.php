{{--
    ინვოისის PDF — შეკვეთისთვისაც და ხელით გაცემულისთვისაც.
    მონაცემები App\Services\Invoice\InvoicePdf-იდან მოდის; ყველა თანხა ლარშია.

    ⚠️ ₾ სიმბოლო არ გამოიყენო — dompdf-ის DejaVu Sans-ს ეს გლიფი არ აქვს
       და PDF-ში ცარიელ კვადრატად ჩანს. ვწერთ „ლარი".
--}}
@php
    $subtotal = collect($items)->sum(fn ($i) => $i['quantity'] * $i['price']);
    $delivery = (float) ($delivery ?? 0);
    $total    = $subtotal + $delivery;
    $vatRate  = 0.18;
    $vat      = $total * $vatRate / (1 + $vatRate);   // დღგ ფასში შედის
    $hasImages = collect($items)->contains(fn ($i) => !empty($i['image']));
    $logo      = public_path('web-assets/img/logo.png');
    $money     = fn ($v) => number_format((float) $v, 2, '.', ' ');
@endphp
<!doctype html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
        }

        .header {
            width: 100%;
            display: table;
            margin-bottom: 20px;
        }

        .logo {
            display: table-cell;
            width: 50%;
            vertical-align: middle;
        }

        .logo img {
            width: 160px;
        }

        .invoice-info {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: middle;
        }

        .invoice-info h2 {
            margin: 0 0 4px;
        }

        .info-block {
            width: 100%;
            display: table;
            margin-bottom: 20px;
        }

        .info-left, .info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .info-block p {
            margin: 0 0 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 6px;
        }

        th {
            background: #f5f5f5;
        }

        .num {
            text-align: center;
            white-space: nowrap;
        }

        .totals-table {
            width: 260px;
            float: right;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .totals-table th {
            text-align: left;
        }

        .totals-table td {
            text-align: right;
            white-space: nowrap;
        }

        .note {
            clear: both;
            padding-top: 20px;
            font-size: 11px;
            color: #444;
        }

        .footer {
            clear: both;
            width: 100%;
            text-align: center;
            margin-top: 40px;
            font-size: 11px;
            color: #555;
        }
    </style>
</head>
<body>
<div class="header">
    <div class="logo">
        @if(is_file($logo))
            <img src="{{ $logo }}" alt="IAPI.GE">
        @endif
    </div>
    <div class="invoice-info">
        <h2>ინვოისი #{{ $number }}</h2>
        <small>თარიღი: {{ $date }}</small>
    </div>
</div>

<div class="info-block">
    <div class="info-left">
        <p><strong>მომხმარებელი:</strong><br>{{ $customer['name'] ?: '---' }}</p>
        @if(!empty($customer['code']))
            <p><strong>საიდენტიფიკაციო კოდი:</strong><br>{{ $customer['code'] }}</p>
        @endif
        <p><strong>მისამართი:</strong><br>{{ $customer['address'] ?: '---' }}</p>
        <p><strong>ტელეფონი:</strong><br>{{ $customer['phone'] ?: '---' }}</p>
        <p style="margin: 0;"><strong>ელ-ფოსტა:</strong><br>{{ $customer['email'] ?: '---' }}</p>
    </div>
    <div class="info-right" style="text-align: right;">
        <p><strong>კომპანია:</strong><br>შპს უნიპრო (416353635)</p>
        <p><strong>იურიდიული მისამართი:</strong><br>ქ.რუსთავი, 21-ე მ/რ 2# ბინა 69</p>
        <p><strong>ტელეფონი:</strong><br>+995 557 240 200</p>
        <p><strong>ელ-ფოსტა:</strong><br>info@unipro.ge</p>
        <p style="margin: 0;"><strong>კომპანიის ანგარიშები:</strong>
            <br>თიბისი ბანკი: GE44TB7110145067800002
            <br>საქართველოს ბანკი: GE73BG0000000378162629
        </p>
    </div>
</div>

<table>
    <thead>
    <tr>
        <th class="num" style="width: 28px;">#</th>
        @if($hasImages)
            <th class="num" style="width: 64px;">სურათი</th>
        @endif
        <th>პროდუქტი / მომსახურება</th>
        <th class="num">რაოდენობა</th>
        <th class="num">ფასი (ლარი)</th>
        <th class="num">ჯამი (ლარი)</th>
    </tr>
    </thead>
    <tbody>
    @foreach($items as $i => $item)
        <tr>
            <td class="num">{{ $i + 1 }}</td>
            @if($hasImages)
                <td class="num">
                    @if(!empty($item['image']))
                        <img src="{{ $item['image'] }}" width="50">
                    @endif
                </td>
            @endif
            <td>{{ $item['name'] }}</td>
            <td class="num">{{ rtrim(rtrim(number_format($item['quantity'], 2, '.', ''), '0'), '.') }}</td>
            <td class="num">{{ $money($item['price']) }}</td>
            <td class="num">{{ $money($item['quantity'] * $item['price']) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="totals-table">
    <tr>
        <th>პროდუქციის ჯამი:</th>
        <td>{{ $money($subtotal) }} ლარი</td>
    </tr>
    <tr>
        <th>მიტანის მომსახურება:</th>
        <td>{{ $money($delivery) }} ლარი</td>
    </tr>
    <tr>
        <th>მათ შორის დღგ (18%):</th>
        <td>{{ $money($vat) }} ლარი</td>
    </tr>
    <tr>
        <th>სრული თანხა:</th>
        <td><strong>{{ $money($total) }} ლარი</strong></td>
    </tr>
</table>

@if(!empty($note))
    <div class="note">
        <strong>შენიშვნა:</strong> {!! nl2br(e($note)) !!}
    </div>
@endif

<div class="footer">
    ეს დოკუმენტი ავტომატურად დაგენერირდა Unipro.ge სისტემით.<br>
    <strong>დამატებითი ინფორმაციისთვის: info@unipro.ge | +995 557 240 200</strong>
</div>
</body>
</html>
