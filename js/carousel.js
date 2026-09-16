// Carousel 3D functionality
document.addEventListener("DOMContentLoaded", () => {
  const carousel = document.getElementById("carousel3d");
  if (!carousel) return;
  
  const spinner = carousel.querySelector(".spinner");
  const prevBtn = document.getElementById("prevBtn");
  const nextBtn = document.getElementById("nextBtn");
  const progressDots = document.querySelectorAll(".progress-dot");
  const carouselItems = document.querySelectorAll(".carousel-item");
  
  if (!spinner || !prevBtn || !nextBtn) return;
  
  let angle = 0;
  let autoRotate = true;
  let rotationSpeed = 0.2; // steady rotation speed
  let currentIndex = 0;
  let isAnimating = false;
  let targetAngle = 0;
  let smoothAngle = 0;

  // Center-locked animation (no mouse-based movement)
  function animate() {
    if (autoRotate && !isAnimating) {
      angle = (angle + rotationSpeed) % 360;
      targetAngle = angle;
    }
    smoothAngle += (targetAngle - smoothAngle) * 0.08;
    // keep spinner centered at all times
    spinner.style.transform = `translateX(-50%) rotateY(${smoothAngle}deg)`;
    updateProgressDots();
    requestAnimationFrame(animate);
  }

  function updateProgressDots() {
    const normalizedAngle = ((smoothAngle % 360) + 360) % 360;
    const itemAngle = 360 / carouselItems.length;
    const currentItemIndex = Math.round(normalizedAngle / itemAngle) % carouselItems.length;
    progressDots.forEach((dot, index) => {
      dot.classList.toggle("active", index === currentItemIndex);
    });
    // ensure only center/front card is highlighted
    carouselItems.forEach((item, index) => {
      if (index === currentItemIndex) item.classList.add("active");
      else item.classList.remove("active");
    });
  }

  // Manual navigation keeps rotation centered
  function goToNext() {
    if (isAnimating) return;
    isAnimating = true;
    currentIndex = (currentIndex + 1) % carouselItems.length;
    targetAngle = currentIndex * (360 / carouselItems.length);
    autoRotate = false;
    setTimeout(() => {
      isAnimating = false;
      autoRotate = true;
    }, 1200);
  }

  function goToPrev() {
    if (isAnimating) return;
    isAnimating = true;
    currentIndex = (currentIndex - 1 + carouselItems.length) % carouselItems.length;
    targetAngle = currentIndex * (360 / carouselItems.length);
    autoRotate = false;
    setTimeout(() => {
      isAnimating = false;
      autoRotate = true;
    }, 1200);
  }

  function handleProgressClick(e) {
    if (isAnimating) return;
    const index = parseInt(e.target.dataset.index);
    if (!Number.isNaN(index) && index !== currentIndex) {
      isAnimating = true;
      currentIndex = index;
      targetAngle = currentIndex * (360 / carouselItems.length);
      autoRotate = false;
      setTimeout(() => {
        isAnimating = false;
        autoRotate = true;
      }, 1200);
    }
  }

  // Event listeners
  prevBtn.addEventListener("click", goToPrev);
  nextBtn.addEventListener("click", goToNext);
  
  progressDots.forEach(dot => {
    dot.addEventListener("click", handleProgressClick);
  });

  // Initialize
  animate();

  // Keyboard navigation
  document.addEventListener("keydown", (e) => {
    if (e.key === "ArrowLeft") goToPrev();
    if (e.key === "ArrowRight") goToNext();
  });

  // Touch swipe navigation (does not change center position)
  let touchStartX = 0;
  let touchStartY = 0;
  carousel.addEventListener("touchstart", (e) => {
    touchStartX = e.touches[0].clientX;
    touchStartY = e.touches[0].clientY;
  });
  carousel.addEventListener("touchend", (e) => {
    const dx = e.changedTouches[0].clientX - touchStartX;
    const dy = e.changedTouches[0].clientY - touchStartY;
    if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 50) {
      if (dx > 0) goToPrev(); else goToNext();
    }
  });
});

