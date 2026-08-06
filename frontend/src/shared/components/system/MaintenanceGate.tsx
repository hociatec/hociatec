import type { ReactNode } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link, useLocation } from 'react-router';

import { fetchSystemStatus } from '@/shared/api/systemStatus';
import { systemQueryKeys } from '@/shared/system/queryKeys';

const ADMIN_ALLOWED_PREFIXES = ['/admin', '/login'];

export const MaintenanceGate = ({ children }: { children: ReactNode }) => {
  const location = useLocation();
  const { data } = useQuery({
    queryKey: systemQueryKeys.status(),
    queryFn: fetchSystemStatus,
    retry: false,
  });
  const maintenance = data?.maintenance ?? null;

  const adminAllowed = ADMIN_ALLOWED_PREFIXES.some(
    (prefix) => location.pathname === prefix || location.pathname.startsWith(`${prefix}/`),
  );

  if (maintenance?.enabled && !adminAllowed) {
    return (
      <main className="min-h-screen bg-brand-900 px-6 py-16 text-white">
        <section className="mx-auto flex max-w-2xl flex-col gap-6 rounded-xl border border-amber-200/30 bg-white/[0.04] p-8 shadow-2xl shadow-black/30">
          <h1 className="text-4xl font-bold">Site temporairement indisponible</h1>
          <p className="text-lg text-stone-200">{maintenance.message}</p>
          <Link to="/login" className="btn-secondary w-fit">
            Accès administrateur
          </Link>
        </section>
      </main>
    );
  }

  return <>{children}</>;
};
