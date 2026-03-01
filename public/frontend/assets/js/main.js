/* ===================================================
   CloudSoft Hosting - استضافة كلاودسوفت
   JavaScript: Theme Toggle, Scroll Animations, etc.
   =================================================== */

// ===========================
// Page Loader — إخفاء اللورد عند اكتمال التحميل
// ===========================
function initPageLoader() {
  const loader = document.getElementById('pageLoader');
  if (!loader) return;
  function hideLoader() {
    loader.classList.add('hide');
    setTimeout(() => loader.remove(), 550);
  }
  if (document.readyState === 'complete') {
    setTimeout(hideLoader, 300);
  } else {
    window.addEventListener('load', () => setTimeout(hideLoader, 300));
  }
}
initPageLoader();

document.addEventListener('DOMContentLoaded', () => {

  // ===========================
  // 1. Theme Toggle (Dark/Light)
  // ===========================
  const themeToggle = document.getElementById('themeToggle');
  const htmlEl = document.documentElement;

  // Load saved theme
  const savedTheme = localStorage.getItem('yj-theme') || 'light';
  htmlEl.setAttribute('data-theme', savedTheme);
  updateThemeIcon(savedTheme);

  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      const current = htmlEl.getAttribute('data-theme');
      const next = current === 'dark' ? 'light' : 'dark';
      htmlEl.setAttribute('data-theme', next);
      localStorage.setItem('yj-theme', next);
      updateThemeIcon(next);
    });
  }

  function updateThemeIcon(theme) {
    if (!themeToggle) return;
    const icon = themeToggle.querySelector('i');
    if (icon) {
      icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }
  }

  // ===========================
  // 2. Navbar scroll effect
  // ===========================
  const navbar = document.querySelector('.main-navbar');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 50) {
      navbar?.classList.add('scrolled');
    } else {
      navbar?.classList.remove('scrolled');
    }
  });

  // ===========================
  // 3. Active nav link highlight
  // ===========================
  const currentPage = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.main-navbar .nav-link').forEach(link => {
    const href = link.getAttribute('href');
    if (href === currentPage || (currentPage === '' && href === 'index.html')) {
      link.classList.add('active');
    }
  });

  // ===========================
  // 4. Scroll Animations (AOS substitute)
  // ===========================
  const animatedElements = document.querySelectorAll('.animate-on-scroll');
  
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('animated');
        observer.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.15,
    rootMargin: '0px 0px -40px 0px'
  });

  animatedElements.forEach(el => observer.observe(el));

  // ===========================
  // 5. Counter Animation
  // ===========================
  const counters = document.querySelectorAll('.counter-num');
  const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const target = parseInt(entry.target.getAttribute('data-count'));
        animateCounter(entry.target, target);
        counterObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.5 });

  counters.forEach(counter => counterObserver.observe(counter));

  function animateCounter(el, target) {
    let count = 0;
    const increment = Math.ceil(target / 60);
    const timer = setInterval(() => {
      count += increment;
      if (count >= target) {
        count = target;
        clearInterval(timer);
      }
      el.textContent = count + '+';
    }, 30);
  }

  // ===========================
  // 6. Back To Top
  // ===========================
  const backToTop = document.getElementById('backToTop');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 400) {
      backToTop?.classList.add('active');
    } else {
      backToTop?.classList.remove('active');
    }
  });

  backToTop?.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  // ===========================
  // 7. Lightbox for Gallery
  // ===========================
  const lightbox = document.getElementById('lightbox');
  const lightboxImg = document.getElementById('lightboxImg');
  const lightboxClose = document.getElementById('lightboxClose');

  document.querySelectorAll('.gallery-item').forEach(item => {
    item.addEventListener('click', () => {
      const img = item.querySelector('img');
      if (img && lightboxImg && lightbox) {
        lightboxImg.src = img.src;
        lightboxImg.alt = img.alt;
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
      }
    });
  });

  function closeLightbox() {
    lightbox?.classList.remove('active');
    document.body.style.overflow = '';
  }

  lightboxClose?.addEventListener('click', closeLightbox);
  lightbox?.addEventListener('click', (e) => {
    if (e.target === lightbox) closeLightbox();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeLightbox();
  });

  // ===========================
  // 8. Course Filter
  // ===========================
  const filterBtns = document.querySelectorAll('.filter-btn');
  const courseCards = document.querySelectorAll('.course-filter-item');

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const filter = btn.getAttribute('data-filter');
      courseCards.forEach(card => {
        if (filter === 'all' || card.getAttribute('data-category') === filter) {
          card.style.display = '';
          setTimeout(() => card.style.opacity = '1', 10);
        } else {
          card.style.opacity = '0';
          setTimeout(() => card.style.display = 'none', 300);
        }
      });
    });
  });

  // ===========================
  // 9. Contact Form (Formspree)
  // ===========================
  const contactForm = document.getElementById('contactForm');
  contactForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = contactForm.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الإرسال...';

    try {
      const res = await fetch(contactForm.action, {
        method: 'POST',
        body: new FormData(contactForm),
        headers: { Accept: 'application/json' }
      });
      const data = await res.json();
      if (data.ok) {
        btn.innerHTML = '<i class="fas fa-check-circle"></i> تم الإرسال بنجاح!';
        btn.style.background = '#28a745';
        contactForm.reset();
        setTimeout(() => {
          btn.innerHTML = originalText;
          btn.disabled = false;
          btn.style.background = '';
        }, 3000);
      } else {
        btn.innerHTML = '<i class="fas fa-exclamation-circle"></i> حدث خطأ، حاول لاحقاً';
        btn.style.background = '#dc3545';
        setTimeout(() => {
          btn.innerHTML = originalText;
          btn.disabled = false;
          btn.style.background = '';
        }, 3000);
      }
    } catch (err) {
      btn.innerHTML = '<i class="fas fa-exclamation-circle"></i> خطأ في الاتصال';
      btn.style.background = '#dc3545';
      setTimeout(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        btn.style.background = '';
      }, 3000);
    }
  });

  // ===========================
  // 10. Newsletter Form
  // ===========================
  const newsletterForm = document.getElementById('newsletterForm');
  newsletterForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    const emailInput = document.getElementById('newsletterEmail');
    const btn = newsletterForm.querySelector('button[type="submit"]');
    if (!emailInput?.value.trim()) {
      emailInput?.focus();
      return;
    }
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الاشتراك...';
    // محاكاة إرسال (يمكن ربطه بخدمة مثل Formspree أو Mailchimp لاحقاً)
    setTimeout(() => {
      btn.innerHTML = '<i class="fas fa-check-circle"></i> تم الاشتراك بنجاح!';
      btn.style.background = '#28a745';
      newsletterForm.reset();
      setTimeout(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        btn.style.background = '';
      }, 3000);
    }, 800);
  });

  // ===========================
  // 11. Smooth scroll for anchor links
  // ===========================
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth' });
        // Close mobile navbar
        const navCollapse = document.querySelector('.navbar-collapse');
        if (navCollapse?.classList.contains('show')) {
          const bsCollapse = bootstrap.Collapse.getInstance(navCollapse);
          bsCollapse?.hide();
        }
      }
    });
  });

  // ===========================
  // 12. Typing effect for hero
  // ===========================
  const typingEl = document.getElementById('typingText');
  if (typingEl) {
    const texts = typingEl.getAttribute('data-texts')?.split('|') || [];
    let textIndex = 0;
    let charIndex = 0;
    let isDeleting = false;
    let typingSpeed = 100;

    function type() {
      const currentText = texts[textIndex];
      if (!currentText) return;

      if (isDeleting) {
        typingEl.textContent = currentText.substring(0, charIndex - 1);
        charIndex--;
        typingSpeed = 50;
      } else {
        typingEl.textContent = currentText.substring(0, charIndex + 1);
        charIndex++;
        typingSpeed = 100;
      }

      if (!isDeleting && charIndex === currentText.length) {
        typingSpeed = 2000;
        isDeleting = true;
      } else if (isDeleting && charIndex === 0) {
        isDeleting = false;
        textIndex = (textIndex + 1) % texts.length;
        typingSpeed = 500;
      }

      setTimeout(type, typingSpeed);
    }

    type();
  }

});
