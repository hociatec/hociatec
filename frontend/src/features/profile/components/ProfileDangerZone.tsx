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

export const ProfileDangerZone = ({ isDeleting, onConfirmDelete }: { isDeleting: boolean; onConfirmDelete: () => void }) => (
  <section className="profile-danger-zone" aria-labelledby="profile-danger-heading">
    <div>
      <h2 id="profile-danger-heading">Zone sensible</h2>
      <p>La suppression du compte est définitive et nécessite une confirmation.</p>
    </div>
    <AlertDialog>
      <AlertDialogTrigger asChild>
        <button type="button" className="profile-action-button profile-action-button--danger" disabled={isDeleting}>
          Supprimer mon compte
        </button>
      </AlertDialogTrigger>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Confirmer la suppression</AlertDialogTitle>
          <AlertDialogDescription>
            Cette action entraîne la suppression de votre compte et de vos accès aux services Hociatec. Un membre de notre équipe vous recontactera pour finaliser la procédure.
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
  </section>
);
