export function initCountUp() {
    const nodes = document.querySelectorAll('[data-count-up]');
    if (!nodes.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(({ target, isIntersecting }) => {
            if (!isIntersecting) return;
            observer.unobserve(target);
            const value = Number(target.dataset.countUp);
            if (!Number.isFinite(value) || value < 0) return;
            const finalText = target.textContent;
            const match = finalText.match(/[\d.,]+/);
            if (!match) return;
            const prefix = finalText.slice(0, match.index);
            const suffix = finalText.slice(match.index + match[0].length);
            const locale = match[0].includes('.') ? 'vi-VN' : 'en-US';
            const format = (number) => `${prefix}${Math.round(number).toLocaleString(locale)}${suffix}`;
            const start = performance.now();
            target.setAttribute('aria-label', finalText.trim());
            function frame(now) {
                const progress = Math.min(1, (now - start) / 900);
                target.textContent = progress === 1 ? finalText : format(value * (1 - (1 - progress) ** 3));
                if (progress < 1) requestAnimationFrame(frame);
            }
            requestAnimationFrame(frame);
        });
    }, { threshold: 0.2 });
    nodes.forEach(node => observer.observe(node));
}
