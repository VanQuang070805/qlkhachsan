'use client';

import React, { useEffect, useRef } from 'react';
import { gsap } from 'gsap';

export interface BlurTextProps {
  text: string;
  delay?: number;
  className?: string;
  animateBy?: 'words' | 'letters';
  direction?: 'top' | 'bottom';
  duration?: number;
}

const BlurText: React.FC<BlurTextProps> = ({
  text,
  delay = 55,
  className = '',
  animateBy = 'words',
  direction = 'top',
  duration = 0.65,
}) => {
  const containerRef = useRef<HTMLParagraphElement>(null);
  const segments = animateBy === 'words' ? text.split(' ') : text.split('');

  useEffect(() => {
    const el = containerRef.current;
    if (!el) return;

    const spans = el.querySelectorAll('.rb-blur-segment');
    if (!spans.length) return;

    const yOffset = direction === 'top' ? -14 : 14;
    const tween = gsap.fromTo(
      spans,
      {
        opacity: 0,
        y: yOffset,
        filter: 'blur(10px)',
      },
      {
        opacity: 1,
        y: 0,
        filter: 'blur(0px)',
        duration,
        stagger: delay / 1000,
        ease: 'power3.out',
        clearProps: 'filter,transform',
      },
    );

    return () => {
      tween.kill();
    };
  }, [text, delay, animateBy, direction, duration]);

  return (
    <p ref={containerRef} className={`inline-flex flex-wrap justify-center ${className}`}>
      {segments.map((segment, index) => (
        <span
          key={index}
          className="rb-blur-segment inline-block will-change-[transform,filter,opacity]"
        >
          {segment === ' ' ? '\u00A0' : segment}
          {animateBy === 'words' && index < segments.length - 1 && '\u00A0'}
        </span>
      ))}
    </p>
  );
};

export default BlurText;
