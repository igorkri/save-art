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
<script>
    document.addEventListener('livewire:init', function () {
        function autoCloseToast() {
            var toast = document.getElementById('toast');
            if (toast && toast.style.display !== 'none' && {{ $autoClose ? 'true' : 'false' }}) {
                setTimeout(function () {
                    Livewire.dispatch('closeNotification');
                }, 400);
            }
        }
        // Автозакрытие при первом показе
        autoCloseToast();
        // Автозакрытие при динамическом показе
        Livewire.on('showNotification', function () {
            autoCloseToast();
        });
    });
</script>
