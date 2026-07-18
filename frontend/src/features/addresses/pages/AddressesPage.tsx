import { useEffect, useState, type Dispatch, type SetStateAction } from 'react';

import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useToast } from '@/shared/components/ui/toast';
import {
  createAddress,
  deleteAddress,
  fetchMyAddresses,
  setDefaultAddress,
  updateAddress,
  type AddressDto,
} from '../api';

type AddressFormState = {
  name: string;
  address: string;
  postalCode: string;
  city: string;
  company: string;
  companySiren: string;
  companyVatNumber: string;
  purchaseOrderNumber: string;
  isDefault?: boolean;
};

const emptyForm = (): AddressFormState => ({
  name: '',
  address: '',
  postalCode: '',
  city: '',
  company: '',
  companySiren: '',
  companyVatNumber: '',
  purchaseOrderNumber: '',
  isDefault: false,
});

const toAddressPayload = (form: AddressFormState) => ({
  name: form.name,
  address: form.address,
  postalCode: form.postalCode,
  city: form.city,
  company: form.company.trim() || undefined,
  companySiren: form.companySiren.trim() || undefined,
  companyVatNumber: form.companyVatNumber.trim() || undefined,
  purchaseOrderNumber: form.purchaseOrderNumber.trim() || undefined,
  isDefault: form.isDefault,
});

export const AddressesPage = () => {
  useDocumentTitle('Mes adresses');
  const { show } = useToast();
  const [items, setItems] = useState<AddressDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [savingId, setSavingId] = useState<number | 'new' | null>(null);

  const [form, setForm] = useState<AddressFormState>(emptyForm());
  const [editing, setEditing] = useState<AddressDto | null>(null);
  const [editForm, setEditForm] = useState<AddressFormState>(emptyForm());

  useEffect(() => {
    void fetchMyAddresses()
      .then(setItems)
      .catch((e) => show(e.message, { variant: 'error' }))
      .finally(() => setLoading(false));
  }, [show]);

  const resetForm = () => setForm(emptyForm());

  const handleCreate = () => {
    if (!form.name || !form.address || !form.postalCode || !form.city) return;
    setSavingId('new');
    void createAddress(toAddressPayload(form))
      .then((a) => {
        setItems((prev) => [...prev, a].sort((x, y) => Number(y.isDefault) - Number(x.isDefault)));
        resetForm();
        show('Adresse ajoutée', { variant: 'success' });
      })
      .catch((e) => show(e.message, { variant: 'error' }))
      .finally(() => setSavingId(null));
  };

  const openEdit = (addr: AddressDto) => {
    setEditing(addr);
    setEditForm({
      name: addr.name,
      address: addr.address,
      postalCode: addr.postalCode,
      city: addr.city,
      company: addr.company ?? '',
      companySiren: addr.companySiren ?? '',
      companyVatNumber: addr.companyVatNumber ?? '',
      purchaseOrderNumber: addr.purchaseOrderNumber ?? '',
    });
  };

  const handleUpdate = () => {
    if (!editing) return;
    setSavingId(editing.id);
    void updateAddress(editing.id, toAddressPayload(editForm))
      .then((a) => {
        setItems((prev) => prev.map((it) => (it.id === a.id ? a : it)));
        show('Adresse mise à jour', { variant: 'success' });
        setEditing(null);
      })
      .catch((e) => show(e.message, { variant: 'error' }))
      .finally(() => setSavingId(null));
  };

  const handleDelete = (id: number) => {
    setSavingId(id);
    void deleteAddress(id)
      .then(() => {
        setItems((prev) => prev.filter((it) => it.id !== id));
        show('Adresse supprimée', { variant: 'success' });
      })
      .catch((e) => show(e.message, { variant: 'error' }))
      .finally(() => setSavingId(null));
  };

  const renderB2bFields = (
    currentForm: AddressFormState,
    setCurrentForm: Dispatch<SetStateAction<AddressFormState>>,
  ) => (
    <>
      <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
        <p className="text-sm font-semibold text-slate-900">Informations de facturation professionnelle</p>
        <p className="mt-1 text-sm text-slate-600">
          Optionnel. À renseigner si la facture doit comporter des mentions société.
        </p>
      </div>
      <label className="grid gap-2 text-sm font-medium text-slate-700">
        Société
        <input
          className="rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-slate-400"
          placeholder="Nom de la société"
          value={currentForm.company}
          onChange={(e) => setCurrentForm((prev) => ({ ...prev, company: e.target.value }))}
        />
      </label>
      <div className="grid gap-4 md:grid-cols-2">
        <label className="grid gap-2 text-sm font-medium text-slate-700">
          SIREN client
          <input
            className="rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-slate-400"
            placeholder="123456789"
            value={currentForm.companySiren}
            onChange={(e) => setCurrentForm((prev) => ({ ...prev, companySiren: e.target.value }))}
          />
        </label>
        <label className="grid gap-2 text-sm font-medium text-slate-700">
          TVA intracommunautaire
          <input
            className="rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-slate-400"
            placeholder="FR12345678901"
            value={currentForm.companyVatNumber}
            onChange={(e) => setCurrentForm((prev) => ({ ...prev, companyVatNumber: e.target.value }))}
          />
        </label>
      </div>
      <label className="grid gap-2 text-sm font-medium text-slate-700">
        Bon de commande
        <input
          className="rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-slate-400"
          placeholder="BC-2026-001"
          value={currentForm.purchaseOrderNumber}
          onChange={(e) => setCurrentForm((prev) => ({ ...prev, purchaseOrderNumber: e.target.value }))}
        />
      </label>
    </>
  );

  return (
    <SiteLayout>
      <div className="mx-auto max-w-6xl px-4 py-10">
        <div className="mb-8 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Mon espace</p>
            <h1 className="text-3xl font-semibold text-slate-900">Mes adresses</h1>
            <p className="mt-2 max-w-2xl text-slate-600">
              Gérez vos adresses de livraison et les informations de facturation utilisées sur vos commandes.
            </p>
          </div>
        </div>

        <div className="grid gap-6 lg:grid-cols-[1.05fr_1.4fr]">
          <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div className="mb-5">
              <h2 className="text-xl font-semibold text-slate-900">Ajouter une adresse</h2>
              <p className="mt-1 text-sm text-slate-600">
                Renseignez une adresse claire pour faciliter la préparation, la livraison et la facturation.
              </p>
            </div>
            <div className="grid gap-4">
              <label className="grid gap-2 text-sm font-medium text-slate-700">
                Nom
                <input
                  className="rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-slate-400"
                  placeholder="Prénom Nom ou Libellé du lieu"
                  value={form.name}
                  onChange={(e) => setForm({ ...form, name: e.target.value })}
                />
              </label>
              <label className="grid gap-2 text-sm font-medium text-slate-700">
                Adresse
                <input
                  className="rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-slate-400"
                  placeholder="Numéro et rue"
                  value={form.address}
                  onChange={(e) => setForm({ ...form, address: e.target.value })}
                />
              </label>
              <div className="grid gap-4 md:grid-cols-2">
                <label className="grid gap-2 text-sm font-medium text-slate-700">
                  Code postal
                  <input
                    className="rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-slate-400"
                    placeholder="92000"
                    value={form.postalCode}
                    onChange={(e) => setForm({ ...form, postalCode: e.target.value })}
                  />
                </label>
                <label className="grid gap-2 text-sm font-medium text-slate-700">
                  Ville
                  <input
                    className="rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-slate-400"
                    placeholder="Nanterre"
                    value={form.city}
                    onChange={(e) => setForm({ ...form, city: e.target.value })}
                  />
                </label>
              </div>
              {renderB2bFields(form, setForm)}
              <label className="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                <input
                  type="checkbox"
                  checked={Boolean(form.isDefault)}
                  onChange={(e) => setForm({ ...form, isDefault: e.target.checked })}
                />
                Définir comme adresse par défaut
              </label>
              <button
                type="button"
                className="inline-flex items-center justify-center rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                onClick={handleCreate}
                disabled={savingId === 'new'}
              >
                {savingId === 'new' ? 'Ajout en cours...' : 'Ajouter l’adresse'}
              </button>
            </div>
          </section>

          <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div className="mb-5 flex items-end justify-between gap-4">
              <div>
                <h2 className="text-xl font-semibold text-slate-900">Adresses enregistrées</h2>
                <p className="mt-1 text-sm text-slate-600">
                  Sélectionnez, modifiez ou supprimez vos adresses existantes.
                </p>
              </div>
            </div>

            {loading ? (
              <p className="text-slate-600">Chargement...</p>
            ) : items.length === 0 ? (
              <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
                Aucune adresse enregistrée.
              </div>
            ) : (
              <ul className="list-none space-y-4">
                {items.map((it) => (
                  <li key={it.id} className="rounded-2xl border border-slate-200 p-4">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                      <div className="space-y-1">
                        <div className="flex flex-wrap items-center gap-2">
                          <h3 className="text-base font-semibold text-slate-900">{it.name}</h3>
                          {it.isDefault && (
                            <span className="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                              Par défaut
                            </span>
                          )}
                        </div>
                        {it.company ? <p className="text-sm font-medium text-slate-800">{it.company}</p> : null}
                        <p className="text-sm text-slate-700">{it.address}</p>
                        <p className="text-sm text-slate-600">
                          {it.postalCode} {it.city}
                        </p>
                        {it.companySiren ? <p className="text-xs text-slate-500">SIREN : {it.companySiren}</p> : null}
                        {it.companyVatNumber ? <p className="text-xs text-slate-500">TVA : {it.companyVatNumber}</p> : null}
                        {it.purchaseOrderNumber ? (
                          <p className="text-xs text-slate-500">Bon de commande : {it.purchaseOrderNumber}</p>
                        ) : null}
                      </div>
                      <div className="flex flex-wrap gap-2 lg:justify-end">
                        <button
                          type="button"
                          className="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-500"
                          onClick={() => openEdit(it)}
                        >
                          Modifier
                        </button>
                        <button
                          type="button"
                          className="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-500 disabled:cursor-not-allowed disabled:opacity-50"
                          onClick={() =>
                            void setDefaultAddress(it.id)
                              .then(() => {
                                setItems((prev) => prev.map((p) => ({ ...p, isDefault: p.id === it.id })));
                                show('Adresse par défaut définie', { variant: 'success' });
                              })
                              .catch((e) => show(e.message, { variant: 'error' }))
                          }
                          disabled={it.isDefault}
                        >
                          Définir par défaut
                        </button>
                        <button
                          type="button"
                          className="inline-flex items-center rounded-full border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 transition hover:border-red-400 disabled:cursor-not-allowed disabled:opacity-50"
                          onClick={() => handleDelete(it.id)}
                          disabled={savingId === it.id}
                        >
                          Supprimer
                        </button>
                      </div>
                    </div>
                  </li>
                ))}
              </ul>
            )}
          </section>
        </div>

        {editing && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4 py-6">
            <div className="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl">
              <h3 className="text-2xl font-semibold text-slate-900">Modifier l’adresse</h3>
              <p className="mt-1 text-sm text-slate-600">
                Mettez à jour les informations sans perdre vos commandes existantes.
              </p>
              <div className="mt-6 grid gap-4">
                <label className="grid gap-2 text-sm font-medium text-slate-700">
                  Nom
                  <input
                    className="rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-slate-400"
                    placeholder="Nom"
                    value={editForm.name}
                    onChange={(e) => setEditForm({ ...editForm, name: e.target.value })}
                  />
                </label>
                <label className="grid gap-2 text-sm font-medium text-slate-700">
                  Adresse
                  <input
                    className="rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-slate-400"
                    placeholder="Adresse"
                    value={editForm.address}
                    onChange={(e) => setEditForm({ ...editForm, address: e.target.value })}
                  />
                </label>
                <div className="grid gap-4 md:grid-cols-2">
                  <label className="grid gap-2 text-sm font-medium text-slate-700">
                    Code postal
                    <input
                      className="rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-slate-400"
                      placeholder="Code postal"
                      value={editForm.postalCode}
                      onChange={(e) => setEditForm({ ...editForm, postalCode: e.target.value })}
                    />
                  </label>
                  <label className="grid gap-2 text-sm font-medium text-slate-700">
                    Ville
                    <input
                      className="rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-slate-400"
                      placeholder="Ville"
                      value={editForm.city}
                      onChange={(e) => setEditForm({ ...editForm, city: e.target.value })}
                    />
                  </label>
                </div>
                {renderB2bFields(editForm, setEditForm)}
              </div>
              <div className="mt-6 flex flex-wrap justify-end gap-3">
                <button
                  type="button"
                  className="inline-flex items-center justify-center rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-500"
                  onClick={() => setEditing(null)}
                >
                  Annuler
                </button>
                <button
                  type="button"
                  className="inline-flex items-center justify-center rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                  onClick={handleUpdate}
                  disabled={savingId === editing.id}
                >
                  {savingId === editing.id ? 'Enregistrement...' : 'Enregistrer'}
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </SiteLayout>
  );
};
