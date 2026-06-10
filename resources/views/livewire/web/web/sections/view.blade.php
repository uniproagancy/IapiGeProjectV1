@extends('layouts.web')

@section('title', $section->title . ' — iapi.ge')

@section('content')
    <div class="container py-4 py-lg-5">

        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('web.home') }}">მთავარი</a></li>
                @if($section->category)
                    <li class="breadcrumb-item">
                        {{ $section->category->translations->where('locale','ka')->first()?->title }}
                    </li>
                @endif
                <li class="breadcrumb-item active">{{ $section->title }}</li>
            </ol>
        </nav>

        <h1 class="h3 mb-4">{{ $section->title }}</h1>

        @if($products->count() > 0)
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">
                @foreach($products as $product)
                    <div class="col">
                        @include('web.partials.product-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-info">ამ სექციაში პროდუქტი ჯერ არ არის.</div>
        @endif

    </div>
@endsection