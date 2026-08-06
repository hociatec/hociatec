import { useEffect, useRef, useState, type KeyboardEvent } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

import { HomeFeaturedServiceCard } from '@/features/home/homeContent';
import type { QuoteServiceDto } from '@/features/quotes/publicApi';
import { useInterval } from '@/shared/hooks/useInterval';

const SLIDE_INTERVAL_MS = 5200;

export const HomeFeaturedServicesCarousel = ({ services }: { services: QuoteServiceDto[] }) => {
  const [activeIndex, setActiveIndex] = useState(0);
  const [isPaused, setIsPaused] = useState(false);
  const trackRef = useRef<HTMLDivElement | null>(null);
  const canSlide = services.length > 1;

  const shouldAutoPlay = canSlide && !isPaused;
  const prefersReducedMotion =
    typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const intervalDelay = shouldAutoPlay && !prefersReducedMotion ? SLIDE_INTERVAL_MS : null;

  useInterval(
    () => {
      setActiveIndex((current) => (current + 1) % services.length);
    },
    intervalDelay,
  );

  useEffect(() => {
    const track = trackRef.current;
    const slide = track?.querySelector<HTMLElement>(
      `[data-service-slide="${activeIndex.toString()}"]`,
    );

    if (!track || !slide) {
      return;
    }

    track.scrollTo({
      left: slide.offsetLeft - track.offsetLeft,
      behavior: 'smooth',
    });
  }, [activeIndex]);

  const move = (direction: 1 | -1) => {
    setActiveIndex((current) => (current + direction + services.length) % services.length);
  };

  const handleKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
    if (!canSlide) return;

    if (event.key === 'ArrowLeft') {
      event.preventDefault();
      move(-1);
      return;
    }

    if (event.key === 'ArrowRight') {
      event.preventDefault();
      move(1);
      return;
    }

    if (event.key === 'Home') {
      event.preventDefault();
      setActiveIndex(0);
    }

    if (event.key === 'End') {
      event.preventDefault();
      setActiveIndex(services.length - 1);
    }
  };

  return (
    <div
      className="home-services-carousel"
      onMouseEnter={() => setIsPaused(true)}
      onMouseLeave={() => setIsPaused(false)}
      onFocus={() => setIsPaused(true)}
      onBlur={() => setIsPaused(false)}
      onKeyDown={handleKeyDown}
      role="region"
      tabIndex={0}
      aria-label="Sélection des services mis en avant"
    >
      <span className="sr-only" aria-live="polite">
        {canSlide ? `Slide ${activeIndex + 1} sur ${services.length}` : 'Service en vedette'}
      </span>
      <div className="home-services-carousel__viewport">
        <div ref={trackRef} className="home-services-carousel__track">
          {services.map((service, index) => (
            <div
              key={service.id}
              className="home-services-carousel__slide"
              data-service-slide={index}
            >
              <HomeFeaturedServiceCard service={service} />
            </div>
          ))}
        </div>
      </div>

      {canSlide && (
        <div className="home-services-carousel__controls" aria-label="Services mis en avant">
          <button
            type="button"
            className="home-services-carousel__button"
            onClick={() => move(-1)}
            aria-label="Service précédent"
          >
            <ChevronLeft aria-hidden="true" />
          </button>
          <div className="home-services-carousel__dots">
            {services.map((service, index) => (
              <button
                key={service.id}
                type="button"
                className={`home-services-carousel__dot${index === activeIndex ? ' is-active' : ''}`}
                onClick={() => setActiveIndex(index)}
                aria-label={`Afficher ${service.title}`}
                aria-current={index === activeIndex ? 'true' : undefined}
              />
            ))}
          </div>
          <button
            type="button"
            className="home-services-carousel__button"
            onClick={() => move(1)}
            aria-label="Service suivant"
          >
            <ChevronRight aria-hidden="true" />
          </button>
        </div>
      )}
    </div>
  );
};
