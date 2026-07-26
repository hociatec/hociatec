import { Link } from 'react-router-dom';

type MarketingTemplateActionsCardProps = {
  templateId: number;
  isTransactionalView: boolean;
  isMarketingTemplate: boolean;
};

export const MarketingTemplateActionsCard = ({
  templateId,
  isTransactionalView,
  isMarketingTemplate,
}: MarketingTemplateActionsCardProps) => {
  const editPath = isTransactionalView
    ? `/admin/transactional-emails/${templateId}/edit`
    : `/admin/marketing/templates/${templateId}/edit`;
  const newTemplatePath = isTransactionalView
    ? '/admin/transactional-emails/new'
    : '/admin/marketing/templates/new';

  return (
    <div className="rounded-xl border border-brand-100 bg-white p-6 shadow-sm">
      <h2 className="text-xl font-semibold text-brand-900">Actions rapides</h2>
      <div className="mt-4 space-y-3 text-sm">
        {isMarketingTemplate ? (
          <div className="rounded-2xl border border-brand-100 bg-brand-50 px-4 py-3">
            <p className="m-0 font-semibold text-stone-700">Créer une campagne avec ce modèle</p>
            <Link
              to={`/admin/marketing/new?templateId=${templateId}`}
              className="mt-3 inline-flex rounded-lg border border-brand-200 px-3 py-2 text-xs font-semibold text-stone-700 transition hover:border-brand-600 hover:text-brand-900"
            >
              Ouvrir
            </Link>
          </div>
        ) : null}
        <div className="rounded-2xl border border-brand-100 bg-brand-50 px-4 py-3">
          <p className="m-0 font-semibold text-stone-700">Modifier ce modèle</p>
          <Link
            to={editPath}
            className="mt-3 inline-flex rounded-lg border border-brand-200 px-3 py-2 text-xs font-semibold text-stone-700 transition hover:border-brand-600 hover:text-brand-900"
          >
            Ouvrir
          </Link>
        </div>
        <div className="rounded-2xl border border-brand-100 bg-brand-50 px-4 py-3">
          <p className="m-0 font-semibold text-stone-700">
            Dupliquer manuellement dans un nouveau modèle
          </p>
          <Link
            to={newTemplatePath}
            className="mt-3 inline-flex rounded-lg border border-brand-200 px-3 py-2 text-xs font-semibold text-stone-700 transition hover:border-brand-600 hover:text-brand-900"
          >
            Ouvrir
          </Link>
        </div>
      </div>
    </div>
  );
};
