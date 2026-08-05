import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { StableContent } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { AddressCard } from '@/features/addresses/components/AddressCard';
import { AddressEditDialog } from '@/features/addresses/components/AddressEditDialog';
import { useAddressesPage } from '@/features/addresses/hooks/useAddressesPage';

import '@/features/addresses/AddressesPage.css';

export const AddressesPage = () => {
  useDocumentTitle('Mes adresses');
  const page = useAddressesPage();

  return (
    <SiteLayout>
      <main className="mx-auto max-w-6xl px-4 py-10">
        <header className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <h1 className="text-3xl font-semibold text-brand-900">Mes adresses</h1>
            <p className="mt-2 max-w-2xl text-stone-600">
              Gérez vos adresses de livraison et les informations de facturation utilisées sur vos
              commandes.
            </p>
          </div>
          <button
            type="button"
            className="address-submit shrink-0"
            onClick={page.openCreate}
            aria-haspopup="dialog"
          >
            Ajouter une adresse
          </button>
        </header>
        <div className="grid gap-6">
          <section className="rounded-xl border border-brand-100 bg-white p-6 shadow-sm">
            <div className="mb-5">
              <h2 className="text-xl font-semibold text-brand-900">Adresses enregistrées</h2>
              <p className="mt-1 text-sm text-stone-600">
                Sélectionnez, modifiez ou supprimez vos adresses existantes.
              </p>
            </div>
            <StableContent
              loading={page.loading}
              hasContent={page.items.length > 0 || !page.loading}
              loadingLabel="Chargement..."
            >
              {page.items.length === 0 ? (
                <div className="rounded-2xl border border-dashed border-brand-100 bg-brand-50 px-4 py-8 text-center text-stone-600">
                  Aucune adresse enregistrée.
                </div>
              ) : (
                <ul className="list-none space-y-4">
                  {page.items.map((address) => (
                    <AddressCard
                      key={address.id}
                      address={address}
                      deleting={page.savingId === address.id}
                      onDelete={() => page.handleDelete(address.id)}
                      onEdit={() => page.openEdit(address)}
                      onSetDefault={() => page.handleSetDefault(address.id)}
                    />
                  ))}
                </ul>
              )}
            </StableContent>
          </section>
        </div>
        {page.creating && (
          <AddressEditDialog
            mode="create"
            form={page.form}
            saving={page.savingId === 'new'}
            setForm={page.setForm}
            onClose={page.closeCreate}
            onSubmit={page.handleCreate}
          />
        )}
        {page.editing && (
          <AddressEditDialog
            form={page.editForm}
            saving={page.savingId === page.editing.id}
            setForm={page.setEditForm}
            onClose={page.closeEdit}
            onSubmit={page.handleUpdate}
          />
        )}
      </main>
    </SiteLayout>
  );
};
