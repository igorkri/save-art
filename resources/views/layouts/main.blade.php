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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <title>Save-Art</title>
</head>
<body>

<livewire:components.toast
    type="red"
    title="Помилка"
    :messages="['Error text',]"
    :show="false"
/>

<div class="modal_fill" id="modal_fill">
    <div class="modal_blur"></div>
    <livewire:auth.login-form/>
    <livewire:auth.register-form/>
    <livewire:auth.reset-form/>
</div>
<x-header/>
@yield('content')

<livewire:components.advertising/>
<x-footer/>

{{-- Показать flash сообщения --}}
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('status'))
    <div class="alert alert-info">{{ session('status') }}</div>
@endif

@livewireScripts
</body>
</html>
