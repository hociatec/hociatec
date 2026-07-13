import { useEffect, useState } from 'react';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import {
  createAddress,
  deleteAddress,
  fetchMyAddresses,
  setDefaultAddress,
  updateAddress,
  type AddressDto,
} from '../api';
import { useToast } from '@/shared/components/ui/toast';

export const AddressesPage = () => {
  useDocumentTitle('Mes adresses');
  const { show } = useToast();
  const [items, setItems] = useState<AddressDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [savingId, setSavingId] = useState<number | 'new' | null>(null);

  const [form, setForm] = useState({ name: '', address: '', postalCode: '', city: '', isDefault: false });
  const [editing, setEditing] = useState<AddressDto | null>(null);
  const [editForm, setEditForm] = useState({ name: '', address: '', postalCode: '', city: '' });

  useEffect(() => {
    void fetchMyAddresses()
      .then(setItems)
      .catch((e) => show(e.message, { variant: 'error' }))
      .finally(() => setLoading(false));
  }, []);

  const resetForm = () => setForm({ name: '', address: '', postalCode: '', city: '', isDefault: false });

  const handleCreate = () => {
    if (!form.name || !form.address || !form.postalCode || !form.city) return;
    setSavingId('new');
    void createAddress({
      name: form.name,
      address: form.address,
      postalCode: form.postalCode,
      city: form.city,
      isDefault: form.isDefault,
    })
      .then((a) => {
        setItems((prev) => [...prev, a].sort((x, y) => Number(y.isDefault) - Number(x.isDefault)));
        resetForm();
        show("Adresse ajoutée", { variant: 'success' });
      })
      .catch((e) => show(e.message, { variant: 'error' }))
      .finally(() => setSavingId(null));
  };

  const openEdit = (addr: AddressDto) => {
    setEditing(addr);
    setEditForm({ name: addr.name, address: addr.address, postalCode: addr.postalCode, city: addr.city });
  };

  const handleUpdate = () => {
    if (!editing) return;
    const payload = { name: editForm.name, address: editForm.address, postalCode: editForm.postalCode, city: editForm.city };
    setSavingId(editing.id);
    void updateAddress(editing.id, payload)
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

  return (
    <SiteLayout>
      <div className="container" style={{ padding: '2rem 1rem' }}>
        <h1 style={{ fontSize: '1.5rem', fontWeight: 600, marginBottom: '1rem' }}>Mes adresses</h1>

        <section style={{ marginBottom: '2rem' }}>
          <h2 style={{ fontSize: '1.1rem', fontWeight: 600, marginBottom: '0.75rem' }}>Ajouter une adresse</h2>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 2fr 1fr 1fr auto auto', gap: '0.5rem', alignItems: 'center' }}>
            <input placeholder="Nom" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
            <input placeholder="Adresse" value={form.address} onChange={(e) => setForm({ ...form, address: e.target.value })} />
            <input placeholder="Code postal" value={form.postalCode} onChange={(e) => setForm({ ...form, postalCode: e.target.value })} />
            <input placeholder="Ville" value={form.city} onChange={(e) => setForm({ ...form, city: e.target.value })} />
            <label style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
              <input type="checkbox" checked={form.isDefault} onChange={(e) => setForm({ ...form, isDefault: e.target.checked })} />
              <span>Par défaut</span>
            </label>
            <button type="button" className="hero__button hero__button--secondary" onClick={handleCreate} disabled={savingId === 'new'}>
              {savingId === 'new' ? 'Ajout...' : 'Ajouter'}
            </button>
          </div>
        </section>

        <section>
          <h2 style={{ fontSize: '1.1rem', fontWeight: 600, marginBottom: '0.5rem' }}>Mes adresses enregistrées</h2>
          {loading ? (
            <p>Chargement...</p>
          ) : items.length === 0 ? (
            <p>Aucune adresse enregistrée.</p>
          ) : (
            <ul style={{ display: 'grid', gap: '0.75rem' }}>
              {items.map((it) => (
                <li key={it.id} style={{ display: 'grid', gridTemplateColumns: '1.5fr 2fr 1fr 1fr auto', gap: '0.5rem', alignItems: 'center' }}>
                  <div>
                    <div style={{ fontWeight: 600 }}>
                      {it.name} {it.isDefault && <span style={{ marginLeft: '0.5rem', fontSize: '0.85em' }}>(par défaut)</span>}
                    </div>
                    <div>{it.address}</div>
                  </div>
                  <div />
                  <div>{it.postalCode}</div>
                  <div>{it.city}</div>
                  <div style={{ display: 'flex', gap: '0.5rem', justifyContent: 'flex-end' }}>
                    <button type="button" className="catalog-cart-button" onClick={() => openEdit(it)}>
                      Modifier
                    </button>
                    <button type="button" className="catalog-cart-button" onClick={() => void setDefaultAddress(it.id).then(() => { setItems((prev) => prev.map((p) => ({ ...p, isDefault: p.id === it.id }))); show('Adresse par défaut définie', { variant: 'success' }); }).catch((e) => show(e.message, { variant: 'error' }))} disabled={it.isDefault}>
                      Définir par défaut
                    </button>
                    <button type="button" className="catalog-cart-button catalog-cart-button--remove" onClick={() => handleDelete(it.id)} disabled={savingId === it.id}>
                      Supprimer
                    </button>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </section>

        {editing && (
          <div role="dialog" aria-modal="true" style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.3)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
            <div style={{ background: '#fff', padding: '1rem', borderRadius: 8, width: 'min(600px, 90vw)' }}>
              <h3 style={{ fontSize: '1.2rem', marginBottom: '1rem' }}>Modifier l'adresse</h3>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr', gap: '0.5rem' }}>
                <input placeholder="Nom" value={editForm.name} onChange={(e) => setEditForm({ ...editForm, name: e.target.value })} />
                <input placeholder="Adresse" value={editForm.address} onChange={(e) => setEditForm({ ...editForm, address: e.target.value })} />
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.5rem' }}>
                  <input placeholder="Code postal" value={editForm.postalCode} onChange={(e) => setEditForm({ ...editForm, postalCode: e.target.value })} />
                  <input placeholder="Ville" value={editForm.city} onChange={(e) => setEditForm({ ...editForm, city: e.target.value })} />
                </div>
              </div>
              <div style={{ display: 'flex', gap: '0.5rem', marginTop: '1rem', justifyContent: 'flex-end' }}>
                <button type="button" className="profile-form__button profile-form__button--ghost" onClick={() => setEditing(null)}>Annuler</button>
                <button type="button" className="profile-form__button profile-form__button--primary" onClick={handleUpdate} disabled={savingId === editing.id}>{savingId === editing.id ? 'Sauvegarde...' : 'Enregistrer'}</button>
              </div>
            </div>
          </div>
        )}
      </div>
    </SiteLayout>
  );
};

