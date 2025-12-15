<ul class="navbar-nav position-relative font-neue">
    @foreach($menu_list as $menuItem)
        <li class="nav-item me-lg-n1 me-xl-0">
            <a class="nav-link"
               href="{{ url($menuItem->url) }}"
               style="font-size: 14px">
                {{ $menuItem->translation(app()->getLocale())->title ?? $menuItem->translation('ka')->title }}
            </a>
        </li>
    @endforeach
</ul>