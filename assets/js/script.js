document.addEventListener('DOMContentLoaded', function() {
  // Mobile nav toggle
  const toggle = document.getElementById('navToggle');
  const nav = document.getElementById('mainNav');
  if (toggle && nav) {
    toggle.addEventListener('click', function (e) {
      e.stopPropagation();
      nav.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
      if (!nav.contains(e.target) && !toggle.contains(e.target)) {
        nav.classList.remove('open');
      }
    });
  }

  // Header scroll effect
  const header = document.getElementById('siteHeader');
  if (header) {
    let ticking = false;
    window.addEventListener('scroll', function () {
      if (!ticking) {
        window.requestAnimationFrame(function () {
          header.classList.toggle('scrolled', window.scrollY > 20);
          ticking = false;
        });
        ticking = true;
      }
    });
  }

  // Alert dismiss
  document.querySelectorAll('.alert .btn-close').forEach(function (btn) {
    btn.addEventListener('click', function () {
      this.parentElement.style.display = 'none';
    });
  });

  // Confirm dialogs
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm(this.getAttribute('data-confirm') || 'Are you sure?')) {
        e.preventDefault();
      }
    });
  });

  // Intersection Observer for fade-in animations
  const animateElements = document.querySelectorAll('.animate-in');
  if (animateElements.length && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });
    animateElements.forEach(function (el) {
      el.style.opacity = '0';
      el.style.transform = 'translateY(24px)';
      el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
      observer.observe(el);
    });
  } else {
    animateElements.forEach(function (el) {
      el.style.opacity = '1';
      el.style.transform = 'translateY(0)';
    });
  }

  // Counter animation for stat numbers
  function animateCounter(el) {
    var target = parseInt(el.getAttribute('data-target')) || parseInt(el.textContent.replace(/[^0-9]/g, '')) || 0;
    if (target === 0) return;
    var suffix = el.textContent.replace(/[0-9]/g, '');
    var current = 0;
    var increment = Math.ceil(target / 60);
    var timer = setInterval(function () {
      current += increment;
      if (current >= target) {
        current = target;
        clearInterval(timer);
      }
      el.textContent = current + suffix;
    }, 25);
  }

  if ('IntersectionObserver' in window) {
    var counterObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var number = entry.target.querySelector('.stat-number, .number');
          if (number) animateCounter(number);
          counterObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.3 });
    document.querySelectorAll('.stats-grid, .hero-stats').forEach(function (el) {
      counterObserver.observe(el);
    });
  }
});
