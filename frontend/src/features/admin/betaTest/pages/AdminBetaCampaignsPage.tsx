import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  fetchAdminCampaigns,
  createAdminCampaign,
  updateAdminCampaign,
  deleteAdminCampaign,
  type AdminCampaignDto,
} from '../api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useToast } from '@/shared/components/ui/toast';
import { useConfirm } from '@/shared/components/ui/confirm';
import { Edit, Eye, Plus, Trash2, X } from 'lucide-react';

export const AdminBetaCampaignsPage = () => {
  const queryClient = useQueryClient();
  const toast = useToast();
  const confirm = useConfirm();

  // Modals visibility & data state
  const [isAddOpen, setIsAddOpen] = useState(false);
  const [isEditOpen, setIsEditOpen] = useState(false);
  const [isDetailOpen, setIsDetailOpen] = useState(false);
  const [selectedCampaign, setSelectedCampaign] = useState<AdminCampaignDto | null>(null);

  // Forms states
  const [addForm, setAddForm] = useState({ name: '', description: '', status: 'draft' });
  const [editForm, setEditForm] = useState({ name: '', description: '', status: 'draft' });

  // Query campaigns list
  const { data: campaigns = [], isLoading, error } = useQuery({
    queryKey: ['adminCampaigns'],
    queryFn: fetchAdminCampaigns,
  });

  // Mutations
  const createMutation = useMutation({
    mutationFn: (payload: typeof addForm) => createAdminCampaign(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['adminCampaigns'] });
      toast.show('La campagne bêta a été créée avec succès.', { variant: 'success' });
      setIsAddOpen(false);
      setAddForm({ name: '', description: '', status: 'draft' });
    },
    onError: (err) => {
      toast.show(err instanceof Error ? err.message : 'Erreur lors de la création.', { variant: 'error' });
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: typeof editForm }) =>
      updateAdminCampaign(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['adminCampaigns'] });
      toast.show('La campagne bêta a été mise à jour.', { variant: 'success' });
      setIsEditOpen(false);
      setSelectedCampaign(null);
    },
    onError: (err) => {
      toast.show(err instanceof Error ? err.message : 'Erreur lors de la modification.', { variant: 'error' });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => deleteAdminCampaign(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['adminCampaigns'] });
      toast.show('La campagne bêta a été supprimée.', { variant: 'success' });
    },
    onError: (err) => {
      toast.show(err instanceof Error ? err.message : 'Erreur lors de la suppression.', { variant: 'error' });
    },
  });

  // Handlers
  const handleAddSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!addForm.name.trim() || !addForm.description.trim()) {
      toast.show('Le nom et la description sont obligatoires.', { variant: 'error' });
      return;
    }
    createMutation.mutate(addForm);
  };

  const handleEditSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedCampaign) return;
    if (!editForm.name.trim() || !editForm.description.trim()) {
      toast.show('Le nom et la description sont obligatoires.', { variant: 'error' });
      return;
    }
    updateMutation.mutate({ id: selectedCampaign.id, payload: editForm });
  };

  const handleDelete = async (campaign: AdminCampaignDto) => {
    if (
      await confirm({
        title: 'Supprimer la campagne',
        description: `Êtes-vous sûr de vouloir supprimer définitivement la campagne "${campaign.name}" ?`,
        confirmLabel: 'Supprimer',
        cancelLabel: 'Annuler',
      })
    ) {
      deleteMutation.mutate(campaign.id);
    }
  };

  const openEdit = (campaign: AdminCampaignDto) => {
    setSelectedCampaign(campaign);
    setEditForm({
      name: campaign.name,
      description: campaign.description,
      status: campaign.status,
    });
    setIsEditOpen(true);
  };

  const openDetail = (campaign: AdminCampaignDto) => {
    setSelectedCampaign(campaign);
    setIsDetailOpen(true);
  };

  const statusLabels: Record<string, string> = {
    draft: 'Brouillon',
    active: 'Active',
    closed: 'Clôturée',
  };

  return (
    <PageContainer size="admin" title="Gestion des Campagnes Bêta">
      <header className="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
          <p className="text-stone-500">Planifiez, lancez et gérez les campagnes de tests de l'application.</p>
        </div>
        <button
          type="button"
          onClick={() => setIsAddOpen(true)}
          className="inline-flex items-center gap-2 rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800 transition shadow-sm"
        >
          <Plus size={16} />
          <span>Nouvelle campagne</span>
        </button>
      </header>

      {isLoading ? (
        <p className="text-stone-500">Chargement des campagnes...</p>
      ) : error ? (
        <p className="text-red-600">Erreur lors du chargement des campagnes.</p>
      ) : campaigns.length === 0 ? (
        <div className="p-8 text-center bg-white border border-stone-200 rounded-lg text-stone-500">
          Aucune campagne bêta configurée pour le moment.
        </div>
      ) : (
        <div className="overflow-x-auto bg-white border border-stone-200 rounded-lg shadow-sm">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-stone-50 border-b border-stone-200 text-sm font-semibold text-stone-600">
                <th className="p-4">Nom de la Campagne</th>
                <th className="p-4">Description</th>
                <th className="p-4">Date de création</th>
                <th className="p-4">Statut</th>
                <th className="p-4">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-stone-200 text-sm">
              {campaigns.map((c) => (
                <tr key={c.id} className="hover:bg-stone-50 transition">
                  <td className="p-4 font-semibold text-stone-900">{c.name}</td>
                  <td className="p-4 text-stone-600 max-w-xs truncate">{c.description}</td>
                  <td className="p-4 text-stone-500">
                    {new Date(c.createdAt).toLocaleDateString()}
                  </td>
                  <td className="p-4">
                    <span
                      className={`inline-flex px-2 py-1 rounded text-xs font-semibold ${
                        c.status === 'active'
                          ? 'bg-green-100 text-green-800'
                          : c.status === 'closed'
                            ? 'bg-red-100 text-red-800'
                            : 'bg-stone-100 text-stone-800'
                      }`}
                    >
                      {statusLabels[c.status] || c.status}
                    </span>
                  </td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <button
                        type="button"
                        onClick={() => openDetail(c)}
                        className="p-1.5 text-stone-600 hover:text-brand-700 bg-stone-50 rounded hover:bg-stone-100 transition"
                        title="Consulter"
                      >
                        <Eye size={16} />
                      </button>
                      <button
                        type="button"
                        onClick={() => openEdit(c)}
                        className="p-1.5 text-stone-600 hover:text-brand-700 bg-stone-50 rounded hover:bg-stone-100 transition"
                        title="Modifier"
                      >
                        <Edit size={16} />
                      </button>
                      <button
                        type="button"
                        onClick={() => handleDelete(c)}
                        className="p-1.5 text-red-600 hover:text-red-800 bg-red-50 rounded hover:bg-red-100 transition"
                        title="Supprimer"
                      >
                        <Trash2 size={16} />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Add Campaign Modal */}
      {isAddOpen && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-lg max-w-xl w-full flex flex-col shadow-xl">
            <header className="p-4 border-b border-stone-200 flex items-center justify-between">
              <h2 className="font-bold text-lg text-stone-900">Nouvelle Campagne Bêta</h2>
              <button
                type="button"
                className="p-1 text-stone-500 hover:text-stone-700 rounded-full hover:bg-stone-100"
                onClick={() => setIsAddOpen(false)}
                aria-label="Fermer la modal"
              >
                <X size={20} />
              </button>
            </header>
            <form onSubmit={handleAddSubmit} className="p-6 space-y-4">
              <div>
                <label className="block text-sm font-semibold text-stone-700 mb-1">
                  Nom de la campagne *
                </label>
                <input
                  type="text"
                  className="w-full rounded-lg border border-stone-300 p-3 text-sm focus:outline-none focus:border-brand-700"
                  placeholder="Ex: Refonte du panier"
                  value={addForm.name}
                  onChange={(e) => setAddForm({ ...addForm, name: e.target.value })}
                  required
                />
              </div>
              <div>
                <label className="block text-sm font-semibold text-stone-700 mb-1">
                  Description *
                </label>
                <textarea
                  className="w-full rounded-lg border border-stone-300 p-3 text-sm focus:outline-none focus:border-brand-700"
                  placeholder="Objectifs de la campagne, fonctionnalités à tester..."
                  rows={4}
                  value={addForm.description}
                  onChange={(e) => setAddForm({ ...addForm, description: e.target.value })}
                  required
                />
              </div>
              <div>
                <label className="block text-sm font-semibold text-stone-700 mb-1">
                  Statut initial
                </label>
                <select
                  className="w-full rounded-lg border border-stone-300 p-3 text-sm bg-white focus:outline-none focus:border-brand-700"
                  value={addForm.status}
                  onChange={(e) => setAddForm({ ...addForm, status: e.target.value })}
                >
                  <option value="draft">Brouillon</option>
                  <option value="active">Active</option>
                  <option value="closed">Clôturée</option>
                </select>
              </div>
              <footer className="pt-4 border-t border-stone-150 flex justify-end gap-3">
                <button
                  type="button"
                  onClick={() => setIsAddOpen(false)}
                  className="px-4 py-2 border border-stone-350 text-stone-700 rounded-lg text-sm font-semibold hover:bg-stone-50"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  disabled={createMutation.isPending}
                  className="px-4 py-2 bg-brand-700 hover:bg-brand-800 text-white font-semibold rounded-lg text-sm disabled:opacity-50"
                >
                  {createMutation.isPending ? 'Création...' : 'Créer la campagne'}
                </button>
              </footer>
            </form>
          </div>
        </div>
      )}

      {/* Edit Campaign Modal */}
      {isEditOpen && selectedCampaign && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-lg max-w-xl w-full flex flex-col shadow-xl">
            <header className="p-4 border-b border-stone-200 flex items-center justify-between">
              <h2 className="font-bold text-lg text-stone-900">Modifier la Campagne Bêta</h2>
              <button
                type="button"
                className="p-1 text-stone-500 hover:text-stone-700 rounded-full hover:bg-stone-100"
                onClick={() => setIsEditOpen(false)}
                aria-label="Fermer la modal"
              >
                <X size={20} />
              </button>
            </header>
            <form onSubmit={handleEditSubmit} className="p-6 space-y-4">
              <div>
                <label className="block text-sm font-semibold text-stone-700 mb-1">
                  Nom de la campagne *
                </label>
                <input
                  type="text"
                  className="w-full rounded-lg border border-stone-300 p-3 text-sm focus:outline-none focus:border-brand-700"
                  value={editForm.name}
                  onChange={(e) => setEditForm({ ...editForm, name: e.target.value })}
                  required
                />
              </div>
              <div>
                <label className="block text-sm font-semibold text-stone-700 mb-1">
                  Description *
                </label>
                <textarea
                  className="w-full rounded-lg border border-stone-300 p-3 text-sm focus:outline-none focus:border-brand-700"
                  rows={4}
                  value={editForm.description}
                  onChange={(e) => setEditForm({ ...editForm, description: e.target.value })}
                  required
                />
              </div>
              <div>
                <label className="block text-sm font-semibold text-stone-700 mb-1">
                  Statut
                </label>
                <select
                  className="w-full rounded-lg border border-stone-300 p-3 text-sm bg-white focus:outline-none focus:border-brand-700"
                  value={editForm.status}
                  onChange={(e) => setEditForm({ ...editForm, status: e.target.value })}
                >
                  <option value="draft">Brouillon</option>
                  <option value="active">Active</option>
                  <option value="closed">Clôturée</option>
                </select>
              </div>
              <footer className="pt-4 border-t border-stone-150 flex justify-end gap-3">
                <button
                  type="button"
                  onClick={() => setIsEditOpen(false)}
                  className="px-4 py-2 border border-stone-350 text-stone-700 rounded-lg text-sm font-semibold hover:bg-stone-50"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  disabled={updateMutation.isPending}
                  className="px-4 py-2 bg-brand-700 hover:bg-brand-800 text-white font-semibold rounded-lg text-sm disabled:opacity-50"
                >
                  {updateMutation.isPending ? 'Enregistrement...' : 'Enregistrer'}
                </button>
              </footer>
            </form>
          </div>
        </div>
      )}

      {/* Consult Campaign Modal */}
      {isDetailOpen && selectedCampaign && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-lg max-w-xl w-full flex flex-col shadow-xl">
            <header className="p-4 border-b border-stone-200 flex items-center justify-between">
              <h2 className="font-bold text-lg text-stone-900">Détails de la Campagne</h2>
              <button
                type="button"
                className="p-1 text-stone-500 hover:text-stone-700 rounded-full hover:bg-stone-100"
                onClick={() => setIsDetailOpen(false)}
                aria-label="Fermer la modal"
              >
                <X size={20} />
              </button>
            </header>
            <div className="p-6 space-y-4">
              <div>
                <strong className="block text-xs uppercase text-stone-500">Nom</strong>
                <p className="text-base text-stone-900 mt-1 font-semibold">{selectedCampaign.name}</p>
              </div>
              <div>
                <strong className="block text-xs uppercase text-stone-500">Description</strong>
                <p className="text-sm text-stone-700 mt-1 whitespace-pre-wrap leading-relaxed">
                  {selectedCampaign.description}
                </p>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <strong className="block text-xs uppercase text-stone-500">Statut</strong>
                  <span
                    className={`inline-flex px-2 py-1 rounded text-xs font-semibold mt-2 ${
                      selectedCampaign.status === 'active'
                        ? 'bg-green-100 text-green-800'
                        : selectedCampaign.status === 'closed'
                          ? 'bg-red-100 text-red-800'
                          : 'bg-stone-100 text-stone-800'
                    }`}
                  >
                    {statusLabels[selectedCampaign.status] || selectedCampaign.status}
                  </span>
                </div>
                <div>
                  <strong className="block text-xs uppercase text-stone-500">Date de création</strong>
                  <p className="text-sm text-stone-700 mt-2">
                    {new Date(selectedCampaign.createdAt).toLocaleString()}
                  </p>
                </div>
              </div>
              <footer className="pt-4 border-t border-stone-150 flex justify-end">
                <button
                  type="button"
                  onClick={() => setIsDetailOpen(false)}
                  className="px-4 py-2 bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-lg text-sm font-semibold"
                >
                  Fermer
                </button>
              </footer>
            </div>
          </div>
        </div>
      )}
    </PageContainer>
  );
};
