document.addEventListener('DOMContentLoaded', () => {
  const splash = document.querySelector('.splash-screen');
  const onboard = document.querySelector('.onboarding-container');
  const slides = document.querySelector('.slides');
  const dots = document.querySelectorAll('.dot');
  const btn = document.getElementById('onboardBtn');
  let current = 0;

  function showSlide(index) {
    slides.style.transform = `translateX(-${index * 100}%)`;
    dots.forEach((d,i) => d.classList.toggle('active', i === index));
    btn.textContent = index === dots.length - 1 ? 'Continue' : 'Next';
  }

  btn.addEventListener('click', () => {
    if (current < dots.length - 1) {
      current++;
      showSlide(current);
    } else {
      onboard.classList.remove('active');
      document.getElementById('userType').classList.add('active');
    }
  });

  let startX = 0;
  slides.addEventListener('touchstart', e => startX = e.touches[0].clientX);
  slides.addEventListener('touchend', e => {
    const dx = e.changedTouches[0].clientX - startX;
    if (dx > 50 && current > 0) { current--; showSlide(current); }
    else if (dx < -50 && current < dots.length - 1) { current++; showSlide(current); }
  });

  setTimeout(() => {
    splash.classList.add('hidden');
    onboard.classList.add('active');
  }, 2500);

  showSlide(current);

  const homeBtn = document.getElementById('btnHomeowner');
  const serviceBtn = document.getElementById('btnProvider');
  function navigateWithFade(url) {
    document.body.classList.add('fade-out');
    onboard.classList.add('fade-out');
    document.getElementById('userType').classList.add('fade-out');
    setTimeout(() => { window.location.href = url; }, 400);
  }
  homeBtn.addEventListener('click', () => navigateWithFade('index.php'));
  serviceBtn.addEventListener('click', () => navigateWithFade('provider/provider_index.php'));
});