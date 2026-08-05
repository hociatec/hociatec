// @vitest-environment jsdom
import { act, cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it } from 'vitest';

import { NetworkStatusBanner } from './NetworkStatusBanner';

const setNavigatorOnline = (value: boolean) => {
  Object.defineProperty(window.navigator, 'onLine', {
    configurable: true,
    value,
  });
};

describe('NetworkStatusBanner', () => {
  afterEach(() => {
    cleanup();
    setNavigatorOnline(true);
  });

  it('stays hidden while the application has not lost connectivity', () => {
    setNavigatorOnline(true);

    render(<NetworkStatusBanner />);

    expect(screen.queryByRole('status')).toBeNull();
  });

  it('announces offline state and recovery', () => {
    setNavigatorOnline(true);

    render(<NetworkStatusBanner />);

    setNavigatorOnline(false);
    act(() => {
      window.dispatchEvent(new Event('offline'));
    });

    expect(screen.getByRole('status').textContent).toContain('Vous êtes hors connexion');

    setNavigatorOnline(true);
    act(() => {
      window.dispatchEvent(new Event('online'));
    });

    expect(screen.getByRole('status').textContent).toContain('Connexion rétablie');
  });
});
