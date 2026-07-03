@section('seo')
    <title>{{ $section->title }} — IAPI.GE</title>
@endsection

@section('meta_description'){{ $section->title }} — იყიდე საუკეთესო ფასად IAPI.GE-ზე. სწრაფი მიტანა და გარანტია მთელ საქართველოში.@endsection

@section('canonical'){{ route('web.section.view', $section->slug) }}@endsection

@section('og_tags')
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $section->title }} — IAPI.GE">
    <meta property="og:description" content="{{ $section->title }} — იყიდე საუკეთესო ფასად IAPI.GE-ზე.">
    <meta property="og:url" content="{{ route('web.section.view', $section->slug) }}">
    <meta property="og:image" content="{{ asset('web-assets/img/logo.png') }}">
@endsection

@section('structured_data')
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "მთავარი",
                "item": "{{ route('web.main.index') }}"
            },
            {
                "@type": "ListItem",
                "position": 2,
                "name": "{{ $section->title }}"
            }
        ]
    }
    </script>
@endsection

<div>
    <section class="container py-4 py-lg-5">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('web.main.index') }}">მთავარი</a>
                </li>
                @if($section->category)
                    <li class="breadcrumb-item">
                        {{ $section->category->translations->where('locale','ka')->first()?->title }}
                    </li>
                @endif
                <li class="breadcrumb-item active" aria-current="page">{{ $section->title }}</li>
            </ol>
        </nav>
        <h1 class="h3 mb-4">{{ $section->title }}</h1>
        @if($products->count() > 0)
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">
                @foreach($products as $product)
                    <div class="col">
                        @include('livewire.web.product.product-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-info">
                <div class="alert-body">ამ სექციაში პროდუქტი ჯერ არ არის.</div>
            </div>
        @endif
    </section>
</div>