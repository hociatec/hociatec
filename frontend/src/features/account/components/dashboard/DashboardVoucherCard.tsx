import type { LoyaltyBalanceDto } from '@/features/loyalty/api/loyaltyApi';
import {
  formatEuroCents,
  formatFrenchNumber,
  formatOptionalEuroCents,
} from '@/shared/lib/formatters';
import { DashboardPanel } from './DashboardPanel';

export const DashboardVoucherCard = ({
  conversionEuroCents,
  conversionPoints,
  conversionState,
  convertPoints,
  hasConvertiblePoints,
  loyalty,
  onConvert,
  onConvertPointsChange,
}: {
  conversionEuroCents: number;
  conversionPoints: number;
  conversionState: 'idle' | 'saving';
  convertPoints: string;
  hasConvertiblePoints: boolean;
  loyalty: LoyaltyBalanceDto;
  onConvert: () => void;
  onConvertPointsChange: (value: string) => void;
}) => (
  <DashboardPanel heading="Fidélité" id="loyalty-title" className="client-dashboard__loyalty">
    <div className="client-dashboard__charts">
      <div className="client-dashboard__chart">
        <div className="client-dashboard__chart-label">
          <span>Points disponibles</span>
          <strong>{formatFrenchNumber(loyalty.points)} pts</strong>
        </div>
        <progress
          className="client-dashboard__chart-progress"
          value={loyalty.points}
          max={Math.max(1000, loyalty.points)}
        />
      </div>
      <div className="client-dashboard__chart">
        <div className="client-dashboard__chart-label">
          <span>Valeur convertible</span>
          <strong>{formatOptionalEuroCents(loyalty.euroCents)}</strong>
        </div>
        <progress
          className="client-dashboard__chart-progress"
          value={loyalty.euroCents / 100}
          max={Math.max(50, loyalty.euroCents / 100)}
        />
      </div>
    </div>
    <div className="client-dashboard__conversion">
      <label>
        <span>Points à convertir</span>
        <input
          type="number"
          min={hasConvertiblePoints ? 100 : 0}
          step={100}
          value={convertPoints}
          onChange={(event) => onConvertPointsChange(event.target.value)}
          readOnly={!hasConvertiblePoints}
        />
      </label>
      <div>
        <strong>{formatEuroCents(conversionEuroCents)}</strong>
        <span>en bon de réduction</span>
      </div>
      <button
        type="button"
        onClick={onConvert}
        disabled={
          conversionState === 'saving' || conversionPoints <= 0 || conversionPoints > loyalty.points
        }
      >
        {conversionState === 'saving' ? 'Conversion...' : 'Convertir'}
      </button>
    </div>
  </DashboardPanel>
);
