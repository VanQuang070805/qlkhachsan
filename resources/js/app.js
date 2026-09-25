import './bootstrap';
import Lenis from 'lenis';
import { gsap } from 'gsap';
import { Draggable } from 'gsap/Draggable';
import { Flip } from 'gsap/Flip';
import { ScrollToPlugin } from 'gsap/ScrollToPlugin';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';
import { createApp } from 'vue';
import RoyalChat from './components/RoyalChat.vue';
import { enhanceDatePickers } from './date-picker';

gsap.registerPlugin(Draggable, Flip, ScrollToPlugin, ScrollTrigger, SplitText);

document.addEventListener('DOMContentLoaded', () => {
    const pageLoader = document.querySelector('[data-page-loader]');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const chatRoot = document.querySelector('[data-royal-chat]');
    if (chatRoot) createApp(RoyalChat, { endpoint: chatRoot.dataset.endpoint }).mount(chatRoot);

    document.querySelectorAll('.modal-content').forEach((content) => {
        if (content.querySelector('.window-controls')) return;
        const controls = document.createElement('div');
        controls.className = 'window-controls window-controls--modal';
        controls.setAttribute('role', 'group');
        controls.setAttribute('aria-label', 'Điều khiển hộp thoại');
        controls.innerHTML = '<button class="window-control window-control--close" type="button" data-bs-dismiss="modal" aria-label="Đóng hộp thoại"></button>';
        content.prepend(controls);
    });

    enhanceDatePickers();

    if (pageLoader) {
        const content = pageLoader.querySelector('.page-loader__content');
        const progress = pageLoader.querySelector('.page-loader__line i');
        const stars = pageLoader.querySelectorAll('.page-loader__stars i');
        const hasArrived = sessionStorage.getItem('royal-arrival-seen') === '1';

        const revealPage = () => {
            sessionStorage.setItem('royal-arrival-seen', '1');
            if (reduceMotion || hasArrived) {
                pageLoader.classList.add('is-hidden');
                return;
            }

            const starsTween = gsap.to(stars, { y: -10, autoAlpha: 0.35, duration: 1.4, stagger: 0.06, repeat: -1, yoyo: true, ease: 'sine.inOut' });
            gsap.timeline({
                defaults: { ease: 'power3.out' },
                onComplete: () => {
                    starsTween.kill();
                    pageLoader.classList.add('is-hidden');
                },
            })
                .fromTo(content, { autoAlpha: 0, y: 10 }, { autoAlpha: 1, y: 0, duration: 0.28 })
                .to(progress, { scaleX: 1, duration: 0.32, ease: 'power2.inOut' }, '<')
                .to(content, { autoAlpha: 0, y: -8, duration: 0.18 }, '+=0.04')
                .to(pageLoader, { autoAlpha: 0, duration: 0.24, ease: 'power2.out' }, '-=0.08');
        };

        revealPage();
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) pageLoader.classList.add('is-hidden');
        });
    }

    const lenis = reduceMotion ? null : new Lenis({
        anchors: false,
        lerp: 0.1,
        smoothWheel: true,
        syncTouch: false,
    });

    lenis?.on('scroll', ScrollTrigger.update);

    const updateLenis = (time) => lenis?.raf(time * 1000);
    if (lenis) gsap.ticker.add(updateLenis);

    window.addEventListener('pagehide', () => {
        if (lenis) gsap.ticker.remove(updateLenis);
        lenis?.destroy();
    }, { once: true });

    const toggle = document.querySelector('[data-nav-toggle]');
    const navigation = document.querySelector('[data-primary-nav]');
    const siteHeaders = document.querySelectorAll('[data-site-header]');
    const cleanup = [];

    const authCard = document.querySelector('.auth-card');
    if (authCard && !reduceMotion) {
        gsap.from(authCard.querySelectorAll('.auth-intro > *, .form-group, .btn-auth, .divider'), {
            autoAlpha: 0,
            y: 10,
            duration: .38,
            stagger: .035,
            delay: .08,
            ease: 'power2.out',
            clearProps: 'opacity,visibility,transform',
        });
    }

    if (siteHeaders.length) {
        const updateHeaderState = () => {
            siteHeaders.forEach((siteHeader) => {
                siteHeader.classList.toggle('is-scrolled', window.scrollY > 48);
            });
        };

        updateHeaderState();
        const headerTrigger = ScrollTrigger.create({
            start: 48,
            end: 'max',
            onUpdate: updateHeaderState,
            onRefresh: updateHeaderState,
        });
        cleanup.push(() => headerTrigger.kill());
    }

    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        const targetId = anchor.getAttribute('href')?.slice(1);
        const target = targetId ? document.getElementById(decodeURIComponent(targetId)) : null;

        if (!target) return;

        anchor.addEventListener('click', (event) => {
            event.preventDefault();
            if (window.location.hash !== `#${targetId}`) {
                window.history.pushState(null, '', `#${targetId}`);
            }

            if (anchor.hasAttribute('data-gsap-scroll-to')) {
                lenis?.stop();
                gsap.to(window, {
                    duration: 1.05,
                    ease: 'power2.inOut',
                    overwrite: 'auto',
                    scrollTo: { y: target, offsetY: 88, autoKill: true },
                    onComplete: () => lenis?.start(),
                    onInterrupt: () => lenis?.start(),
                });
                return;
            }

            if (lenis) {
                lenis.scrollTo(target, { offset: -88 });
            } else {
                gsap.to(window, {
                    duration: 1.05,
                    ease: 'power2.inOut',
                    scrollTo: { y: target, offsetY: 88, autoKill: true },
                });
            }
        });
    });

        document.querySelectorAll('.hero, .page-hero, .search-hero, .editorial-hero, .contact-hero, .contact-panel, .auth-container').forEach((surface) => {
            if (surface.querySelector(':scope > .ambient-stars')) return;

            surface.setAttribute('data-star-surface', '');
            const starfield = document.createElement('div');
            starfield.className = 'ambient-stars';
            starfield.setAttribute('aria-hidden', 'true');
            const starTweens = [];

            for (let index = 0; index < 28; index += 1) {
                const star = document.createElement('span');
                const depth = index % 7 === 0 ? 'near' : index % 3 === 0 ? 'mid' : 'far';
                const depthFactor = depth === 'near' ? 1.65 : depth === 'mid' ? 1.2 : 0.85;
                const size = (index % 6 === 0 ? 3.2 : 1.3 + (index % 3) * 0.65) * depthFactor;
                star.className = `ambient-star ambient-star--${depth}`;
                star.style.setProperty('--star-x', `${(index * 37 + 9) % 96}%`);
                star.style.setProperty('--star-y', `${(index * 53 + 7) % 92}%`);
                star.style.setProperty('--star-size', `${size}px`);
                star.style.setProperty('--star-alpha', `${0.44 + (index % 5) * 0.1}`);
                starfield.appendChild(star);

                const tween = gsap.to(star, {
                    x: ((index % 5) - 2) * (8 + (index % 6) * depthFactor),
                    y: -(22 + (index % 5) * 7) * depthFactor,
                    opacity: depth === 'near' ? 0.92 : 0.32 + (index % 4) * 0.16,
                    scale: index % 2 ? 1.45 : 0.6,
                    duration: (depth === 'near' ? 2.8 : depth === 'mid' ? 3.8 : 5.4) + (index % 5) * 0.4,
                    delay: -(index % 7) * 0.65,
                    ease: 'sine.inOut',
                    repeat: -1,
                    yoyo: true,
                });
                starTweens.push(tween);
                cleanup.push(() => tween.kill());
            }

            const comet = document.createElement('span');
            comet.className = 'ambient-comet';
            comet.style.top = '26%';
            starfield.appendChild(comet);

            const flight = gsap.fromTo(comet, {
                x: 0,
                y: 0,
                autoAlpha: 0,
            }, {
                x: () => surface.clientWidth + 180,
                y: () => surface.clientHeight * 0.18,
                autoAlpha: 0.72,
                duration: 2.3,
                delay: 1.8,
                ease: 'power1.inOut',
                repeat: -1,
                repeatDelay: 6.5,
                keyframes: [
                    { autoAlpha: 0, duration: 0.15 },
                    { autoAlpha: 0.72, duration: 0.35 },
                    { autoAlpha: 0, duration: 0.3, delay: 1.5 },
                ],
            });
            starTweens.push(flight);
            cleanup.push(() => flight.kill());

            surface.prepend(starfield);

            const visibilityTrigger = ScrollTrigger.create({
                trigger: surface,
                start: 'top bottom',
                end: 'bottom top',
                onToggle: (self) => {
                    starTweens.forEach((tween) => {
                        if (self.isActive) tween.play();
                        else tween.pause();
                    });
                },
            });
            cleanup.push(() => visibilityTrigger.kill());

            const moveFieldX = gsap.quickTo(starfield, 'x', { duration: 0.8, ease: 'power3.out' });
            const moveFieldY = gsap.quickTo(starfield, 'y', { duration: 0.8, ease: 'power3.out' });
            const onStarPointerMove = (event) => {
                if (event.pointerType === 'touch') return;
                const bounds = surface.getBoundingClientRect();
                const normalizedX = (event.clientX - bounds.left) / bounds.width - 0.5;
                const normalizedY = (event.clientY - bounds.top) / bounds.height - 0.5;
                moveFieldX(normalizedX * 26);
                moveFieldY(normalizedY * 18);
            };
            const resetStarField = () => {
                moveFieldX(0);
                moveFieldY(0);
            };
            surface.addEventListener('pointermove', onStarPointerMove, { passive: true });
            surface.addEventListener('pointerleave', resetStarField, { passive: true });
            cleanup.push(() => {
                surface.removeEventListener('pointermove', onStarPointerMove);
                surface.removeEventListener('pointerleave', resetStarField);
            });
        });

        const scrollProgress = document.querySelector('[data-scroll-progress]');
        if (scrollProgress) {
            gsap.to(scrollProgress, {
                scaleX: 1,
                ease: 'none',
                scrollTrigger: {
                    trigger: document.documentElement,
                    start: 'top top',
                    end: 'bottom bottom',
                    scrub: 0.25,
                },
            });
        }

        const customerReveals = [...document.querySelectorAll(
            '[data-reveal], .legacy-main .search-card, .legacy-main .filter-card, .legacy-main .room-card, .legacy-main .review-card'
        )].filter((el) => !el.closest('.section'));
        if (customerReveals.length) ScrollTrigger.batch(customerReveals, {
            start: 'top 90%',
            once: true,
            interval: 0.06,
            batchMax: 6,
            onEnter: batch => gsap.fromTo(batch,
                { autoAlpha: 0, y: 20 },
                { autoAlpha: 1, y: 0, duration: .52, stagger: .065, ease: 'power3.out', clearProps: 'opacity,visibility,transform' },
            ),
        });

        document.querySelectorAll('[data-booking-steps]').forEach((steps) => {
            const items = steps.querySelectorAll(':scope > li');
            gsap.fromTo(items,
                { autoAlpha: 0, y: 12 },
                { autoAlpha: 1, y: 0, duration: .5, stagger: .075, ease: 'power2.out', clearProps: 'opacity,visibility,transform' },
            );
        });

        document.querySelectorAll('.window-controls').forEach((controls) => {
            gsap.from(controls.children, {
                scale: 0,
                duration: .38,
                stagger: .07,
                ease: 'back.out(2.1)',
                clearProps: 'transform',
            });
        });

        document.querySelectorAll('[data-window-frame]').forEach((frame) => {
            const controls = frame.querySelector('.window-controls');
            if (!controls) return;

            const title = frame.dataset.windowTitle || 'Cửa sổ';
            const restore = document.createElement('button');
            restore.type = 'button';
            restore.className = 'window-restore';
            restore.hidden = true;
            restore.innerHTML = '<i class="bi bi-window" aria-hidden="true"></i><span></span>';
            restore.querySelector('span').textContent = `Mở lại ${title}`;
            frame.after(restore);

            const setClosed = (closed) => {
                gsap.killTweensOf(frame);
                if (closed) {
                    if (reduceMotion) frame.hidden = true;
                    else gsap.to(frame, { autoAlpha: 0, scale: .97, y: 8, duration: .2, ease: 'power2.in', onComplete: () => { frame.hidden = true; gsap.set(frame, { clearProps: 'opacity,visibility,transform' }); } });
                    restore.hidden = false;
                    return;
                }
                frame.hidden = false;
                restore.hidden = true;
                if (!reduceMotion) gsap.fromTo(frame, { autoAlpha: 0, scale: .97, y: 8 }, { autoAlpha: 1, scale: 1, y: 0, duration: .34, ease: 'power3.out', clearProps: 'opacity,visibility,transform' });
            };

            restore.addEventListener('click', () => setClosed(false));
            frame.addEventListener('input', () => frame.classList.add('has-window-changes'));
            frame.addEventListener('change', () => frame.classList.add('has-window-changes'));

            controls.addEventListener('click', async (event) => {
                const control = event.target.closest('[data-window-action]');
                if (!control) return;
                const action = control.dataset.windowAction;
                if (action === 'close') {
                    setClosed(true);
                    restore.focus();
                    return;
                }
                if (action === 'minimize') {
                    const minimized = frame.classList.toggle('is-window-minimized');
                    control.setAttribute('aria-expanded', String(!minimized));
                    control.setAttribute('aria-label', `${minimized ? 'Mở rộng' : 'Thu gọn'} ${title}`);
                    if (!reduceMotion) gsap.fromTo(frame, { scale: .99 }, { scale: 1, duration: .25, ease: 'power2.out', clearProps: 'transform' });
                    return;
                }
                if (action === 'zoom') {
                    if (event.altKey) {
                        frame.classList.toggle('is-window-zoomed');
                    } else if (document.fullscreenElement === frame) {
                        await document.exitFullscreen?.();
                    } else if (frame.requestFullscreen) {
                        try {
                            await frame.requestFullscreen();
                            if (document.fullscreenElement !== frame) frame.classList.toggle('is-window-zoomed');
                        } catch {
                            frame.classList.toggle('is-window-zoomed');
                        }
                    } else {
                        frame.classList.toggle('is-window-zoomed');
                    }
                }
            });
        });

        const interiorHero = document.querySelector('.page-hero');
        if (interiorHero) {
            const heroParts = interiorHero.querySelectorAll(':scope > .eyebrow, :scope > h1, :scope > p, :scope > .button');
            gsap.from(heroParts, {
                autoAlpha: 0,
                y: 22,
                duration: 0.76,
                stagger: 0.11,
                ease: 'power3.out',
                clearProps: 'transform',
            });
        }

        const contactHero = document.querySelector('.contact-page--combined .contact-hero');
        if (contactHero) {
            const orbit = contactHero.querySelector('.contact-hero__orbit');
            const heroCopy = contactHero.querySelector('.contact-hero__copy');

            if (heroCopy) {
                gsap.from(heroCopy.querySelectorAll('.editorial-eyebrow, h1, :scope > p, .contact-hero__actions > *'), {
                    autoAlpha: 0,
                    y: 22,
                    duration: 0.78,
                    stagger: 0.11,
                    ease: 'power3.out',
                    delay: 0.08,
                    clearProps: 'transform',
                });
            }

            if (orbit) {
                gsap.to(orbit, {
                    yPercent: 10,
                    ease: 'none',
                    scrollTrigger: {
                        trigger: contactHero,
                        start: 'top top',
                        end: 'bottom top',
                        scrub: 0.8,
                    },
                });
            }
        }

        const resortGallery = document.querySelector('[data-resort-gallery]');
        if (resortGallery) {
            const viewport = resortGallery.querySelector('[data-gallery-viewport]');
            const track = resortGallery.querySelector('[data-gallery-track]');
            const slides = [...track.querySelectorAll('.contact-gallery__slide')];
            const dots = resortGallery.querySelector('[data-gallery-dots]');
            const previous = resortGallery.querySelector('[data-gallery-prev]');
            const next = resortGallery.querySelector('[data-gallery-next]');
            const toggleAutoplay = resortGallery.querySelector('[data-gallery-toggle]');
            let current = 0;
            let autoplay;
            let draggable;
            let autoplayPaused = false;

            const dotButtons = slides.map((slide, index) => {
                slide.setAttribute('aria-hidden', String(index !== 0));
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.setAttribute('aria-label', `Xem ảnh ${index + 1}`);
                dot.classList.toggle('is-active', index === 0);
                dot.setAttribute('aria-current', index === 0 ? 'true' : 'false');
                dots.appendChild(dot);
                return dot;
            });

            const pauseAutoplay = () => autoplay?.kill();
            const scheduleAutoplay = () => {
                pauseAutoplay();
                if (autoplayPaused) return;
                autoplay = gsap.delayedCall(5.2, () => goTo(current + 1));
            };
            const updateA11y = () => {
                slides.forEach((slide, index) => slide.setAttribute('aria-hidden', String(index !== current)));
                dotButtons.forEach((dot, index) => {
                    dot.classList.toggle('is-active', index === current);
                    dot.setAttribute('aria-current', index === current ? 'true' : 'false');
                });
            };
            const goTo = (index, userInitiated = false) => {
                current = (index + slides.length) % slides.length;
                updateA11y();
                const activeImage = slides[current].querySelector('img');
                gsap.to(track, {
                    x: -current * viewport.clientWidth,
                    duration: 0.72,
                    ease: 'power3.inOut',
                    overwrite: true,
                    onUpdate: () => draggable?.update(),
                });
                if (activeImage) {
                    gsap.fromTo(activeImage, { scale: 1.07 }, {
                        scale: 1,
                        duration: 1.15,
                        ease: 'power3.out',
                        overwrite: true,
                    });
                }
                if (userInitiated) scheduleAutoplay();
                else scheduleAutoplay();
            };

            draggable = Draggable.create(track, {
                type: 'x',
                bounds: { minX: -(slides.length - 1) * viewport.clientWidth, maxX: 0 },
                edgeResistance: 0.82,
                zIndexBoost: false,
                onPress: () => {
                    pauseAutoplay();
                    gsap.killTweensOf(track);
                },
                onRelease: function () {
                    goTo(Math.round(-this.x / viewport.clientWidth), true);
                },
            })[0];

            const showPrevious = () => goTo(current - 1, true);
            const showNext = () => goTo(current + 1, true);
            const toggleGalleryAutoplay = () => {
                autoplayPaused = !autoplayPaused;
                toggleAutoplay.setAttribute('aria-pressed', String(autoplayPaused));
                toggleAutoplay.setAttribute('aria-label', autoplayPaused ? 'Tiếp tục tự động chuyển ảnh' : 'Tạm dừng tự động chuyển ảnh');
                toggleAutoplay.innerHTML = autoplayPaused
                    ? '<i class="bi bi-play-fill" aria-hidden="true"></i>'
                    : '<i class="bi bi-pause-fill" aria-hidden="true"></i>';
                if (autoplayPaused) pauseAutoplay();
                else scheduleAutoplay();
            };
            const onKeyDown = (event) => {
                if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
                event.preventDefault();
                goTo(current + (event.key === 'ArrowRight' ? 1 : -1), true);
            };
            const onResize = () => {
                draggable.applyBounds({ minX: -(slides.length - 1) * viewport.clientWidth, maxX: 0 });
                gsap.set(track, { x: -current * viewport.clientWidth });
                draggable.update();
            };
            const onFocusOut = (event) => {
                if (!resortGallery.contains(event.relatedTarget)) scheduleAutoplay();
            };

            previous.addEventListener('click', showPrevious);
            next.addEventListener('click', showNext);
            toggleAutoplay.addEventListener('click', toggleGalleryAutoplay);
            viewport.addEventListener('keydown', onKeyDown);
            resortGallery.addEventListener('focusin', pauseAutoplay);
            resortGallery.addEventListener('focusout', onFocusOut);
            window.addEventListener('resize', onResize, { passive: true });
            dotButtons.forEach((dot, index) => dot.addEventListener('click', () => goTo(index, true)));
            scheduleAutoplay();

            cleanup.push(() => {
                pauseAutoplay();
                draggable.kill();
                previous.removeEventListener('click', showPrevious);
                next.removeEventListener('click', showNext);
                toggleAutoplay.removeEventListener('click', toggleGalleryAutoplay);
                viewport.removeEventListener('keydown', onKeyDown);
                resortGallery.removeEventListener('focusin', pauseAutoplay);
                resortGallery.removeEventListener('focusout', onFocusOut);
                window.removeEventListener('resize', onResize);
            });
        }

        const hero = document.querySelector('.hero');

        if (hero) {
            const title = hero.querySelector('#hero-title');
            const eyebrow = hero.querySelector('.hero__content .eyebrow');
            const description = hero.querySelector('.hero__description');
            const actions = hero.querySelectorAll('.hero__actions > *');
            const availability = hero.querySelector('.availability-card');

            if (title) {
                SplitText.create(title, {
                    aria: 'none',
                    autoSplit: true,
                    type: 'lines',
                    onSplit: (split) => {
                        const intro = gsap.timeline({
                            delay: 0.12,
                            defaults: { ease: 'power3.out' },
                        });

                        if (eyebrow) {
                            intro.from(eyebrow, { autoAlpha: 0, y: 14, duration: 0.55 });
                        }

                        intro
                            .from(split.lines, {
                                autoAlpha: 0,
                                yPercent: 28,
                                duration: 0.86,
                                stagger: 0.13,
                                ease: 'power4.out',
                            }, eyebrow ? '-=0.28' : 0)
                            .from(description, { autoAlpha: 0, y: 16, duration: 0.58 }, '-=0.46')
                            .from(actions, {
                                autoAlpha: 0,
                                y: 14,
                                duration: 0.52,
                                stagger: 0.1,
                            }, '-=0.28');

                        if (availability) {
                            intro.from(availability, {
                                autoAlpha: 0,
                                y: 20,
                                scale: 0.99,
                                duration: 0.66,
                                clearProps: 'transform',
                            }, '-=0.14');
                        }

                        return intro;
                    },
                });

                document.fonts?.ready.then(() => ScrollTrigger.refresh());
            }

            const glow = hero.querySelector('.hero__glow');
            if (glow) {
                const moveGlowX = gsap.quickTo(glow, 'x', { duration: 0.72, ease: 'power3.out' });
                const moveGlowY = gsap.quickTo(glow, 'y', { duration: 0.72, ease: 'power3.out' });
                const onPointerMove = (event) => {
                    if (event.pointerType === 'touch') return;

                    const bounds = hero.getBoundingClientRect();
                    moveGlowX((event.clientX - bounds.left - bounds.width / 2) * 0.035);
                    moveGlowY((event.clientY - bounds.top - bounds.height / 2) * 0.025);
                };
                const onPointerLeave = () => {
                    moveGlowX(0);
                    moveGlowY(0);
                };

                hero.addEventListener('pointermove', onPointerMove, { passive: true });
                hero.addEventListener('pointerleave', onPointerLeave, { passive: true });
                cleanup.push(() => {
                    hero.removeEventListener('pointermove', onPointerMove);
                    hero.removeEventListener('pointerleave', onPointerLeave);
                });

                gsap.to(glow, {
                    yPercent: 3,
                    ease: 'none',
                    scrollTrigger: {
                        trigger: hero,
                        start: 'top top',
                        end: 'bottom top',
                        scrub: 0.8,
                    },
                });
            }
        }

        // Reveal each content group once as it enters view, with short transforms.
        document.querySelectorAll('.section').forEach((section) => {
            const heading = section.querySelector('.section-heading');
            const headingParts = heading?.querySelectorAll('.eyebrow, h2, p, .text-link');

            if (headingParts?.length) {
                gsap.from(headingParts, {
                    autoAlpha: 0,
                    y: 20,
                    duration: 0.68,
                    stagger: 0.1,
                    ease: 'power3.out',
                    scrollTrigger: {
                        trigger: heading,
                        start: 'top 84%',
                        once: true,
                    },
                });
            }

            const roomCards = section.querySelectorAll('.room-card');
            if (roomCards.length) {
                gsap.from(roomCards, {
                    autoAlpha: 0,
                    y: 30,
                    duration: 0.78,
                    stagger: 0.14,
                    ease: 'power3.out',
                    scrollTrigger: {
                        trigger: roomCards[0],
                        start: 'top 88%',
                        once: true,
                    },
                });
            }

            const reviewCards = section.querySelectorAll('.review-card');
            if (reviewCards.length) {
                gsap.from(reviewCards, {
                    autoAlpha: 0,
                    y: 24,
                    duration: 0.72,
                    stagger: 0.14,
                    ease: 'power3.out',
                    scrollTrigger: {
                        trigger: reviewCards[0],
                        start: 'top 88%',
                        once: true,
                    },
                });
            }

            const quietPanel = section.querySelector('.quiet-panel');
            if (quietPanel) {
                gsap.from(quietPanel.querySelectorAll('.quiet-panel > div, .quiet-panel__list li'), {
                    autoAlpha: 0,
                    y: 22,
                    duration: 0.7,
                    stagger: 0.13,
                    ease: 'power3.out',
                    scrollTrigger: {
                        trigger: quietPanel,
                        start: 'top 82%',
                        once: true,
                    },
                });
            }
        });

        const roomHero = document.querySelector('[data-room-hero]');
        if (roomHero && !reduceMotion) {
            const heroTitle = roomHero.querySelector('h1');
            const heroParts = roomHero.querySelectorAll('.room-detail-intro__eyebrow, .room-detail-intro__meta span, .room-detail-intro__price');
            const titleSplit = heroTitle ? new SplitText(heroTitle, { type: 'lines', mask: 'lines' }) : null;

            if (titleSplit?.lines?.length) {
                gsap.from(titleSplit.lines, { yPercent: 110, duration: 0.92, stagger: 0.08, ease: 'power4.out', delay: 0.08 });
                cleanup.push(() => titleSplit.revert());
            }
            gsap.from(heroParts, { autoAlpha: 0, y: 16, duration: 0.65, stagger: 0.07, ease: 'power3.out', delay: 0.22 });
        }

        const roomStory = document.querySelector('[data-room-story]');
        if (roomStory && !reduceMotion) {
            gsap.from(roomStory.querySelectorAll('.room-story__heading > *, .room-metric'), {
                autoAlpha: 0,
                y: 22,
                duration: 0.64,
                stagger: 0.08,
                ease: 'power3.out',
                scrollTrigger: { trigger: roomStory, start: 'top 84%', once: true },
            });
        }

        const roomGallery = document.querySelector('.room-detail-gallery');
        if (roomGallery && !reduceMotion) {
            const viewport = roomGallery.querySelector('.contact-gallery__viewport');
            gsap.fromTo(viewport, {
                clipPath: 'inset(0 0 100% 0 round 28px)',
                scale: 1.035,
            }, {
                clipPath: 'inset(0 0 0% 0 round 28px)',
                scale: 1,
                duration: 1.05,
                ease: 'power4.out',
                clearProps: 'clipPath,transform',
                scrollTrigger: { trigger: roomGallery, start: 'top 84%', once: true },
            });
        }

        document.querySelectorAll('.room-detail-nav a[href^="#"]').forEach((link) => {
            const section = document.querySelector(link.getAttribute('href'));
            if (!section) return;
            const activeTrigger = ScrollTrigger.create({
                trigger: section,
                start: 'top 42%',
                end: 'bottom 42%',
                toggleClass: { targets: link, className: 'is-active' },
            });
            cleanup.push(() => activeTrigger.kill());
        });

        const closingCta = document.querySelector('.closing-cta');
        if (closingCta) {
            gsap.from(closingCta.querySelectorAll('.eyebrow, h2, p, .button'), {
                autoAlpha: 0,
                y: 22,
                duration: 0.72,
                stagger: 0.12,
                ease: 'power3.out',
                scrollTrigger: {
                    trigger: closingCta,
                    start: 'top 82%',
                    once: true,
                },
            });
        }

        if (navigation && toggle) {
            const onToggle = () => {
                const isOpen = toggle.getAttribute('aria-expanded') === 'true';
                const state = Flip.getState([navigation, ...navigation.children]);

                toggle.setAttribute('aria-expanded', String(!isOpen));
                navigation.classList.toggle('is-open', !isOpen);

                Flip.from(state, {
                    duration: isOpen ? 0.28 : 0.42,
                    ease: isOpen ? 'power2.in' : 'power3.out',
                    fade: true,
                    stagger: isOpen ? 0 : 0.035,
                });
            };

            toggle.addEventListener('click', onToggle);
            cleanup.push(() => toggle.removeEventListener('click', onToggle));
        }

    window.addEventListener('pagehide', () => {
        cleanup.forEach((dispose) => dispose());
    }, { once: true });

    document.querySelectorAll('.rm-btn:not(.taken), .payment-choice').forEach((control) => {
        control.addEventListener('click', () => {
            gsap.fromTo(control, { scale: 0.96 }, {
                scale: 1,
                duration: 0.42,
                ease: 'back.out(2.2)',
                clearProps: 'scale',
            });
        });
    });

    const reviewTrack = document.querySelector('[data-review-track]');
    if (reviewTrack) {
        const reviewCarousel = reviewTrack.parentElement;
        const reviewsMotion = gsap.matchMedia();

        reviewsMotion.add('(max-width: 680px)', () => {
            const draggable = Draggable.create(reviewTrack, {
                type: 'x',
                bounds: reviewCarousel,
                edgeResistance: 0.84,
                zIndexBoost: false,
                onPress: () => {
                    gsap.killTweensOf(reviewTrack);
                    reviewTrack.classList.add('is-dragging');
                },
                onRelease: function () {
                    reviewTrack.classList.remove('is-dragging');

                    const card = reviewTrack.querySelector('.review-card');
                    if (!card) return;

                    const gap = Number.parseFloat(getComputedStyle(reviewTrack).columnGap) || 0;
                    const step = card.getBoundingClientRect().width + gap;
                    const minimumX = Math.min(0, reviewCarousel.clientWidth - reviewTrack.scrollWidth);
                    const targetX = gsap.utils.clamp(minimumX, 0, Math.round(-this.x / step) * -step);

                    gsap.to(reviewTrack, {
                        x: targetX,
                        duration: 0.42,
                        ease: 'power3.out',
                        overwrite: true,
                        onUpdate: () => this.update(),
                    });
                },
            })[0];

            const moveByCard = (direction) => {
                const card = reviewTrack.querySelector('.review-card');
                if (!card) return;

                const gap = Number.parseFloat(getComputedStyle(reviewTrack).columnGap) || 0;
                const step = card.getBoundingClientRect().width + gap;
                const minimumX = Math.min(0, reviewCarousel.clientWidth - reviewTrack.scrollWidth);
                const nextX = gsap.utils.clamp(minimumX, 0, draggable.x - direction * step);

                gsap.to(reviewTrack, {
                    x: nextX,
                    duration: 0.42,
                    ease: 'power3.out',
                    overwrite: true,
                    onUpdate: () => draggable.update(),
                });
            };

            const onKeyDown = (event) => {
                if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
                    event.preventDefault();
                    moveByCard(event.key === 'ArrowRight' ? 1 : -1);
                }
            };

            reviewTrack.addEventListener('keydown', onKeyDown);

            return () => {
                reviewTrack.classList.remove('is-dragging');
                reviewTrack.removeEventListener('keydown', onKeyDown);
                draggable.kill();
                gsap.set(reviewTrack, { clearProps: 'transform' });
            };
        });
    }

    const chatbot = document.querySelector('[data-chatbot]');
    if (chatbot) {
        const panel = chatbot.querySelector('[data-chatbot-panel]');
        const launcher = chatbot.querySelector('[data-chatbot-toggle]');
        const closeButton = chatbot.querySelector('[data-chatbot-close]');
        const form = chatbot.querySelector('[data-chatbot-form]');
        const input = chatbot.querySelector('[data-chatbot-input]');
        const sendButton = chatbot.querySelector('[data-chatbot-send]');
        const messages = chatbot.querySelector('[data-chatbot-messages]');
        const suggestions = chatbot.querySelector('[data-chatbot-suggestions]');
        let history = [];
        let pendingRequest = null;

        try {
            history = JSON.parse(sessionStorage.getItem('royal-chat-history') || '[]');
            if (!Array.isArray(history)) history = [];
        } catch { history = []; }

        const appendMessage = (content, role, animate = true) => {
            const message = document.createElement('div');
            message.className = `royal-chat__message royal-chat__message--${role}`;
            message.textContent = content;
            messages.appendChild(message);
            messages.scrollTop = messages.scrollHeight;
            if (animate && !reduceMotion) gsap.from(message, { autoAlpha: 0, y: 8, duration: .3, ease: 'power2.out' });
            return message;
        };

        if (history.length) {
            messages.replaceChildren();
            history.slice(-10).forEach(item => appendMessage(item.content, item.role, false));
        }

        const persistHistory = () => {
            history = history.slice(-10);
            sessionStorage.setItem('royal-chat-history', JSON.stringify(history));
        };
        const setOpen = open => {
            launcher.setAttribute('aria-expanded', String(open));
            if (open) {
                panel.hidden = false;
                if (!reduceMotion) gsap.fromTo(panel, { autoAlpha: 0, y: 14, scale: .97 }, { autoAlpha: 1, y: 0, scale: 1, duration: .34, ease: 'power3.out', clearProps: 'opacity,transform,visibility' });
                input.focus();
                return;
            }
            if (reduceMotion) { panel.hidden = true; return; }
            gsap.to(panel, { autoAlpha: 0, y: 10, scale: .98, duration: .2, ease: 'power2.in', onComplete: () => { panel.hidden = true; gsap.set(panel, { clearProps: 'opacity,transform,visibility' }); } });
        };

        launcher.addEventListener('click', () => setOpen(panel.hidden));
        closeButton.addEventListener('click', () => setOpen(false));
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && !panel.hidden) setOpen(false); });
        input.addEventListener('input', () => { input.style.height = 'auto'; input.style.height = `${Math.min(input.scrollHeight, 96)}px`; });
        input.addEventListener('keydown', event => { if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); form.requestSubmit(); } });
        chatbot.querySelectorAll('[data-chatbot-prompt]').forEach(button => button.addEventListener('click', () => { input.value = button.dataset.chatbotPrompt; form.requestSubmit(); }));

        form.addEventListener('submit', async event => {
            event.preventDefault();
            const content = input.value.trim();
            if (!content || pendingRequest) return;

            const previousHistory = history.slice(-8);
            appendMessage(content, 'user');
            history.push({ role: 'user', content });
            persistHistory();
            input.value = '';
            input.style.height = 'auto';
            suggestions.hidden = true;
            sendButton.disabled = true;
            const typing = document.createElement('div');
            typing.className = 'royal-chat__message royal-chat__message--assistant royal-chat__message--typing';
            typing.setAttribute('aria-label', 'Trợ lý đang trả lời');
            typing.innerHTML = '<i></i><i></i><i></i>';
            messages.appendChild(typing);
            messages.scrollTop = messages.scrollHeight;
            pendingRequest = new AbortController();

            try {
                const response = await fetch(chatbot.dataset.endpoint, {
                    method: 'POST',
                    signal: pendingRequest.signal,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ message: content, history: previousHistory }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Request failed');
                const reply = String(data.reply || 'Tôi chưa thể trả lời lúc này. Bạn vui lòng thử lại.');
                typing.remove();
                appendMessage(reply, 'assistant');
                history.push({ role: 'assistant', content: reply });
                persistHistory();
            } catch (error) {
                typing.remove();
                if (error.name !== 'AbortError') appendMessage('Kết nối đang gián đoạn. Bạn vui lòng thử lại sau ít phút.', 'assistant');
            } finally {
                pendingRequest = null;
                sendButton.disabled = false;
                input.focus();
            }
        });
        cleanup.push(() => pendingRequest?.abort());
    }

});

// Native account menu closes consistently for pointer and keyboard navigation.
document.addEventListener('click', event => document.querySelectorAll('.account-menu[open]').forEach(menu => { if (!menu.contains(event.target)) menu.removeAttribute('open'); }));
document.addEventListener('keydown', event => { if (event.key === 'Escape') document.querySelectorAll('.account-menu[open]').forEach(menu => { menu.removeAttribute('open'); menu.querySelector('summary').focus(); }); });
