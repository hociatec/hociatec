import type { ChangeEvent } from 'react';

import { GALLERY_SIZE, type ProductFormState } from '@/features/admin/catalog/utils/productFormConfig';

type FormChangeEvent = ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>;

export const ProductContentMediaSection = ({ form, galleryFiles, galleryPreviews, galleryToRemove, initialGallery, onChange, onGalleryFileChange, onRemoveGallery }: {
  form: ProductFormState;
  galleryFiles: Array<File | null>;
  galleryPreviews: Array<string | null>;
  galleryToRemove: number[];
  initialGallery: Array<string | null>;
  onChange: (event: FormChangeEvent) => void;
  onGalleryFileChange: (index: number, fileList: FileList | null) => void;
  onRemoveGallery: (index: number) => void;
}) => <>
  <div className="catalog-form-row"><label>Description courte<textarea name="shortDescription" rows={2} maxLength={240} value={form.shortDescription} onChange={onChange} /></label></div>
  <div className="catalog-form-row"><label>Description détaillée<textarea name="description" rows={6} value={form.description} onChange={onChange} required /></label></div>
  <div className="catalog-form-row">
    <span className="register-form__label">Galerie</span>
    <div className="catalog-gallery-grid">{Array.from({ length: GALLERY_SIZE }, (_, index) => { const preview = galleryPreviews[index]; const hasExisting = initialGallery[index] !== null; const hasNewFile = galleryFiles[index] instanceof File; return <div key={index} className="catalog-gallery-slot"><div className="catalog-gallery-preview" aria-label={`Image ${index + 1}`}>{preview ? <img src={preview} alt={`Illustration ${index + 1}`} /> : <div className="catalog-gallery-placeholder"><span>{index + 1}</span></div>}</div><div className="catalog-gallery-slot__actions"><label className="catalog-gallery-upload"><input type="file" accept="image/*" onChange={(event) => onGalleryFileChange(index, event.target.files)} hidden />{preview ? 'Remplacer' : 'Ajouter'}</label>{(preview || hasExisting) && <button type="button" className="catalog-gallery-remove" onClick={() => onRemoveGallery(index)}>{hasNewFile ? 'Annuler' : 'Supprimer'}</button>}</div>{index === 0 && <p className="muted mt-1.5">Image principale affichée sur les cartes produits.</p>}{galleryToRemove.includes(index) && <p className="catalog-gallery-alert">L'image sera supprimée lors de l'enregistrement.</p>}</div>; })}</div>
  </div>
  <div className="catalog-form-row catalog-form-row--columns"><label>Texte alternatif (accessibilité)<input name="imageAlt" value={form.imageAlt} onChange={onChange} maxLength={160} placeholder="Décrivez brièvement l'image principale" /></label></div>
</>;
