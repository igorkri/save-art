$(document).ready(function () {
  $('#videos .imgs').slick({
    dots: true,
    arrows: true,
    slidesToShow: 1,
    slidesToScroll: 1,
    touchThreshold: 100,
    swipeToSlide: true,
    infinite: true,
    prevArrow: $('#videos .pagin .prev'),
    nextArrow: $('#videos .pagin .next'),
    appendDots: $('#videos .pagin .dots'),
  });
});

$(document).ready(function () {
  $('#best_works .slider').slick({
    dots: false,
    arrows: false,
    slidesToShow: 1,
    slidesToScroll: 1,
    touchThreshold: 100,
    swipeToSlide: true,
    infinite: true,
    variableWidth: true,
    speed: 3000,
    autoplay: true,
    autoplaySpeed: 0,
    cssEase: 'linear',
    pauseOnHover: false
  });
});

$(document).ready(function () {
  $('.project_description .block .cont .slider .imgs').slick({
    dots: true,
    arrows: true,
    slidesToShow: 1,
    slidesToScroll: 1,
    touchThreshold: 100,
    swipeToSlide: true,
    infinite: true,
    prevArrow: $('.project_description .block .cont .slider .pagin .prev'),
    nextArrow: $('.project_description .block .cont .slider .pagin .next'),
    appendDots: $('.project_description .block .cont .slider .pagin .dots'),
  });
});

$(document).ready(function () {
  $('#videos .imgs').slick({
    dots: true,
    arrows: true,
    slidesToShow: 1,
    slidesToScroll: 1,
    touchThreshold: 100,
    swipeToSlide: true,
    infinite: true,
    prevArrow: $('#videos .pagin .prev'),
    nextArrow: $('#videos .pagin .next'),
    appendDots: $('#videos .pagin .dots'),
  });
});

$(document).ready(function () {
  $('#pictures .imgs').slick({
    dots: true,
    arrows: true,
    slidesToShow: 1,
    slidesToScroll: 1,
    touchThreshold: 100,
    swipeToSlide: true,
    infinite: true,
    fade: true,
    prevArrow: $('#pictures .pagin .prev'),
    nextArrow: $('#pictures .pagin .next'),
    appendDots: $('#pictures .pagin .dots'),
  });
});

document.querySelectorAll('#pictures .imgs_small .pict').forEach((smallImg, index) => {
  smallImg.addEventListener('click', () => {
    $('#pictures .imgs').slick('slickGoTo', index);

    // Додаємо клас .on та знімаємо з інших
    document.querySelectorAll('#pictures .imgs_small .pict').forEach(img => img.classList.remove('on'));
    smallImg.classList.add('on');

    // Прокручування до початку контейнера
    smallImg.scrollIntoView({ behavior: "smooth", block: "nearest", inline: "start" });
  });
});

$('#pictures .imgs').on('afterChange', function (event, slick, currentSlide) {
  const smallImages = document.querySelectorAll('#pictures .imgs_small .pict');

  // Додаємо клас .on та знімаємо з інших
  smallImages.forEach(img => img.classList.remove('on'));
  smallImages[currentSlide].classList.add('on');

  // Прокручування до відповідного маленького зображення
  smallImages[currentSlide].scrollIntoView({ behavior: "smooth", block: "nearest", inline: "start" });
});

// Подія після зміни слайда
$('#pictures .imgs').on('beforeChange', function (event, slick, currentSlide, nextSlide) {
  // Знаходимо всі iframe у слайдах
  const iframes = $('.slider').find('iframe');

  iframes.each(function () {
    // Зупиняємо відео, використовуючи API YouTube
    const iframeSrc = $(this).attr('src');
    if (iframeSrc.includes('youtube.com')) {
      // Додаємо ?enablejsapi=1 до URL, якщо це ще не зроблено
      if (!iframeSrc.includes('enablejsapi=1')) {
        $(this).attr('src', iframeSrc + (iframeSrc.includes('?') ? '&' : '?') + 'enablejsapi=1');
      }
      // Викликаємо зупинку через API
      this.contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*');
    }
  });
});

$('#videos .imgs').on('beforeChange', function (event, slick, currentSlide, nextSlide) {
  // Знаходимо всі iframe у слайдах
  const iframes = $('.slider').find('iframe');

  iframes.each(function () {
    // Зупиняємо відео, використовуючи API YouTube
    const iframeSrc = $(this).attr('src');
    if (iframeSrc.includes('youtube.com')) {
      // Додаємо ?enablejsapi=1 до URL, якщо це ще не зроблено
      if (!iframeSrc.includes('enablejsapi=1')) {
        $(this).attr('src', iframeSrc + (iframeSrc.includes('?') ? '&' : '?') + 'enablejsapi=1');
      }
      // Викликаємо зупинку через API
      this.contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*');
    }
  });
});