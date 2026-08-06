import { parseNonNegativeInteger } from '@/shared/lib/parsers';

type QuoteQuantityControlProps = {
  productName: string;
  quantity: number;
  onDecrease: () => void;
  onIncrease: () => void;
  onChange: (quantity: number) => void;
};

export const QuoteQuantityControl = ({
  productName,
  quantity,
  onDecrease,
  onIncrease,
  onChange,
}: QuoteQuantityControlProps) => (
  <div className="inline-flex items-center gap-2">
    <button
      type="button"
      className="px-2 py-1 border rounded"
      aria-label={`Diminuer la quantité de ${productName}`}
      onClick={onDecrease}
    >
      -
    </button>
      <input
        type="number"
        min={0}
        className="w-16 text-center border rounded py-1"
        aria-label={`Quantité de ${productName}`}
        value={quantity}
        onChange={(event) => {
        const nextQuantity = parseNonNegativeInteger(event.target.value, 0);
        onChange(nextQuantity);
      }}
    />
    <button
      type="button"
      className="px-2 py-1 border rounded"
      aria-label={`Augmenter la quantité de ${productName}`}
      onClick={onIncrease}
    >
      +
    </button>
  </div>
);
