<!-- ✅ Categories Offcanvas - FIXED Mobile + Text Alignment -->

<div>
    <div class="offcanvas offcanvas-start pb-sm-2 px-sm-2 p-0"
         id="categoryOffcanvas"
         tabindex="-1"
         aria-labelledby="categoryOffcanvasLabel"
         style="width: 400px; z-index: 1060; padding: 0 !important;">

        <!-- ✅ HEADER -->
        <div class="offcanvas-header flex-column align-items-start py-3 pt-lg-4">
            <div class="d-flex align-items-center justify-content-between w-100 mb-3 mb-lg-4">
                <h5 class="offcanvas-title font-neue" id="categoryOffcanvasLabel">
                    კატეგორიები
                </h5>
                <button type="button"
                        class="btn-close text-white"
                        data-bs-dismiss="offcanvas"
                        aria-label="დახურვა"
                        style="background-color: #fff; opacity: 1 !important;"></button>
            </div>
        </div>

        <!-- ✅ BODY - CATEGORIES LIST -->
        <div class="offcanvas-body d-flex flex-column gap-0 pt-2 p-0">
                <ul class="w-100 rounded-top-0 rounded-bottom-4 py-1"
                    style="list-style: none; padding: 0 !important; margin: 0;">
                @forelse($product_categories as $category)
                    <li class="position-static border-bottom offcanvas-cat-item">
                        <div class="position-relative pt-2 pb-2 px-4">
                            <!-- ✅ ONE STRUCTURE FOR ALL SCREENS -->
                            @if($category->children->where('active', 1)->where('show', 1)->count() > 0)
                                <!-- ✅ Has subcategories - Expandable button -->
                                <button type="button"
                                        class="w-100 btn btn-link text-start p-0 d-flex align-items-center gap-2 text-decoration-none offcanvas-cat-btn"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#subcategories-{{ $category->id }}"
                                        aria-expanded="false"
                                        style="color: #252525;">
                                    <img src="{{ asset('web-assets/icons/categories/'.$category->id.'.svg') }}"
                                         width="25"
                                         height="25"
                                         alt=""
                                         class="flex-shrink-0">
                                    <span class="flex-grow-1 font-neue fw-medium text-start"
                                          style="font-size: 13px;">
                                {{ $category->translation(app()->getLocale())->title ?? $category->translation('ka')->title }}
                            </span>
                                    <i class="ci-chevron-right fs-base offcanvas-cat-chevron flex-shrink-0"
                                       style="transition: transform 0.3s ease;"></i>
                                </button>
                            @else
                                <!-- ✅ No subcategories - Direct link -->
                                <a href="{{ route('web.products.index', $category->translation(app()->getLocale())->slug ?? $category->translation('ka')->slug) }}"
                                   class="d-flex align-items-center gap-2 text-decoration-none font-neue fw-medium offcanvas-cat-link"
                                   style="font-size: 13px; color: #252525;">
                                    <img src="{{ asset('web-assets/icons/categories/'.$category->id.'.svg') }}"
                                         width="25"
                                         height="25"
                                         alt=""
                                         class="flex-shrink-0">
                                    <span class="flex-grow-1 font-neue fw-medium text-start">
                                {{ $category->translation(app()->getLocale())->title ?? $category->translation('ka')->title }}
                            </span>
                                </a>
                            @endif
                        </div>

                        <!-- ✅ SUBCATEGORIES - ONE FOR ALL SCREENS -->
                        @if($category->children->where('active', 1)->where('show', 1)->count() > 0)
                            <div class="collapse" id="subcategories-{{ $category->id }}">
                                <ul class="list-unstyled bg-light ps-0 ms-0 mb-0"
                                    style="padding: 0; margin: 0; border-top: 1px solid #e9ecef;">
                                    @foreach($category->children->where('active', 1)->where('show', 1) as $subcategory)
                                        <li class="border-bottom">
                                            <a href="{{ route('web.products.index', $subcategory->translation(app()->getLocale())->slug ?? $subcategory->translation('ka')->slug) }}"
                                               class="d-flex align-items-center gap-2 py-3 px-4 text-decoration-none font-neue fw-normal offcanvas-subcat-link"
                                               style="font-size: 12px; color: #555; transition: all 0.3s ease;"
                                               onclick="bootstrap.Offcanvas.getInstance(document.getElementById('categoryOffcanvas'))?.hide()">
                                                <i class="ci-tag opacity-50 flex-shrink-0"></i>
                                                <span class="text-truncate text-start">
                                            {{ $subcategory->translation(app()->getLocale())->title ?? $subcategory->translation('ka')->title }}
                                        </span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </li>
                @empty
                    <li class="p-4 text-center text-muted">
                        <p class="font-neue">კატეგორიები არ მოიძებნა</p>
                    </li>
                @endforelse
            </ul>
        </div>
    </div>

    <style>
        .offcanvas {
            transition: visibility 0.3s ease, transform 0.3s ease;
        }

        .offcanvas-backdrop {
            transition: opacity 0.3s ease;
        }

        /* ✅ Category item hover effect -->
        .category-item {
            transition: background-color 0.3s ease;
        }

        .category-item:hover {
            background-color: #f8f9fa;
        }

        .category-item:hover a,
        .category-item:hover button,
        .category-item:hover span {
            color: #ff6900 !important;
        }

        .category-item:hover img {
            opacity: 0.8;
        }

        /* ✅ Category link styling -->
        .category-link {
            color: #252525;
            text-decoration: none;
        }

        .category-link:hover {
            color: #ff6900 !important;
        }

        /* ✅ Chevron rotation animation -->
        .transition-chevron {
            transition: transform 0.3s ease;
            display: inline-block;
        }

        /* Desktop chevron rotation -->
        .d-lg-flex[data-bs-toggle="collapse"][aria-expanded="true"] .transition-chevron {
            transform: rotate(90deg);
        }

        /* Mobile chevron rotation -->
        .btn-link[data-bs-toggle="collapse"][aria-expanded="true"] .transition-chevron {
            transform: rotate(90deg);
        }

        /* ✅ Subcategory link styling -->
        .subcategory-link,
        .subcategory-link-mobile {
            color: #555 !important;
            transition: all 0.3s ease;
            text-align: left;
        }

        .subcategory-link:hover,
        .subcategory-link-mobile:hover {
            color: #ff6900 !important;
            background-color: #f0f0f0 !important;
            padding-left: calc(1rem + 2px) !important;
        }

        /* ✅ Remove default button styles -->
        .btn-link {
            color: inherit !important;
            text-decoration: none !important;
        }

        .btn-link:hover,
        .btn-link:focus {
            color: #ff6900 !important;
            text-decoration: none !important;
        }

        /* ✅ Subcategory background -->
        .bg-light {
            background-color: #f8f9fa !important;
        }

        /* ✅ Text alignment - LEFT -->
        .text-start {
            text-align: left !important;
        }

        /* ✅ Ensure text doesn't wrap -->
        .text-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* ✅ Min width for proper layout -->
        .min-w-0 {
            min-width: 0;
        }

        /* ✅ Responsive adjustments -->
        @media (max-width: 768px) {
            .offcanvas {
                width: 100% !important;
                max-width: 400px;
            }
        }
    </style>

    <!-- ✅ JAVASCRIPT - Chevron rotation for both desktop and mobile -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            // ✅ Handle ALL collapse buttons (desktop and mobile)
            const allCollapseButtons = document.querySelectorAll('[data-bs-toggle="collapse"]');

            allCollapseButtons.forEach(button => {
                const target = button.getAttribute('data-bs-target');
                const collapseElement = document.querySelector(target);

                if (collapseElement) {
                    // ✅ On show
                    collapseElement.addEventListener('show.bs.collapse', () => {
                        button.setAttribute('aria-expanded', 'true');
                    });

                    // ✅ On hide
                    collapseElement.addEventListener('hide.bs.collapse', () => {
                        button.setAttribute('aria-expanded', 'false');
                    });
                }
            });
        });
    </script>
</div>