import type { AuthUser } from '../../../shared/types/auth';
import { formatGender } from '../lib/profileFormatters';

type ProfileInformationCardProps = {
  user: AuthUser;
  formattedRoles: string;
  formattedBirthDate: string;
  onStartEditing: () => void;
};

export const ProfileInformationCard = ({
  user,
  formattedRoles,
  formattedBirthDate,
  onStartEditing,
}: ProfileInformationCardProps) => (
  <section
    className="profile-card profile-card--highlight profile-card--main"
    aria-labelledby="profile-info-heading"
  >
    <div className="profile-card__header">
      <h2 id="profile-info-heading">Informations personnelles</h2>
        <button
          type="button"
          className="profile-card__edit"
          onClick={onStartEditing}
          aria-haspopup="dialog"
        >
          Modifier
        </button>
    </div>

    <div className="profile-detail-groups">
      <dl className="profile-details">
        <div><dt>Nom complet</dt><dd>{user.firstName} {user.lastName}</dd></div>
        <div><dt>Date de naissance</dt><dd>{formattedBirthDate}</dd></div>
        <div><dt>Sexe</dt><dd>{formatGender(user.gender)}</dd></div>
      </dl>
      <dl className="profile-details">
        <div><dt>Adresse e-mail</dt><dd>{user.email}</dd></div>
        <div><dt>Téléphone</dt><dd>{user.phoneNumber}</dd></div>
        <div><dt>Rôle</dt><dd>{formattedRoles}</dd></div>
      </dl>
    </div>
  </section>
);
