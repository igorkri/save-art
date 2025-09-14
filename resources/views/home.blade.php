@extends('layouts.main')

@section('content')

    <section class="hero" id="hero">
        <video poster="../../img/main/hero.webp" autoplay muted loop preload="none">
            <source src="../../img/main/hero.webm" type="video/webm">
        </video>
        <h1>Мистецтво допомоги — найсучасніше з мистецтв</h1>
    </section>

    <section class="donates">
        <div class="block">
            <div class="title">
                <h6>ДОЛУЧАЙТЕСЬ ДО ВІДРОДЖЕННЯ ТА РОЗВИТКУ УКРАЇНСЬКОЇ КУЛЬТУРИ</h6>
                <a href="../../app/saveart/08.html" class="btn_decor">
                    <p>Підтримати платформу</p>
                    <div>
                        <svg width="20" height="18" viewBox="0 0 20 18" fill="" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                  d="M6.30348 2.37569L8.1912 0.488909C9.51524 -0.834472 13.0373 0.615678 16.2089 3.78919C19.3841 6.9627 20.8349 10.4863 19.5108 11.8097L17.6231 13.6965C17.2929 14.0298 16.8227 14.1932 16.2457 14.1932C16.1886 14.1932 16.1297 14.1876 16.0703 14.182C16.0499 14.1801 16.0294 14.1782 16.0089 14.1765V14.6632C16.0089 15.4899 15.3251 16.2366 14.1745 16.8133C14.1545 16.8233 14.1345 16.8333 14.1145 16.8399C12.657 17.5533 10.4792 18 8.00444 18C5.52968 18 3.35186 17.5533 1.89439 16.8399C1.87438 16.8333 1.85437 16.8233 1.83436 16.8133C0.683706 16.2366 0 15.4899 0 14.6632V11.9964C0 10.1262 3.51533 8.65943 8.00443 8.65943C8.01443 8.65943 8.02527 8.66026 8.03611 8.66109C8.04695 8.66193 8.05779 8.66276 8.0678 8.66276C7.31737 7.70595 6.72039 6.73924 6.32349 5.83252C5.63978 4.27246 5.63312 3.04572 6.30348 2.37569ZM11.9634 8.03604C15.0217 11.0929 18.1467 12.2297 19.0406 11.3363C19.9311 10.4462 18.797 7.31928 15.7387 4.26247C13.3574 1.879 10.9361 0.662273 9.55864 0.662273C9.16843 0.662273 8.86159 0.762272 8.66148 0.958951C7.77096 1.85244 8.90844 4.97581 11.9634 8.03604ZM8.62812 9.33943C8.42133 9.33276 8.21455 9.32609 8.00443 9.32609C3.68204 9.32609 0.666999 10.7362 0.666999 11.9963C0.666999 13.2563 3.682 14.6631 8.00439 14.6631C10.3257 14.6631 12.5036 14.2464 13.8944 13.5497C13.6676 13.4397 13.8477 13.5396 13.2073 13.1896C12.567 12.8397 9.89881 10.7062 9.25846 10.0328C8.61811 9.35937 8.83156 9.57278 8.62812 9.33943Z"/>
                        </svg>
                    </div>
                </a>
            </div>
            <h2>
                Твоя підтримка —<br/>
                натхнення для митця
            </h2>
            <div class="offer">
                <div class="text">
                    <p>
                        Ми пропонуємо прозору систему донатів, яка забезпечить майбутній проєкт в будь-якій галузі
                        мистецтва
                        стабільною
                        підтримкою.
                    </p>
                    <p>
                        Донатерами можуть бути як фізичні так і юридичні особи. Навіть 10₴ допоможуть ...
                    </p>
                    <p>
                        Ви побачите результат своєї допомоги у вигляді готового проєкту або навіть виставки робіт
                        автора.
                    </p>
                </div>
                <div class="don">
                    <a href="01.1-01.2.html" class="btn_decor">
                        <p>Підтримати проєкти</p>
                        <div>
                            <svg width="20" height="18" viewBox="0 0 20 18" fill="" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                      d="M6.30348 2.37569L8.1912 0.488909C9.51524 -0.834472 13.0373 0.615678 16.2089 3.78919C19.3841 6.9627 20.8349 10.4863 19.5108 11.8097L17.6231 13.6965C17.2929 14.0298 16.8227 14.1932 16.2457 14.1932C16.1886 14.1932 16.1297 14.1876 16.0703 14.182C16.0499 14.1801 16.0294 14.1782 16.0089 14.1765V14.6632C16.0089 15.4899 15.3251 16.2366 14.1745 16.8133C14.1545 16.8233 14.1345 16.8333 14.1145 16.8399C12.657 17.5533 10.4792 18 8.00444 18C5.52968 18 3.35186 17.5533 1.89439 16.8399C1.87438 16.8333 1.85437 16.8233 1.83436 16.8133C0.683706 16.2366 0 15.4899 0 14.6632V11.9964C0 10.1262 3.51533 8.65943 8.00443 8.65943C8.01443 8.65943 8.02527 8.66026 8.03611 8.66109C8.04695 8.66193 8.05779 8.66276 8.0678 8.66276C7.31737 7.70595 6.72039 6.73924 6.32349 5.83252C5.63978 4.27246 5.63312 3.04572 6.30348 2.37569ZM11.9634 8.03604C15.0217 11.0929 18.1467 12.2297 19.0406 11.3363C19.9311 10.4462 18.797 7.31928 15.7387 4.26247C13.3574 1.879 10.9361 0.662273 9.55864 0.662273C9.16843 0.662273 8.86159 0.762272 8.66148 0.958951C7.77096 1.85244 8.90844 4.97581 11.9634 8.03604ZM8.62812 9.33943C8.42133 9.33276 8.21455 9.32609 8.00443 9.32609C3.68204 9.32609 0.666999 10.7362 0.666999 11.9963C0.666999 13.2563 3.682 14.6631 8.00439 14.6631C10.3257 14.6631 12.5036 14.2464 13.8944 13.5497C13.6676 13.4397 13.8477 13.5396 13.2073 13.1896C12.567 12.8397 9.89881 10.7062 9.25846 10.0328C8.61811 9.35937 8.83156 9.57278 8.62812 9.33943Z"/>
                            </svg>
                        </div>
                    </a>
                    <div class="cards">
                        <div>
                            <img src="../../img/main/card1.svg" alt="">
                        </div>
                        <div>
                            <img src="../../img/main/card2.svg" alt="">
                        </div>
                        <div>
                            <img src="../../img/main/card3.svg" alt="">
                        </div>
                        <div>
                            <img src="../../img/main/card4.svg" alt="">
                        </div>
                        <div>
                            <img src="../../img/main/card5.svg" alt="">
                        </div>
                        <div>
                            <img src="../../img/main/card6.svg" alt="">
                        </div>
                        <div>
                            <img src="../../img/main/card7.svg" alt="">
                        </div>
                        <div>
                            <img src="../../img/main/card8.svg" alt="">
                        </div>
                    </div>
                    <div class="question">
                        <p>Як працює система донатерства?</p>
                        <a href="../../app/saveart/terms.html">Дізнайтесь</a>
                    </div>
                </div>
            </div>
            <div class="items">
                <a href="../../app/saveart/02.1.1-02.1.2-02.1.3-02.2.1.html" class="project_item">
                    <div class="head">
                        <img src="../../img/person.webp" alt="">
                        <p>Ім’я Прізвище</p>
                    </div>
                    <div class="img bordered">
                        <img src="../../img/project.webp" alt="">
                    </div>
                    <h6 class="name">
                        Назва оголошеного проєкту. Не більше ніж на два рядки. Крапки вкінці...
                    </h6>
                    <div class="amount">
                        <div class="money">
                            <h4>61 840 <span>UAH</span></h4>
                            <p>10 000 000</p>
                        </div>
                        <div class="line"><span style="width: 10%;"></span></div>
                        <div class="people">
                            <p>15</p>
                            <div class="photos">
                                <img src="../../img/main/people1.webp" alt="">
                                <img src="../../img/main/people4.webp" alt="">
                                <img src="../../img/main/people3.webp" alt="">
                                <img src="../../img/main/people2.webp" alt="">
                                <img src="../../img/main/people1.webp" alt="">
                            </div>
                        </div>
                    </div>
                    <div class="info">
                        <div>
                            <p>Образотворче мистецтво</p>
                            <p>Живопис</p>
                        </div>
                        <div>
                            <p>Проєкт</p>
                            <p>
                                <img src="../../img/declared.svg" alt="">
                                Оголошений
                            </p>
                        </div>
                        <div>
                            <p>Збір</p>
                            <p>Активний</p>
                        </div>
                        <div>
                            <p>Дата оголошення</p>
                            <p>02 12 2024</p>
                        </div>
                    </div>
                </a>
                <a href="../../app/saveart/02.1.1-02.1.2-02.1.3-02.2.1.html" class="project_item">
                    <div class="head">
                        <img src="../../img/universal.webp" alt="">
                        <p>Universal Pictures</p>
                    </div>
                    <div class="img bordered">
                        <img src="../../img/main/donates_project.webp" alt="">
                    </div>
                    <h6 class="name">
                        Назва оголошеного проєкту.
                    </h6>
                    <div class="amount">
                        <div class="money">
                            <h4>100 002 050 <span>UAH</span></h4>
                            <p>100 000 000</p>
                        </div>
                        <div class="line"><span style="width: 100%;"></span></div>
                        <div class="people">
                            <p>2 254</p>
                            <div class="photos">
                                <img src="../../img/main/people1.webp" alt="">
                                <img src="../../img/main/people4.webp" alt="">
                                <img src="../../img/main/people3.webp" alt="">
                                <img src="../../img/main/people2.webp" alt="">
                                <img src="../../img/main/people1.webp" alt="">
                            </div>
                        </div>
                    </div>
                    <div class="info">
                        <div>
                            <p>Кінематограф</p>
                            <p>Повнометражний фільм</p>
                        </div>
                        <div>
                            <p>Проєкт</p>
                            <p style="color: #FECC39;">
                                <img src="../../img/check_yellow.svg" alt="">
                                Завершений
                            </p>
                        </div>
                        <div>
                            <p>Збір</p>
                            <p>Завершений</p>
                        </div>
                        <div>
                            <p>Дата оголошення</p>
                            <p>02 12 2024</p>
                        </div>
                        <div>
                            <p>Дата завершення</p>
                            <p>01 03 2025</p>
                        </div>
                    </div>
                </a>
                <a href="../../app/saveart/02.1.1-02.1.2-02.1.3-02.2.1.html" class="project_item">
                    <div class="head">
                        <img src="../../img/person.webp" alt="">
                        <p>Ім’я Прізвище</p>
                    </div>
                    <div class="img bordered">
                        <img src="../../img/project.webp" alt="">
                    </div>
                    <h6 class="name">
                        Назва оголошеного проєкту. Не більше ніж на два рядки. Крапки вкінці...
                    </h6>
                    <div class="amount">
                        <div class="money">
                            <h4>61 840 <span>UAH</span></h4>
                            <p>10 000 000</p>
                        </div>
                        <div class="line"><span style="width: 10%;"></span></div>
                        <div class="people">
                            <p>15</p>
                            <div class="photos">
                                <img src="../../img/main/people1.webp" alt="">
                                <img src="../../img/main/people4.webp" alt="">
                                <img src="../../img/main/people3.webp" alt="">
                                <img src="../../img/main/people2.webp" alt="">
                                <img src="../../img/main/people1.webp" alt="">
                            </div>
                        </div>
                    </div>
                    <div class="info">
                        <div>
                            <p>Образотворче мистецтво</p>
                            <p>Живопис</p>
                        </div>
                        <div>
                            <p>Проєкт</p>
                            <p>
                                <img src="../../img/declared.svg" alt="">
                                Оголошений
                            </p>
                        </div>
                        <div>
                            <p>Збір</p>
                            <p>Активний</p>
                        </div>
                        <div>
                            <p>Дата оголошення</p>
                            <p>02 12 2024</p>
                        </div>
                    </div>
                </a>
                <a href="../../app/saveart/02.1.1-02.1.2-02.1.3-02.2.1.html" class="project_item">
                    <div class="head">
                        <img src="../../img/universal.webp" alt="">
                        <p>Universal Pictures</p>
                    </div>
                    <div class="img bordered">
                        <img src="../../img/main/donates_project.webp" alt="">
                    </div>
                    <h6 class="name">
                        Назва оголошеного проєкту.
                    </h6>
                    <div class="amount">
                        <div class="money">
                            <h4>100 002 050 <span>UAH</span></h4>
                            <p>100 000 000</p>
                        </div>
                        <div class="line"><span style="width: 100%;"></span></div>
                        <div class="people">
                            <p>2 254</p>
                            <div class="photos">
                                <img src="../../img/main/people1.webp" alt="">
                                <img src="../../img/main/people4.webp" alt="">
                                <img src="../../img/main/people3.webp" alt="">
                                <img src="../../img/main/people2.webp" alt="">
                                <img src="../../img/main/people1.webp" alt="">
                            </div>
                        </div>
                    </div>
                    <div class="info">
                        <div>
                            <p>Кінематограф</p>
                            <p>Повнометражний фільм</p>
                        </div>
                        <div>
                            <p>Проєкт</p>
                            <p style="color: #FECC39;">
                                <img src="../../img/check_yellow.svg" alt="">
                                Завершений
                            </p>
                        </div>
                        <div>
                            <p>Збір</p>
                            <p>Завершений</p>
                        </div>
                        <div>
                            <p>Дата оголошення</p>
                            <p>02 12 2024</p>
                        </div>
                        <div>
                            <p>Дата завершення</p>
                            <p>01 03 2025</p>
                        </div>
                    </div>
                </a>
            </div>
            <a href="../../app/saveart/01.1-01.2.html" class="btn dark">Всі проєкти</a>
        </div>
    </section>

    <section class="collected">
        <div class="block">
            <div class="title">
                <p>Зібрано за весь час</p>
                <h2><span>2 325 250</span> ₴</h2>
            </div>
            <div class="graph">
                <div class="buttons">
                    <button onclick="updateChart('day')">Д</button>
                    <button onclick="updateChart('week')">Т</button>
                    <button onclick="updateChart('month')" class="active">М</button>
                    <button onclick="updateChart('year')">Р</button>
                </div>
                <p>Оновлено: <span>12.12.2024 13:00</span></p>
                <div class="chart_container">
                    <canvas id="incomeChart"></canvas>
                </div>
            </div>
            <div class="text">
                <p>
                    Загальна сума зібраних коштів є відкритою інформацією. Модератори платформи пильно контролюють їх
                    використання
                    та
                    унеможливлюють випадки шахрайства.
                </p>
                <p>
                    Невикористані кошти із завершених проєктів надходять до фонду платформи.
                </p>
            </div>
            <div class="total">
                <div>
                    <p>Оголошених проєктів</p>
                    <h2>624</h2>
                </div>
                <div>
                    <p>Проєктів в роботі</p>
                    <h2>387</h2>
                </div>
                <div>
                    <p>Завершених проєктів</p>
                    <h2>1 126</h2>
                </div>
                <div>
                    <p>Проданих проєктів</p>
                    <h2>107</h2>
                </div>
            </div>
        </div>
    </section>

    <section class="partners" id="partners">
        <div class="block">
            <h2>Партнери</h2>
            <div class="cont">
                <div>
                    <img src="../../img/main/idua.svg" alt="">
                    <h6>ID_Art_UA</h6>
                    <p>Пара слів хто це</p>
                </div>
                <div>
                    <img src="../../img/main/ingsot.svg" alt="">
                    <h6>ingsot.com</h6>
                    <p>Топ ІТ корпорація</p>
                </div>
                <div>
                    <img src="../../img/main/partner.webp" alt="">
                    <h6>Костянтин Костянтинопольський</h6>
                    <p>Пара слів хто це</p>
                </div>
                <div>
                    <img src="../../img/main/ingsot.svg" alt="">
                    <h6>ingsot.com</h6>
                    <p>Топ ІТ корпорація</p>
                </div>
            </div>
        </div>
    </section>

@endsection
