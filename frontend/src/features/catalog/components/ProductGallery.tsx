import type { CatalogProduct } from '../api';

type ProductSlide = CatalogProduct['gallery'][number];

interface ProductGalleryProps {
  activeSlide: number;
  onImageError: (url: string) => void;
  onNextSlide: () => void;
  onPrevSlide: () => void;
  onSelectSlide: (index: number) => void;
  slides: ProductSlide[];
}

export const ProductGallery = ({
  activeSlide,
  onImageError,
  onNextSlide,
  onPrevSlide,
  onSelectSlide,
  slides,
}: ProductGalleryProps) => (
  <div className="catalog-detail-hero">
    {slides.length > 0 ? (
      <div className="catalog-slider">
        <div className="catalog-slider__viewport">
          {slides.map((slide, index) => (
            <img
              key={slide.url + index}
              src={slide.url}
              alt={slide.alt}
              className={`catalog-slider__image${index === activeSlide ? ' is-active' : ''}`}
              width={720}
              height={540}
              loading={index === 0 ? 'eager' : 'lazy'}
              decoding="async"
              fetchPriority={index === 0 ? 'high' : 'low'}
              onError={() => onImageError(slide.url)}
            />
          ))}
        </div>
        {slides.length > 1 && (
          <>
            <button
              type="button"
              className="catalog-slider__control catalog-slider__control--prev"
              onClick={onPrevSlide}
              aria-label="Image precedente"
            >
              ‹
            </button>
            <button
              type="button"
              className="catalog-slider__control catalog-slider__control--next"
              onClick={onNextSlide}
              aria-label="Image suivante"
            >
              ›
            </button>
            <div className="catalog-slider__dots" role="tablist">
              {slides.map((slide, index) => (
                <button
                  key={slide.url + index}
                  type="button"
                  className={`catalog-slider__dot${index === activeSlide ? ' is-active' : ''}`}
                  onClick={() => onSelectSlide(index)}
                  aria-label={`Afficher l'image ${index + 1}`}
                  aria-pressed={index === activeSlide}
                />
              ))}
            </div>
          </>
        )}
      </div>
    ) : (
      <div className="catalog-product-card__placeholder catalog-detail-hero__placeholder">Produit</div>
    )}
  </div>
);
