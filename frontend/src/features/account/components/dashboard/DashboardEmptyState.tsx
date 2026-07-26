export const DashboardEmptyState = ({ children }: { children: React.ReactNode }) => (
  <div className="client-dashboard__calm-state">
    <strong>Rien d'urgent à traiter.</strong>
    <p>{children}</p>
  </div>
);
