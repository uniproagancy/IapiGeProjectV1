<nav class="container pt-3 my-3 my-md-4" aria-label="breadcrumb">
    @if($product->category?->parent?->id != 2)
    <ol class="breadcrumb font-neue">
        <li class="breadcrumb-item">
            <a href="{{ route('web.main.index') }}"> მთავარი გვერდი</a>
        </li>
        @if($product->category->parent)
            <li class="breadcrumb-item">
                <a href="{{ route('web.products.index', $product->category->parent->translation('ka')->slug) }}">
                    {{ $product->category->parent->translation(app()->getLocale())->title ?? $product->category->parent->translation('ka')->title }}
                </a>
            </li>
        @endif
        <li class="breadcrumb-item">
            <a href="{{ route('web.products.index', $product->category->translation('ka')->slug) }}">
                {{ $product->category->translation(app()->getLocale())->title ?? $product->category->translation('ka')->title }}
            </a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">
            {{ $product->translation(app()->getLocale())->title ?? $product->translation('ka')->title }}
        </li>
    </ol>
    @endif
</nav>
