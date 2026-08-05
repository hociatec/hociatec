import { createContext, useContext, type PropsWithChildren } from 'react';

interface SiteHeaderActionsState {
  cartQuantity: number;
  isAdmin: boolean;
  isAuthenticated: boolean;
  onLogout: () => void | Promise<void>;
}

const defaultState: SiteHeaderActionsState = {
  cartQuantity: 0,
  isAdmin: false,
  isAuthenticated: false,
  onLogout: () => undefined,
};

const SiteHeaderActionsContext = createContext<SiteHeaderActionsState>(defaultState);

export const SiteHeaderActionsProvider = ({
  children,
  value,
}: PropsWithChildren<{ value: SiteHeaderActionsState }>) => (
  <SiteHeaderActionsContext.Provider value={value}>
    {children}
  </SiteHeaderActionsContext.Provider>
);

export const useSiteHeaderActionsState = () => useContext(SiteHeaderActionsContext);
