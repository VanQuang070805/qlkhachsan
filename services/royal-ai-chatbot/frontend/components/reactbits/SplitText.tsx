'use client';

import React, { useRef, useEffect } from 'react';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText as GSAPSplitText } from 'gsap/SplitText';

gsap.registerPlugin(ScrollTrigger, GSAPSplitText);

export interface SplitTextProps {
  text: string;
  className?: string;
  delay?: number;
  duration?: number;
  ease?: string;
  splitType?: 'chars' | 'words' | 'lines' | 'words, chars';
  from?: gsap.TweenVars;
  to?: gsap.TweenVars;
  threshold?: number;
  rootMargin?: string;
  textAlign?: React.CSSProperties['textAlign'];
  onLetterAnimationComplete?: () => void;
}

const SplitText: React.FC<SplitTextProps> = ({
  text,
  className = '',
  delay = 45,
  duration = 0.65,
  ease = 'power3.out',
  splitType = 'chars',
  from = { opacity: 0, y: 28 },
  to = { opacity: 1, y: 0 },
  threshold = 0.1,
  rootMargin = '-80px',
  textAlign = 'center',
  onLetterAnimationComplete,
}) => {
  const ref = useRef<HTMLParagraphElement>(null);
  const animationCompletedRef = useRef(false);

  useEffect(() => {
    const el = ref.current;
    if (!el || animationCompletedRef.current) return;

    const reduceMotion =
      typeof window !== 'undefined' &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduceMotion) return;

    const absoluteLines = splitType === 'lines';
    if (absoluteLines) el.style.position = 'relative';

    const splitter = new GSAPSplitText(el, {
      type: splitType,
      absolute: absoluteLines,
      linesClass: 'rb-split-line',
    });

    let targets: Element[] = [];
    switch (splitType) {
      case 'lines':
        targets = splitter.lines;
        break;
      case 'words':
        targets = splitter.words;
        break;
      case 'words, chars':
        targets = [...splitter.words, ...splitter.chars];
        break;
      default:
        targets = splitter.chars;
    }

    targets.forEach((t) => {
      (t as HTMLElement).style.willChange = 'transform, opacity';
    });

    const startPct = (1 - threshold) * 100;
    const tl = gsap.timeline({
      scrollTrigger: {
        trigger: el,
        start: `top ${startPct}%`,
        toggleActions: 'play none none none',
        once: true,
      },
      smoothChildTiming: true,
      onComplete: () => {
        animationCompletedRef.current = true;
        gsap.set(targets, {
          ...to,
          clearProps: 'willChange',
          immediateRender: true,
        });
        onLetterAnimationComplete?.();
      },
    });

    tl.set(targets, { ...from, immediateRender: false, force3D: true });
    tl.to(targets, {
      ...to,
      duration,
      ease,
      stagger: delay / 1000,
      force3D: true,
    });

    return () => {
      tl.kill();
      gsap.killTweensOf(targets);
      splitter.revert();
    };
  }, [
    text,
    delay,
    duration,
    ease,
    splitType,
    from,
    to,
    threshold,
    rootMargin,
    onLetterAnimationComplete,
  ]);

  return (
    <p
      ref={ref}
      className={`rb-split-parent inline-block overflow-hidden whitespace-normal ${className}`}
      style={{ textAlign, wordWrap: 'break-word' }}
    >
      {text}
    </p>
  );
};

export default SplitText;
