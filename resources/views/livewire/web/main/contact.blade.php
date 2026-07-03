@section('seo')
    <title>კონტაქტი — IAPI.GE | დაგვიკავშირდით</title>
@endsection

@section('meta_description')დაგვიკავშირდით IAPI.GE-ზე. მისამართი: თბილისი, შარტავას ქ. №3. ტელ: +995 555 700 720. ელ-ფოსტა: info@iapi.ge. ყოველდღე 10:00-დან 00:00-მდე.@endsection

@section('canonical'){{ route('web.main.contact') }}@endsection

@section('og_tags')
    <meta property="og:type" content="website">
    <meta property="og:title" content="კონტაქტი — IAPI.GE">
    <meta property="og:description" content="დაგვიკავშირდით — თბილისი, შარტავას ქ. №3. ტელ: +995 555 700 720.">
    <meta property="og:url" content="{{ route('web.main.contact') }}">
    <meta property="og:image" content="{{ asset('web-assets/img/logo.png') }}">
@endsection

@section('structured_data')
    @php
        $storeSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Store',
            'name' => 'IAPI.GE',
            'url' => route('web.main.index'),
            'logo' => asset('web-assets/img/logo.png'),
            'telephone' => '+995555700720',
            'email' => 'info@iapi.ge',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'შარტავას ქ. №3',
                'addressLocality' => 'თბილისი',
                'addressCountry' => 'GE',
            ],
            'openingHours' => 'Mo-Su 10:00-00:00',
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($storeSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

<div>
	<section class="position-relative bg-body-tertiary py-4">
		<img src="{{ asset('web-assets/img/contact.png') }}" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover rtl-flip" alt="IAPI.GE კონტაქტი — დაგვიკავშირდით">
		<div class="container position-relative z-2 py-4 py-md-5 my-lg-3 my-xl-4 my-xxl-5">
		  <div class="row pt-lg-2 pb-2 pb-sm-3 pb-lg-4">
			<div class="col-9 col-md-8 col-lg-6">
			  <h1 class="display-4 mb-lg-4 font-neue">დაგვიკავშირდი</h1>
			  <p class="mb-0">გჭირდება დახმარება? მოგვმართე შენთვის ხელსაყრელი მეთოდით და ჩვენ დაგეხმარებით!</p>
			</div>
		  </div>
		</div>
	  </section>
    <section class="container pt-5 mt-2 mt-sm-3 mt-lg-4 mt-xl-5 mb-n3">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4 pt-lg-2 pt-xl-0">
            <div class="col">
                <div class="d-flex align-items-center">
                    <i class="ci-map-pin fs-lg text-dark-emphasis"></i>
                    <h3 class="h6 ps-2 ms-1 mb-0 font-neue">ჩვენი მისამართი</h3>
                </div>
                <hr class="text-dark-emphasis opacity-50 my-3 my-md-4">
                <ul class="list-unstyled">
                    <li>{{ __('trans.address_city') }}</li>
                    <li>ქ. თბილისი, შარტავას ქ. №3</li>
                </ul>
            </div>
            <div class="col">
                <div class="d-flex align-items-center">
                    <i class="ci-phone-outgoing fs-lg text-dark-emphasis"></i>
                    <h3 class="h6 ps-2 ms-1 mb-0 font-neue">დაგვირეკე</h3>
                </div>
                <hr class="text-dark-emphasis opacity-50 my-3 my-md-4">
                <ul class="list-unstyled text-center" style="font-size: 14px;">
                    <li class="d-flex justify-content-between">
                        <span>ტელ:</span>
                        <span>+995 555 700 720</span>
                    </li>
                </ul>
            </div>
            <div class="col">
                <div class="d-flex align-items-center">
                    <i class="ci-mail fs-lg text-dark-emphasis"></i>
                    <h3 class="h6 ps-2 ms-1 mb-0 font-neue">მოგვწერე</h3>
                </div>
                <hr class="text-dark-emphasis opacity-50 my-3 my-md-4">
                <ul class="list-unstyled">
                    <li class="d-flex justify-content-between">
                        <span>ელ-ფოსტა:</span>
                        <span><a href="mailto:info@iapi.ge">info@iapi.ge</a></span>
                    </li>
                </ul>
            </div>
            <div class="col">
                <div class="d-flex align-items-center">
                    <i class="ci-clock fs-lg text-dark-emphasis"></i>
                    <h3 class="h6 ps-2 ms-1 mb-0 font-neue">{{ __('trans.working_hours') }}</h3>
                </div>
                <hr class="text-dark-emphasis opacity-50 my-3 my-md-4">
                <ul class="list-unstyled">
                    <li class="d-flex justify-content-between">
                        <span>ყოველდღე:</span>
                        <span>10:00 - 00:00</span>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</div>