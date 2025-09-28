@extends('layouts.saveart')
@section('title', __('profile_legal.title'))
@section('content')
  <div class="under_header">
    <img src="{{ asset('img/under_header/under_header10.webp') }}" alt="{{ __('header.logo_alt') }}">
    <h3>Налаштування</h3>
  </div>

  <section class="profile_settings" id="profile_settings">
    <div class="block">
        @include('profile.nav.tab')
      <form>
        <div class="add_picture circle">
          <label>
            <input type="file">
            <svg width="20" height="14" viewBox="0 0 20 14" fill="" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" clip-rule="evenodd"
                d="M7 -3.05176e-05L4 1.99997H0V14H20V1.99997H16L13 -3.05176e-05H7ZM10 12C12.2091 12 14 10.2091 14 7.99997C14 5.79083 12.2091 3.99997 10 3.99997C7.79086 3.99997 6 5.79083 6 7.99997C6 10.2091 7.79086 12 10 12Z" />
            </svg>
          </label>
          <div class="img on">
            <button type="button">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="" xmlns="http://www.w3.org/2000/svg">
                <path
                  d="M10.4999 7.99998L15.9999 2.5L13.4999 0L7.99995 5.50012L2.49963 0L0 2.49988L5.50014 8.00002L0 13.5004L2.49988 16L8 10.4999L13.5 15.9999L16 13.4999L10.4999 7.99998Z" />
              </svg>
            </button>
            <div class="img_preview">
              <img src="../../img/person.webp" alt="">
            </div>
          </div>
        </div>
        <h6>Дані юридичної особи</h6>
        <div class="input">
          <p>Назва</p>
          <div>
            <input type="text" placeholder="Вкажіть назву">
          </div>
        </div>
        <div class="input">
          <p>Назва англійською</p>
          <div>
            <input type="text" placeholder="Вкажіть назву">
          </div>
        </div>
        <div class="input">
          <p>ЄДРПОУ</p>
          <div>
            <input type="text" placeholder="Художник, скульптор, архітектор, режисер, співак">
          </div>
        </div>
        <div class="input">
          <p>Уповноважена особа</p>
          <div>
            <input type="text" placeholder="Вкажіть повне ім’я">
          </div>
        </div>
        <hr>
        <p>
          Контактна інформація буде доступна для перегляду відвідувачами сайту лише після запиту до вас.
        </p>
        <div class="input">
          <p>Контакти</p>
          <div>
            <input type="text" placeholder="Вкажіть адресу">
          </div>
        </div>
        <div class="input">
          <p>Електронна пошта</p>
          <div>
            <input type="text" placeholder="Вкажіть e-mail">
          </div>
        </div>
        <div class="input">
          <p>Телефон</p>
          <div>
            <input type="number" placeholder="Вкажіть номер телефону">
          </div>
        </div>
        <button type="submit">
          <svg width="14" height="12" viewBox="0 0 14 12" fill="" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd"
              d="M13.9992 2.91495L5.415 11.4992L0.000786781 6.08495L2.415 3.67073L5.415 6.67073L11.585 0.500732L13.9992 2.91495Z" />
          </svg>
        </button>
      </form>
    </div>
  </section>
@endsection