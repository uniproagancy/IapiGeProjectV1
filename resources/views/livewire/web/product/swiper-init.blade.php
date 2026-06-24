<style>
    .swiper-prev, .swiper-next {
        width: 35px;
        height: 35px;
        background: #eef1f6;
        color: #252525;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        z-index: 10;
        cursor: pointer;
        transition: color .25s ease-in-out, background-color .25s ease-in-out;
    }

    .swiper-prev { left: 0px; }
    .swiper-next { right: 0px; }

    .swiper-prev:hover,
    .swiper-next:hover {
        background: #f2223b;
        color: #ffffff;
    }

    .swiper-prev i,
    .swiper-next i { font-size: 20px; }
</style>

<script>
    function initSwipers() {
        document.querySelectorAll(".product-swiper").forEach(function (el) {
            // თუ უკვე ინიციალიზებულია — გამოვტოვოთ
            if (el.swiper) return;

            const id = el.dataset.section;
            new Swiper(el, {
                spaceBetween: 30,
                loop: false,
                navigation: {
                    nextEl: `.swiper-next[data-section="${id}"]`,
                    prevEl: `.swiper-prev[data-section="${id}"]`,
                },
                breakpoints: {
                    0:    { slidesPerView: 2 },
                    576:  { slidesPerView: 3 },
                    768:  { slidesPerView: 4 },
                    992:  { slidesPerView: 5 },
                    1200: { slidesPerView: 6 },
                }
            });
        });

        document.querySelectorAll(".brand-swiper").forEach(function (el) {
            if (el.swiper) return;

            const id = el.dataset.section;
            new Swiper(el, {
                spaceBetween: 30,
                loop: false,
                navigation: {
                    nextEl: `.swiper-next[data-section="${id}"]`,
                    prevEl: `.swiper-prev[data-section="${id}"]`,
                },
                breakpoints: {
                    0:    { slidesPerView: 2 },
                    576:  { slidesPerView: 3 },
                    768:  { slidesPerView: 4 },
                    992:  { slidesPerView: 5 },
                    1200: { slidesPerView: 5 },
                }
            });
        });
    }

    // პირველი ჩატვირთვა
    document.addEventListener("DOMContentLoaded", initSwipers);

    // Livewire update-ის შემდეგ ხელახლა
    document.addEventListener("livewire:navigated", initSwipers);
    document.addEventListener("livewire:update", initSwipers);

    // ============ Wishlist Batch Load ============
    // Alpine Store — wishlist product IDs
    document.addEventListener('alpine:init', () => {
        Alpine.store('wishlist', {
            ids: [],
            load(productIds) {
                if (!productIds.length) return;
                @auth
                fetch('/wishlist/check', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                    },
                    body: JSON.stringify({ ids: productIds }),
                })
                    .then(r => r.ok ? r.json() : [])
                    .then(ids => { Alpine.store('wishlist').ids = ids; })
                    .catch(() => {});
                @else
                // არ არის ავტორიზებული — ცარიელი
                @endauth
            }
        });
    });

    function loadWishlistStatuses() {
        const ids = [...document.querySelectorAll('[data-product-id]')]
            .map(el => parseInt(el.dataset.productId))
            .filter(Boolean);

        if (ids.length && window.Alpine) {
            Alpine.store('wishlist').load(ids);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(loadWishlistStatuses, 100);
    });

    document.addEventListener('livewire:navigated', loadWishlistStatuses);

    // Livewire v3
    if (typeof Livewire !== 'undefined') {
        Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
            succeed(({ snapshot, effect }) => {
                setTimeout(initSwipers, 50);
            });
        });
    }
</script>