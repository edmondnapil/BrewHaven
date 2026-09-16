// ========== FUTURISTIC MILK TEA DASHBOARD ANIMATIONS ==========

// Enhanced ScrollReveal Animations with milk tea theme
ScrollReveal().reveal('.hero-text', {
  delay: 200,
  duration: 1200,
  origin: 'left',
  distance: '60px',
  easing: 'ease-out',
  opacity: 0
});

ScrollReveal().reveal('.hero-image', {
  delay: 400,
  duration: 1200,
  origin: 'right',
  distance: '60px',
  easing: 'ease-out',
  opacity: 0
});

ScrollReveal().reveal('.product-card', {
  interval: 150,
  delay: 300,
  duration: 1000,
  origin: 'bottom',
  distance: '50px',
  easing: 'ease-out',
  opacity: 0,
  scale: 0.9
});

ScrollReveal().reveal('.products h2', {
  delay: 100,
  duration: 1000,
  origin: 'top',
  distance: '40px',
  easing: 'ease-out',
  opacity: 0
});

// ========== ENHANCED FLAVOR CHIP INTERACTIONS ==========
document.addEventListener('DOMContentLoaded', () => {
  const chips = document.querySelectorAll('.flavor-chip');
  const heroImg = document.getElementById('hero-img');
  const title = document.querySelector('.hero-text h1');

  // Add ripple effect on click
  chips.forEach(chip => {
    chip.addEventListener('click', function(e) {
      // Remove active class from all chips
      chips.forEach(c => {
        c.classList.remove('active');
        c.style.transform = '';
      });

      // Add active class to clicked chip
      this.classList.add('active');

      // Ripple effect
      const ripple = document.createElement('span');
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;

      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      ripple.classList.add('ripple');

      this.appendChild(ripple);

      setTimeout(() => {
        ripple.remove();
      }, 600);

      // Update hero image with smooth transition
      const img = this.getAttribute('data-img');
      const txt = this.getAttribute('data-title');

      if (img && heroImg) {
        heroImg.style.opacity = '0';
        heroImg.style.transform = 'scale(0.9)';

        setTimeout(() => {
          heroImg.src = img;
          heroImg.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
          heroImg.style.opacity = '1';
          heroImg.style.transform = 'scale(1)';
        }, 200);
      }

      if (txt && title) {
        title.style.opacity = '0';
        title.style.transform = 'translateY(-20px)';

        setTimeout(() => {
          title.innerHTML = txt.replace('Milk Tea', '<span>Milk Tea</span>').toUpperCase();
          title.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
          title.style.opacity = '1';
          title.style.transform = 'translateY(0)';
        }, 200);
      }
    });

    // Add hover sound effect simulation (visual feedback)
    chip.addEventListener('mouseenter', function() {
      this.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
    });
  });
});

// ========== ENHANCED AUTO-SLIDE WITH SMOOTH SCROLLING ==========
const slider = document.getElementById('slider');
let scrollAmount = 0;
let isScrolling = true;
let scrollDirection = 1;

function autoSlide() {
  if (!slider) return;

  const maxScroll = slider.scrollWidth - slider.clientWidth;

  if (scrollAmount >= maxScroll) {
    scrollDirection = -1;
  } else if (scrollAmount <= 0) {
    scrollDirection = 1;
  }

  scrollAmount += scrollDirection * 1.5;

  slider.scrollTo({
    left: scrollAmount,
    behavior: 'smooth'
  });
}

// Start auto-slide if slider exists
if (slider) {
  let slideInterval = setInterval(autoSlide, 50);

  // Pause on hover
  slider.addEventListener('mouseenter', () => {
    clearInterval(slideInterval);
  });

  slider.addEventListener('mouseleave', () => {
    slideInterval = setInterval(autoSlide, 50);
  });

  // Pause on touch
  let touchStartX = 0;
  slider.addEventListener('touchstart', (e) => {
    touchStartX = e.touches[0].clientX;
    clearInterval(slideInterval);
  });

  slider.addEventListener('touchend', () => {
    slideInterval = setInterval(autoSlide, 50);
  });
}

// ========== PARALLAX EFFECT FOR HERO SECTION ==========
window.addEventListener('scroll', () => {
  const scrolled = window.pageYOffset;
  const hero = document.querySelector('.hero');
  const heroImage = document.querySelector('.hero-image');

  if (hero && scrolled < hero.offsetHeight) {
    const parallaxSpeed = 0.5;
    if (heroImage) {
      heroImage.style.transform = `translateY(${scrolled * parallaxSpeed}px)`;
    }
  }
});

// ========== SMOOTH BUTTON INTERACTIONS ==========
document.querySelectorAll('.price-btn, .delivery-btn').forEach(button => {
  button.addEventListener('mouseenter', function() {
    this.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
  });

  button.addEventListener('click', function(e) {
    // Ripple effect
    const ripple = document.createElement('span');
    const rect = this.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = e.clientX - rect.left - size / 2;
    const y = e.clientY - rect.top - size / 2;

    ripple.style.width = ripple.style.height = size + 'px';
    ripple.style.left = x + 'px';
    ripple.style.top = y + 'px';
    ripple.classList.add('ripple');

    this.appendChild(ripple);

    setTimeout(() => {
      ripple.remove();
    }, 600);
  });
});

// ========== ADD RIPPLE EFFECT STYLES ==========
const style = document.createElement('style');
style.textContent = `
  .ripple {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.6);
    transform: scale(0);
    animation: ripple-animation 0.6s ease-out;
    pointer-events: none;
  }

  @keyframes ripple-animation {
    to {
      transform: scale(4);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);

// ========== CURSOR FOLLOW EFFECT (OPTIONAL FUTURISTIC TOUCH) ==========
let cursor = null;

if (window.innerWidth > 768) {
  cursor = document.createElement('div');
  cursor.className = 'custom-cursor';
  cursor.style.cssText = `
    width: 20px;
    height: 20px;
    border: 2px solid rgba(212, 196, 176, 0.5);
    border-radius: 50%;
    position: fixed;
    pointer-events: none;
    z-index: 9999;
    transition: transform 0.1s ease;
    display: none;
  `;
  document.body.appendChild(cursor);

  document.addEventListener('mousemove', (e) => {
    if (cursor) {
      cursor.style.display = 'block';
      cursor.style.left = e.clientX - 10 + 'px';
      cursor.style.top = e.clientY - 10 + 'px';
    }
  });

  // Scale cursor on hover over interactive elements
  document.querySelectorAll('a, button, .flavor-chip, .product-card').forEach(el => {
    el.addEventListener('mouseenter', () => {
      if (cursor) cursor.style.transform = 'scale(1.5)';
    });
    el.addEventListener('mouseleave', () => {
      if (cursor) cursor.style.transform = 'scale(1)';
    });
  });
}

// ========== LOADING ANIMATION ==========
window.addEventListener('load', () => {
  document.body.style.opacity = '0';
  document.body.style.transition = 'opacity 0.5s ease-in';

  setTimeout(() => {
    document.body.style.opacity = '1';
  }, 100);
});

// ========== SMOOTH SCROLL TO SECTIONS ==========
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute('href'));
    if (target) {
      target.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
  });
});

// ========== INTERSECTION OBSERVER FOR FADE-IN ANIMATIONS ==========
const observerOptions = {
  threshold: 0.1,
  rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.style.opacity = '1';
      entry.target.style.transform = 'translateY(0)';
    }
  });
}, observerOptions);

// Observe all product cards
document.querySelectorAll('.product-card').forEach(card => {
  card.style.opacity = '0';
  card.style.transform = 'translateY(30px)';
  card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
  observer.observe(card);
});
