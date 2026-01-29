<!doctype html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
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

        .totals-table {
            width: 250px;
            float: right;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .totals-table th, .totals-table td {
            border: 1px solid #ddd;
            padding: 6px;
        }

        .footer {
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
        <img src="{{ public_path('images/logo.png') }}" alt="Company Logo">
    </div>
    <div class="invoice-info">
        <h2>ინვოისი #{{ $order_id }}</h2>
        <small>თარიღი: {{ date('d.m.Y') }}</small>
    </div>
</div>
<div class="info-block">
    <div class="info-left">
        <p style="margin: 0 0 8px 0;"><strong>მომხმარებელი:</strong><br>{{ $user_name }} {{ $user_lastname }}</p>
        <p style="margin: 0 0 8px 0;"><strong>მისამართი:</strong><br>{{ $user_address ?? '---' }}</p>
        <p style="margin: 0 0 8px 0;"><strong>ტელეფონი:</strong><br>{{ $user_phone }}</p>
        <p style="margin: 0;"><strong>ელ-ფოსტა:</strong><br>{{ $user_email }}</p>
    </div>
    <div class="info-right" style="text-align: right;">
        <p style="margin: 0 0 8px 0;"><strong>კომპანია:</strong><br>შპს უნიპრო (416353635)</p>
        <p style="margin: 0 0 8px 0;"><strong>იურიდიული მისამართი:</strong><br>ქ.რუსთავი, 21-ე მ/რ 2# ბინა 69</p>
        <p style="margin: 0 0 8px 0;"><strong>ტელეფონი:</strong><br>+995 557 240 200</p>
        <p style="margin: 0 0 8px 0;"><strong>ელ-ფოსტა:</strong><br>info@unipro.ge</p>
        <p style="margin: 0;"><strong>კომპანიის ანგარიშები:</strong>
            <br>თიბისი ბანკი: GE44TB7110145067800002
            <br>საქართველოს ბანკი: GE73BG0000000378162629
        </p>
    </div>
</div>
<table>
    <thead>
    <tr>
        <th style="width: 70px; text-align:center;">სურათი</th>
        <th>პროდუქტი</th>
        <th style="text-align:center;">რაოდენობა</th>
        <th style="text-align:center;">ფასი</th>
        <th style="text-align:center;">ჯამი</th>
    </tr>
    </thead>
    <tbody>
    @php
        $subtotal = 0;
    @endphp
    @foreach($products as $product)
        @php
            $line_total = ($product->quantity * $product->price) / 100;
            $subtotal += $line_total;
        @endphp
        <tr>
            <td style="text-align:center;">
                <img src="{{ public_path('storage/'.$product->product->main_image) }}" width="50">
            </td>
            <td>
                {{ $product->product->translations->where('locale','ka')->first()->title }}
            </td>
            <td style="text-align:center;">{{ $product->quantity }}</td>
            <td style="text-align:center;">
                {{ number_format($product->price / 100, 2) }}
            </td>
            <td style="text-align:center;">
                {{ number_format($line_total, 2) }}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
@php
    $delivery = 0;
    $total = $subtotal + $delivery;
    $vat_percent = 0.18;
    $vat_amount = $total * $vat_percent / (1+$vat_percent);
@endphp
<table class="totals-table">
    <tr>
        <th>სრული ჯამი:</th>
        <td>{{ number_format($subtotal, 2) }}</td>
    </tr>
    <tr>
        <th>მათშორის დღგ:</th>
        <td>{{ number_format($vat_amount, 2) }}</td>
    </tr>
    <tr>
        <th>მიტანის მომსახურება:</th>
        <td>{{ number_format($delivery / 100, 2) }}</td>
    </tr>
    <tr>
        <th>სრული თანხა:</th>
        <td><strong>{{ number_format($total, 2) }}</strong></td>
    </tr>
</table>
<div class="footer">
    ეს დოკუმენტი ავტომატურად დაგენერირდა Unipro.ge სისტემით.<br>
    <strong>დამატებითი ინფორმაციისთვის: info@unipro.ge | +995 557 240 200</strong>
</div>
</body>
</html>
