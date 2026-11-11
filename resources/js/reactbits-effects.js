import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';
import { Renderer, Program, Mesh, Triangle, Plane, Texture, RenderTarget } from 'ogl';

/**
 * ============================================================================
 * REACT BITS LUXURY COMPONENT SUITE & IPHONE 16 PRO CONTROLLER
 * Rosaliza Hotel HaNoi · Quiet Luxury Editorial System
 * ============================================================================
 */

export function initReactBitsEffects({ reduceMotion = false } = {}) {
    const cleanup = [];

    // -------------------------------------------------------------------------
    // 1. AUTO-DECORATE EXISTING SURFACES
    // -------------------------------------------------------------------------
    document.querySelectorAll(
        '.melia-service-card, .melia-room-card, .melia-dining-card, .melia-package-card, .melia-value-card, .melia-channel-card, .melia-form-card, .rb-lookbook-item, .data-panel, .operations-overview .metric'
    ).forEach((card) => {
        if (!card.hasAttribute('data-rb-spotlight')) {
            card.setAttribute('data-rb-spotlight', '');
        }
    });

    document.querySelectorAll(
        '.melia-service-card__media, .melia-room-card__media, .melia-dining-card__media, .melia-activity-card__thumb, .rb-lookbook-item'
    ).forEach((media) => {
        if (!media.hasAttribute('data-rb-glare')) {
            media.setAttribute('data-rb-glare', '');
        }
    });

    document.querySelectorAll(
        '.melia-overview__visual, .melia-story-visual, .iphone16-pro'
    ).forEach((el) => {
        if (!el.hasAttribute('data-rb-tilt')) {
            el.setAttribute('data-rb-tilt', '');
        }
    });

// Magnet auto-assignment removed

    // -------------------------------------------------------------------------
    // 2. SPOTLIGHT CARD (Radial cursor follow)
    // -------------------------------------------------------------------------
    document.querySelectorAll('[data-rb-spotlight]').forEach((card) => {
        let rafId = null;
        const onPointerMove = (e) => {
            if (e.pointerType === 'touch') return;
            if (rafId) return;
            rafId = requestAnimationFrame(() => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                card.style.setProperty('--rb-x', `${x}px`);
                card.style.setProperty('--rb-y', `${y}px`);
                rafId = null;
            });
        };
        card.addEventListener('pointermove', onPointerMove, { passive: true });
        cleanup.push(() => {
            if (rafId) cancelAnimationFrame(rafId);
            card.removeEventListener('pointermove', onPointerMove);
        });
    });

    // -------------------------------------------------------------------------
    // 3. SPLIT TEXT (GSAP Character / Word Reveal)
    // -------------------------------------------------------------------------
    if (!reduceMotion && typeof SplitText !== 'undefined') {
        const splitTargets = document.querySelectorAll(
            '[data-rb-split], .melia-title-display, .melia-page-hero__title'
        );

        splitTargets.forEach((el) => {
            if (el.dataset.rbSplitBound === '1') return;
            el.dataset.rbSplitBound = '1';

            const mode = el.dataset.rbSplit || 'words,chars';
            const split = new SplitText(el, {
                type: mode,
                charsClass: 'rb-char',
                wordsClass: 'rb-word',
            });

            const units = split.chars?.length ? split.chars : split.words;
            if (!units?.length) return;

            const tween = gsap.from(units, {
                autoAlpha: 0,
                y: 22,
                rotateX: -16,
                duration: 0.65,
                stagger: split.chars?.length ? 0.018 : 0.05,
                ease: 'power3.out',
                clearProps: 'transform,opacity,visibility',
                scrollTrigger: {
                    trigger: el,
                    start: 'top 92%',
                    once: true,
                },
            });

            cleanup.push(() => {
                tween.kill();
                split.revert();
            });
        });
    }

    // -------------------------------------------------------------------------
    // 4. BLUR TEXT (GSAP Kinetic Blur Reveal)
    // -------------------------------------------------------------------------
    if (!reduceMotion && typeof SplitText !== 'undefined') {
        const blurTargets = document.querySelectorAll(
            '[data-rb-blur], .melia-overview__lead, .melia-section-header h2'
        );

        blurTargets.forEach((el) => {
            if (el.dataset.rbBlurBound === '1' || el.dataset.rbSplitBound === '1') return;
            el.dataset.rbBlurBound = '1';

            const split = new SplitText(el, {
                type: 'words',
                wordsClass: 'rb-word',
            });

            if (!split.words?.length) return;

            const tween = gsap.fromTo(
                split.words,
                {
                    autoAlpha: 0,
                    y: 12,
                    filter: 'blur(8px)',
                },
                {
                    autoAlpha: 1,
                    y: 0,
                    filter: 'blur(0px)',
                    duration: 0.6,
                    stagger: 0.034,
                    ease: 'power3.out',
                    clearProps: 'filter,transform,opacity,visibility',
                    scrollTrigger: {
                        trigger: el,
                        start: 'top 88%',
                        once: true,
                    },
                }
            );

            cleanup.push(() => {
                tween.kill();
                split.revert();
            });
        });
    }

    // -------------------------------------------------------------------------
    // 5. TILTED CARD (3D Perspective Tilt)
    // -------------------------------------------------------------------------
    if (!reduceMotion && window.matchMedia('(min-width: 900px)').matches) {
        document.querySelectorAll('[data-rb-tilt]').forEach((el) => {
            const maxTilt = Number(el.dataset.rbTiltMax || 5);
            gsap.set(el, { transformPerspective: 1100 });

            const rotX = gsap.quickTo(el, 'rotateX', { duration: 0.4, ease: 'power2.out' });
            const rotY = gsap.quickTo(el, 'rotateY', { duration: 0.4, ease: 'power2.out' });

            const onMove = (e) => {
                if (e.pointerType === 'touch') return;
                const rect = el.getBoundingClientRect();
                if (!rect.width || !rect.height) return;
                const nx = (e.clientX - rect.left) / rect.width - 0.5;
                const ny = (e.clientY - rect.top) / rect.height - 0.5;
                rotY(nx * maxTilt * 2);
                rotX(-ny * maxTilt * 2);
            };

            const onLeave = () => {
                rotX(0);
                rotY(0);
            };

            el.addEventListener('pointermove', onMove, { passive: true });
            el.addEventListener('pointerleave', onLeave);
            cleanup.push(() => {
                el.removeEventListener('pointermove', onMove);
                el.removeEventListener('pointerleave', onLeave);
            });
        });
    }

    // 6. MAGNET BUTTON (Disabled by user request)

    // -------------------------------------------------------------------------
    // 7. SPECULAR BUTTON (React Bits WebGL2 Shader)
    // -------------------------------------------------------------------------
    if (!reduceMotion) initSpecularButtons();

    // -------------------------------------------------------------------------
    // 8. ACCORDION GALLERY (React Bits + GSAP 3D Album for Homepage Header)
    // -------------------------------------------------------------------------
    initAccordionGalleries({ reduceMotion });

    // -------------------------------------------------------------------------
    // 9. STEPPER (React Bits 4-Step Reservation to Payment Process)
    // -------------------------------------------------------------------------
    initSteppers();

    // -------------------------------------------------------------------------
    // 10. FLEX CAROUSEL (React Bits + OGL WebGL Liquid Refraction Carousel)
    // -------------------------------------------------------------------------
    const flexCarouselCleanup = initFlexCarousels();
    if (flexCarouselCleanup) cleanup.push(flexCarouselCleanup);

    // -------------------------------------------------------------------------
    // 11. SCROLL STACK (React Bits 3D Card Stacking on Scroll)
    // -------------------------------------------------------------------------
    const scrollStackCleanup = initScrollStacks({ reduceMotion });
    if (scrollStackCleanup) cleanup.push(scrollStackCleanup);

    // -------------------------------------------------------------------------
    // 12. PEEK RATING (React Bits Star Rating with Lift, Magnify & Tooltip)
    // -------------------------------------------------------------------------
    initPeekRatings();

    // -------------------------------------------------------------------------
    // 13. CODE SLOTS (React Bits OTP Input with Smooth Caret, Cascade & Wash)
    // -------------------------------------------------------------------------
    initCodeSlots();

    // -------------------------------------------------------------------------
    // 14. BRANCHED MENU (React Bits Curved SVG Lines for Admin & Staff Sidebar)
    // -------------------------------------------------------------------------
    const branchedMenuCleanup = initBranchedMenus();
    if (branchedMenuCleanup) cleanup.push(branchedMenuCleanup);

    // -------------------------------------------------------------------------
    // 15. AUTHENTIC IPHONE 16 PRO CONTROLLER (Responsive across Mobile, Tablet, Desktop)
    // -------------------------------------------------------------------------
    initIPhoneMockup();

    return () => {
        cleanup.forEach((fn) => fn());
    };
}

/**
 * AnimatedContent adapted to the server-rendered customer pages.
 * Each marked wrapper reveals its existing direct children as they enter view.
 */
export function initAnimatedContent(elements, { reduceMotion = false } = {}) {
    if (reduceMotion || !elements?.length) return () => {};

    const tweens = [];
    [...new Set(elements)].forEach((root) => {
        if (!root?.isConnected || root.dataset.animatedContentBound === '1') return;
        root.dataset.animatedContentBound = '1';
        root.setAttribute('data-animated-content', '');

        const items = [...root.children].filter((item) => !item.hidden && getComputedStyle(item).display !== 'none');
        const targets = items.length ? items : [root];
        const distance = Number(root.dataset.acDistance || 28);
        const direction = root.dataset.acDirection === 'horizontal' ? 'x' : 'y';
        const reverse = root.dataset.acReverse === 'true';
        const amount = reverse ? -distance : distance;
        const thresholdValue = root.dataset.acThreshold === undefined ? 0.1 : Number(root.dataset.acThreshold);
        const threshold = Math.max(0, Math.min(1, Number.isFinite(thresholdValue) ? thresholdValue : 0.1));
        const duration = Math.max(0.2, Number(root.dataset.acDuration || 0.68));
        const stagger = Math.max(0, Number(root.dataset.acStagger || 0.055));
        const blur = Math.max(0, Number(root.dataset.acBlur || 0));
        const delay = Math.max(0, Number(root.dataset.acDelay || 0));
        const once = root.dataset.acOnce !== 'false';
        const from = { autoAlpha: Number(root.dataset.acOpacity ?? 0), [direction]: amount };
        const to = {
            autoAlpha: 1,
            [direction]: 0,
            duration,
            delay,
            stagger,
            ease: root.dataset.acEase || 'power3.out',
            clearProps: 'opacity,visibility,transform,filter',
        };
        if (blur) {
            from.filter = `blur(${blur}px)`;
            to.filter = 'blur(0px)';
        }

        if (root.dataset.acScale) from.scale = Number(root.dataset.acScale);
        if (root.dataset.acRotate) from.rotation = Number(root.dataset.acRotate);
        if (root.dataset.acTilt) from.rotationX = Number(root.dataset.acTilt);

        const tween = gsap.fromTo(targets, from, {
            ...to,
            scrollTrigger: {
                trigger: root,
                start: `top ${Math.round((1 - threshold) * 100)}%`,
                once,
                invalidateOnRefresh: true,
            },
        });
        tweens.push(tween);
    });

    return () => tweens.forEach((tween) => {
        tween.scrollTrigger?.kill();
        tween.kill();
    });
}


/**
 * ============================================================================
 * SPECULAR BUTTON (WebGL2 Edge Light Reflection Shader)
 * ============================================================================
 */
export function initSpecularButtons() {
    const PAD = 24;
    const TAU = Math.PI * 2;

    const VERTEX = `#version 300 es
    in vec2 aPosition;
    void main() {
      gl_Position = vec4(aPosition, 0.0, 1.0);
    }`;

    const FRAGMENT = `#version 300 es
    precision highp float;
    uniform vec2 uCenter;
    uniform vec2 uHalf;
    uniform float uRadius;
    uniform float uPx;
    uniform float uSpot;
    uniform vec2 uEntry;
    uniform vec3 uLineColor;
    uniform vec3 uBaseColor;
    uniform float uIntensity;
    uniform vec2 uWindow;
    uniform float uThickness;
    uniform float uGlow;
    out vec4 outColor;

    float roundedBox(vec2 p, vec2 b, float r) {
      vec2 q = abs(p) - b + r;
      return length(max(q, 0.0)) + min(max(q.x, q.y), 0.0) - r;
    }

    float loopPosition(vec2 p, vec2 b, float r) {
      vec2 inner = b - r;
      float arc = 1.5707963 * r;
      float quarter = inner.x + inner.y + arc;
      vec2 a = abs(p);
      vec2 q = a - inner;
      float s = q.x > 0.0 && q.y > 0.0
        ? inner.y + atan(q.y, q.x) * r
        : (q.x > q.y ? min(a.y, inner.y) : inner.y + arc + inner.x - min(a.x, inner.x));
      if (p.x < 0.0) s = p.y >= 0.0 ? 2.0 * quarter - s : 2.0 * quarter + s;
      else if (p.y < 0.0) s = 4.0 * quarter - s;
      return s / max(4.0 * quarter, 0.0001);
    }

    float lobe(float position, float center) {
      float gap = abs(fract(position - center + 0.5) - 0.5);
      return 1.0 - smoothstep(uWindow.x, uWindow.x + uWindow.y, gap);
    }

    void main() {
      vec2 p = gl_FragCoord.xy - uCenter;
      float d = roundedBox(p, uHalf, uRadius);
      float position = loopPosition(p, uHalf, uRadius);
      float key = lobe(position, uSpot);
      float back = lobe(position, uSpot + 0.5);
      float streak = key + back * 0.45;
      float width = max(uThickness * uPx * mix(1.0, max(key, back), 0.6), 0.35);
      float band = smoothstep(-width - 0.6, -width + 0.6, d) * (1.0 - smoothstep(-0.6, 0.6, d));
      float hair = smoothstep(-uPx - 0.6, -uPx + 0.6, d) * (1.0 - smoothstep(-0.6, 0.6, d));
      float inside = 1.0 - smoothstep(-0.6, 0.6, d);
      vec2 pool = (p - uEntry) / (uHalf.y * vec2(1.6, 1.1));
      float glow = streak * 0.2 * exp(-abs(d + width * 0.5) / (5.0 * uPx)) + inside * 0.08 * exp(-dot(pool, pool));
      float light = clamp((band * streak + glow * uGlow) * uIntensity, 0.0, 1.0);
      float alpha = clamp(light * 1.5 + hair * 0.35, 0.0, 1.0);
      vec3 col = mix(uBaseColor, uLineColor, light);
      outColor = vec4(col * alpha, alpha);
    }`;

    const clamp = (v, min, max) => Math.min(max, Math.max(min, v));
    const wrap = v => v - Math.floor(v);

    const loopPos = (x, y, halfX, halfY, r) => {
        const innerX = halfX - r;
        const innerY = halfY - r;
        const arc = (Math.PI / 2) * r;
        const quarter = innerX + innerY + arc;
        const ax = Math.abs(x);
        const ay = Math.abs(y);
        const qx = ax - innerX;
        const qy = ay - innerY;
        let s;
        if (qx > 0 && qy > 0) s = innerY + Math.atan2(qy, qx) * r;
        else if (qx > qy) s = Math.min(ay, innerY);
        else s = innerY + arc + innerX - Math.min(ax, innerX);
        if (x < 0) s = y >= 0 ? 2 * quarter - s : 2 * quarter + s;
        else if (y < 0) s = 4 * quarter - s;
        return wrap(s / Math.max(4 * quarter, 0.0001));
    };

    const rimPoint = (pos, halfX, halfY, r) => {
        const innerX = halfX - r;
        const innerY = halfY - r;
        const arc = (Math.PI / 2) * r;
        const quarter = innerX + innerY + arc;
        let s = wrap(pos) * 4 * quarter;
        let signX = 1;
        let signY = 1;
        if (s > 3 * quarter) {
            s = 4 * quarter - s;
            signY = -1;
        } else if (s > 2 * quarter) {
            s -= 2 * quarter;
            signX = -1;
            signY = -1;
        } else if (s > quarter) {
            s = 2 * quarter - s;
            signX = -1;
        }
        let x = innerX - (s - innerY - arc);
        let y = halfY;
        if (s <= innerY) {
            x = halfX;
            y = s;
        } else if (s <= innerY + arc) {
            const angle = (s - innerY) / Math.max(r, 0.0001);
            x = innerX + Math.cos(angle) * r;
            y = innerY + Math.sin(angle) * r;
        }
        return [x * signX, y * signY];
    };

    document.querySelectorAll('[data-specular-button]').forEach((btn) => {
        let canvas = btn.querySelector('.specular-button__light');
        if (!canvas) {
            canvas = document.createElement('canvas');
            canvas.className = 'specular-button__light';
            canvas.setAttribute('aria-hidden', 'true');
            btn.prepend(canvas);
        }

        const gl = canvas.getContext('webgl2', {
            alpha: true,
            premultipliedAlpha: true,
            antialias: false,
            depth: false,
            stencil: false,
        });
        if (!gl) return;

        const vs = gl.createShader(gl.VERTEX_SHADER);
        gl.shaderSource(vs, VERTEX);
        gl.compileShader(vs);
        const fs = gl.createShader(gl.FRAGMENT_SHADER);
        gl.shaderSource(fs, FRAGMENT);
        gl.compileShader(fs);

        const prog = gl.createProgram();
        gl.attachShader(prog, vs);
        gl.attachShader(prog, fs);
        gl.bindAttribLocation(prog, 0, 'aPosition');
        gl.linkProgram(prog);

        const uCenter = gl.getUniformLocation(prog, 'uCenter');
        const uHalf = gl.getUniformLocation(prog, 'uHalf');
        const uRadius = gl.getUniformLocation(prog, 'uRadius');
        const uPx = gl.getUniformLocation(prog, 'uPx');
        const uSpot = gl.getUniformLocation(prog, 'uSpot');
        const uEntry = gl.getUniformLocation(prog, 'uEntry');
        const uLineColor = gl.getUniformLocation(prog, 'uLineColor');
        const uBaseColor = gl.getUniformLocation(prog, 'uBaseColor');
        const uIntensity = gl.getUniformLocation(prog, 'uIntensity');
        const uWindow = gl.getUniformLocation(prog, 'uWindow');
        const uThickness = gl.getUniformLocation(prog, 'uThickness');
        const uGlow = gl.getUniformLocation(prog, 'uGlow');

        const buf = gl.createBuffer();
        const vao = gl.createVertexArray();
        gl.bindVertexArray(vao);
        gl.bindBuffer(gl.ARRAY_BUFFER, buf);
        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 3, -1, -1, 3]), gl.STATIC_DRAW);
        gl.enableVertexAttribArray(0);
        gl.vertexAttribPointer(0, 2, gl.FLOAT, false, 0, 0);
        gl.bindVertexArray(null);

        const view = { width: btn.offsetWidth || 180, height: btn.offsetHeight || 50, ratio: window.devicePixelRatio || 1 };
        const pointer = { x: 0, y: 0, known: false };
        const state = { spot: 0.15, level: 0.4, press: 0, ready: true };

        const resize = () => {
            view.width = Math.max(1, btn.offsetWidth);
            view.height = Math.max(1, btn.offsetHeight);
            view.ratio = Math.min(window.devicePixelRatio || 1, 2);
            canvas.width = Math.round((view.width + PAD * 2) * view.ratio);
            canvas.height = Math.round((view.height + PAD * 2) * view.ratio);
        };
        resize();

        const draw = () => {
            const { width, height } = view;
            const radius = Math.min(width / 2, height / 2);
            const scaleX = canvas.width / (width + PAD * 2);
            const scaleY = canvas.height / (height + PAD * 2);

            gl.viewport(0, 0, canvas.width, canvas.height);
            gl.clearColor(0, 0, 0, 0);
            gl.clear(gl.COLOR_BUFFER_BIT);
            gl.useProgram(prog);

            gl.uniform2f(uCenter, (PAD + width / 2) * scaleX, (PAD + height / 2) * scaleY);
            gl.uniform2f(uHalf, (width / 2) * scaleX, (height / 2) * scaleY);
            gl.uniform1f(uRadius, radius * scaleX);
            gl.uniform1f(uPx, view.ratio);

            const [rimX, rimY] = rimPoint(state.spot, width / 2, height / 2, radius);
            gl.uniform1f(uSpot, state.spot);
            gl.uniform2f(uEntry, rimX * scaleX, rimY * scaleY);
            gl.uniform3f(uLineColor, 0.72, 0.61, 0.45); // Warm Gold #B89C72
            gl.uniform3f(uBaseColor, 0.2, 0.18, 0.14);
            gl.uniform1f(uIntensity, state.level + state.press * 0.8);
            gl.uniform2f(uWindow, 12 / 360, 36 / 360);
            gl.uniform1f(uThickness, 1.4);
            gl.uniform1f(uGlow, 1.0);

            gl.bindVertexArray(vao);
            gl.drawArrays(gl.TRIANGLES, 0, 3);
            gl.bindVertexArray(null);
        };

        let raf = 0;
        const tick = () => {
            const rect = btn.getBoundingClientRect();
            if (pointer.known) {
                const x = pointer.x - (rect.left + rect.width / 2);
                const y = (rect.top + rect.height / 2) - pointer.y;
                const dist = Math.hypot(x, y);
                if (dist < 260) {
                    const targetSpot = loopPos(x, y, view.width / 2, view.height / 2, view.height / 2);
                    let diff = targetSpot - state.spot;
                    diff -= Math.round(diff);
                    state.spot = wrap(state.spot + diff * 0.16);
                    state.level = 0.4 + (1 - dist / 260) * 0.6;
                }
            } else {
                state.spot = wrap(state.spot + 0.002);
                state.level = 0.35;
            }
            draw();
            raf = requestAnimationFrame(tick);
        };
        raf = requestAnimationFrame(tick);

        window.addEventListener('pointermove', (e) => {
            pointer.x = e.clientX;
            pointer.y = e.clientY;
            pointer.known = true;
        }, { passive: true });

        window.addEventListener('resize', resize, { passive: true });
    });
}


/**
 * ============================================================================
 * ACCORDION GALLERY (GSAP 3D Interactive Album at Top of Homepage)
 * ============================================================================
 */
export function initAccordionGalleries() {
    const cleanups = [];
    document.querySelectorAll('[data-accordion-gallery]').forEach((root) => {
        const panels = [...root.querySelectorAll('.ag-panel')];
        if (!panels.length) return;

        const count = panels.length;
        const defaultAttr = parseInt(root.dataset.defaultIndex ?? '', 10);
        const initialActive = Number.isFinite(defaultAttr)
            ? Math.min(Math.max(defaultAttr, 0), count - 1)
            : Math.max(0, panels.findIndex((panel) => panel.classList.contains('ag-panel--active')));

        let activeIdx = initialActive;
        const expandRatio = 0.54;
        const duration = 0.6;
        const ease = 'power3.out';
        const parallax = 0.5;
        const tilt = 8;
        const stagger = 0.06;

        let mediaSize = 320;
        let tlRef = null;
        let firstRun = true;
        let lastW = 0;
        let lastH = 0;
        const listeners = [];

        const mediaRefs = panels.map((p) => p.querySelector('.ag-panel__media'));
        const barRefs = panels.map((p) => p.querySelector('.ag-panel__bar'));
        const textRefs = panels.map((p) => p.querySelector('.ag-panel__text'));

        root.setAttribute('role', 'list');
        panels.forEach((panel) => {
            panel.setAttribute('role', 'listitem');
            if (!panel.hasAttribute('tabindex')) panel.tabIndex = 0;
        });

        const applyLayout = (animate = true) => {
            const vertical = root.classList.contains('accordion-gallery--vertical');
            const r = Math.min(Math.max(expandRatio, 0.2), 0.9);
            const grow = count > 1 ? (r * (count - 1)) / (1 - r) : 1;

            if (tlRef) tlRef.kill();
            const dur = animate ? duration : 0;
            const tl = gsap.timeline();

            panels.forEach((panel, i) => {
                const isActive = i === activeIdx;
                panel.classList.toggle('ag-panel--active', isActive);
                if (isActive) {
                    panel.setAttribute('aria-current', 'true');
                } else {
                    panel.removeAttribute('aria-current');
                }

                const media = mediaRefs[i];
                const bar = barRefs[i];
                const text = textRefs[i];

                const rot = isActive ? 0 : i < activeIdx ? tilt : -tilt;
                const rotProp = vertical ? { rotateX: -rot } : { rotateY: rot };

                tl.to(
                    panel,
                    {
                        flexGrow: isActive ? grow : 1,
                        ...rotProp,
                        '--ag-dim': isActive ? 0 : 0.35,
                        duration: dur,
                        ease,
                    },
                    0
                );

                if (media) {
                    const drift = Math.max(-1.5, Math.min(1.5, activeIdx - i));
                    const shift = drift * parallax * mediaSize * 0.06;
                    tl.to(
                        media,
                        {
                            xPercent: -50,
                            yPercent: -50,
                            x: vertical ? 0 : isActive ? 0 : shift,
                            y: vertical ? (isActive ? 0 : shift) : 0,
                            '--ag-gray': isActive ? 0 : 1,
                            '--ag-dim': isActive ? 0 : 0.35,
                            duration: dur,
                            ease,
                        },
                        0
                    );
                }

                if (bar && text) {
                    if (isActive) {
                        tl.to(
                            [bar, text],
                            {
                                opacity: 1,
                                x: 0,
                                duration: dur,
                                ease,
                                stagger: dur > 0 ? stagger : 0,
                            },
                            0
                        );
                    } else {
                        tl.to(
                            [bar, text],
                            {
                                opacity: 0,
                                x: -14,
                                duration: dur * 0.6,
                                ease,
                            },
                            0
                        );
                    }
                }
            });

            tlRef = tl;
        };

        const setActive = (idx, animate = true) => {
            const clamped = Math.min(Math.max(idx, 0), count - 1);
            if (clamped === activeIdx && !firstRun) return;
            activeIdx = clamped;
            applyLayout(animate);
        };

        panels.forEach((p, idx) => {
            const onEnter = () => setActive(idx, true);
            const onFocus = () => setActive(idx, true);
            const onClick = (e) => {
                if (activeIdx !== idx) {
                    e.preventDefault();
                    setActive(idx, true);
                }
            };
            const onKeyDown = (event) => {
                if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(event.key)) return;
                event.preventDefault();
                const backwards = event.key === 'ArrowLeft' || event.key === 'ArrowUp';
                const next = event.key === 'Home' ? 0 : event.key === 'End' ? count - 1 : (activeIdx + (backwards ? -1 : 1) + count) % count;
                panels[next].focus();
                setActive(next, true);
            };
            p.addEventListener('mouseenter', onEnter);
            p.addEventListener('pointerenter', onEnter);
            p.addEventListener('focus', onFocus);
            p.addEventListener('click', onClick);
            p.addEventListener('keydown', onKeyDown);
            listeners.push(() => {
                p.removeEventListener('mouseenter', onEnter);
                p.removeEventListener('pointerenter', onEnter);
                p.removeEventListener('focus', onFocus);
                p.removeEventListener('click', onClick);
                p.removeEventListener('keydown', onKeyDown);
            });
        });

        const measure = () => {
            const rect = root.getBoundingClientRect();
            if (!firstRun && Math.abs(rect.width - lastW) < 2 && Math.abs(rect.height - lastH) < 2) {
                return;
            }
            lastW = rect.width;
            lastH = rect.height;
            const vertical = root.classList.contains('accordion-gallery--vertical');
            const total = vertical ? rect.height : rect.width;
            const gap = parseFloat(getComputedStyle(root).gap) || 10;
            const usable = Math.max(total - gap * (count - 1), 120);
            const r = Math.min(Math.max(expandRatio, 0.2), 0.9);
            mediaSize = Math.max(140, usable * r * 1.22);
            root.style.setProperty('--ag-media-size', `${mediaSize}px`);
            applyLayout(!firstRun);
            firstRun = false;
        };

        const observer = new ResizeObserver(measure);
        observer.observe(root);
        measure();

        cleanups.push(() => {
            observer.disconnect();
            listeners.forEach((dispose) => dispose());
            if (tlRef) tlRef.kill();
        });
    });

    return () => cleanups.forEach((dispose) => dispose());
}


/**
 * ============================================================================
 * STEPPER (React Bits Stepper Component Synchronized with iPhone Mockup)
 * ============================================================================
 */
export function initSteppers() {
    document.querySelectorAll('[data-stepper]').forEach((stepper) => {
        const segments = [...stepper.querySelectorAll('.stepper__segment')];
        const panes = [...stepper.querySelectorAll('.stepper__pane')];
        const glider = stepper.querySelector('.stepper__glider');
        const btnBack = stepper.querySelector('[data-stepper-back]');
        const btnNext = stepper.querySelector('[data-stepper-next]');
        const totalSteps = segments.length || 3;
        let currentStep = 1;

        const compactQuery = window.matchMedia('(max-width: 520px)');
        const track = stepper.querySelector('.stepper__track');

        const syncLayout = () => {
            track?.setAttribute('data-layout', 'row');
            stepper.setAttribute('data-tight', compactQuery.matches ? 'true' : 'false');
        };

        const updateGlider = (activeSegment) => {
            if (!glider || !activeSegment) return;
            glider.style.transform = `translate3d(${activeSegment.offsetLeft}px, ${activeSegment.offsetTop}px, 0)`;
            glider.style.width = `${activeSegment.offsetWidth}px`;
            glider.style.height = `${activeSegment.offsetHeight}px`;
        };

        const goTo = (step) => {
            const previousStep = currentStep;
            currentStep = Math.max(1, Math.min(step, totalSteps));

            segments.forEach((seg, i) => {
                const sNum = i + 1;
                const status = sNum === currentStep ? 'active' : sNum < currentStep ? 'complete' : 'upcoming';
                seg.setAttribute('data-status', status);
                seg.classList.toggle('is-active', sNum === currentStep);
                seg.setAttribute('aria-selected', String(sNum === currentStep));
                seg.tabIndex = sNum === currentStep ? 0 : -1;

                const badge = seg.querySelector('.stepper__badge');
                if (badge) {
                    badge.setAttribute('data-status', status);
                    badge.innerHTML = status === 'complete' ? '<i class="bi bi-check-lg"></i>' : String(sNum);
                }
                const title = seg.querySelector('.stepper__title');
                if (title) title.setAttribute('data-status', status);
            });

            panes.forEach((p) => {
                const pNum = Number(p.dataset.pane);
                p.classList.toggle('is-active', pNum === currentStep);
                p.inert = pNum !== currentStep;
                if (pNum === currentStep && previousStep !== currentStep) {
                    p.getAnimations().forEach(animation => animation.cancel());
                    p.animate([{ opacity: 0, transform: `translateX(${currentStep > previousStep ? 16 : -16}px)` }, { opacity: 1, transform: 'translateX(0)' }], {
                        duration: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 420,
                        easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                    });
                }
            });

            const activeSeg = segments[currentStep - 1];
            updateGlider(activeSeg);
            if (activeSeg && window.matchMedia('(min-width: 521px) and (max-width: 640px)').matches) {
                const track = activeSeg.parentElement;
                track?.scrollTo({ left: activeSeg.offsetLeft - track.offsetLeft - 4, behavior: 'smooth' });
            }

            // Sync with iPhone Mockup if configured
            if (activeSeg && activeSeg.dataset.screen && typeof window.__switchIPhoneScreen === 'function') {
                window.__switchIPhoneScreen(activeSeg.dataset.screen);
            }

            if (btnBack) {
                if (currentStep <= 1) btnBack.setAttribute('data-hidden', '');
                else btnBack.removeAttribute('data-hidden');
            }

            if (btnNext) {
                const label = btnNext.querySelector('span');
                if (label) {
                    label.textContent = currentStep === totalSteps ? 'Hoàn tất & Đặt phòng ngay' : 'Tiếp tục bước tiếp';
                }
                if (currentStep === totalSteps) {
                    btnNext.onclick = () => window.location.href = '/rooms';
                } else {
                    btnNext.onclick = () => goTo(currentStep + 1);
                }
            }
        };

        // Expose for external controls & iPhone tabbar sync
        window.__stepperGoTo = goTo;
        window.__stepperSyncScreen = (screenId) => {
            const idx = segments.findIndex((s) => s.dataset.screen === screenId);
            if (idx !== -1) {
                goTo(idx + 1);
            }
        };

        segments.forEach((seg, i) => {
            seg.addEventListener('click', () => goTo(i + 1));
            seg.addEventListener('keydown', (event) => {
                if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(event.key)) return;
                event.preventDefault();
                const forward = event.key === 'ArrowRight' || event.key === 'ArrowDown';
                const index = event.key === 'Home' ? 0 : event.key === 'End' ? segments.length - 1 : (i + (forward ? 1 : -1) + segments.length) % segments.length;
                segments[index].focus();
                goTo(index + 1);
            });
        });

        // Quick navigation buttons inside panes
        stepper.querySelectorAll('[data-stepper-goto]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const target = parseInt(btn.dataset.stepperGoto, 10);
                if (!isNaN(target)) goTo(target);
            });
        });

        if (btnBack) btnBack.addEventListener('click', () => goTo(currentStep - 1));
        syncLayout();
        compactQuery.addEventListener?.('change', syncLayout);
        const observer = new ResizeObserver(() => {
            syncLayout();
            updateGlider(segments[currentStep - 1]);
        });
        observer.observe(stepper);
        segments.forEach(segment => observer.observe(segment));
        window.addEventListener('pagehide', () => {
            observer.disconnect();
            compactQuery.removeEventListener?.('change', syncLayout);
        }, { once: true });
        setTimeout(() => goTo(1), 100);
    });

    // Global listeners for Mockup action triggers inside Stepper panes
    document.querySelectorAll('[data-mockup-trigger-nfc]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (typeof window.__stepperGoTo === 'function') window.__stepperGoTo(2);
            setTimeout(() => {
                const unlockBtn = document.querySelector('[data-wallet-unlock]');
                if (unlockBtn) unlockBtn.click();
            }, 180);
        });
    });

    document.querySelectorAll('[data-mockup-trigger-chat]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (typeof window.__stepperGoTo === 'function') window.__stepperGoTo(3);
            setTimeout(() => {
                const firstChip = document.querySelector('[data-chat-prompt]');
                if (firstChip) firstChip.click();
            }, 180);
        });
    });
}


/**
 * ============================================================================
 * 10. FLEX CAROUSEL (React Bits WebGL Liquid Refraction Gallery with OGL)
 * ============================================================================
 */
const DEFAULT_FLEX_ITEMS = [
    {
        src: '/images/melia/hero-hanoi-suite.jpg',
        alt: 'Phòng Heritage Executive Suite với ban công nhìn ra Phố Cổ Hà Nội',
        title: 'Heritage Executive Suite',
        subtitle: 'Tầm nhìn di sản Phố Cổ & Hồ Gươm'
    },
    {
        src: '/images/melia/service-rooftop-bar.jpg',
        alt: 'Moonlight Sky Bar tầng thượng ngắm hoàng hôn Hà Nội',
        title: 'Moonlight Sky Bar',
        subtitle: 'Hoàng hôn lãng mạn & cocktail tầng thượng'
    },
    {
        src: '/images/melia/service-lotus-spa.jpg',
        alt: 'Lotus Sanctuary Spa với liệu pháp thảo mộc cổ truyền',
        title: 'Lotus Sanctuary Spa',
        subtitle: 'Liệu pháp thảo mộc cổ truyền & chuông xoay'
    },
    {
        src: '/images/melia/infinity-pool.jpg',
        alt: 'Hồ bơi vô cực bốn mùa nước ấm',
        title: 'Heated Sky Pool',
        subtitle: 'Hồ bơi vô cực nước ấm bốn mùa'
    },
    {
        src: '/images/melia/the-level-lounge.jpg',
        alt: 'The Level Executive Lounge đẳng cấp',
        title: 'The Level Lounge',
        subtitle: 'Đặc quyền thượng lưu & trà chiều thư thái'
    },
    {
        src: '/images/melia/service-heritage-dining.jpg',
        alt: 'Rosaliza Gastronomy ẩm thực tinh hoa',
        title: 'Rosaliza Gastronomy',
        subtitle: 'Tinh hoa ẩm thực Á - Âu thượng hạng'
    },
    {
        src: '/images/melia/wellness-meditation.jpg',
        alt: 'Không gian thiền và chuông xoay Tây Tạng',
        title: 'Herbal Sound & Spa',
        subtitle: 'Trị liệu chuông xoay & thảo mộc bản địa'
    },
    {
        src: '/images/melia/sunset-beach-bar.jpg',
        alt: 'Golden Hour Lounge cocktail hoàng hôn',
        title: 'Golden Hour Lounge',
        subtitle: 'Cocktail thủ công ngắm hoàng hôn Hà Nội'
    },
    {
        src: '/images/melia/activity-lotus-tea.jpg',
        alt: 'Nghi thức trà sen Tây Hồ cổ truyền',
        title: 'Thiền Trà Sen Tây Hồ',
        subtitle: 'Trà sen trăm năm thanh tịnh'
    },
    {
        src: '/images/melia/activity-hoankiem-walk.jpg',
        alt: 'Hồ Gươm và 36 phố phường Hà Nội',
        title: 'Hồ Gươm & Phố Cổ',
        subtitle: 'Khám phá nét đẹp di sản ngàn năm'
    },
    {
        src: '/images/melia/dining-ciao-chao.jpg',
        alt: 'Nhà hàng Le Rosaliza phong vị ẩm thực thượng lưu',
        title: 'Le Rosaliza Restaurant',
        subtitle: 'Bữa sáng di sản và thực đơn bếp trưởng'
    },
    {
        src: '/images/melia/hanoi-hoankiem-overview.jpg',
        alt: 'Toàn cảnh Hồ Hoàn Kiếm xanh mát giữa lòng thủ đô',
        title: 'Trái Tim Thủ Đô',
        subtitle: 'Toàn cảnh Hồ Hoàn Kiếm xanh mát'
    }
];

const BEND_PRESETS = {
  liquid: {
    lensWidth: 0.74,
    lensHeight: 1.18,
    tilt: 62,
    roundness: 1,
    bend: 0.34,
    reach: 0.38,
    curl: 'twist',
    dispersion: 0.45,
    liquid: 0,
    followCursor: false
  },
  ribbon: {
    lensWidth: 0.8,
    lensHeight: 0.8,
    tilt: 0,
    roundness: 1,
    bend: 0.34,
    reach: 0.34,
    curl: 'twist',
    dispersion: 0.4,
    liquid: 0,
    followCursor: false
  },
  vortex: {
    lensWidth: 0.7,
    lensHeight: 0.95,
    tilt: 30,
    roundness: 1,
    bend: 0.46,
    reach: 0.3,
    curl: 'twist',
    dispersion: 0.5,
    liquid: 0,
    followCursor: false
  },
  arch: {
    lensWidth: 0.8,
    lensHeight: 0.8,
    tilt: 0,
    roundness: 1,
    bend: 0.3,
    reach: 0.36,
    curl: 'rise',
    dispersion: 0.4,
    liquid: 0,
    followCursor: false
  }
};

const FIT_ASPECT = { portrait: 0.75, square: 1, landscape: 4 / 3 };
const TAPS = 12;
const PIXEL_BUDGET = 4.5e6;
const INTRO_DURATION = { rise: 2.1, bloom: 1.6, spin: 2.2, deal: 1.5, fade: 0.35 };

const fcWrap = (value, size) => ((((value + size / 2) % size) + size) % size) - size / 2;
const fcClamp01 = value => Math.min(Math.max(value, 0), 1);
const fcEaseOut = value => 1 - Math.pow(1 - fcClamp01(value), 3);
const fcEaseOutQuint = value => 1 - Math.pow(1 - fcClamp01(value), 5);
const fcEaseInOut = value => {
  const t = fcClamp01(value);
  return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
};

const fcCardVertex = `#version 300 es
in vec3 position;
in vec2 uv;
uniform vec4 uRect;
uniform vec2 uResolution;
out vec2 vUv;
out vec2 vLocal;
void main() {
  vUv = uv;
  vLocal = vec2(position.x, -position.y) * uRect.zw;
  vec2 px = uRect.xy + vLocal;
  gl_Position = vec4(px.x / uResolution.x * 2.0 - 1.0, 1.0 - px.y / uResolution.y * 2.0, 0.0, 1.0);
}
`;

const fcCardFragment = `#version 300 es
precision highp float;
uniform sampler2D tMap;
uniform vec2 uSize;
uniform vec2 uImage;
uniform float uRadius;
uniform float uAlpha;
uniform float uReady;
uniform float uShift;
uniform float uDpr;
uniform vec3 uPlaceholder;
in vec2 vUv;
in vec2 vLocal;
out vec4 fragColor;

float roundedBox(vec2 p, vec2 b, float r) {
  vec2 q = abs(p) - b + r;
  return length(max(q, 0.0)) + min(max(q.x, q.y), 0.0) - r;
}

void main() {
  float sd = roundedBox(vLocal, uSize * 0.5, min(uRadius, min(uSize.x, uSize.y) * 0.5));
  float mask = clamp(0.5 - sd * uDpr, 0.0, 1.0);
  vec2 local = vLocal / uSize + 0.5;
  float cardAspect = uSize.x / uSize.y;
  float imageAspect = uImage.x / max(uImage.y, 1.0);
  vec2 scale = imageAspect > cardAspect ? vec2(cardAspect / imageAspect, 1.0) : vec2(1.0, imageAspect / cardAspect);
  scale /= 1.08;
  vec2 uv = vec2(local.x, 1.0 - local.y);
  uv = (uv - 0.5) * scale + 0.5;
  uv.x += uShift * (1.0 - scale.x) * 0.5;
  vec3 image = texture(tMap, uv).rgb;
  vec3 color = mix(uPlaceholder, image, uReady);
  float alpha = mask * uAlpha;
  fragColor = vec4(color * alpha, alpha);
}
`;

const fcLensVertex = `#version 300 es
in vec2 position;
void main() {
  gl_Position = vec4(position, 0.0, 1.0);
}
`;

const fcLensFragment = `#version 300 es
precision highp float;
uniform sampler2D tScene;
uniform vec2 uResolution;
uniform float uDpr;
uniform vec2 uCenter;
uniform vec2 uHalf;
uniform float uAngle;
uniform float uExponent;
uniform float uInner;
uniform float uOuter;
uniform float uFlow;
uniform float uCurl;
uniform float uDispersion;
uniform float uStrength;
uniform float uSceneAlpha;
out vec4 fragColor;

void main() {
  vec2 frag = gl_FragCoord.xy / uDpr;
  vec2 uv = frag / uResolution;
  vec2 rel = frag - vec2(uCenter.x, uResolution.y - uCenter.y);
  float ca = cos(uAngle);
  float sa = sin(uAngle);
  vec2 local = vec2(ca * rel.x + sa * rel.y, -sa * rel.x + ca * rel.y);
  vec2 k = max(abs(local) / uHalf, vec2(1e-5));
  float nd = pow(pow(k.x, uExponent) + pow(k.y, uExponent), 1.0 / uExponent);
  vec2 grad = pow(k, vec2(uExponent - 1.0)) * sign(local) / uHalf * pow(nd, 1.0 - uExponent);
  float glen = max(length(grad), 1e-6);
  float edge = (nd - 1.0) / glen;
  vec2 outward = grad / glen;
  vec2 normal = vec2(ca * outward.x - sa * outward.y, sa * outward.x + ca * outward.y);
  vec2 along = vec2(-normal.y, normal.x);

  float t = clamp((edge + uInner) / (uInner + uOuter), 0.0, 1.0);
  float ramp = t * t * t * (t * (t * 6.0 - 15.0) + 10.0);
  float slope = 16.0 * t * t * (1.0 - t) * (1.0 - t);
  float reachX = rel.x / (uResolution.x * 0.5);
  float side = smoothstep(0.02, 0.3, abs(reachX)) * (uCurl == 0.0 ? sign(reachX) : uCurl);
  float lift = ramp * side * uFlow * uStrength;
  vec2 swirl = along * along.y * side * slope * uFlow * uStrength * 0.35;
  vec2 drift = vec2(0.0, -lift) - swirl;
  vec2 shifted = uv + drift / uResolution;

  vec2 texels = uResolution * uDpr;
  vec2 gx = dFdx(shifted);
  vec2 gy = dFdy(shifted);
  gx *= min(1.0, 3.0 / max(length(gx * texels), 1e-4));
  gy *= min(1.0, 3.0 / max(length(gy * texels), 1e-4));

  vec4 color = textureGrad(tScene, shifted, gx, gy);
  vec2 spread = vec2(0.0, side * slope * uFlow * uStrength) / uResolution * uDispersion;
  float spreadPx = length(spread * texels);
  if (color.a > 0.002 && spreadPx > 0.25) {
    vec3 base = color.rgb / color.a;
    vec3 sumColor = vec3(0.0);
    vec3 sumWeight = vec3(0.0);
    for (int i = 0; i < 12; i++) {
      float s = (float(i) + 0.5) / 12.0;
      vec4 c = textureGrad(tScene, shifted + spread * (s - 0.5), gx, gy);
      vec3 w = max(1.0 - abs(vec3(s) - vec3(0.15, 0.5, 0.85)) * 2.6, 0.0) * c.a;
      sumColor += c.rgb * (w / max(c.a, 0.002));
      sumWeight += w;
    }
    vec3 split = mix(base, sumColor / max(sumWeight, vec3(1e-4)), clamp(sumWeight * 2.0, 0.0, 1.0));
    color.rgb = mix(color.rgb, clamp(split, 0.0, 1.0) * color.a, smoothstep(0.25, 1.5, spreadPx));
  }

  fragColor = color * uSceneAlpha;
}
`;

export function initFlexCarousels() {
    const cleanups = [];
    document.querySelectorAll('[data-flex-carousel]').forEach((container) => {
        if (container.dataset.rbInit === '1') return;
        container.dataset.rbInit = '1';

        // Items configuration
        let items = DEFAULT_FLEX_ITEMS;
        if (container.dataset.items) {
            try {
                const parsed = JSON.parse(container.dataset.items);
                if (Array.isArray(parsed) && parsed.length) items = parsed;
            } catch (e) {
                console.warn('Invalid JSON in data-items for flex-carousel', e);
            }
        }

        const presetKey = container.dataset.preset || 'liquid';
        const base = BEND_PRESETS[presetKey] || BEND_PRESETS.liquid;
        const pick = (val, key) => (val === undefined || val === null ? base[key] : val);

        const cardHeight = parseFloat(container.dataset.cardHeight || '0.5');
        const gap = parseFloat(container.dataset.gap || '12');
        const radius = parseFloat(container.dataset.radius || '16');
        const fit = container.dataset.fit || 'natural';
        const squeeze = parseFloat(container.dataset.squeeze || '0.2');
        const focusOnClick = container.dataset.focusOnClick !== 'false';
        const autoplay = container.dataset.autoplay === 'true';
        const interval = parseFloat(container.dataset.interval || '4');
        const captions = container.dataset.captions !== 'false';
        const captureWheel = container.dataset.captureWheel !== 'false';
        const intro = container.dataset.intro || 'rise';

        const s = {
            intro,
            cardHeight,
            gap,
            radius,
            fit,
            lensWidth: pick(container.dataset.lensWidth ? parseFloat(container.dataset.lensWidth) : null, 'lensWidth'),
            lensHeight: pick(container.dataset.lensHeight ? parseFloat(container.dataset.lensHeight) : null, 'lensHeight'),
            tilt: pick(container.dataset.tilt ? parseFloat(container.dataset.tilt) : null, 'tilt'),
            roundness: pick(container.dataset.roundness ? parseFloat(container.dataset.roundness) : null, 'roundness'),
            bend: pick(container.dataset.bend ? parseFloat(container.dataset.bend) : null, 'bend'),
            reach: pick(container.dataset.reach ? parseFloat(container.dataset.reach) : null, 'reach'),
            curl: pick(container.dataset.curl, 'curl'),
            dispersion: pick(container.dataset.dispersion ? parseFloat(container.dataset.dispersion) : null, 'dispersion'),
            liquid: pick(container.dataset.liquid ? parseFloat(container.dataset.liquid) : null, 'liquid'),
            followCursor: pick(container.dataset.followCursor !== undefined ? container.dataset.followCursor === 'true' : null, 'followCursor'),
            squeeze,
            focusOnClick,
            autoplay,
            interval,
            captureWheel
        };

        const captionHost = container.closest('.flex-carousel-stage') || container;
        captionHost.style.setProperty('--flex-carousel-half', `${Math.min(Math.max(cardHeight, 0.05), 1) * 50}%`);
        if (!container.hasAttribute('tabindex')) container.setAttribute('tabindex', '0');
        if (!container.hasAttribute('role')) container.setAttribute('role', 'region');
        if (!container.hasAttribute('aria-roledescription')) container.setAttribute('aria-roledescription', 'carousel');
        if (!container.hasAttribute('aria-label')) container.setAttribute('aria-label', 'Image carousel');

        const renderer = new Renderer({
            dpr: Math.min(window.devicePixelRatio || 1, 2),
            alpha: true,
            premultipliedAlpha: true,
            antialias: false,
            depth: false
        });
        const gl = renderer.gl;
        if (!renderer.isWebgl2) {
            gl.getExtension('WEBGL_lose_context')?.loseContext();
            return;
        }

        gl.clearColor(0, 0, 0, 0);
        const canvas = gl.canvas;
        canvas.style.display = 'block';
        canvas.style.width = '100%';
        canvas.style.height = '100%';
        canvas.setAttribute('aria-hidden', 'true');
        container.prepend(canvas);

        const cardProgram = new Program(gl, {
            vertex: fcCardVertex,
            fragment: fcCardFragment,
            transparent: true,
            depthTest: false,
            depthWrite: false,
            uniforms: {
                tMap: { value: new Texture(gl) },
                uRect: { value: [0, 0, 1, 1] },
                uResolution: { value: [1, 1] },
                uSize: { value: [1, 1] },
                uImage: { value: [1, 1] },
                uRadius: { value: radius },
                uAlpha: { value: 1 },
                uReady: { value: 0 },
                uShift: { value: 0 },
                uDpr: { value: 1 },
                uPlaceholder: { value: [0.5, 0.5, 0.5] }
            }
        });
        cardProgram.setBlendFunc(gl.ONE, gl.ONE_MINUS_SRC_ALPHA);
        const cardMesh = new Mesh(gl, { geometry: new Plane(gl), program: cardProgram });

        const target = new RenderTarget(gl, {
            width: 2,
            height: 2,
            depth: false,
            minFilter: gl.LINEAR_MIPMAP_LINEAR,
            magFilter: gl.LINEAR
        });

        const lensUniforms = {
            tScene: { value: target.texture },
            uResolution: { value: [1, 1] },
            uDpr: { value: 1 },
            uCenter: { value: [0, 0] },
            uHalf: { value: [1, 1] },
            uAngle: { value: 0 },
            uExponent: { value: 2 },
            uInner: { value: 60 },
            uOuter: { value: 80 },
            uFlow: { value: 0 },
            uCurl: { value: 0 },
            uDispersion: { value: 0 },
            uStrength: { value: 0 },
            uSceneAlpha: { value: 0 }
        };

        const lensMesh = new Mesh(gl, {
            geometry: new Triangle(gl),
            program: new Program(gl, {
                vertex: fcLensVertex,
                fragment: fcLensFragment,
                uniforms: lensUniforms,
                depthTest: false,
                depthWrite: false
            })
        });

        const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
        const anisotropy = renderer.getExtension('EXT_texture_filter_anisotropic') ? 8 : 0;

        let slots = [];
        let width = 1;
        let height = 1;
        let pos = 0;
        let vel = 0;
        let goal = 0;
        let mode = 'spring';
        let wheelAt = 0;
        let raf = 0;
        let last = performance.now();
        let visible = true;
        let alive = true;
        let dirty = true;
        let activeIndex = -1;
        let interactedAt = -Infinity;
        let autoplayAt = performance.now();
        let hasFocus = false;
        let deform = 0;
        let deformVel = 0;
        let layout = null;
        let resnap = false;
        let hover = '';
        let lift = 1;
        let energy = 0;
        let lastPos = 0;
        const lens = { x: 0, y: 0, vx: 0, vy: 0, ready: false };
        const pointer = {
            x: 0,
            y: 0,
            over: false,
            down: false,
            id: -1,
            startX: 0,
            startY: 0,
            startPos: 0,
            dragging: false,
            touch: false,
            samples: []
        };
        const introState = { kind: 'none', t: 0, running: false, done: false, readyAt: 0 };
        const focus = { index: -1, pending: -1, t: 0, v: 0, target: 0 };
        let instances = [];

        // Captions UI
        let captionEl = captionHost.querySelector('.flex-carousel__caption');
        let liveEl = container.querySelector('.flex-carousel__live');
        if (captions) {
            if (!captionEl) {
                captionEl = document.createElement('div');
                captionEl.className = 'flex-carousel__caption';
                captionEl.setAttribute('aria-hidden', 'true');
                captionEl.style.display = 'none';
                captionHost.appendChild(captionEl);
            }
            if (!liveEl) {
                liveEl = document.createElement('div');
                liveEl.className = 'flex-carousel__live';
                liveEl.setAttribute('aria-live', 'polite');
                liveEl.setAttribute('aria-atomic', 'true');
                container.appendChild(liveEl);
            }
        }

        let stateActive = 0;
        let stateRevealed = false;
        let stateFocusOpen = false;

        const renderDigitsHtml = (val) => {
            const str = String(val).padStart(2, '0');
            return `<span class="flex-carousel__digits">${str.split('').map(d => `
                <span class="flex-carousel__digit">
                    <span class="flex-carousel__reel" style="transform: translateY(-${Number(d) * 10}%)">
                        ${'0123456789'.split('').map(n => `<span>${n}</span>`).join('')}
                    </span>
                </span>
            `).join('')}</span>`;
        };

        const updateCaptionsUI = () => {
            if (!captionEl || !captions) return;
            const cur = items[stateActive] || items[0];
            if (!cur) {
                captionEl.style.display = 'none';
                return;
            }
            captionEl.style.display = 'flex';
            captionEl.innerHTML = `
                <span class="flex-carousel__title">
                    ${cur.title || cur.alt || ''}
                    ${cur.subtitle ? `<span class="flex-carousel__subtitle">${cur.subtitle}</span>` : ''}
                </span>
                <span class="flex-carousel__count" ${stateFocusOpen ? 'data-hidden=""' : ''}>
                    ${renderDigitsHtml(stateActive + 1)}
                    <span class="flex-carousel__slash">/</span>
                    <span>${String(items.length).padStart(2, '0')}</span>
                </span>
            `;
            if (liveEl) {
                const label = cur.title || cur.alt || `Image ${stateActive + 1}`;
                liveEl.textContent = `${label}, ${stateActive + 1} of ${items.length}`;
            }
        };

        const setActive = (idx) => {
            stateActive = idx;
            updateCaptionsUI();
        };
        const setRevealed = (rev) => {
            stateRevealed = rev;
            updateCaptionsUI();
        };
        const setFocusOpen = (fo) => {
            stateFocusOpen = fo;
            updateCaptionsUI();
        };

        const loadSlot = (item, index) => {
            const texture = new Texture(gl, {
                generateMipmaps: true,
                minFilter: gl.LINEAR_MIPMAP_LINEAR,
                magFilter: gl.LINEAR,
                anisotropy
            });
            const slot = {
                item,
                index,
                texture,
                aspect: 0.8,
                loaded: false,
                failed: false,
                ready: 0,
                color: [0.5, 0.5, 0.5],
                image: [1, 1],
                dispose: () => {}
            };
            const image = new Image();
            if (item.src.startsWith('http') && !item.src.startsWith(window.location.origin)) {
                image.crossOrigin = 'anonymous';
            }
            image.decoding = 'async';
            image.onload = () => {
                if (!alive || !slots.includes(slot)) return;
                texture.image = image;
                texture.update();
                slot.image = [image.naturalWidth || 1, image.naturalHeight || 1];
                slot.aspect = slot.image[0] / slot.image[1];
                try {
                    const probe = document.createElement('canvas');
                    probe.width = 8;
                    probe.height = 8;
                    const ctx = probe.getContext('2d', { willReadFrequently: true });
                    if (ctx) {
                        ctx.drawImage(image, 0, 0, 8, 8);
                        const data = ctx.getImageData(0, 0, 8, 8).data;
                        const avg = [0, 0, 0];
                        for (let i = 0; i < data.length; i += 4) {
                            avg[0] += data[i];
                            avg[1] += data[i + 1];
                            avg[2] += data[i + 2];
                        }
                        slot.color = avg.map(v => v / 64 / 255);
                    }
                } catch {
                    slot.color = [0.5, 0.5, 0.5];
                }
                slot.loaded = true;
                dirty = true;
                start();
            };
            image.onerror = () => {
                if (!alive) return;
                slot.failed = true;
                dirty = true;
                start();
            };
            image.src = item.src;
            slot.dispose = () => {
                image.onload = null;
                image.onerror = null;
                gl.deleteTexture(texture.texture);
            };
            return slot;
        };

        const setItems = (next) => {
            slots.forEach(slot => slot.dispose());
            slots = next.map(loadSlot);
            activeIndex = -1;
            layout = null;
            resnap = true;
            focus.target = 0;
            focus.t = 0;
            focus.v = 0;
            focus.pending = -1;
            setFocusOpen(false);
            introState.readyAt = performance.now();
            dirty = true;
            start();
        };

        const metrics = (settings) => {
            const cardH = Math.max(24, settings.cardHeight * height);
            const fixed = FIT_ASPECT[settings.fit];
            const widths = slots.map(slot => (fixed || slot.aspect) * cardH);
            const centers = [];
            let cursor = 0;
            for (let i = 0; i < widths.length; i++) {
                centers.push(cursor + widths[i] / 2);
                cursor += widths[i] + settings.gap;
            }
            return { cardH, widths, centers, gap: settings.gap, loop: Math.max(cursor, 1) };
        };

        const nearest = (m, at) => {
            let best = 0;
            let bestDist = Infinity;
            for (let i = 0; i < m.centers.length; i++) {
                const dist = Math.abs(fcWrap(m.centers[i] - at, m.loop));
                if (dist < bestDist) {
                    bestDist = dist;
                    best = i;
                }
            }
            return best;
        };

        const snapPoint = (m, at) => {
            const i = nearest(m, at);
            return at + fcWrap(m.centers[i] - at, m.loop);
        };

        const remap = (from, to, at) => {
            const i = nearest(from, at);
            const offset = fcWrap(at - from.centers[i], from.loop);
            const cycles = Math.round((at - offset - from.centers[i]) / from.loop);
            return cycles * to.loop + to.centers[i] + offset * (to.widths[i] / from.widths[i]);
        };

        const step = (m, delta) => {
            let at = snapPoint(m, goal);
            let index = nearest(m, at);
            const n = m.centers.length;
            for (let k = 0; k < Math.abs(delta); k++) {
                const next = (index + (delta > 0 ? 1 : n - 1)) % n;
                const distance = delta > 0
                    ? m.widths[index] / 2 + m.gap + m.widths[next] / 2
                    : -(m.widths[next] / 2 + m.gap + m.widths[index] / 2);
                at += distance;
                index = next;
            }
            goal = at;
            mode = 'spring';
            dirty = true;
            start();
        };

        const goTo = (m, index) => {
            const i = ((index % m.centers.length) + m.centers.length) % m.centers.length;
            goal = goal + fcWrap(m.centers[i] - goal, m.loop);
            mode = 'spring';
            dirty = true;
            start();
        };

        const openFocus = (index) => {
            focus.index = index;
            focus.pending = -1;
            focus.target = 1;
            setFocusOpen(true);
            dirty = true;
            start();
        };

        const closeFocus = () => {
            focus.pending = -1;
            if (focus.target === 0) return false;
            focus.target = 0;
            setFocusOpen(false);
            dirty = true;
            start();
            return true;
        };

        const skipIntro = () => {
            if (introState.running) introState.t = 1;
        };

        const introEffects = () => {
            const t = introState.running ? introState.t : introState.done ? 1 : 0;
            const e = { sceneAlpha: 1, strength: 1, card: null };
            if (!introState.done && !introState.running) {
                e.sceneAlpha = 0;
                e.strength = 0;
                return e;
            }
            if (t >= 1) return e;
            const kind = introState.kind;
            if (kind === 'rise') {
                e.strength = fcEaseInOut((t - 0.3) / 0.65);
                e.card = rel => {
                    const delay = Math.min(Math.abs(rel) / (width * 0.6), 1) * 0.34;
                    const local = fcClamp01((t - delay) / 0.6);
                    return {
                        alpha: fcClamp01(local * 4),
                        x: 0,
                        y: (1 - fcEaseOutQuint(local)) * height * 0.62,
                        scale: 0.5 + 0.5 * fcEaseInOut((local - 0.18) / 0.82)
                    };
                };
            } else if (kind === 'bloom') {
                e.strength = fcEaseInOut((t - 0.2) / 0.8);
                e.card = rel => {
                    const delay = Math.min(Math.abs(rel) / (width * 0.6), 1) * 0.25;
                    const local = fcEaseOut((t - delay) / 0.55);
                    return { alpha: local, x: 0, y: 0, scale: 0.92 + 0.08 * local };
                };
            } else if (kind === 'spin') {
                e.sceneAlpha = fcEaseOut(t / 0.25);
                e.strength = fcEaseOut((t - 0.55) / 0.45);
            } else if (kind === 'deal') {
                e.strength = fcEaseOut((t - 0.45) / 0.5);
                e.card = rel => {
                    const spread = Math.min(Math.abs(rel) / (width * 0.6), 1) * 0.3;
                    const local = fcEaseOut((t - 0.12 - spread) / 0.5);
                    return { alpha: fcEaseOut((t - spread) / 0.12), x: -rel * (1 - local), y: 0, scale: 1 };
                };
            } else {
                e.sceneAlpha = fcEaseOut(t);
                e.strength = fcEaseOut(t);
            }
            return e;
        };

        const beginIntro = (settings, m) => {
            const kind = reducedMotion && settings.intro !== 'none' ? 'fade' : settings.intro;
            introState.kind = INTRO_DURATION[kind] ? kind : 'none';
            introState.running = introState.kind !== 'none';
            introState.done = !introState.running;
            introState.t = 0;
            if (introState.done) setRevealed(true);
            if (introState.kind === 'spin') {
                const distance = m.loop * 1.6 + width;
                pos = goal + distance;
                vel = -distance * 3;
                mode = 'spring';
            }
        };

        const resize = () => {
            width = Math.max(1, container.clientWidth);
            height = Math.max(1, container.clientHeight);
            renderer.dpr = Math.min(window.devicePixelRatio || 1, 2, Math.sqrt(PIXEL_BUDGET / (width * height)));
            renderer.setSize(width, height);
            target.setSize(Math.max(2, Math.round(width * renderer.dpr)), Math.max(2, Math.round(height * renderer.dpr)));
            lensUniforms.tScene.value = target.texture;
            dirty = true;
            start();
        };

        const frame = (now) => {
            raf = 0;
            if (!alive) return;
            const dt = Math.min(0.05, Math.max(0.001, (now - last) / 1000));
            last = now;
            if (!s || !slots.length) {
                if (visible) raf = requestAnimationFrame(frame);
                return;
            }

            const m = metrics(s);
            const n = slots.length;
            let animating = false;

            if (resnap) {
                goal = snapPoint(m, goal);
                pos = goal;
                vel = 0;
                resnap = false;
            } else if (layout && layout.loop !== m.loop) {
                pos = remap(layout, m, pos);
                goal = remap(layout, m, goal);
                pointer.startPos = pos + (pointer.x - pointer.startX);
                animating = true;
            }
            layout = m;

            if (!introState.running && !introState.done) {
                const allSettled = slots.every(slot => slot.loaded || slot.failed);
                if (allSettled || now - introState.readyAt > 3500) {
                    goal = snapPoint(m, goal);
                    pos = goal;
                    beginIntro(s, m);
                }
            }
            if (introState.running) {
                introState.t = Math.min(1, introState.t + dt / (INTRO_DURATION[introState.kind] || 1));
                if (introState.t >= 1) {
                    introState.running = false;
                    introState.done = true;
                    setRevealed(true);
                }
                animating = true;
            }

            if (mode === 'wheel' && now - wheelAt > 150) {
                goal = snapPoint(m, goal);
                mode = 'spring';
            }
            if (!pointer.dragging) {
                const spinning = introState.running && introState.kind === 'spin';
                const stiffness = spinning ? 9 : mode === 'wheel' ? 80 : 55;
                const damping = 2 * Math.sqrt(stiffness);
                const steps = Math.ceil(dt / (1 / 240));
                const h = dt / steps;
                for (let i = 0; i < steps; i++) {
                    const acc = stiffness * (goal - pos) - damping * vel;
                    vel += acc * h;
                    pos += vel * h;
                }
                if (Math.abs(goal - pos) < 0.05 && Math.abs(vel) < 0.5) {
                    pos = goal;
                    vel = 0;
                } else {
                    animating = true;
                }
            } else {
                animating = true;
            }

            if (Math.abs(pos) > m.loop * 8) {
                const shift = Math.round(pos / m.loop) * m.loop;
                pos -= shift;
                goal -= shift;
                pointer.startPos -= shift;
            }

            const current = nearest(m, pos);
            if (current !== activeIndex) {
                activeIndex = current;
                setActive(current);
            }

            if (focus.pending >= 0 && mode === 'spring' && Math.abs(goal - pos) < 1.5 && Math.abs(vel) < 30) {
                if (current === focus.pending) openFocus(current);
                else focus.pending = -1;
            }

            if (
                s.autoplay &&
                !reducedMotion &&
                introState.done &&
                focus.target === 0 &&
                focus.t < 0.01 &&
                !pointer.over &&
                !pointer.down &&
                !hasFocus &&
                mode === 'spring' &&
                Math.abs(goal - pos) < 1 &&
                now - interactedAt > 3000 &&
                now - autoplayAt > s.interval * 1000
            ) {
                autoplayAt = now;
                step(m, 1);
            }
            if (s.autoplay && !reducedMotion) animating = true;

            const travel = Math.abs(pos - lastPos) / dt;
            lastPos = pos;
            const energyTarget = reducedMotion ? 0 : Math.min(travel / 2600, 1);
            energy += (energyTarget - energy) * (1 - Math.exp(-dt / (energyTarget > energy ? 0.07 : 0.35)));
            if (energy > 0.001) animating = true;

            const liquidAmount = reducedMotion ? 0 : s.liquid;
            const push = Math.max(-1, Math.min(1, vel / 2200));
            const deformStiffness = 120;
            const deformDamping = 2 * Math.sqrt(deformStiffness) * 0.32;
            deformVel += (deformStiffness * (push - deform) - deformDamping * deformVel) * dt;
            deform += deformVel * dt;
            if (Math.abs(deform) > 0.0005 || Math.abs(deformVel) > 0.005) animating = true;

            const focusStiffness = 64;
            focus.v += (focusStiffness * (focus.target - focus.t) - 2 * Math.sqrt(focusStiffness) * focus.v) * dt;
            focus.t += focus.v * dt;
            if (Math.abs(focus.target - focus.t) < 0.0005 && Math.abs(focus.v) < 0.001) {
                focus.t = focus.target;
                focus.v = 0;
            } else {
                animating = true;
            }

            const focusAmount = fcClamp01(focus.t);
            const focusEase = fcEaseInOut(focusAmount);
            const focusW = focus.index >= 0 && focus.index < n ? m.widths[focus.index] : m.cardH;
            const focusScale = Math.max(1, Math.min(1.3, (height * 0.84) / m.cardH, (width * 0.92) / focusW));
            const nextLift = 1 + (focusScale - 1) * focusEase;
            if (Math.abs(nextLift - lift) > 0.0005) {
                lift = nextLift;
                container.style.setProperty('--flex-carousel-lift', lift.toFixed(4));
            }

            const effects = introEffects();

            const homeX = width / 2;
            const homeY = height / 2;
            const follow = s.followCursor && pointer.over && !pointer.dragging && !pointer.touch && focus.target === 0;
            const aimX = follow ? pointer.x : homeX;
            const aimY = follow ? pointer.y : homeY;
            if (!lens.ready) {
                lens.x = homeX;
                lens.y = homeY;
                lens.ready = true;
            }
            const lensK = 110;
            const lensC = 2 * Math.sqrt(lensK) * 0.8;
            lens.vx += (lensK * (aimX - lens.x) - lensC * lens.vx) * dt;
            lens.vy += (lensK * (aimY - lens.y) - lensC * lens.vy) * dt;
            lens.x += lens.vx * dt;
            lens.y += lens.vy * dt;
            if (Math.abs(aimX - lens.x) + Math.abs(aimY - lens.y) > 0.2 || Math.abs(lens.vx) + Math.abs(lens.vy) > 0.5) {
                animating = true;
            }

            const cardH = m.cardH;
            let halfW = (s.lensWidth * width) / 2;
            let halfH = (s.lensHeight * width) / 2;
            const squash = Math.abs(deform) * liquidAmount;
            halfW *= 1 + squash * 0.16;
            halfH *= 1 - squash * 0.08;
            const lensX = lens.x - deform * 14 * liquidAmount;

            for (let i = 0; i < n; i++) {
                const slot = slots[i];
                if (slot.loaded && slot.ready < 1) {
                    slot.ready = Math.min(1, slot.ready + dt / 0.45);
                    animating = true;
                }
            }

            const waiting = !introState.done;
            if (dirty || animating || pointer.dragging) {
                dirty = false;
                instances = [];
                const dpr = renderer.dpr;
                cardProgram.uniforms.uResolution.value = [width, height];
                cardProgram.uniforms.uDpr.value = dpr;
                cardProgram.uniforms.uRadius.value = s.radius;
                const shrink = 1 - fcClamp01(s.squeeze) * energy;
                const draws = [];
                for (let i = 0; i < n; i++) {
                    const w = m.widths[i];
                    const baseRel = fcWrap(m.centers[i] - pos, m.loop);
                    for (let k = -3; k <= 3; k++) {
                        const rel = baseRel + k * m.loop;
                        if (Math.abs(rel) - w / 2 > width + 40) continue;
                        const fx = effects.card ? effects.card(rel) : null;
                        let x = homeX + rel + (fx ? fx.x : 0);
                        let scale = shrink * (fx ? fx.scale : 1);
                        let alpha = fx ? fx.alpha : 1;
                        if (focusAmount > 0) {
                            if (i === focus.index && Math.abs(rel) < w) {
                                scale *= 1 + (focusScale - 1) * focusEase;
                            } else {
                                const order = Math.min(Math.abs(rel) / width, 1) * 0.25;
                                const part = fcEaseInOut(focusAmount * 1.25 - order);
                                x += Math.sign(rel) * part * width * 0.7;
                                alpha *= 1 - part;
                            }
                        }
                        const cw = w * scale;
                        if (alpha <= 0.001 || x + cw / 2 < -40 || x - cw / 2 > width + 40) continue;
                        draws.push({ i, rel, x, y: homeY + (fx ? fx.y : 0), cw, ch: cardH * scale, alpha });
                    }
                }
                draws.sort((a, b) => Math.abs(b.rel) - Math.abs(a.rel));
                let first = true;
                for (const draw of draws) {
                    const slot = slots[draw.i];
                    cardProgram.uniforms.tMap.value = slot.texture;
                    cardProgram.uniforms.uRect.value = [draw.x, draw.y, draw.cw + 2, draw.ch + 2];
                    cardProgram.uniforms.uSize.value = [draw.cw, draw.ch];
                    cardProgram.uniforms.uImage.value = slot.image;
                    cardProgram.uniforms.uAlpha.value = draw.alpha;
                    cardProgram.uniforms.uReady.value = slot.ready;
                    cardProgram.uniforms.uShift.value = reducedMotion ? 0 : Math.max(-1, Math.min(1, draw.rel / (width * 0.75)));
                    cardProgram.uniforms.uPlaceholder.value = slot.color;
                    renderer.render({ scene: cardMesh, target, clear: first });
                    first = false;
                    instances.push({
                        index: draw.i,
                        x0: draw.x - draw.cw / 2,
                        x1: draw.x + draw.cw / 2,
                        y0: draw.y - draw.ch / 2,
                        y1: draw.y + draw.ch / 2
                    });
                }
                if (first) {
                    renderer.bindFramebuffer(target);
                    gl.viewport(0, 0, target.width, target.height);
                    gl.clear(gl.COLOR_BUFFER_BIT);
                }
                renderer.bindFramebuffer();
                target.texture.bind();
                gl.generateMipmap(gl.TEXTURE_2D);

                lensUniforms.uResolution.value = [width, height];
                lensUniforms.uDpr.value = dpr;
                lensUniforms.uCenter.value = [lensX, lens.y];
                lensUniforms.uHalf.value = [Math.max(halfW, 1), Math.max(halfH, 1)];
                lensUniforms.uAngle.value = (s.tilt * Math.PI) / 180;
                lensUniforms.uExponent.value = 2 + Math.pow(1 - fcClamp01(s.roundness), 1.5) * 10;
                const spanW = Math.max(halfW, 1);
                const spanH = Math.max(halfH, 1);
                const inner = Math.max(4, s.reach * (spanW + spanH) * 0.5);
                lensUniforms.uInner.value = inner;
                lensUniforms.uOuter.value = inner * 1.6;
                lensUniforms.uFlow.value = s.bend * (spanW + spanH) * 0.45;
                lensUniforms.uCurl.value = s.curl === 'rise' ? 1 : s.curl === 'fall' ? -1 : 0;
                lensUniforms.uDispersion.value = s.dispersion * 0.12 * (1 + Math.abs(deform) * liquidAmount * 1.2);
                lensUniforms.uStrength.value = effects.strength * (1 - focusEase);
                lensUniforms.uSceneAlpha.value = effects.sceneAlpha;
                renderer.render({ scene: lensMesh });
            }

            let nextHover = '';
            if (pointer.over && !pointer.dragging && introState.done && s.focusOnClick) {
                const hit = instances.find(
                    inst => pointer.x >= inst.x0 && pointer.x <= inst.x1 && pointer.y >= inst.y0 && pointer.y <= inst.y1
                );
                if (focus.target > 0) nextHover = 'close';
                else if (hit) nextHover = 'open';
            }
            if (nextHover !== hover) {
                hover = nextHover;
                if (hover) container.setAttribute('data-hover', hover);
                else container.removeAttribute('data-hover');
            }

            if (visible && (animating || waiting || dirty || pointer.down)) {
                raf = requestAnimationFrame(frame);
            }
        };

        const start = () => {
            if (raf || !visible || !alive) return;
            last = performance.now();
            raf = requestAnimationFrame(frame);
        };

        const localPoint = (e) => {
            const rect = container.getBoundingClientRect();
            return [e.clientX - rect.left, e.clientY - rect.top];
        };

        const onPointerDown = (e) => {
            if (e.button !== undefined && e.button > 0) return;
            skipIntro();
            const [x, y] = localPoint(e);
            pointer.down = true;
            pointer.id = e.pointerId;
            pointer.touch = e.pointerType === 'touch';
            pointer.startX = x;
            pointer.startY = y;
            pointer.x = x;
            pointer.y = y;
            pointer.startPos = pos;
            pointer.dragging = false;
            pointer.samples = [{ x, t: performance.now() }];
            interactedAt = performance.now();
            if (Math.abs(vel) > 40) {
                goal = pos;
                vel = 0;
            }
            dirty = true;
            start();
        };

        const onPointerMove = (e) => {
            const [x, y] = localPoint(e);
            pointer.x = x;
            pointer.y = y;
            pointer.over = true;
            if (pointer.down && e.pointerId === pointer.id) {
                const dx = x - pointer.startX;
                const dy = y - pointer.startY;
                const slop = pointer.touch ? 10 : 5;
                if (!pointer.dragging) {
                    if (pointer.touch && Math.abs(dy) > slop && Math.abs(dy) > Math.abs(dx)) {
                        pointer.down = false;
                        return;
                    }
                    if (Math.abs(dx) > slop) {
                        pointer.dragging = true;
                        pointer.startX = x;
                        pointer.startPos = pos;
                        closeFocus();
                        try {
                            container.setPointerCapture(e.pointerId);
                        } catch {
                            pointer.dragging = true;
                        }
                        container.setAttribute('data-dragging', '');
                    }
                }
                if (pointer.dragging) {
                    pos = pointer.startPos - (x - pointer.startX);
                    goal = pos;
                    vel = 0;
                    const now = performance.now();
                    pointer.samples.push({ x, t: now });
                    while (pointer.samples.length > 2 && now - pointer.samples[0].t > 100) pointer.samples.shift();
                }
            }
            dirty = true;
            start();
        };

        const onPointerUp = (e) => {
            if (!pointer.down || e.pointerId !== pointer.id) return;
            pointer.down = false;
            container.removeAttribute('data-dragging');
            const m = metrics(s);
            interactedAt = performance.now();
            if (pointer.dragging) {
                pointer.dragging = false;
                const now = performance.now();
                const first = pointer.samples[0];
                const lastSample = pointer.samples[pointer.samples.length - 1];
                let velocity = 0;
                if (first && lastSample && lastSample.t > first.t && now - lastSample.t < 70) {
                    velocity = -((lastSample.x - first.x) / (lastSample.t - first.t)) * 1000;
                }
                vel = velocity;
                const landing = snapPoint(m, pos + velocity * 0.32);
                goal = landing;
                if (Math.abs(velocity) > 400 && Math.abs(landing - pos) < 1) step(m, velocity > 0 ? 1 : -1);
                mode = 'spring';
                start();
                return;
            }

            if (closeFocus()) return;
            const [x, y] = localPoint(e);
            const hit = instances.find(inst => x >= inst.x0 && x <= inst.x1 && y >= inst.y0 && y <= inst.y1);
            if (!hit) return;
            if (hit.index === activeIndex && Math.abs(goal - pos) < 2) {
                if (s.focusOnClick) openFocus(hit.index);
            } else {
                const rel = (hit.x0 + hit.x1) / 2 - width / 2;
                goal = snapPoint(m, pos + rel);
                mode = 'spring';
                if (s.focusOnClick) focus.pending = hit.index;
                start();
            }
        };

        const onPointerLeave = () => {
            pointer.over = false;
            dirty = true;
            start();
        };

        const onPointerCancel = () => {
            pointer.down = false;
            pointer.dragging = false;
            container.removeAttribute('data-dragging');
            goal = snapPoint(metrics(s), pos);
            mode = 'spring';
            start();
        };

        const onWheel = (e) => {
            if (e.ctrlKey) return;
            let dx = e.deltaX;
            let dy = e.deltaY;
            if (e.shiftKey && Math.abs(dx) < Math.abs(dy)) {
                dx = dy;
                dy = 0;
            }
            const unit = e.deltaMode === 1 ? 16 : e.deltaMode === 2 ? height : 1;
            const horizontal = Math.abs(dx) > Math.abs(dy);
            if (!horizontal && !s.captureWheel) return;
            e.preventDefault();
            skipIntro();
            interactedAt = performance.now();
            if (closeFocus()) return;
            const delta = Math.max(-120, Math.min(120, (horizontal ? dx : dy) * unit));
            goal += delta * 1.25;
            mode = 'wheel';
            wheelAt = performance.now();
            start();
        };

        const onKeyDown = (e) => {
            const m = metrics(s);
            if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                e.preventDefault();
                skipIntro();
                closeFocus();
                interactedAt = performance.now();
                step(m, 1);
            } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                e.preventDefault();
                skipIntro();
                closeFocus();
                interactedAt = performance.now();
                step(m, -1);
            } else if (e.key === 'Home') {
                e.preventDefault();
                closeFocus();
                goTo(m, 0);
            } else if (e.key === 'End') {
                e.preventDefault();
                closeFocus();
                goTo(m, slots.length - 1);
            } else if (e.key === 'Escape') {
                if (closeFocus()) e.preventDefault();
            } else if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                if (closeFocus() || activeIndex < 0) return;
                if (s.focusOnClick) openFocus(activeIndex);
            }
        };

        const onFocus = () => { hasFocus = true; };
        const onBlur = () => { hasFocus = false; };
        const onVisibility = () => { if (!document.hidden) start(); };

        container.addEventListener('pointerdown', onPointerDown);
        container.addEventListener('pointermove', onPointerMove);
        container.addEventListener('pointerup', onPointerUp);
        container.addEventListener('pointerleave', onPointerLeave);
        container.addEventListener('pointercancel', onPointerCancel);
        container.addEventListener('wheel', onWheel, { passive: false });
        container.addEventListener('keydown', onKeyDown);
        container.addEventListener('focus', onFocus);
        container.addEventListener('blur', onBlur);
        document.addEventListener('visibilitychange', onVisibility);

        const resizeObserver = new ResizeObserver(resize);
        resizeObserver.observe(container);
        const intersectionObserver = new IntersectionObserver(([entry]) => {
            visible = entry.isIntersecting;
            start();
        });
        intersectionObserver.observe(container);

        resize();
        setItems(items);
        updateCaptionsUI();

        cleanups.push(() => {
            alive = false;
            visible = false;
            cancelAnimationFrame(raf);
            resizeObserver.disconnect();
            intersectionObserver.disconnect();
            container.removeEventListener('pointerdown', onPointerDown);
            container.removeEventListener('pointermove', onPointerMove);
            container.removeEventListener('pointerup', onPointerUp);
            container.removeEventListener('pointerleave', onPointerLeave);
            container.removeEventListener('pointercancel', onPointerCancel);
            container.removeEventListener('wheel', onWheel);
            container.removeEventListener('keydown', onKeyDown);
            container.removeEventListener('focus', onFocus);
            container.removeEventListener('blur', onBlur);
            document.removeEventListener('visibilitychange', onVisibility);
            slots.forEach(slot => slot.dispose());
            slots = [];
            gl.getExtension('WEBGL_lose_context')?.loseContext();
            if (canvas.parentNode) canvas.parentNode.removeChild(canvas);
            if (captionEl && captionEl.parentNode) captionEl.parentNode.removeChild(captionEl);
            if (liveEl && liveEl.parentNode) liveEl.parentNode.removeChild(liveEl);
            delete container.dataset.rbInit;
        });
    });

    return () => cleanups.forEach(fn => fn());
}

/**
 * ============================================================================
 * SCROLL STACK (3D Card Stacking On Scroll)
 * ============================================================================
 */
export function initScrollStacks({ reduceMotion = false } = {}) {
    const cleanups = [];
    document.querySelectorAll('[data-scroll-stack]').forEach((stack) => {
        const slots = [...stack.querySelectorAll('[data-stack-slot]')];
        if (slots.length < 2) return;
        let frame = 0;
        const update = () => {
            frame = 0;
            if (window.innerWidth <= 768) {
                slots.forEach((slot) => {
                    slot.style.top = '';
                    slot.style.zIndex = '';
                    const card = slot.firstElementChild;
                    if (card) {
                        card.style.transform = '';
                        card.style.removeProperty('--scroll-stack-dim');
                    }
                });
                return;
            }
            const heights = slots.map(slot => slot.offsetHeight);
            // React Bits window-scroll geometry; keep tall cards fully readable.
            const top = Math.max(16, Math.min(innerHeight * 0.15, innerHeight - Math.max(...heights) - 90));
            const offset = 24;
            const tops = slots.map(slot => slot.getBoundingClientRect().top);
            const depths = slots.map(() => 0);
            for (let j = 1; j < slots.length; j++) {
                const pinBefore = top + (j - 1) * offset;
                const cover = Math.min(1, Math.max(0, (pinBefore + heights[j - 1] - tops[j]) / Math.max(1, heights[j - 1] - offset)));
                for (let i = 0; i < j; i++) depths[i] += cover;
            }
            slots.forEach((slot, i) => {
                slot.style.top = `${top + i * offset}px`;
                slot.style.zIndex = String(i + 1);
                const card = slot.firstElementChild;
                if (!card) return;
                const depth = reduceMotion ? 0 : depths[i];
                card.style.transform = depth < 0.0005 ? '' : `scale(${Math.max(0.8, 1 - depth * 0.05).toFixed(4)})`;
                card.style.setProperty('--scroll-stack-dim', (1 - Math.pow(0.8, depth)).toFixed(3));
            });
        };
        const schedule = () => { if (!frame) frame = requestAnimationFrame(update); };
        const observer = new ResizeObserver(schedule);
        slots.forEach(slot => observer.observe(slot));
        window.addEventListener('scroll', schedule, { passive: true });
        window.addEventListener('resize', schedule);
        update();
        cleanups.push(() => {
            cancelAnimationFrame(frame);
            observer.disconnect();
            window.removeEventListener('scroll', schedule);
            window.removeEventListener('resize', schedule);
        });
    });
    return () => cleanups.forEach(cleanup => cleanup());
}


/**
 * ============================================================================
 * PEEK RATING (Interactive Review Rating with Lift, Magnify & Tooltip)
 * ============================================================================
 */
export function initPeekRatings() {
    document.querySelectorAll('[data-peek-rating]').forEach((root) => {
        const row = root.querySelector('.peek-rating__row');
        const tip = root.querySelector('.peek-rating__tip');
        const stars = [...root.querySelectorAll('.peek-rating__star')];
        const ratingInput = document.querySelector('#ratingInput');
        const ratingDesc = document.querySelector('#ratingDescriptor');

        const labels = [
            '1 sao · Chưa hài lòng',
            '2 sao · Tạm ổn',
            '3 sao · Hài lòng',
            '4 sao · Rất tốt',
            '5 sao · Tuyệt vời & Hoàn hảo',
        ];

        let currentValue = Number(root.dataset.default || 5);

        const paint = (hoverIdx = null) => {
            const activeCount = hoverIdx !== null ? hoverIdx + 1 : currentValue;

            stars.forEach((star, i) => {
                const lift = star.querySelector('.peek-rating__lift');
                const glyph = star.querySelector('.peek-rating__glyph');
                const isLit = i < activeCount;

                if (glyph) glyph.setAttribute('data-lit', String(isLit));
                if (lift) {
                    if (hoverIdx !== null && i <= hoverIdx) {
                        lift.style.transform = `translateY(-8px) scale(${i === hoverIdx ? 1.2 : 1.05})`;
                    } else {
                        lift.style.transform = 'translateY(0) scale(1)';
                    }
                }
            });

            if (tip && hoverIdx !== null) {
                tip.textContent = labels[hoverIdx] || `${hoverIdx + 1} sao`;
                const star = stars[hoverIdx];
                if (star) {
                    const left = star.offsetLeft + star.offsetWidth / 2;
                    tip.style.transform = `translate(calc(${left}px - 50%), 0)`;
                    tip.setAttribute('data-show', 'true');
                }
            } else if (tip) {
                tip.setAttribute('data-show', 'false');
            }
        };

        stars.forEach((star, i) => {
            star.addEventListener('mouseenter', () => paint(i));
            star.addEventListener('keydown', (event) => {
                if (!['ArrowLeft', 'ArrowDown', 'ArrowRight', 'ArrowUp'].includes(event.key)) return;
                event.preventDefault();
                const direction = ['ArrowRight', 'ArrowUp'].includes(event.key) ? 1 : -1;
                const next = (i + direction + stars.length) % stars.length;
                stars[next].focus();
                stars[next].click();
            });
            star.addEventListener('click', () => {
                currentValue = i + 1;
                paint();
                stars.forEach((item, index) => item.setAttribute('aria-checked', String(index === i)));
                if (ratingInput) ratingInput.value = currentValue;
                if (ratingDesc) ratingDesc.textContent = labels[i];

                // Pop animation
                const glyph = star.querySelector('.peek-rating__glyph');
                if (glyph && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    gsap.fromTo(glyph, { scale: 1.4 }, { scale: 1, duration: 0.35, ease: 'back.out(2)' });
                }
            });
        });

        root.addEventListener('mouseleave', () => paint(null));
        paint(null);
    });
}


/**
 * ============================================================================
 * CODE SLOTS (OTP 6-Digit Slot Input with Smooth Caret, Cascade & Wash)
 * ============================================================================
 */
export function initCodeSlots() {
    document.querySelectorAll('[data-code-slots]').forEach((root) => {
        const input = root.querySelector('.code-slots__input');
        const slots = [...root.querySelectorAll('.code-slots__slot')];
        const grid = root.querySelector('.code-slots__row');
        const caret = root.querySelector('.code-slots__caret');
        const hiddenInput = root.closest('form')?.querySelector('#otp-hidden') || document.querySelector('#otp-hidden');

        if (!input || !slots.length) return;
        input.value = input.value || hiddenInput?.value || '';

        const updateState = () => {
            const val = input.value.replace(/\D/g, '').slice(0, slots.length);
            input.value = val;
            if (hiddenInput) hiddenInput.value = val;

            slots.forEach((slot, i) => {
                const digit = slot.querySelector('.code-slots__digit');
                const ch = val[i] || '';
                if (ch) {
                    slot.setAttribute('data-filled', 'true');
                    if (digit) digit.textContent = ch;
                } else {
                    slot.removeAttribute('data-filled');
                    if (digit) digit.textContent = '';
                }
                slot.toggleAttribute('data-active', i === val.length);
            });

            // Move caret
            if (caret) {
                const activeIdx = Math.min(input.selectionStart ?? val.length, slots.length - 1);
                const activeSlot = slots[activeIdx];
                if (activeSlot) {
                    caret.style.transform = `translateX(${activeSlot.offsetLeft - slots[0].offsetLeft}px)`;
                }
            }
        };

        const onInput = () => {
            if (grid) grid.dataset.status = 'idle';
            updateState();
        };
        const onFocus = () => caret?.setAttribute('data-show', '');
        const onBlur = () => caret?.removeAttribute('data-show');
        const onKeyDown = (e) => {
            if (e.key === 'Backspace' && !input.value) {
                updateState();
            }
        };

        input.addEventListener('input', onInput);
        input.addEventListener('focus', onFocus);
        input.addEventListener('blur', onBlur);
        input.addEventListener('keydown', onKeyDown);
        input.addEventListener('keyup', updateState);
        input.addEventListener('click', updateState);
        const observer = new ResizeObserver(updateState);
        observer.observe(root);
        window.addEventListener('pagehide', () => observer.disconnect(), { once: true });
        if (document.activeElement === input) onFocus();

        updateState();
    });
}


/**
 * ============================================================================
 * BRANCHED MENU (Curved SVG Lines for Admin & Staff Sidebar)
 * ============================================================================
 */
export function initBranchedMenus() {
    const cleanups = [];
    document.querySelectorAll('[data-branched-menu]').forEach((nav) => {
        const sections = [...nav.querySelectorAll('.branched-menu__section')];
        const marker = nav.querySelector('.branched-menu__marker');

        sections.forEach((sec) => {
            const head = sec.querySelector('.branched-menu__head');
            const tree = sec.querySelector('.branched-menu__tree');
            const items = [...sec.querySelectorAll('.branched-menu__item')];
            const stateKey = `royal-sidebar:${head?.querySelector('span')?.textContent.trim() || ''}`;

            try {
                const savedState = sessionStorage.getItem(stateKey);
                if (savedState !== null) sec.toggleAttribute('data-open', savedState === 'open');
            } catch (_) { /* Use the markup's default when storage is unavailable. */ }

            items.forEach((item) => {
                if (item.classList.contains('is-active')) item.setAttribute('aria-current', 'page');
            });

            // Generate curved SVG tree branches
            if (tree && items.length) {
                let existingSvg = tree.querySelector('.branched-menu__lines');
                if (!existingSvg) {
                    const trunk = 14;
                    const indent = 36;
                    const rowHeight = 38;
                    const radius = 10;
                    const PAD = 4;
                    const r = Math.min(radius, rowHeight / 2 - 2);
                    const endX = indent - 6;
                    const bodyH = PAD * 2 + items.length * rowHeight;
                    const rowY = k => PAD + k * rowHeight + rowHeight / 2;

                    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                    svg.setAttribute('class', 'branched-menu__lines');
                    svg.setAttribute('width', String(indent));
                    svg.setAttribute('height', String(bodyH));
                    svg.setAttribute('aria-hidden', 'true');

                    const basePath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    basePath.setAttribute('class', 'branched-menu__base');
                    basePath.setAttribute('d', `M ${trunk} 0 V ${rowY(items.length - 1) - r}`);
                    svg.appendChild(basePath);

                    items.forEach((item, k) => {
                        const branchPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                        branchPath.setAttribute('class', 'branched-menu__base');
                        branchPath.setAttribute('d', `M ${trunk} ${rowY(k) - r} A ${r} ${r} 0 0 0 ${trunk + r} ${rowY(k)} H ${endX}`);
                        svg.appendChild(branchPath);

                        const reachPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                        const reachLength = rowY(k) - r + (Math.PI * r) / 2 + (endX - trunk - r);
                        const isActive = item.classList.contains('is-active');
                        reachPath.setAttribute('class', 'branched-menu__reach');
                        reachPath.setAttribute('d', `M ${trunk} 0 V ${rowY(k) - r} A ${r} ${r} 0 0 0 ${trunk + r} ${rowY(k)} H ${endX}`);
                        reachPath.style.strokeDasharray = String(reachLength);
                        reachPath.style.strokeDashoffset = isActive ? '0' : String(reachLength);
                        svg.appendChild(reachPath);
                    });

                    tree.prepend(svg);
                }
            }

            if (head) {
                const body = sec.querySelector('.branched-menu__body');
                if (body) {
                    if (!body.id) body.id = `${nav.id || 'branched-menu'}-panel-${sections.indexOf(sec) + 1}`;
                    head.setAttribute('aria-controls', body.id);
                }
                if (body) body.inert = !sec.hasAttribute('data-open');
                head.setAttribute('aria-expanded', String(sec.hasAttribute('data-open')));
                const onToggle = () => {
                    const isOpen = sec.hasAttribute('data-open');
                    if (isOpen) sec.removeAttribute('data-open');
                    else sec.setAttribute('data-open', '');
                    try { sessionStorage.setItem(stateKey, isOpen ? 'closed' : 'open'); } catch (_) {}
                    head.setAttribute('aria-expanded', String(!isOpen));
                    if (body) body.inert = isOpen;
                    updateMarker();
                };
                head.addEventListener('click', onToggle);
                cleanups.push(() => head.removeEventListener('click', onToggle));
            }
        });

        sections.forEach((sec) => {
            const open = sec.hasAttribute('data-open');
            const head = sec.querySelector('.branched-menu__head');
            const body = sec.querySelector('.branched-menu__body');
            head?.setAttribute('aria-expanded', String(open));
            if (body) body.inert = !open;
        });

        const updateMarker = () => {
            if (!marker) return;
            const activeHead = sections.find((sec) => sec.hasAttribute('data-open') && sec.querySelector('.branched-menu__item.is-active'))?.querySelector('.branched-menu__head');
            if (activeHead) {
                marker.style.top = `${activeHead.offsetTop + 10}px`;
                marker.setAttribute('data-on', '');
            } else {
                marker.removeAttribute('data-on');
            }
        };

        updateMarker();
        if (window.ResizeObserver) {
            const observer = new ResizeObserver(updateMarker);
            observer.observe(nav);
            cleanups.push(() => observer.disconnect());
        }
    });

    return () => cleanups.forEach((cleanup) => cleanup());
}


/**
 * ============================================================================
 * AUTHENTIC IPHONE 16 PRO CONTROLLER (Responsive across Mobile, Tablet, Desktop)
 * ============================================================================
 */
export function initIPhoneMockup() {
    const iphoneShowcase = document.querySelector('[data-iphone-showcase]');
    if (!iphoneShowcase) return;

    const rows = [...iphoneShowcase.querySelectorAll('[data-iphone-trigger]')];
    const tabs = [...iphoneShowcase.querySelectorAll('[data-iphone-tab]')];
    const screens = [...iphoneShowcase.querySelectorAll('[data-iphone-screen]')];
    const viewport = iphoneShowcase.querySelector('.iphone16-pro__viewport');

    const switchScreen = (screenId) => {
        screens.forEach((s) => {
            const active = s.dataset.iphoneScreen === screenId;
            s.classList.toggle('is-active', active);
            if (active && viewport) {
                viewport.setAttribute('data-theme', s.dataset.statusbarTheme || 'light');
            }
        });
        rows.forEach((r) => {
            const active = r.dataset.iphoneTrigger === screenId;
            r.classList.toggle('is-active', active);
            r.setAttribute('aria-selected', String(active));
        });
        tabs.forEach((t) => {
            const active = t.dataset.iphoneTab === screenId;
            t.classList.toggle('is-active', active);
            t.setAttribute('aria-pressed', String(active));
        });
    };

    window.__switchIPhoneScreen = switchScreen;

    rows.forEach((row) => {
        row.addEventListener('click', () => {
            const screenId = row.dataset.iphoneTrigger;
            switchScreen(screenId);
            if (typeof window.__stepperSyncScreen === 'function') {
                window.__stepperSyncScreen(screenId);
            }
        });
    });

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const screenId = tab.dataset.iphoneTab;
            switchScreen(screenId);
            if (typeof window.__stepperSyncScreen === 'function') {
                window.__stepperSyncScreen(screenId);
            }
        });
    });

    // Digital Room Key Tap-to-Unlock with Haptic Wave Animation
    const unlockBtn = iphoneShowcase.querySelector('[data-wallet-unlock]');
    const unlockText = iphoneShowcase.querySelector('[data-wallet-unlock-text]');
    const unlockStatus = iphoneShowcase.querySelector('[data-wallet-status]');
    if (unlockBtn && unlockText) {
        unlockBtn.addEventListener('click', () => {
            const unlocked = unlockBtn.classList.toggle('is-unlocked');
            if (unlocked) {
                unlockText.innerHTML = '<i class="bi bi-check-circle-fill" style="color:#34c759;margin-right:6px;"></i>Đã mở khóa cửa Suite 802';
                if (unlockStatus) unlockStatus.textContent = 'Cửa phòng đã mở · Vui lòng vào phòng';
                unlockBtn.style.background = 'rgba(52, 199, 89, 0.18)';
                unlockBtn.style.borderColor = '#34c759';
            } else {
                unlockText.innerHTML = '<i class="bi bi-broadcast" style="margin-right:6px;"></i>Chạm để mở khóa phòng';
                if (unlockStatus) unlockStatus.textContent = 'Giữ gần tay nắm cửa thông minh';
                unlockBtn.style.background = '';
                unlockBtn.style.borderColor = '';
            }
        });
    }

    // Interactive AI Prompt Chips
    const chatMsgContainer = iphoneShowcase.querySelector('.m-chat-messages');
    iphoneShowcase.querySelectorAll('[data-chat-prompt]').forEach((chip) => {
        chip.addEventListener('click', () => {
            const text = chip.textContent.trim();
            if (!chatMsgContainer) return;

            const userBubble = document.createElement('div');
            userBubble.className = 'm-chat-msg is-user';
            userBubble.textContent = text;
            chatMsgContainer.appendChild(userBubble);

            const botBubble = document.createElement('div');
            botBubble.className = 'm-chat-msg is-bot';
            botBubble.innerHTML = `<em>Đang tra cứu dịch vụ...</em>`;
            chatMsgContainer.appendChild(botBubble);

            chatMsgContainer.scrollTop = chatMsgContainer.scrollHeight;

            setTimeout(() => {
                if (text.includes('sân bay')) {
                    botBubble.textContent = 'Bạn vui lòng liên hệ lễ tân để kiểm tra dịch vụ đưa đón sân bay và xác nhận lịch trình nhé.';
                } else if (text.includes('Spa')) {
                    botBubble.textContent = 'Dạ Lotus Spa mở cửa từ 09:00 - 22:00. Quý khách được tặng voucher giảm 20% khi đặt phòng trực tuyến ạ.';
                } else {
                    botBubble.textContent = 'Dạ yêu cầu của quý khách đã được chuyển tới lễ tân. Chúng tôi luôn sẵn lòng phục vụ 24/7!';
                }
                chatMsgContainer.scrollTop = chatMsgContainer.scrollHeight;
            }, 600);
        });
    });
}
