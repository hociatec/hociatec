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
} from '../../../shared/components/ui/alert-dialog';

export const ProfileDangerZone = ({
  isDeleting,
  isRevokingSessions,
  onConfirmDelete,
  onConfirmRevokeAllSessions,
}: {
  isDeleting: boolean;
  isRevokingSessions: boolean;
  onConfirmDelete: () => void;
  onConfirmRevokeAllSessions: () => void;
}) => (
  <section className="profile-danger-zone" aria-labelledby="profile-danger-heading">
    <div>
      <h2 id="profile-danger-heading">Zone sensible</h2>
      <p>Vous pouvez révoquer tous vos accès ou demander la suppression définitive du compte.</p>
    </div>
    <div className="profile-danger-zone__actions">
      <AlertDialog>
        <AlertDialogTrigger asChild>
          <button
            type="button"
            className="profile-action-button profile-action-button--warning"
            disabled={isRevokingSessions}
          >
            Révoquer tous les accès
          </button>
        </AlertDialogTrigger>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Révoquer toutes les sessions</AlertDialogTitle>
            <AlertDialogDescription>
              Tous vos accès en cours seront fermés sur le site web et sur iPhone. Vous devrez vous reconnecter ensuite.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={isRevokingSessions}>Annuler</AlertDialogCancel>
            <AlertDialogAction onClick={onConfirmRevokeAllSessions} disabled={isRevokingSessions}>
              {isRevokingSessions ? 'Révocation...' : 'Confirmer'}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
      <AlertDialog>
        <AlertDialogTrigger asChild>
          <button
            type="button"
            className="profile-action-button profile-action-button--danger"
            disabled={isDeleting}
          >
            Supprimer mon compte
          </button>
        </AlertDialogTrigger>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Confirmer la suppression</AlertDialogTitle>
            <AlertDialogDescription>
              Cette action entraîne la suppression de votre compte et de vos accès aux services
              Hociatec. Un membre de notre équipe vous recontactera pour finaliser la procédure.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={isDeleting}>Annuler</AlertDialogCancel>
            <AlertDialogAction onClick={onConfirmDelete} disabled={isDeleting}>
              {isDeleting ? 'Suppression...' : 'Confirmer'}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  </section>
);
