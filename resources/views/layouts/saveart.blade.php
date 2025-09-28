<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Unbounded:wght@600..700&family=Wix+Madefor+Display&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @livewireStyles
    <title>@yield('title', 'Save-Art')</title>
</head>
<body>
<livewire:notification
    :title="session('notification.title')"
    :message="session('notification.message')"
    :show="session()->has('notification')"
    :class="session('notification.class', 'red')"
    :auto-close="session('notification.autoClose', true)"
/>
<div class="modal_fill" id="modal_fill">
    <div class="modal_content" id="application">
        <div class="head">
            <div class="title">
                <h6>Заявка</h6>
                <button type="button" class="close">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M10.4999 7.99998L15.9999 2.5L13.4999 0L7.99995 5.50012L2.49963 0L0 2.49988L5.50014 8.00002L0 13.5004L2.49988 16L8 10.4999L13.5 15.9999L16 13.4999L10.4999 7.99998Z"/>
                    </svg>
                </button>
            </div>
        </div>
        <div class="body">
            <form class="login_form">
                <div class="input">
                    <p>Ваше ім’я</p>
                    <div>
                        <input type="text" placeholder="Вкажіть повне ім’я">
                    </div>

                </div>
                <div class="input">
                    <p>Електронна пошта</p>
                    <div>
                        <input type="text" placeholder="sample@mail.com">
                    </div>

                </div>
                <div class="input">
                    <p>Телефон</p>
                    <div>
                        <input type="number" placeholder="Вкажіть номер">
                    </div>

                </div>
                <div class="input">
                    <p>Розкажіть про себе</p>
                    <div>
                        <textarea rows="4" placeholder="Одне-два речення"></textarea>
                    </div>
                </div>
                <div class="file">
                    <label>
                        <div class="img"></div>
                        <input type="file">
                        <span>Завантажте резюме (не обов’язково)</span>
                    </label>
                    <button type="button">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M10.4999 7.99998L15.9999 2.5L13.4999 0L7.99995 5.50012L2.49963 0L0 2.49988L5.50014 8.00002L0 13.5004L2.49988 16L8 10.4999L13.5 15.9999L16 13.4999L10.4999 7.99998Z"/>
                        </svg>
                    </button>
                </div>
                <button type="submit" class="btn">Відправити</button>
            </form>
        </div>
    </div>
</div>

<div class="modal" id="modal">
    <livewire:auth.auth-modal />
</div>

@include('layouts.partials.header')
@yield('content')

<livewire:advertising>
@include('layouts.partials.footer')
@livewireScripts
@stack('scripts')
<script src="{{ asset('js/script.js') }}" defer></script>
@if(session('notification'))
<script>
    window.addEventListener('livewire:load', function () {
        console.log('Livewire loaded, emitting notification:', @json(session('notification.title')), @json(session('notification.message')));
        window.livewire.emit('showNotification', @json(session('notification.title')), @json(session('notification.message')));
    });
</script>
@endif
</body>
</html>
