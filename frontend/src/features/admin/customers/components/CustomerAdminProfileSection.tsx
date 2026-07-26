export const CustomerAdminProfileSection = ({
  adminNotes,
  adminTagsInput,
  parsedTags,
  saveMessage,
  saveState,
  onAdminNotesChange,
  onAdminTagsInputChange,
  onSave,
}: {
  adminNotes: string;
  adminTagsInput: string;
  parsedTags: string[];
  saveMessage: string | null;
  saveState: 'idle' | 'saving' | 'saved' | 'error';
  onAdminNotesChange: (value: string) => void;
  onAdminTagsInputChange: (value: string) => void;
  onSave: () => void;
}) => (
  <section className="overflow-hidden rounded-xl border border-brand-100 bg-white shadow-sm">
    <div className="border-b border-brand-100 bg-brand-50/80 px-5 py-4">
      <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h2 className="font-semibold text-brand-900">Suivi interne</h2>
          <p className="mt-1 text-sm text-stone-500">Centralise les repères utiles pour le support, les relances et le suivi commercial.</p>
        </div>
        <div className="flex items-center gap-3">
          {saveMessage ? (
            <div className={`rounded-full px-3 py-1 text-xs font-medium ${saveState === 'error' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'}`}>
              {saveMessage}
            </div>
          ) : null}
          <div className="rounded-full bg-white px-3 py-1 text-xs text-stone-500 shadow-sm">
            {parsedTags.length} tag{parsedTags.length > 1 ? 's' : ''}
          </div>
        </div>
      </div>
    </div>
    <div className="px-5 py-5">
      <div className="mb-5 flex items-center justify-between gap-3">
        <div>
          <div className="text-sm font-medium text-brand-900">Édition rapide</div>
          <div className="text-xs text-stone-500">Mets à jour les tags et les notes, puis enregistre.</div>
        </div>
        <div className="flex items-center gap-3">
          {saveState === 'saving' ? <div className="text-xs text-stone-500">Enregistrement en cours...</div> : null}
          <button type="button" className="inline-flex items-center rounded-full bg-brand-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-60" onClick={onSave} disabled={saveState === 'saving'}>
            {saveState === 'saving' ? 'Enregistrement...' : 'Enregistrer'}
          </button>
        </div>
      </div>
      <div className="grid gap-5 xl:grid-cols-[minmax(0,1.15fr)_minmax(300px,0.85fr)]">
        <div className="rounded-2xl border border-brand-100 bg-white p-4">
          <div className="mb-3">
            <h3 className="text-sm font-semibold text-brand-900">Tags client</h3>
            <p className="mt-1 text-xs text-stone-500">Sépare les tags par des virgules pour classer rapidement le client.</p>
          </div>
          <input className="register-form__input" value={adminTagsInput} onChange={(event) => onAdminTagsInputChange(event.target.value)} placeholder="vip, sav, relance, pro..." />
          <div className="mt-4 rounded-2xl bg-brand-50 p-4">
            <div className="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Aperçu des tags</div>
            <div className="flex min-h-14 flex-wrap gap-2">
              {parsedTags.length > 0 ? parsedTags.map((tag) => (
                <span key={tag} className="rounded-full border border-brand-100 bg-white px-3 py-1.5 text-xs font-medium text-stone-700 shadow-sm">
                  {tag}
                </span>
              )) : (
                <span className="text-sm text-stone-400">Aucun tag pour le moment.</span>
              )}
            </div>
          </div>
        </div>
        <div className="rounded-2xl border border-brand-100 bg-brand-50 p-4">
          <div className="mb-3">
            <h3 className="text-sm font-semibold text-brand-900">Notes internes</h3>
            <p className="mt-1 text-xs text-stone-500">Historique SAV, préférences client, points de vigilance, contexte commercial.</p>
          </div>
          <textarea className="register-form__input min-h-52 bg-white" value={adminNotes} onChange={(event) => onAdminNotesChange(event.target.value)} placeholder="Ex: client prioritaire, rappeler pour validation, attente de justificatif, sensible au délai..." />
        </div>
      </div>
    </div>
  </section>
);
