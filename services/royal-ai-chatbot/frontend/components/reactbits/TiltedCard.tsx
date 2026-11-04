'use client';

import React, { useRef } from 'react';
import { gsap } from 'gsap';

export interface TiltedCardProps extends React.PropsWithChildren {
  className?: string;
  rotateAmplitude?: number;
  scaleOnHover?: number;
}

const TiltedCard: React.FC<TiltedCardProps> = ({
  children,
  className = '',
  rotateAmplitude = 8,
  scaleOnHover = 1.02,
}) => {
  const ref = useRef<HTMLDivElement>(null);

  const handleMouseMove: React.MouseEventHandler<HTMLDivElement> = (e) => {
    if (!ref.current) return;
    const rect = ref.current.getBoundingClientRect();
    const offsetX = e.clientX - rect.left - rect.width / 2;
    const offsetY = e.clientY - rect.top - rect.height / 2;

    const rotationX = (offsetY / (rect.height / 2)) * -rotateAmplitude;
    const rotationY = (offsetX / (rect.width / 2)) * rotateAmplitude;

    gsap.to(ref.current, {
      rotateX: rotationX,
      rotateY: rotationY,
      scale: scaleOnHover,
      duration: 0.35,
      ease: 'power2.out',
      transformPerspective: 900,
    });
  };

  const handleMouseLeave = () => {
    if (!ref.current) return;
    gsap.to(ref.current, {
      rotateX: 0,
      rotateY: 0,
      scale: 1,
      duration: 0.55,
      ease: 'power3.out',
    });
  };

  return (
    <div
      ref={ref}
      onMouseMove={handleMouseMove}
      onMouseLeave={handleMouseLeave}
      className={`will-change-transform [transform-style:preserve-3d] ${className}`}
    >
      {children}
    </div>
  );
};

export default TiltedCard;
