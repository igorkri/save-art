<div class="toast {{ $class }}{{ $show ? ' on' : '' }}" id="toast" style="display: {{ $show ? 'block' : 'none' }};">
    <div class="head">
        <p>{{ $title }}</p>
        <button type="button" wire:click="closeNotification">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M10.4999 7.99998L15.9999 2.5L13.4999 0L7.99995 5.50012L2.49963 0L0 2.49988L5.50014 8.00002L0 13.5004L2.49988 16L8 10.4999L13.5 15.9999L16 13.4999L10.4999 7.99998Z"/>
            </svg>
        </button>
    </div>

    <p>{{ $message }}</p>
</div>

<!-- Auto-close notification -->


@if($show && $autoClose)
    @push('scripts')
        <script>
            window.addEventListener('livewire:load', function () {
                setTimeout(function() {
                    if (window.livewire && window.livewire.find) {
                        window.livewire.find(@this.__instance.id).call('closeNotification');
                        console.log('Livewire notification auto-close triggered');
                    } else {
                        console.warn('Livewire not ready for notification auto-close');
                    }
                }, 4000);
            });
        </script>
    @endpush
@endif
