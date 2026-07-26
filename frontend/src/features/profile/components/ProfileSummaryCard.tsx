export const ProfileSummaryCard = ({ initials, name, email }: { initials: string; name: string; email: string }) => (
  <aside className="profile-summary-card" aria-label="Résumé du profil">
    <span className="profile-avatar" aria-hidden="true">{initials}</span>
    <div>
      <strong>{name}</strong>
      <span>{email}</span>
    </div>
  </aside>
);
