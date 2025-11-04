import { Outlet } from 'react-router-dom';

import { SiteLayout } from '@/shared/components/SiteLayout';

export const AdminLayout = () => (
  <SiteLayout>
    <div className="mx-auto w-full max-w-6xl px-6 py-12">
      <Outlet />
    </div>
  </SiteLayout>
);

