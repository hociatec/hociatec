import { useEffect, useState } from 'react';

import { fetchPublicCategories, type CatalogCategory } from '../api';

type MenuState = 'idle' | 'loading' | 'ready' | 'error';

export const useCatalogMenu = () => {
  const [categories, setCategories] = useState<CatalogCategory[]>([]);
  const [status, setStatus] = useState<MenuState>('idle');
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (status !== 'idle') {
      return;
    }

    setStatus('loading');

    void fetchPublicCategories()
      .then((items) => {
        setCategories(items);
        setStatus('ready');
      })
      .catch((err: Error) => {
        setError(err.message || 'Impossible de charger les categories.');
        setStatus('error');
      });
  }, [status]);

  return { categories, status, error };
};

