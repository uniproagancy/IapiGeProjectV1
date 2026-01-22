<ul class="navbar-nav position-relative font-neue">
    @foreach($menu_list as $menuItem)
        <li class="nav-item me-lg-n1 me-xl-0">
            <a class="nav-link"
               href="{{ url($menuItem->url) }}"
               style="font-size: 14px; margin-top: 7px">
                {{ $menuItem->translation(app()->getLocale())->title ?? $menuItem->translation('ka')->title }}
            </a>
        </li>
    @endforeach
</ul>
{{--<ul class="navbar-nav ms-auto">--}}
{{--    <li class="nav-item dropdown me-lg-n2 me-xl-n1">--}}
{{--        <a class="nav-link dropdown-toggle fs-sm px-3" href="#!" role="button" data-bs-toggle="dropdown" data-bs-trigger="hover" aria-expanded="false">--}}
{{--            {{ strtoupper(app()->getLocale()) }}--}}
{{--        </a>--}}
{{--        <ul class="dropdown-menu fs-sm" style="--cz-dropdown-min-width: 7.5rem; --cz-dropdown-spacer: .25rem">--}}
{{--            <li>--}}
{{--                <a class="dropdown-item {{ app()->getLocale() === 'ka' ? 'active' : '' }}"--}}
{{--                   href="{{ LaravelLocalization::getLocalizedURL('ka') }}">--}}
{{--                    ქართული--}}
{{--                </a>--}}
{{--            </li>--}}
{{--            <li>--}}
{{--                <a class="dropdown-item {{ app()->getLocale() === 'en' ? 'active' : '' }}"--}}
{{--                   href="{{ LaravelLocalization::getLocalizedURL('en') }}">--}}
{{--                    English--}}
{{--                </a>--}}
{{--            </li>--}}
{{--            <li>--}}
{{--                <a class="dropdown-item {{ app()->getLocale() === 'ru' ? 'active' : '' }}"--}}
{{--                   href="{{ LaravelLocalization::getLocalizedURL('ru') }}">--}}
{{--                    Русский--}}
{{--                </a>--}}
{{--            </li>--}}
{{--        </ul>--}}
{{--    </li>--}}
{{--</ul>--}}