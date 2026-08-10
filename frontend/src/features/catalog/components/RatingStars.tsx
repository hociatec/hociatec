type RatingStarsProps = {
  value: number;
  compact?: boolean;
  decorative?: boolean;
};

export const RatingStars = ({ value, compact = false, decorative = false }: RatingStarsProps) => (
  <div
    className={`catalog-review-stars${compact ? ' catalog-review-stars--compact' : ''}`}
    aria-hidden={decorative ? 'true' : undefined}
    aria-label={decorative ? undefined : `${value.toFixed(1)} sur 5`}
  >
    {[1, 2, 3, 4, 5].map((index) => (
      <span key={index} className={index <= Math.round(value) ? 'is-active' : ''}>
        ★
      </span>
    ))}
  </div>
);
