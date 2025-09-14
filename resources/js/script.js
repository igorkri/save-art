// Файл: resources/js/script.js

// Основные UI-интеракты: toast, header dropdown, mobile, password-toggle, login modal (Livewire)

// Ждем загрузки DOM
document.addEventListener('DOMContentLoaded', () => {
  // ---------- Toast ----------
  const toast = document.querySelector('#toast');
  if (toast) {
    const toastClose = toast.querySelector('.head button');
    if (toastClose) {
      toastClose.addEventListener('click', (e) => {
        e.stopPropagation();
        toast.classList.remove('on');
      });
    }
  }

  // ---------- Header dropdown ----------
  const headerLogo = document.querySelector('#header .logo');
  if (headerLogo) {
    const toggleBtn = headerLogo.querySelector('button');
    const dropMenu = headerLogo.querySelector('.drop_menu');

    if (toggleBtn) {
      toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleBtn.classList.toggle('on');
        dropMenu?.classList.toggle('on');
      });

      document.addEventListener('click', (e) => {
        if (!headerLogo.contains(e.target)) {
          toggleBtn.classList.remove('on');
          dropMenu?.classList.remove('on');
        }
      });
    }
  }

  // ---------- Mobile menu ----------
  const burger = document.querySelector('#header .burger');
  const modalMobile = document.querySelector('#header .mobile');
  if (burger && modalMobile) {
    const mobileClose = modalMobile.querySelector('.close button');
    burger.addEventListener('click', (e) => {
      e.stopPropagation();
      modalMobile.classList.toggle('on');
    });
    mobileClose?.addEventListener('click', (e) => {
      e.stopPropagation();
      modalMobile.classList.toggle('on');
    });
    document.addEventListener('click', (e) => {
      if (!modalMobile.contains(e.target)) modalMobile.classList.remove('on');
    });
  }

  // ---------- Password visibility toggle ----------
  document.querySelectorAll('.input.pass > div').forEach((wrapper) => {
    const input = wrapper.querySelector("input[type='password'], input[type='text']");
    const eye = wrapper.querySelector('.eye');
    if (!input || !eye) return;
    eye.addEventListener('click', () => {
      const isPassword = input.getAttribute('type') === 'password';
      input.setAttribute('type', isPassword ? 'text' : 'password');
      eye.classList.toggle('on', isPassword);
    });
  });

  // ---------- Livewire modal integration (login/register/reset) ----------
  const modalFill = document.querySelector('.modal_fill');
  const modalBlur = modalFill?.querySelector('.modal_blur');
  const modal = document.getElementById('modal');

  function showModalLocal() {
    modalFill?.classList.add('active');
    modalFill?.classList.remove('hidden');
    modal?.classList.add('on');
    document.body.style.overflow = 'hidden';
  }

  function hideModalLocal() {
    modalFill?.classList.remove('active');
    modalFill?.classList.add('hidden');
    modal?.classList.remove('on');
    document.body.style.overflow = '';
  }

  function sendLivewireEvent(name) {
    if (!window.Livewire) return false;
    if (typeof Livewire.emit === 'function') {
      Livewire.emit(name);
      return true;
    }
    if (typeof Livewire.dispatch === 'function') {
      Livewire.dispatch(name);
      return true;
    }
    return false;
  }

  function openLoginForm() {
    showModalLocal();
    const sent = sendLivewireEvent('openLoginForm');
    console.log('[log] openLoginForm called, livewire event sent:', sent);
  }

  document.addEventListener('click', (e) => {
    const loginBtn = e.target.closest && e.target.closest('.login');
    if (loginBtn) {
      e.preventDefault();
      openLoginForm();
    }
  });

  document.addEventListener('livewire:init', () => {
    Livewire.on('modal-fill-toggle', (show) => {
      if (show) {
        showModalLocal();
      } else {
        hideModalLocal();
      }
    });
  });

  modalBlur?.addEventListener('click', () => {
    const sent = sendLivewireEvent('closeLoginForm') || sendLivewireEvent('closeRegisterForm') || sendLivewireEvent('closeResetForm');
    if (!sent) hideModalLocal();
  });

  modal?.addEventListener('click', (e) => {
    if (e.target === modal) {
      const sent = sendLivewireEvent('closeLoginForm');
      if (!sent) hideModalLocal();
      return;
    }
    const closeBtn = e.target.closest('.modal_content .head .title .close, .modal_content .head .title .back, .modal_content .head .title button.close');
    if (closeBtn) {
      const sent = sendLivewireEvent('closeLoginForm') || sendLivewireEvent('closeRegisterForm') || sendLivewireEvent('closeResetForm');
      if (!sent) hideModalLocal();
    }
  });

  document.addEventListener('keydown', (ev) => {
    if (ev.key === 'Escape' && modalFill?.classList.contains('active')) {
      const sent = sendLivewireEvent('closeLoginForm') || sendLivewireEvent('closeRegisterForm') || sendLivewireEvent('closeResetForm');
      if (!sent) hideModalLocal();
    }
  });
});
