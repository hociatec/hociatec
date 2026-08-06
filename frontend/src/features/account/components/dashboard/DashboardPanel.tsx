import type { ReactNode } from 'react';

export const DashboardPanel = ({
  children,
  className = '',
  heading,
  id,
}: {
  children: ReactNode;
  className?: string;
  heading: string;
  id?: string;
}) => (
  <section className={`client-dashboard__panel ${className}`.trim()} aria-labelledby={id}>
    <div className="client-dashboard__panel-heading">
      <h2 id={id}>{heading}</h2>
    </div>
    {children}
  </section>
);

export const DashboardStatusNotice = ({ state }: { state: 'loading' | 'success' | 'error' }) => {
  if (state === 'loading')
    return (
      <div className="client-dashboard__notice" role="status" aria-live="polite">
        Chargement de votre espace...
      </div>
    );
  if (state === 'error')
    return (
      <div className="client-dashboard__notice client-dashboard__notice--warning">
        Certaines informations n’ont pas pu être chargées. Les accès rapides restent disponibles.
      </div>
    );
  return null;
};
