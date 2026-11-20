@if (session('status'))
    <div class="container">
        <div class="alert alert-success"><i class="bi bi-check2-circle"></i> {{ session('status') }}</div>
    </div>
@endif

@if (session('error'))
    <div class="container">
        <div class="alert alert-error"><i class="bi bi-exclamation-circle"></i> {{ session('error') }}</div>
    </div>
@endif

@if (session('success'))
    <div class="container">
        <div class="alert alert-success"><i class="bi bi-check2-circle"></i> {{ session('success') }}</div>
    </div>
@endif