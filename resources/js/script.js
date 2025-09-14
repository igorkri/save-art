//! тоаст
document.addEventListener("DOMContentLoaded", () => {
  const toast = document.querySelector("#toast");
  const btn = document.querySelector("#toast .head button");

  btn.addEventListener("click", (event) => {
    event.stopPropagation();
    toast.classList.remove("on");
  });
});

//! дроп в хедері в лого
document.addEventListener("DOMContentLoaded", () => {
  const login = document.querySelector("#header .logo");
  const button = login.querySelector("#header .logo button");
  const dropMenu = login.querySelector("#header .logo .drop_menu");

  // Тогл класів при кліку на кнопку
  button.addEventListener("click", (event) => {
    event.stopPropagation(); // Зупиняємо поширення події
    button.classList.toggle("on");
    dropMenu.classList.toggle("on");
  });

  // Закриття меню при кліку поза межами .logo
  document.addEventListener("click", (event) => {
    if (!login.contains(event.target)) {
      button.classList.remove("on");
      dropMenu.classList.remove("on");
    }
  });
});

//! мобільне меню
document.addEventListener("DOMContentLoaded", () => {
  const burger = document.querySelector("#header .burger");
  const modal_mobile = document.querySelector("#header .mobile");
  const modal_mobile_close = document.querySelector("#header .mobile .close button");

  // Тогл класів при кліку на кнопку
  burger.addEventListener("click", (event) => {
    event.stopPropagation(); // Зупиняємо поширення події
    modal_mobile.classList.toggle("on");
  });

  modal_mobile_close.addEventListener("click", (event) => {
    event.stopPropagation(); // Зупиняємо поширення події
    modal_mobile.classList.toggle("on");
  });

  // Закриття меню при кліку поза межами .login
  document.addEventListener("click", (event) => {
    if (!modal_mobile.contains(event.target)) {
      modal_mobile.classList.remove("on");
    }
  });
});

//! перемикання відимості паролю
document.addEventListener("DOMContentLoaded", () => {
  const wrappers = document.querySelectorAll(".input.pass > div");

  wrappers.forEach((wrapper) => {
    const passwordInput = wrapper.querySelector("input[type='password'], input[type='text']");
    const toggleButton = wrapper.querySelector(".eye");

    toggleButton.addEventListener("click", () => {
      const isPasswordHidden = passwordInput.getAttribute("type") === "password";
      passwordInput.setAttribute("type", isPasswordHidden ? "text" : "password");
      toggleButton.classList.toggle("on", isPasswordHidden);
    });
  });
});

//! логінація - обновленная версия для Livewire
document.addEventListener('DOMContentLoaded', () => {
    const modalFill = document.querySelector('.modal_fill');
    const modalBlur = modalFill?.querySelector('.modal_blur');

    // Функция для открытия формы входа
    function openLoginForm() {
        if (window.Livewire) {
            window.Livewire.dispatch('openLoginForm');
        }
    }

    // Делегирование событий для всех кнопок входа
    document.addEventListener('click', (e) => {
        if (e.target.closest('.login')) {
            e.preventDefault();
            openLoginForm();
        }
    });

    // Слушаем события Livewire для показа/скрытия модалки
    document.addEventListener('livewire:init', () => {
        Livewire.on('modal-fill-toggle', (show) => {
            if (show) {
                modalFill?.classList.add('active');
                modalFill?.classList.remove('hidden');
                document.body.style.overflow = 'hidden'; // Блокируем скролл
            } else {
                modalFill?.classList.remove('active');
                modalFill?.classList.add('hidden');
                document.body.style.overflow = ''; // Восстанавливаем скролл
            }
        });
    });

    // Закриття по кліку на фон
    modalBlur?.addEventListener('click', () => {
        if (window.Livewire) {
            Livewire.dispatch('closeLoginForm');
            Livewire.dispatch('closeRegisterForm');
            Livewire.dispatch('closeResetForm');
        }
    });

    // Закриття по ESC
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modalFill?.classList.contains('active')) {
            if (window.Livewire) {
                Livewire.dispatch('closeLoginForm');
                Livewire.dispatch('closeRegisterForm');
                Livewire.dispatch('closeResetForm');
            }
        }
    });
});

//! Переключение между формами в модальном окне
document.addEventListener('livewire:init', () => {
    // Переключение на форму регистрации из формы входа
    document.addEventListener('click', (e) => {
        const switchingButtons = e.target.closest('.switching button');
        if (switchingButtons) {
            const parentSwitching = switchingButtons.parentElement;
            const buttons = parentSwitching.querySelectorAll('button');
            const buttonIndex = Array.from(buttons).indexOf(switchingButtons);

            // Убираем активный класс со всех кнопок
            buttons.forEach(btn => btn.classList.remove('on'));
            // Добавляем активный класс к нажатой кнопке
            switchingButtons.classList.add('on');

            // Переключаем формы
            if (buttonIndex === 0) {
                // Кнопка "Вхід до спільноти"
                Livewire.dispatch('closeRegisterForm');
                Livewire.dispatch('closeResetForm');
                Livewire.dispatch('openLoginForm');
            } else if (buttonIndex === 1) {
                // Кнопка "Реєстрація"
                Livewire.dispatch('closeLoginForm');
                Livewire.dispatch('closeResetForm');
                Livewire.dispatch('openRegisterForm');
            }
        }
    });

    // Обработка кнопки "Я не пам'ятаю пароль"
    document.addEventListener('click', (e) => {
        if (e.target.closest('.forgot')) {
            e.preventDefault();
            Livewire.dispatch('closeLoginForm');
            Livewire.dispatch('openResetForm');
        }
    });
});

//! модалка в футері
document.addEventListener("DOMContentLoaded", () => {
  const fileContainer = document.querySelector("#application .file");
  const fileInput = fileContainer.querySelector("#application .file input[type='file']");
  const fileLabel = fileContainer.querySelector("#application .file span");
  const fileReset = fileContainer.querySelector("#application .file button");

  fileInput.addEventListener("change", () => {
    if (fileInput.files.length > 0) {
      fileContainer.classList.add("on");
      fileLabel.textContent = fileInput.files[0].name;
    }
  });

  fileReset.addEventListener("click", () => {
    fileInput.value = "";
    fileContainer.classList.remove("on");
    fileLabel.textContent = "Завантажте резюме (не обов’язково)";
  });
});

//! прибирання модального вікна
document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("modal");

  // Закриття при натисканні ESC
  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      closeModal();
    }
  });

  // Закриття при кліку по #modal, але не по його вмісту
  modal.addEventListener("click", function (event) {
    if (event.target === modal) {
      closeModal();
    }
  });

  function closeModal() {
    modal.classList.remove("on");
    modal.innerHTML = ""; // Видаляє всі внутрішні елементи
  }
});

// ! project_unit
document.addEventListener("DOMContentLoaded", () => {
  const project_units = document.querySelectorAll('.project_unit');
  project_units.forEach(unit => {
    const mark = unit.querySelector('.mark');
    if (mark) { // Перевіряємо, чи існує елемент
      mark.addEventListener('click', function () {
        // Якщо елемент уже має клас 'on', то знімаємо його і виходимо
        if (mark.classList.contains('on')) {
          mark.classList.remove('on');
          return;
        }

        // Знімаємо клас 'on' з усіх інших .mark
        document.querySelectorAll('.mark.on').forEach(activeMark => {
          activeMark.classList.remove('on');
        });

        // Додаємо клас 'on' до поточного елемента
        mark.classList.add('on');
      });
    }
  });
});

// Закриття модального вікна через делегування подій
document.addEventListener("DOMContentLoaded", () => {
  modal.addEventListener("click", (event) => {
    if (event.target.closest(".modal_content .head .title .close")) {
      modal.classList.remove("on"); // Знімаємо клас "on"
      const modalContent = modal.querySelector(".modal_content");
      if (modalContent) {
        modalContent.remove(); // Видаляємо .modal_content
      }
    }
  });
});

//! кпопка дропа робіт
document.addEventListener("DOMContentLoaded", () => {
  const authorItem = document.querySelectorAll(".author_item");
  authorItem.forEach(item => {
    const button = item.querySelector(".head .drop_btn button");

    button.addEventListener("click", (event) => {
      event.stopPropagation(); // Зупиняємо поширення події
      item.classList.toggle("on");
    });
  });
});

document.addEventListener("DOMContentLoaded", () => {
  const containers = document.querySelectorAll("#artists .block .item .cont"); // Отримуємо всі елементи з класом .cont

  if (window.innerWidth >= 1024) {
    containers.forEach((container) => {
      let isDragging = false;
      let startX, scrollLeft;

      container.addEventListener("mousedown", (e) => {
        isDragging = true;
        container.classList.add("dragging");
        startX = e.pageX - container.offsetLeft;
        scrollLeft = container.scrollLeft;
      });

      container.addEventListener("mouseleave", () => {
        isDragging = false;
        container.classList.remove("dragging");
      });

      container.addEventListener("mouseup", () => {
        isDragging = false;
        container.classList.remove("dragging");
      });

      container.addEventListener("mousemove", (e) => {
        if (!isDragging) return;
        e.preventDefault();
        const x = e.pageX - container.offsetLeft;
        const walk = (x - startX) * 2; // Регулюємо чутливість
        container.scrollLeft = scrollLeft - walk;
      });

      // Вимикаємо drag для зображень і тексту
      container.querySelectorAll("img, *").forEach((el) => {
        el.setAttribute("draggable", "false");
      });
    });
  }
});

document.querySelectorAll('#faq .side .item').forEach(item => {
  item.querySelector('button').addEventListener('click', function () {
    this.classList.toggle('on');
    const drop = this.nextElementSibling;
    drop.classList.toggle('on');
  })
});

document.querySelectorAll('#faq .cont .item').forEach(item => {
  const head = item.querySelector('.head button');
  const drop = item.querySelector('.drop');

  item.addEventListener('click', function () {
    head.classList.toggle('on');
    drop.classList.toggle('on');
  })
});

function updateVideoSource() {
  const video = document.querySelector('#hero video');
  const source = video.querySelector('source');

  if (window.innerWidth <= 768) {
    source.src = "../../img/main/hero_mobile.webm";
  } else {
    source.src = "../../img/main/hero.webm";
  }

  video.load(); // Перезавантажуємо відео з новим джерелом
}

window.addEventListener('resize', updateVideoSource);
window.addEventListener('DOMContentLoaded', updateVideoSource);

// ! додавання опцій
document.addEventListener("DOMContentLoaded", () => {
  const fields = document.querySelector('#services_new .options .fields');
  const cont = fields.querySelector('#services_new .options .cont');
  const addButton = fields.querySelector('#services_new .options .add');

  // Функція для перевірки кількості div.input
  const updateDeleteButtonVisibility = () => {
    const inputs = cont.querySelectorAll('.input');
    inputs.forEach((input) => {
      const deleteButton = input.querySelector('.delete');
      if (inputs.length === 1) {
        deleteButton.style.display = 'none'; // При одному input ховаємо кнопку
      } else {
        deleteButton.style.display = ''; // Показуємо кнопку
      }
    });
  };

  // Подія при кліку на кнопку .add
  addButton.addEventListener('click', () => {
    const newInput = document.createElement('div');
    newInput.classList.add('input');
    newInput.innerHTML = `
      <div>
        <input type="text" placeholder="Назва опції">
        <button type="button" class="delete"></button>
      </div>
    `;
    cont.appendChild(newInput);
    updateDeleteButtonVisibility(); // Оновлюємо видимість кнопок .delete
  });

  // Делегування події на видалення через .delete
  cont.addEventListener('click', (event) => {
    if (event.target.matches('.delete')) {
      const inputToDelete = event.target.closest('.input');
      inputToDelete.remove(); // Видаляємо div.input
      updateDeleteButtonVisibility(); // Оновлюємо видимість кнопок .delete
    }
  });

  // Перевіряємо стан кнопки при завантаженні сторінки
  updateDeleteButtonVisibility();
});

// ! пошук
document.addEventListener("DOMContentLoaded", () => {
  const members = document.querySelector('#services_new .members');
  const cont = members.querySelector('.cont');
  const search = members.querySelector('.search');

  // Делегування подій для копіювання .item із .search до .cont
  search.addEventListener('click', (event) => {
    if (event.target.tagName === 'BUTTON' && event.target.closest('.item')) {
      const item = event.target.closest('.item');
      const clonedItem = item.cloneNode(true); // Копіюємо елемент .item
      cont.appendChild(clonedItem); // Додаємо в кінець блоку .cont
    }
  });

  // Делегування подій для видалення .item із .cont
  cont.addEventListener('click', (event) => {
    if (event.target.tagName === 'BUTTON' && event.target.closest('.item')) {
      const item = event.target.closest('.item');
      item.remove(); // Видаляємо .item з .cont
    }
  });
});

// ! кнопка вибору виду мистецтва
document.addEventListener("DOMContentLoaded", () => {
  const button = document.querySelector("#profile_newProject .cont .status button");
  const modal = document.querySelector("#modal");
  const modalFill = document.querySelector("#modal_fill");

  // Функція для відкриття модального вікна
  const openModal = () => {
    if (!modal || !modalFill) return;

    modal.classList.add("on"); // Додаємо клас "on"
    const modalLogin = modalFill.querySelector("#choose_art_field");
    if (modalLogin) {
      const clonedContent = modalLogin.cloneNode(true); // Копіюємо блок
      modal.innerHTML = ""; // Очищуємо #modal перед вставкою
      modal.appendChild(clonedContent); // Вставляємо в #modal
    }
  };

  // Додаємо обробник подій для кожної кнопки
  button.addEventListener("click", openModal);

});

// ! видалення лінії
document.addEventListener('click', function (event) {
  if (event.target.closest('.delete')) {
    const line = event.target.closest('.line');
    if (line) {
      line.remove();
    }
  }
});

//! перемикання svg зображень і їх кольорів
document.addEventListener("DOMContentLoaded", () => {
  // 1. Перемикання класу .on для кнопок у .colors
  document.querySelectorAll("#modal_addImg .ready_made .colors button").forEach(button => {
    button.addEventListener("click", () => {
      document.querySelectorAll("#modal_addImg .ready_made .colors button").forEach(b => b.classList.remove("on"));
      button.classList.add("on");
    });
  });

  // 2. Перемикання класу .on для div у .img
  document.querySelectorAll("#modal_addImg .ready_made .imgs div").forEach(div => {
    div.addEventListener("click", () => {
      document.querySelectorAll("#modal_addImg .ready_made .imgs div").forEach(d => d.classList.remove("on"));
      div.classList.add("on");
    });
  });

  // 3. Копіювання SVG з .ready_made .imgs div у .add_file .img, залишаючи першу кнопку
  document.querySelectorAll("#modal_addImg .ready_made .imgs div").forEach(imgDiv => {
    imgDiv.addEventListener("click", () => {
      const svg = imgDiv.querySelector("svg")?.outerHTML;
      if (!svg) return;

      const addFileImg = document.querySelector("#modal_addImg .add_file .img");
      if (!addFileImg) return;

      // Зберігаємо першу кнопку перед очищенням
      const firstButton = addFileImg.querySelector("button");
      addFileImg.innerHTML = ""; // Очищуємо контейнер

      if (firstButton) addFileImg.appendChild(firstButton); // Повертаємо кнопку назад

      // Додаємо SVG
      addFileImg.innerHTML += svg;

      // Додаємо клас .on
      addFileImg.classList.add("on");

      // Перемикання класу .on у .ready_made .imgs div
      document.querySelectorAll("#modal_addImg .ready_made .imgs div").forEach(div => div.classList.remove("on"));
      imgDiv.classList.add("on");
    });
  });

  // 4. Передача background-color у fill для SVG у .ready_made .imgs
  document.querySelectorAll("#modal_addImg .ready_made .colors button").forEach(button => {
    button.addEventListener("click", () => {
      const bgColor = button.querySelector("span")?.style.backgroundColor;
      if (!bgColor) return;

      document.querySelectorAll("#modal_addImg .ready_made .imgs div svg").forEach(svg => {
        svg.setAttribute("fill", bgColor);
      });

      // 5. Передача цього ж значення в .add_file .img svg
      document.querySelectorAll("#modal_addImg .add_file .img svg").forEach(svg => {
        svg.setAttribute("fill", bgColor);
      });
    });
  });

  // 6. Очистка .add_file .img при кліку на кнопку
  document.addEventListener("click", (event) => {
    const button = event.target.closest("#modal_addImg .add_file .img button");
    if (!button) return;

    const addFileImg = document.querySelector("#modal_addImg .add_file .img");
    if (!addFileImg) return;

    // Очищаємо поле вибору файлу
    const inputFile = document.querySelector("#modal_addImg .add_file label input");
    if (inputFile) inputFile.value = "";

    // Зберігаємо кнопку перед очищенням
    const firstButton = addFileImg.querySelector("button");
    addFileImg.innerHTML = ""; // Очищуємо контейнер

    if (firstButton) addFileImg.appendChild(firstButton); // Повертаємо кнопку назад

    // Видаляємо клас .on
    addFileImg.classList.remove("on");
    document.querySelectorAll("#modal_addImg .ready_made .imgs div").forEach(d => d.classList.remove("on"));
  });
});

// ! пересування і видалення зображень в галереї зображень
document.addEventListener("DOMContentLoaded", function () {
  document.addEventListener("click", function (event) {
    const button = event.target.closest("button"); // Знаходимо натиснуту кнопку
    if (!button) return;

    const item = button.closest(".item"); // Знаходимо батьківський .item
    if (!item) return;

    const container = item.parentElement; // Блок .imgs, що містить всі .item
    const items = Array.from(container.children); // Масив всіх .item
    const index = items.indexOf(item); // Поточний індекс .item

    if (button.classList.contains("to_left") && index > 0) {
      container.insertBefore(item, items[index - 1]); // Пересуваємо вліво
    }
    else if (button.classList.contains("to_right") && index < items.length - 1) {
      container.insertBefore(items[index + 1], item); // Пересуваємо вправо
    }
    else if (button.classList.contains("delete")) {
      item.remove(); // Видаляємо .item
    }
  });
});

// ! кнопка додати відео
document.addEventListener("DOMContentLoaded", () => {
  const imgBtn = document.querySelectorAll("#profile_myProjectEdit .slider_container .edit");
  const modal = document.querySelector("#modal");
  const modalFill = document.querySelector("#modal_fill");

  // Функція для відкриття модального вікна
  const openModal = () => {
    if (!modal || !modalFill) return;

    modal.classList.add("on"); // Додаємо клас "on"
    const modalLogin = modalFill.querySelector("#add_block");
    if (modalLogin) {
      const clonedContent = modalLogin.cloneNode(true); // Копіюємо блок
      modal.innerHTML = ""; // Очищуємо #modal перед вставкою
      modal.appendChild(clonedContent); // Вставляємо в #modal
    }
  };

  // Додаємо обробник подій для кожної кнопки
  imgBtn.forEach((button) => {
    button.addEventListener("click", openModal);
  });

  // Закриття модального вікна через делегування подій
  modal.addEventListener("click", (event) => {
    if (event.target.closest(".modal_content .head .title .close")) {
      modal.classList.remove("on"); // Знімаємо клас "on"
      const modalContent = modal.querySelector(".modal_content");
      if (modalContent) {
        modalContent.remove(); // Видаляємо .modal_content
      }
    }
  });
});

//! зняття класу new з айтемів в #notifications
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("#notifications .item.new").forEach((item) => {
    // Видалення класу при наведенні (ПК)
    item.addEventListener("mouseenter", () => {
      item.classList.remove("new");
    });

    // Видалення класу при кліку (мобільні пристрої)
    item.addEventListener("click", () => {
      item.classList.remove("new");
    });
  });
});

//! відкриття чату
document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector('#notifications .messenger form');
  const chat_enter = document.querySelector('#notifications .messenger .head .chat_enter');
  const chat_leave = document.querySelector('#notifications .messenger .head .chat_leave');

  chat_enter.addEventListener("click", () => {
    form.classList.add("on");
  });

  chat_leave.addEventListener("click", () => {
    form.classList.remove("on");
  });
});

//! діапазон цін
document.addEventListener("DOMContentLoaded", () => {
  const minPriceInput = document.getElementById("minPrice");
  const maxPriceInput = document.getElementById("maxPrice");
  const rangeMin = document.getElementById("rangeMin");
  const rangeMax = document.getElementById("rangeMax");
  const track = document.querySelector(".track");

  function updateSlider() {
    let minVal = parseInt(rangeMin.value);
    let maxVal = parseInt(rangeMax.value);

    if (minVal > maxVal - 1000) {
      rangeMin.value = maxVal - 1000;
      minVal = maxVal - 1000;
    }

    if (maxVal < minVal + 1000) {
      rangeMax.value = minVal + 1000;
      maxVal = minVal + 1000;
    }

    minPriceInput.value = minVal;
    maxPriceInput.value = maxVal;

    let minPercent = (minVal / 1000000) * 100;
    let maxPercent = (maxVal / 1000000) * 100;

    track.style.left = minPercent + "%";
    track.style.right = (100 - maxPercent) + "%";
  }

  function updateInputs() {
    let minVal = parseInt(minPriceInput.value) || 0;
    let maxVal = parseInt(maxPriceInput.value) || 1000000;

    if (minVal < 0) minVal = 0;
    if (maxVal > 1000000) maxVal = 1000000;
    if (minVal > maxVal - 1000) minVal = maxVal - 1000;
    if (maxVal < minVal + 1000) maxVal = minVal + 1000;

    rangeMin.value = minVal;
    rangeMax.value = maxVal;

    updateSlider();
  }

  rangeMin.addEventListener("input", updateSlider);
  rangeMax.addEventListener("input", updateSlider);
  minPriceInput.addEventListener("input", updateInputs);
  maxPriceInput.addEventListener("input", updateInputs);

  updateSlider();
});

//! сайд меню дроп
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("#projects .block .side_panel .unit .head").forEach((head) => {
    head.addEventListener("click", () => {
      const unit = head.closest(".unit");
      const box = unit.querySelector(".box");

      unit.classList.toggle("on");
    });
  });
});

//! сайд меню мобільне
document.addEventListener("DOMContentLoaded", () => {
  const openSide = document.querySelector("#projects .cont .top button");
  const closeSide = document.querySelector("#projects .side_panel .top button");
  const sidePanel = document.querySelector("#projects .side_panel");


  openSide.addEventListener("click", (event) => {
    event.stopPropagation(); // Зупиняємо поширення події
    sidePanel.classList.add("on");
  });

  closeSide.addEventListener("click", (event) => {
    event.stopPropagation(); // Зупиняємо поширення події
    sidePanel.classList.remove("on");
  });


});

//! фільтр по імені чи популярності
document.addEventListener("DOMContentLoaded", () => {
  const sort = document.querySelector("#projects .sort_list");
  const button = sort.querySelector(".button");
  const dropMenu = sort.querySelector(".drop");

  // Тогл класів при кліку на кнопку
  button.addEventListener("click", (event) => {
    event.stopPropagation(); // Зупиняємо поширення події
    button.classList.toggle("on");
    dropMenu.classList.toggle("on");
  });

  // Закриття меню при кліку поза межами .logo
  document.addEventListener("click", (event) => {
    if (!sort.contains(event.target)) {
      button.classList.remove("on");
      dropMenu.classList.remove("on");
    }
  });
});


// ! ==========================

//! закриття window
document.addEventListener("DOMContentLoaded", function () {
  const window = document.querySelector(".window");
  const close = document.querySelectorAll(".window .close");

  // Закриття при натисканні ESC
  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      closeWindow();
    }
  });

  // Закриття при кліку по #window, але не по його вмісту
  window.addEventListener("click", function (event) {
    if (event.target === window) {
      closeWindow();
    }
  });

  // Закриття по кнопці
  close.forEach(button => {
    button.addEventListener("click", closeWindow);
  });

  function closeWindow() {
    window.classList.remove("on");
  }
});

//! додавання зображення при виборі файлу
document.addEventListener("DOMContentLoaded", function () {
  const addFileBlocks = document.querySelectorAll(".add_picture");

  if (addFileBlocks.length === 0) {
    return;
  }

  addFileBlocks.forEach(addFile => {
    const fileInput = addFile.querySelector("input[type='file']");
    const imgContainer = addFile.querySelector(".img");
    const imgPreview = addFile.querySelector(".img_preview");
    const removeButton = imgContainer.querySelector("button");

    fileInput.addEventListener("change", function () {
      if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
          imgContainer.classList.add("on");
          imgPreview.innerHTML = `<img src="${e.target.result}" alt="Вибране зображення">`;
        };
        reader.readAsDataURL(this.files[0]);
      }
    });

    removeButton.addEventListener("click", function () {
      imgContainer.classList.remove("on");
      imgPreview.innerHTML = "";
      fileInput.value = "";
    });
  });
});

// // ! пересування блоків з полями
// document.addEventListener("DOMContentLoaded", () => {
//   document.querySelectorAll(".fields").forEach((fieldsContainer) => {
//     fieldsContainer.addEventListener("click", (event) => {
//       const button = event.target.closest("button");
//       if (!button) return;

//       const line = button.closest(".line");

//       if (button.classList.contains("to_up")) {
//         const prev = line.previousElementSibling;
//         if (prev) {
//           fieldsContainer.insertBefore(line, prev);
//         }
//       }

//       if (button.classList.contains("to_bottom")) {
//         const next = line.nextElementSibling;
//         if (next) {
//           fieldsContainer.insertBefore(next, line);
//         }
//       }
//     });
//   });
// });

// ! вибір файла
document.addEventListener("change", (event) => {
  if (event.target.type === "file") {
    const addFile = event.target.closest(".add_file");
    if (addFile) {
      addFile.classList.add("on");
      const fileName = event.target.files.length ? event.target.files[0].name : "";
      addFile.querySelector(".selected span").textContent = fileName;
    }
  }
});

document.addEventListener("click", (event) => {
  if (event.target.closest("button")) {
    const addFile = event.target.closest(".add_file");
    if (addFile) {
      const inputFile = addFile.querySelector("input[type='file']");
      const fileNameSpan = addFile.querySelector(".selected span");

      // Очищує вибір файла
      inputFile.value = "";
      fileNameSpan.textContent = "";
      addFile.classList.remove("on");
    }
  }
});
