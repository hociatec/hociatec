import { formatEuroCents, formatOptionalFrenchDate } from '@/shared/lib/formatters';
import { formatOrderStatusFr, type OrderDto } from '@/features/orders/api';
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
  paying: boolean;
};

export const OrderDetailSummary = ({ order, onPay, onCancel, paying }: OrderDetailSummaryProps) => (
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
      <div className="text-sm capitalize">Statut: {order.statusLabel ?? formatOrderStatusFr(order.status)}</div>
      {order.status === 'pending' ? (
        <>
          <button
            type="button"
            className="inline-flex items-center rounded-full bg-brand-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-60"
            onClick={onPay}
            disabled={paying}
          >
            {paying ? 'Redirection...' : 'Régler cette commande'}
          </button>
          <AlertDialog>
            <AlertDialogTrigger asChild>
              <button type="button" className="text-red-600 underline">Annuler la commande</button>
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
                <AlertDialogAction onClick={onCancel}>Oui, annuler</AlertDialogAction>
              </AlertDialogFooter>
            </AlertDialogContent>
          </AlertDialog>
        </>
      ) : null}
    </div>
  </div>
);
