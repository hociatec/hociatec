import { formatEuroCents, formatOptionalFrenchDate } from '@/shared/lib/formatters';
import type { OrderDto } from '@/features/orders/api';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/shared/components/ui/alert-dialog';

type OrderDetailSummaryProps = {
  order: OrderDto;
  onPay: () => void;
  onCancel: () => void;
  canCancel: boolean;
  canPay: boolean;
  cancelling: boolean;
  paying: boolean;
};

export const OrderDetailSummary = ({
  canCancel,
  canPay,
  cancelling,
  order,
  onPay,
  onCancel,
  paying,
}: OrderDetailSummaryProps) => (
  <div className="flex items-center justify-between">
    <div>
      <div className="font-medium">Commande {order.number}</div>
      <div className="text-sm text-gray-600">Passée le {formatOptionalFrenchDate(order.createdAt)}</div>
      {order.appliedPromotion ? (
        <div className="mt-2 text-sm text-green-700">Réduction appliquée: {order.appliedPromotion.name}</div>
      ) : null}
    </div>
    <div className="space-y-2 text-right">
      {typeof order.subtotalPriceCents === 'number' && (order.discountAmountCents ?? 0) > 0 ? (
        <div className="text-sm text-gray-600">
          <div>Sous-total: {formatEuroCents(order.subtotalPriceCents)}</div>
          <div className="font-semibold text-emerald-700">Remise: - {formatEuroCents(order.discountAmountCents ?? 0)}</div>
        </div>
      ) : null}
      <div className="font-semibold">{formatEuroCents(order.totalPriceCents)}</div>
      <div className="text-sm capitalize">Statut: {order.statusLabel}</div>
      {canPay || canCancel ? (
        <>
          {canPay ? (
            <button
              type="button"
              className="inline-flex items-center rounded-full bg-brand-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-60"
              onClick={onPay}
              disabled={paying}
              aria-busy={paying || undefined}
            >
              {paying ? 'Préparation du paiement...' : 'Régler cette commande'}
            </button>
          ) : null}
          {canCancel ? (
            <AlertDialog>
              <AlertDialogTrigger asChild>
                <button
                  type="button"
                  className="text-red-600 underline disabled:cursor-not-allowed disabled:opacity-60"
                  disabled={cancelling}
                  aria-busy={cancelling || undefined}
                >
                  {cancelling ? 'Annulation...' : 'Annuler la commande'}
                </button>
              </AlertDialogTrigger>
              <AlertDialogContent>
                <AlertDialogHeader>
                  <AlertDialogTitle>Confirmer l'annulation</AlertDialogTitle>
                  <AlertDialogDescription>
                    Voulez-vous annuler cette commande en attente ? Cette action est irréversible.
                  </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                  <AlertDialogCancel>Non</AlertDialogCancel>
                  <AlertDialogAction onClick={onCancel} disabled={cancelling}>
                    Oui, annuler
                  </AlertDialogAction>
                </AlertDialogFooter>
              </AlertDialogContent>
            </AlertDialog>
          ) : null}
        </>
      ) : null}
    </div>
  </div>
);
