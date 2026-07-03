@section('seo')
    <title>{{ $content->heading }} — IAPI.GE</title>
@endsection

@section('meta_description'){{ \Illuminate\Support\Str::limit(strip_tags($content->text), 155) }}@endsection

@section('canonical'){{ route('web.static.index', $page) }}@endsection

@section('og_tags')
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $content->heading }} — IAPI.GE">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($content->text), 155) }}">
    <meta property="og:url" content="{{ route('web.static.index', $page) }}">
    <meta property="og:image" content="{{ asset('web-assets/img/logo.png') }}">
@endsection

<main class="content-wrapper">
    <div class="container py-5 mb-2 mt-n2 mt-sm-1 my-md-3 my-lg-4 mb-xl-5">
        <div class="row justify-content-center">
            <div class="col-lg-11 col-xl-10 col-xxl-9">
                <h1 class="h2 pb-2 pb-sm-2 pb-lg-2 font-neue" style="font-size: 16px">{{ $content->heading }}</h1>
                <hr class="mt-0">
                {!! $content->text !!}
            </div>
        </div>
    </div>
</main>