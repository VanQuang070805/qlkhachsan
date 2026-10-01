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
window.gsap = gsap;

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

    // Auto-dismiss all flash notices, alerts, and toasts after maximum 5 seconds
    const setupAutoDismiss = (element, duration = 5000) => {
        if (!element || element.dataset.autoDismissAttached) return;
        element.dataset.autoDismissAttached = 'true';
        setTimeout(() => {
            if (!element.isConnected) return;
            element.style.transition = 'opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1), transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), max-height 0.4s ease, margin 0.4s ease, padding 0.4s ease';
            element.style.opacity = '0';
            element.style.transform = element.classList.contains('flash-wrap') ? 'translate(-50%, -16px)' : 'translateY(-8px)';
            setTimeout(() => {
                if (element && element.parentNode) element.remove();
            }, 420);
        }, duration);
    };

    document.querySelectorAll('.flash-wrap, .alert:not(.alert-permanent):not(.no-auto-dismiss)').forEach(el => setupAutoDismiss(el, 5000));

    if (window.MutationObserver && document.body) {
        const alertObserver = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        if (node.matches && node.matches('.flash-wrap, .alert:not(.alert-permanent):not(.no-auto-dismiss)')) {
                            setupAutoDismiss(node, 5000);
                        }
                        node.querySelectorAll?.('.flash-wrap, .alert:not(.alert-permanent):not(.no-auto-dismiss)').forEach(el => setupAutoDismiss(el, 5000));
                    }
                });
            });
        });
        alertObserver.observe(document.body, { childList: true, subtree: true });
    }

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

    // Auth Components Animations (Homepage-grade GSAP motion)
    const sanaAuth = document.querySelector('.sana-auth-main');
    const legacyAuthCard = document.querySelector('.auth-card:not(.sana-auth-card)');

    if (!reduceMotion && sanaAuth) {
        const formCol = sanaAuth.querySelector('.sana-form-col');
        const laptop = sanaAuth.querySelector('.sana-laptop');

        if (formCol) {
            const formElements = formCol.querySelectorAll(
                '.sana-heading, .sana-social-btn, .sana-divider, .sana-form-group, .sana-submit-btn, .sana-terms, .sana-switch-link'
            );
            if (formElements.length) {
                gsap.from(formElements, {
                    autoAlpha: 0,
                    y: 18,
                    duration: 0.52,
                    stagger: 0.05,
                    delay: 0.1,
                    ease: 'power2.out',
                    clearProps: 'opacity,visibility,transform',
                });
            }
        }

        if (laptop) {
            // 3D Perspective Laptop Entrance
            gsap.from(laptop, {
                autoAlpha: 0,
                y: 36,
                scale: 0.94,
                rotateY: -8,
                rotateX: 4,
                duration: 0.85,
                delay: 0.16,
                ease: 'power3.out',
                clearProps: 'opacity,visibility',
            });

            // Staggered suite preview cards inside device
            const laptopCards = laptop.querySelectorAll('.sana-laptop__card');
            if (laptopCards.length) {
                gsap.from(laptopCards, {
                    autoAlpha: 0,
                    y: 20,
                    scale: 0.95,
                    duration: 0.55,
                    stagger: 0.09,
                    delay: 0.42,
                    ease: 'power2.out',
                    clearProps: 'opacity,visibility,transform',
                });
            }

            // Prompt bar entrance & gentle pulse
            const promptBar = laptop.querySelector('.sana-laptop__prompt-bar');
            if (promptBar) {
                gsap.from(promptBar, {
                    autoAlpha: 0,
                    y: 14,
                    duration: 0.48,
                    delay: 0.65,
                    ease: 'power2.out',
                    clearProps: 'opacity,visibility,transform',
                });

                const starIcon = promptBar.querySelector('i');
                if (starIcon) {
                    const pulse = gsap.to(starIcon, {
                        scale: 1.25,
                        duration: 1.5,
                        repeat: -1,
                        yoyo: true,
                        ease: 'sine.inOut',
                    });
                    cleanup.push(() => pulse.kill());
                }
            }

            // Interactive 3D mouse parallax tilt effect
            const showcaseCol = sanaAuth.querySelector('.sana-showcase-col');
            if (showcaseCol && window.matchMedia('(min-width: 1081px)').matches) {
                const onMouseMove = (e) => {
                    const rect = showcaseCol.getBoundingClientRect();
                    const xPercent = (e.clientX - rect.left) / rect.width - 0.5;
                    const yPercent = (e.clientY - rect.top) / rect.height - 0.5;
                    gsap.to(laptop, {
                        rotateY: -3 + xPercent * 6,
                        rotateX: 1.5 - yPercent * 5,
                        duration: 0.38,
                        ease: 'power1.out',
                        overwrite: 'auto',
                    });
                };
                const onMouseLeave = () => {
                    gsap.to(laptop, {
                        rotateY: -3,
                        rotateX: 1.5,
                        duration: 0.65,
                        ease: 'power2.out',
                        overwrite: 'auto',
                    });
                };
                showcaseCol.addEventListener('mousemove', onMouseMove);
                showcaseCol.addEventListener('mouseleave', onMouseLeave);
                cleanup.push(() => {
                    showcaseCol.removeEventListener('mousemove', onMouseMove);
                    showcaseCol.removeEventListener('mouseleave', onMouseLeave);
                });
            }
        }
    } else if (!reduceMotion && legacyAuthCard) {
        gsap.from(legacyAuthCard.querySelectorAll('.auth-intro > *, .form-group, .btn-auth, .divider'), {
            autoAlpha: 0,
            y: 10,
            duration: 0.38,
            stagger: 0.035,
            delay: 0.08,
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
            let starBounds = null;
            const cacheStarBounds = () => { starBounds = surface.getBoundingClientRect(); };
            const onStarPointerMove = (event) => {
                if (event.pointerType === 'touch') return;
                if (!starBounds || !starBounds.width) cacheStarBounds();
                const normalizedX = (event.clientX - starBounds.left) / starBounds.width - 0.5;
                const normalizedY = (event.clientY - starBounds.top) / starBounds.height - 0.5;
                moveFieldX(normalizedX * 26);
                moveFieldY(normalizedY * 18);
            };
            const resetStarField = () => {
                starBounds = null;
                moveFieldX(0);
                moveFieldY(0);
            };
            surface.addEventListener('pointerenter', cacheStarBounds, { passive: true });
            surface.addEventListener('pointermove', onStarPointerMove, { passive: true });
            surface.addEventListener('pointerleave', resetStarField, { passive: true });
            cleanup.push(() => {
                surface.removeEventListener('pointerenter', cacheStarBounds);
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

        const rawReveals = [...new Set(document.querySelectorAll(
            '[data-reveal], .legacy-main .search-card, .legacy-main .filter-card, .legacy-main .room-card, .legacy-main .review-card, .commerce-heading, .account-profile__layout, .booking-list-card, .payment-layout, .room-detail, .rooms-page__heading, .booking-checkout-grid > *, .booking-summary-card, .booking-form-card, .booking-header-bar, .payment-summary, .payment-method-panel, .account-profile__identity, .account-profile__form, .room-detail-heading, .room-demo-gallery, .room-detail-content > *, .room-story, .room-amenities, .room-reviews'
        ))].filter((el) => !el.closest('.section'));

        const customerReveals = rawReveals.filter(el => {
            let p = el.parentElement;
            while (p) {
                if (rawReveals.includes(p)) return false;
                p = p.parentElement;
            }
            return true;
        });

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
            if (!reduceMotion) {
                gsap.fromTo(items,
                    { autoAlpha: 0, y: 12 },
                    { autoAlpha: 1, y: 0, duration: .5, stagger: .075, ease: 'power2.out', clearProps: 'opacity,visibility,transform' },
                );
                const fills = steps.querySelectorAll('.royal-step__connector.is-complete .royal-step__connector-fill');
                if (fills.length) {
                    gsap.fromTo(fills,
                        { scaleX: 0, transformOrigin: 'left center' },
                        { scaleX: 1, duration: .65, delay: .18, ease: 'power3.out', clearProps: 'transform' },
                    );
                }
            }

            // Interactive handlers for Step nodes (keyboard & click)
            steps.querySelectorAll('.royal-step').forEach((stepEl) => {
                const target = stepEl.querySelector('.royal-step__content');
                if (!target) return;

                // Support Enter / Space on interactive elements
                target.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        target.click();
                    }
                });
            });

            // Contextual interactivity inside the Room Detail booking card (.bk-card)
            const bkCard = steps.closest('.bk-card');
            if (bkCard) {
                steps.querySelectorAll('.royal-step').forEach((stepEl) => {
                    stepEl.addEventListener('click', (event) => {
                        // If it's already an active link, allow normal navigation
                        if (event.target.closest('a')) return;

                        const stepIdx = Number(stepEl.dataset.step);
                        const checkInInput = document.getElementById('bkCheckIn');
                        const checkOutInput = document.getElementById('bkCheckOut');
                        const bookingTimeNote = document.getElementById('bookingTimeNote');

                        if (stepIdx === 1) {
                            checkInInput?.focus();
                            checkInInput?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        } else if (stepIdx >= 2) {
                            const hasDates = checkInInput?.value && checkOutInput?.value;
                            if (!hasDates) {
                                checkInInput?.focus();
                                if (!reduceMotion) {
                                    gsap.fromTo(checkInInput, { x: -6 }, { x: 0, duration: 0.45, ease: 'elastic.out(1, 0.3)', clearProps: 'x' });
                                }
                                if (bookingTimeNote) {
                                    const originalText = bookingTimeNote.innerHTML;
                                    bookingTimeNote.innerHTML = '<i class="bi bi-exclamation-circle-fill text-danger"></i> <strong class="text-danger">Vui lòng chọn ngày nhận & trả phòng trước khi tiếp tục</strong>';
                                    setTimeout(() => { bookingTimeNote.innerHTML = originalText; }, 3200);
                                }
                            } else {
                                const submitBtn = bkCard.querySelector('button[type="submit"]') || document.getElementById('btnSubmitBooking');
                                submitBtn?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                if (submitBtn && !reduceMotion) {
                                    gsap.fromTo(submitBtn, { scale: 0.96 }, { scale: 1, duration: 0.35, ease: 'back.out(2)' });
                                }
                            }
                        }
                    });
                });
            }
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
            const eyebrow = hero.querySelector('.hero__content .eyebrow, .supaste-hero__badge');
            const description = hero.querySelector('.hero__description, .supaste-hero__subtitle');
            const actions = hero.querySelectorAll('.hero__actions > *, .supaste-hero__action');
            const availability = hero.querySelector('.availability-card');
            const supasteWindow = hero.querySelector('.supaste-window');

            if (title) {
                if (title.classList.contains('supaste-hero__headline')) {
                    const sans = title.querySelector('.supaste-hero__headline-sans');
                    const serif = title.querySelector('.supaste-hero__headline-serif');

                    if (!reduceMotion && sans && serif) {
                        const splitSans = new SplitText(sans, { type: 'words,chars', charsClass: 'supaste-char' });
                        const splitSerif = new SplitText(serif, { type: 'words,chars', charsClass: 'supaste-char' });

                        const intro = gsap.timeline({
                            delay: 0.1,
                            defaults: { ease: 'power3.out' },
                        });

                        if (eyebrow) {
                            intro.from(eyebrow, { autoAlpha: 0, y: 12, filter: 'blur(8px)', duration: 0.6, clearProps: 'filter,transform' });
                        }

                        intro
                            .from(splitSans.chars, {
                                autoAlpha: 0,
                                y: 12,
                                filter: 'blur(10px)',
                                duration: 0.85,
                                stagger: 0.024,
                                clearProps: 'filter,transform',
                            }, eyebrow ? '-=0.25' : 0)
                            .from(splitSerif.chars, {
                                autoAlpha: 0,
                                y: 12,
                                filter: 'blur(10px)',
                                duration: 0.85,
                                stagger: 0.024,
                                clearProps: 'filter,transform',
                            }, '-=0.55')
                            .from(description, {
                                autoAlpha: 0,
                                y: 14,
                                filter: 'blur(6px)',
                                duration: 0.65,
                                clearProps: 'filter,transform',
                            }, '-=0.45')
                            .from(actions, {
                                autoAlpha: 0,
                                y: 14,
                                duration: 0.6,
                                clearProps: 'transform',
                            }, '-=0.35');

                        if (supasteWindow) {
                            intro.from(supasteWindow, {
                                autoAlpha: 0,
                                y: 30,
                                duration: 0.85,
                                ease: 'power3.out',
                                clearProps: 'transform',
                            }, '-=0.4');
                        }
                    }
                } else {
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
                }

                document.fonts?.ready.then(() => ScrollTrigger.refresh());
            }

            const glow = hero.querySelector('.hero__glow');
            if (glow) {
                const moveGlowX = gsap.quickTo(glow, 'x', { duration: 0.72, ease: 'power3.out' });
                const moveGlowY = gsap.quickTo(glow, 'y', { duration: 0.72, ease: 'power3.out' });
                let glowBounds = null;
                const cacheGlowBounds = () => { glowBounds = hero.getBoundingClientRect(); };
                const onPointerMove = (event) => {
                    if (event.pointerType === 'touch') return;
                    if (!glowBounds || !glowBounds.width) cacheGlowBounds();

                    moveGlowX((event.clientX - glowBounds.left - glowBounds.width / 2) * 0.035);
                    moveGlowY((event.clientY - glowBounds.top - glowBounds.height / 2) * 0.025);
                };
                const onPointerLeave = () => {
                    glowBounds = null;
                    moveGlowX(0);
                    moveGlowY(0);
                };

                hero.addEventListener('pointerenter', cacheGlowBounds, { passive: true });
                hero.addEventListener('pointermove', onPointerMove, { passive: true });
                hero.addEventListener('pointerleave', onPointerLeave, { passive: true });
                cleanup.push(() => {
                    hero.removeEventListener('pointerenter', cacheGlowBounds);
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

            const featureCards = document.querySelectorAll('.supaste-feature-card');
            if (featureCards.length && !reduceMotion) {
                gsap.from(featureCards, {
                    autoAlpha: 0,
                    y: 28,
                    duration: 0.75,
                    stagger: 0.1,
                    ease: 'power3.out',
                    clearProps: 'opacity,visibility,transform',
                    scrollTrigger: {
                        trigger: featureCards[0],
                        start: 'top 85%',
                        once: true,
                    },
                });
            }

            const footerContainer = document.querySelector('.supaste-footer__container');
            if (footerContainer && !reduceMotion) {
                gsap.from(footerContainer.children, {
                    autoAlpha: 0,
                    y: 24,
                    duration: 0.8,
                    stagger: 0.1,
                    ease: 'power3.out',
                    clearProps: 'opacity,visibility,transform',
                    scrollTrigger: {
                        trigger: footerContainer,
                        start: 'top 90%',
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
            const targets = [navigation, ...navigation.children];
            const onToggle = () => {
                const isOpen = toggle.getAttribute('aria-expanded') === 'true';
                const state = reduceMotion ? null : Flip.getState(targets);

                toggle.setAttribute('aria-expanded', String(!isOpen));
                navigation.classList.toggle('is-open', !isOpen);

                if (state) {
                    Flip.from(state, {
                        duration: isOpen ? 0.28 : 0.42,
                        ease: isOpen ? 'power2.in' : 'power3.out',
                        fade: true,
                        stagger: isOpen ? 0 : 0.035,
                        onComplete: () => gsap.set(targets, { clearProps: 'transform,opacity' }),
                    });
                }
            };

            const desktopMedia = window.matchMedia('(min-width: 901px)');
            const onDesktopChange = (event) => {
                if (!event.matches) return;
                toggle.setAttribute('aria-expanded', 'false');
                navigation.classList.remove('is-open');
                gsap.set(targets, { clearProps: 'transform,opacity' });
            };

            toggle.addEventListener('click', onToggle);
            desktopMedia.addEventListener('change', onDesktopChange);
            cleanup.push(() => {
                toggle.removeEventListener('click', onToggle);
                desktopMedia.removeEventListener('change', onDesktopChange);
            });
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
});

// Native account menu closes consistently for pointer and keyboard navigation.
document.addEventListener('click', event => document.querySelectorAll('.account-menu[open]').forEach(menu => { if (!menu.contains(event.target)) menu.removeAttribute('open'); }));
document.addEventListener('keydown', event => { if (event.key === 'Escape') document.querySelectorAll('.account-menu[open]').forEach(menu => { menu.removeAttribute('open'); menu.querySelector('summary').focus(); }); });
