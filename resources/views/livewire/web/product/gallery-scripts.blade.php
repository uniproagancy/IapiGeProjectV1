<script>
    document.addEventListener('livewire:initialized', () => {
        initProductGallery();
    });

    function initProductGallery() {
        const productId = '{{ $product->id }}';
        const thumbsEl = document.getElementById('thumbs-' + productId);
        const mainEl = document.getElementById('main-' + productId);

        if (thumbsEl && mainEl) {
            const thumbs = new Swiper('#thumbs-' + productId, {
                direction: 'vertical',
                slidesPerView: 5,
                spaceBetween: 10,
                watchSlidesProgress: true,
            });

            const mainSwiper = new Swiper('#main-' + productId, {
                loop: true,
                spaceBetween: 10,
                navigation: {
                    nextEl: '.slider-next',
                    prevEl: '.slider-prev',
                },
                thumbs: {
                    swiper: thumbs,
                }
            });
        }
    }

    function productGallery() {
        return {
            mainSwiper: null,
            init() {
                this.$nextTick(() => {
                    initProductGallery();
                });
            },
            nextSlide() {
                if (this.mainSwiper) this.mainSwiper.slideNext();
            },
            prevSlide() {
                if (this.mainSwiper) this.mainSwiper.slidePrev();
            }
        }
    }

    function swiperInit(section) {
        return {
            swiper: null,
            init() {
                this.$nextTick(() => {
                    const el = document.querySelector(`.my-swiper[data-section="${section}"]`);
                    if (el) {
                        this.swiper = new Swiper(el, {
                            spaceBetween: 30,
                            loop: false,
                            navigation: {
                                nextEl: `.swiper-next[data-section="${section}"]`,
                                prevEl: `.swiper-prev[data-section="${section}"]`,
                            },
                            breakpoints: {
                                0: {slidesPerView: 2},
                                576: {slidesPerView: 3},
                                768: {slidesPerView: 4},
                                992: {slidesPerView: 5},
                                1200: {slidesPerView: 6},
                            }
                        });
                    }
                });
            }
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        initProductGallery();
    });
</script>