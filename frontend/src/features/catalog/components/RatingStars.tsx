type RatingStarsProps = {
  value: number;
  compact?: boolean;
};

export const RatingStars = ({ value, compact = false }: RatingStarsProps) => (
  <div
    className={`catalog-review-stars${compact ? ' catalog-review-stars--compact' : ''}`}
    aria-label={`${value.toFixed(1)} sur 5`}
  >
    {[1, 2, 3, 4, 5].map((index) => (
      <span key={index} className={index <= Math.round(value) ? 'is-active' : ''}>
        ★
      </span>
    ))}
  </div>
);
