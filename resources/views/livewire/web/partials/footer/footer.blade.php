<footer class="footer position-relative bg-dark" style="margin-top: 50px">
    <span class="position-absolute top-0 start-0 w-100 h-100 bg-body d-none d-block-dark"></span>
    <div class="container position-relative z-1 pt-sm-2 pt-md-3 pt-lg-4" data-bs-theme="dark">
        <div class="accordion py-5" id="footerLinks">
            <div class="row">
                <div class="col-md-4 d-sm-flex flex-md-column align-items-center align-items-md-start pb-3 mb-sm-4">
                    @include('livewire.web.partials.footer.footer-brand')
                </div>
                <div class="col-md-8">
                    <div class="row row-cols-1 row-cols-sm-3 gx-3 gx-md-4">
                        @include('livewire.web.partials.footer.footer-company')
                        @include('livewire.web.partials.footer.footer-account')
                        @include('livewire.web.partials.footer.footer-customer-service')
                    </div>
                </div>
            </div>
        </div>
        @include('livewire.web.partials.footer.footer-categories')
        @include('livewire.web.partials.footer.footer-copyright')
    </div>
</footer>
@include('livewire.web.partials.scroll-to-top')