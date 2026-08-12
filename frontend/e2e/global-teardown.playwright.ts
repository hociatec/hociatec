import { purgeE2eData, shouldAutoSeedE2eData } from './helpers/auth';

async function globalTeardown() {
  if (!shouldAutoSeedE2eData()) {
    return;
  }

  purgeE2eData();
}

export default globalTeardown;
