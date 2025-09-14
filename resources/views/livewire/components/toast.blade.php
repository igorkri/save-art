<div>
    <div class="toast {{ $type }} {{ $show ? 'on' : '' }}" wire:key="toast-{{ $type }}">
        <div class="head">
            <p>{{ $title }}</p>
            <button type="button" wire:click="close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10.4999 7.99998L15.9999 2.5L13.4999 0L7.99995 5.50012L2.49963 0L0 2.49988L5.50014 8.00002L0 13.5004L2.49988 16L8 10.4999L13.5 15.9999L16 13.4999L10.4999 7.99998Z" />
                </svg>
            </button>
        </div>

        @if(is_string($messages))
            <p>{{ $messages }}</p>
        @elseif(is_array($messages) && count($messages) > 0)
            @if(count($messages) === 1)
                <p>{{ $messages[0] }}</p>
            @else
                <ul>
                    @foreach($messages as $message)
                        <li wire:key="message-{{ $loop->index }}">{{ $message }}</li>
                    @endforeach
                </ul>
            @endif
        @endif
    </div>
</div>
