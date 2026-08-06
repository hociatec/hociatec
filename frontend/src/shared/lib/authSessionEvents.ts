import { writeLocalStorage } from './http/storage';

export type AuthSessionEvent = 'login' | 'logout' | 'profile_updated' | 'account_deleted';

const CHANNEL_NAME = 'hociatec.auth.session';
const STORAGE_KEY = 'hociatec.auth.session.event';
const DOM_EVENT_NAME = 'hociatec:auth-session';

let channel: BroadcastChannel | null = null;

const getChannel = () => {
  if (typeof window === 'undefined' || typeof BroadcastChannel === 'undefined') {
    return null;
  }

  channel ??= new BroadcastChannel(CHANNEL_NAME);

  return channel;
};

export const publishAuthSessionEvent = (event: AuthSessionEvent) => {
  if (typeof window === 'undefined') return;

  const payload = JSON.stringify({ event, at: Date.now() });
  getChannel()?.postMessage(event);
  window.dispatchEvent(new CustomEvent<AuthSessionEvent>(DOM_EVENT_NAME, { detail: event }));

  writeLocalStorage(STORAGE_KEY, payload);
};

export const subscribeAuthSessionEvents = (listener: (event: AuthSessionEvent) => void) => {
  if (typeof window === 'undefined') return () => undefined;

  const broadcast = getChannel();
  const onBroadcast = (message: MessageEvent<AuthSessionEvent>) => listener(message.data);
  const onDomEvent = (event: Event) => listener((event as CustomEvent<AuthSessionEvent>).detail);
  const onStorage = (event: StorageEvent) => {
    if (event.key !== STORAGE_KEY || !event.newValue) {
      return;
    }

    try {
      const payload = JSON.parse(event.newValue) as { event?: unknown };
      if (
        payload.event === 'login' ||
        payload.event === 'logout' ||
        payload.event === 'profile_updated' ||
        payload.event === 'account_deleted'
      ) {
        listener(payload.event);
      }
    } catch {
    }
  };

  broadcast?.addEventListener('message', onBroadcast);
  window.addEventListener(DOM_EVENT_NAME, onDomEvent);
  window.addEventListener('storage', onStorage);

  return () => {
    broadcast?.removeEventListener('message', onBroadcast);
    window.removeEventListener(DOM_EVENT_NAME, onDomEvent);
    window.removeEventListener('storage', onStorage);
  };
};
