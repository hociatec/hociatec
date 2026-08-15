import { readLocalStorage, writeLocalStorage } from './storage';
import { createRandomId } from '../random';

const DEVICE_ID_KEY = 'hociatec.device.id';

const isValidDeviceId = (value: string | null): value is string =>
  typeof value === 'string' && /^[A-Za-z0-9._:-]{16,128}$/.test(value);

const createDeviceId = () => {
  if (typeof globalThis.crypto?.randomUUID === 'function') {
    return `web.${globalThis.crypto.randomUUID()}`;
  }

  return `web.${createRandomId('device')}`;
};

export const getOrCreateDeviceId = () => {
  const storedValue = readLocalStorage(DEVICE_ID_KEY);
  if (isValidDeviceId(storedValue)) {
    return storedValue;
  }

  const deviceId = createDeviceId();
  writeLocalStorage(DEVICE_ID_KEY, deviceId);

  return deviceId;
};
