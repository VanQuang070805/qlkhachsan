import '../css/animated-toast.css';

// Adapted from Joly UI Animated Toast for the existing Blade/Vue application.
const icons = {
  success: '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
  error: '<circle cx="12" cy="12" r="9"/><path d="M12 8v5m0 3h.01"/>',
  warning: '<path d="M12 3 2 20h20L12 3Z"/><path d="M12 9v4m0 3h.01"/>',
  info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v5m0-8h.01"/>',
  default: '<path d="M18 8a6 6 0 0 0-12 0c0 7-2 7-2 9h16c0-2-2-2-2-9M10 20h4"/>'
};

export function showAnimatedToast(message, type = 'default', duration = 5000) {
  type = Object.hasOwn(icons, type) ? type : 'default';
  let stack = document.getElementById('animated-toast-stack');
  if (!stack) {
    stack = document.createElement('div');
    stack.id = 'animated-toast-stack';
    stack.className = 'animated-toast-stack';
    stack.setAttribute('aria-label', 'Thông báo');
    document.body.append(stack);
  }
  while (stack.children.length >= 5) stack.firstElementChild.remove();
  const card = document.createElement('div');
  card.className = `animated-toast animated-toast--${type}`;
  card.setAttribute('role', type === 'error' ? 'alert' : 'status');
  card.innerHTML = `<svg class="animated-toast__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${icons[type]}</svg><div class="animated-toast__message"></div><button class="animated-toast__close" type="button" aria-label="Đóng thông báo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg></button>`;
  card.querySelector('.animated-toast__message').textContent = String(message);
  let timer;
  const dismiss = () => {
    clearTimeout(timer);
    if (card.classList.contains('is-closing')) return;
    card.classList.add('is-closing');
    setTimeout(() => card.remove(), 200);
  };
  card.querySelector('button').addEventListener('click', dismiss);
  if (duration > 0) {
    const progress = document.createElement('div');
    progress.className = 'animated-toast__progress';
    progress.style.animationDuration = `${duration}ms`;
    card.append(progress);
    timer = setTimeout(dismiss, duration);
  }
  stack.append(card);
  return dismiss;
}
window.royalToast = showAnimatedToast;

function displayFlashes() {
  document.querySelectorAll('[data-animated-flash]').forEach(el => {
    showAnimatedToast(el.textContent.trim(), el.dataset.animatedFlash);
    el.remove();
  });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', displayFlashes);
else displayFlashes();
