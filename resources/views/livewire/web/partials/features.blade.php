<section class="container pt-5 mt-1 mt-sm-3 mt-lg-2 d-sm-none d-xs-none d-md-block">
    <div class="row row-cols-2 row-cols-md-3 g-4">
        @php
            $features = [
                [
                    'icon' => 'ci-delivery',
                    'title' => 'მიწოდება',
                    'description' => 'მთელი საქართველოს მასშტაბით'
                ],
                [
                    'icon' => 'ci-credit-card',
                    'title' => 'უსაფრთხო გადახდა',
                    'description' => 'გადახდის უსაფრთხოება გარანტირებულია'
                ],
                [
                    'icon' => 'ci-chat',
                    'title' => '24/7 მხარდაჭერა',
                    'description' => 'მეგობრული მომხმარებლის მხარდაჭერა'
                ]
            ];
        @endphp
        @foreach($features as $feature)
            <div class="col">
                <div class="d-flex flex-column flex-xxl-row align-items-center">
                    <div class="d-flex text-dark-emphasis bg-body-tertiary rounded-circle p-4 mb-3 mb-xxl-0">
                        <i class="{{ $feature['icon'] }} fs-2 m-xxl-1"></i>
                    </div>
                    <div class="text-center text-xxl-start ps-xxl-3">
                        <h3 class="h6 mb-1 font-neue">{{ $feature['title'] }}</h3>
                        <p class="fs-sm mb-0">{{ $feature['description'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>