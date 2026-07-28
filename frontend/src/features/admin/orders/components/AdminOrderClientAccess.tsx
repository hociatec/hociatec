import { Link } from 'react-router';

type AdminOrderClientAccessProps = {
  orderId: number;
  userId?: number;
};

export const AdminOrderClientAccess = ({ orderId, userId }: AdminOrderClientAccessProps) => (
  <section className="rounded-xl border border-brand-100 bg-white p-5 shadow-sm">
    <div className="mb-4">
      <h2 className="text-lg font-semibold text-brand-900">Accès client</h2>
      <p className="mt-1 text-sm text-stone-500">
        Raccourcis utiles pour ouvrir le client ou vérifier sa vue commande.
      </p>
    </div>
    <div className="flex flex-wrap gap-4">
      {userId ? (
        <Link
          className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
          to={`/admin/customers/${userId}`}
        >
          Ouvrir la fiche client
        </Link>
      ) : null}
      <Link
        className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
        to={`/orders/${orderId}`}
      >
        Ouvrir la vue client de cette commande
      </Link>
    </div>
  </section>
);
