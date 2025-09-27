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
<livewire:notification />
<div class="modal_fill" id="modal_fill">

    <!-- login -->
    <div class="modal_content" id="modal_login">
        <div class="head">
            <div class="title">
                <h6>save-art.in.ua</h6>
                <button type="button" class="close">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M10.4999 7.99998L15.9999 2.5L13.4999 0L7.99995 5.50012L2.49963 0L0 2.49988L5.50014 8.00002L0 13.5004L2.49988 16L8 10.4999L13.5 15.9999L16 13.4999L10.4999 7.99998Z"/>
                    </svg>
                </button>
            </div>
            <h6>
                save-art.in.ua — платформа для митців та бажаючих долучитись до створення, розвитку і збереження
                новітнього
                українського
                мистецтва
            </h6>
        </div>
        <div class="body">
            <div>
                <div class="switching">
                    <button type="button" class="on">
                        <h6>Вхід до спільноти</h6>
                    </button>
                    <button type="button">
                        <h6>Реєстрація</h6>
                    </button>
                </div>
                <div class="links">
                    <a href="#">save-art.in.ua</a>
                    <a href="#">art-ua.com</a>
                    <a href="#">art-ua.info</a>
                </div>
            </div>
            <form class="login_form">
                <div class="input">
                    <div>
                        <input type="text" placeholder="Ім’я або електронна пошта">
                    </div>

                </div>
                <div class="input pass">
                    <div>
                        <input type="password" placeholder="Пароль">
                        <button type="button" class="eye"></button>
                    </div>

                </div>
                <button type="button" class="forgot">Я не пам’ятаю пароль</button>
                <button type="submit" class="btn">Увійти</button>
            </form>
        </div>
        <div class="foot">
            <a href="" class="google">Продовжити з Google</a>
        </div>
    </div>

    <!-- register -->
    <div class="modal_content" id="modal_register">
        <div class="head">
            <div class="title">
                <h6>save-art.in.ua</h6>
                <button type="button" class="close">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M10.4999 7.99998L15.9999 2.5L13.4999 0L7.99995 5.50012L2.49963 0L0 2.49988L5.50014 8.00002L0 13.5004L2.49988 16L8 10.4999L13.5 15.9999L16 13.4999L10.4999 7.99998Z"/>
                    </svg>
                </button>
            </div>
            <h6>
                save-art.in.ua — платформа для митців та бажаючих долучитись до створення, розвитку і збереження
                новітнього
                українського
                мистецтва
            </h6>
        </div>
        <div class="body">
            <div>
                <div class="switching">
                    <button type="button">
                        <h6>Вхід до спільноти</h6>
                    </button>
                    <button type="button" class="on">
                        <h6>Реєстрація</h6>
                    </button>
                </div>
                <div class="links">
                    <a href="#">save-art.in.ua</a>
                    <a href="#">art-ua.com</a>
                    <a href="#">art-ua.info</a>
                </div>
            </div>
            <form class="login_form">
                <div class="input">
                    <div>
                        <input type="text" placeholder="Ваше ім’я">
                    </div>

                </div>
                <div class="input">
                    <div>
                        <input type="text" placeholder="Електронна пошта">
                    </div>

                </div>
                <div class="input pass">
                    <div>
                        <input type="password" placeholder="Пароль">
                        <button type="button" class="eye"></button>
                    </div>

                </div>
                <div class="input pass">
                    <div>
                        <input type="password" placeholder="Повторіть пароль">
                        <button type="button" class="eye"></button>
                    </div>

                </div>
                <label class="checkbox">
                    <input type="checkbox">
                    <span>Я приймаю умови використання платформи</span>
                </label>
                <button type="submit" class="btn">Увійти</button>
            </form>
        </div>
        <div class="foot">
            <a href="" class="google">Продовжити з Google</a>
        </div>
    </div>

    <!-- reset pass -->
    <div class="modal_content" id="reset_pass">
        <div class="head">
            <div class="title">
                <button type="button" class="back">
                    <svg width="8" height="12" viewBox="0 0 8 12" fill="" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 12L0 6L8 0L8 12Z"/>
                    </svg>
                </button>
                <h6>Відновлення паролю</h6>
                <button type="button" class="close">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M10.4999 7.99998L15.9999 2.5L13.4999 0L7.99995 5.50012L2.49963 0L0 2.49988L5.50014 8.00002L0 13.5004L2.49988 16L8 10.4999L13.5 15.9999L16 13.4999L10.4999 7.99998Z"/>
                    </svg>
                </button>
            </div>
            <h6>
                Вкажіть адресу електронної пошти, яку ви використовували при реєстрації, і ми надішлемо вам інструкції
                щодо
                зміни
                пароля.
            </h6>
        </div>
        <div class="body">
            <form class="login_form">
                <div class="input">
                    <div>
                        <input type="text" placeholder="Електронна пошта">
                    </div>

                </div>
                <div class="input pass">
                    <div>
                        <input type="password" placeholder="Новий пароль">
                        <button type="button" class="eye"></button>
                    </div>

                </div>
                <div class="input pass">
                    <div>
                        <input type="password" placeholder="Повторіть новий пароль">
                        <button type="button" class="eye"></button>
                    </div>

                </div>
                <button type="submit" class="btn">Увійти</button>
            </form>
        </div>
    </div>

    <!-- footer modal -->
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

</div>

@include('layouts.partials.header')
@yield('content')

<livewire:advertising>
@include('layouts.partials.footer')
@livewireScripts
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/charts.js') }}" defer></script>
<script src="{{ asset('js/script.js') }}" defer></script>
</body>
</html>

