{{-- messages from redirect()->with('status') and the first error --}}
@if (session('status') || $errors->any())
    <div class="toast-wrap" role="status">
        @if (session('status'))
            <div class="toast">
                <i class="bi bi-check2-circle"></i>
                <span>{{ session('status') }}</span>
                <button type="button" aria-label="Close"><i class="bi bi-x"></i></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="toast error">
                <i class="bi bi-exclamation-circle"></i>
                <span>{{ $errors->first() }}</span>
                <button type="button" aria-label="Close"><i class="bi bi-x"></i></button>
            </div>
        @endif
    </div>
@endif
