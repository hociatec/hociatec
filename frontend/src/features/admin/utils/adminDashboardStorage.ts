const ADMIN_DEFAULT_TAB_KEY = 'hociatec.admin.dashboard.defaultTab';

export const readDefaultAdminTab = () => {
  if (typeof window === 'undefined') return null;

  try {
    return window.localStorage.getItem(ADMIN_DEFAULT_TAB_KEY);
  } catch {
    return null;
  }
};

export const writeDefaultAdminTab = (value: string) => {
  if (typeof window === 'undefined') return;

  try {
    window.localStorage.setItem(ADMIN_DEFAULT_TAB_KEY, value);
  } catch {
    return;
  }
};
