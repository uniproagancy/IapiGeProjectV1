<div class="d-flex align-items-center">
    <div class="h5 d-flex justify-content-center align-items-center flex-shrink-0 text-primary bg-primary-subtle lh-1 rounded-circle mb-0"
         style="width: 3rem; height: 3rem">
        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }} {{ strtoupper(substr(auth()->user()->lastname, 0, 1)) }}
    </div>
    <div class="min-w-0 ps-3">
        <h5 class="h6 mb-1">{{ auth()->user()->name }} {{ auth()->user()->lastname }}</h5>
        <div class="nav flex-nowrap text-nowrap min-w-0">
            <span>ID: {{ auth()->user()->id }}</span>
        </div>
    </div>
</div>