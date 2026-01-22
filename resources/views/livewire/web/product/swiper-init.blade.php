<style>
    .swiper-prev, .swiper-next {
        width: 40px;
        height: 40px;
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

    .swiper-prev { left: -50px; }
    .swiper-next { right: -50px; }

    .swiper-prev:hover,
    .swiper-next:hover {
        background: #f2223b;
        color: #ffffff;
    }

    .swiper-prev i,
    .swiper-next i {
        font-size: 20px;
    }
</style>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".product-swiper").forEach(function (el) {
            const id = el.dataset.section;
            new Swiper(el, {
                spaceBetween: 30,
                loop: false,
                navigation: {
                    nextEl: `.swiper-next[data-section="${id}"]`,
                    prevEl: `.swiper-prev[data-section="${id}"]`,
                },
                breakpoints: {
                    0: { slidesPerView: 2 },
                    576: { slidesPerView: 3 },
                    768: { slidesPerView: 4 },
                    992: { slidesPerView: 5 },
                    1200: { slidesPerView: 6 },
                }
            });
        });

        document.querySelectorAll(".brand-swiper").forEach(function (el) {
            console.log(el);
            const id = el.dataset.section;
            new Swiper(el, {
                spaceBetween: 30,
                loop: false,
                navigation: {
                    nextEl: `.swiper-next[data-section="${id}"]`,
                    prevEl: `.swiper-prev[data-section="${id}"]`,
                },
                breakpoints: {
                    0: { slidesPerView: 2 },
                    576: { slidesPerView: 3 },
                    768: { slidesPerView: 4 },
                    992: { slidesPerView: 5 },
                    1200: { slidesPerView: 5 },
                }
            });
        });
    });
</script>