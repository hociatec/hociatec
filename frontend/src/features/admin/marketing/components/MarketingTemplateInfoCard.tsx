import type { MarketingTemplate } from '../api';

type MarketingTemplateInfoCardProps = {
  template: MarketingTemplate;
  description: string;
};

export const MarketingTemplateInfoCard = ({
  template,
  description,
}: MarketingTemplateInfoCardProps) => (
  <div className="rounded-xl border border-brand-100 bg-white p-6 shadow-sm">
    <h2 className="text-xl font-semibold text-brand-900">Informations</h2>
    <div className="mt-4 grid gap-4 md:grid-cols-2">
      <div>
        <div className="text-xs font-semibold uppercase tracking-[0.18em] text-stone-400">Nom</div>
        <div className="mt-2 text-sm text-stone-800">{template.name}</div>
      </div>
      <div>
        <div className="text-xs font-semibold uppercase tracking-[0.18em] text-stone-400">Slug</div>
        <div className="mt-2 text-sm text-stone-800">{template.slug}</div>
      </div>
      <div className="md:col-span-2">
        <div className="text-xs font-semibold uppercase tracking-[0.18em] text-stone-400">Objet</div>
        <div className="mt-2 text-sm text-stone-800">{template.subjectTemplate}</div>
      </div>
      <div className="md:col-span-2">
        <div className="text-xs font-semibold uppercase tracking-[0.18em] text-stone-400">
          Description d’usage
        </div>
        <div className="mt-2 text-sm text-stone-600">{description}</div>
      </div>
    </div>
  </div>
);
