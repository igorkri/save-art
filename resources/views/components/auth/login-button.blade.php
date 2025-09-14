<button type="button" class="login" wire:click="$emit('openLoginForm')">
    {{ $slot }}
    @isset($svg)
        {!! $svg !!}
    @endisset
</button>

