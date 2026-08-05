import { existsSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';

const ENV_FILES = ['.env', '.env.local', '.env.development', '.env.staging', '.env.production'];
const SECRET_NAME_PATTERN = /(SECRET|PRIVATE|PASSWORD|TOKEN|JWT|SMTP|STRIPE_SECRET|API_KEY)/i;
const SECRET_VALUE_PATTERN = /(sk_live_|sk_test_|-----BEGIN|eyJ[a-zA-Z0-9_-]{10,})/;

const projectRoot = process.cwd();
const failures: string[] = [];

for (const file of ENV_FILES) {
  const path = resolve(projectRoot, file);
  if (!existsSync(path)) continue;

  const content = readFileSync(path, 'utf8');
  content.split(/\r?\n/).forEach((line, index) => {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#')) return;

    const [rawName, ...rawValueParts] = trimmed.split('=');
    const name = rawName.trim();
    const value = rawValueParts.join('=').trim();

    if (!name.startsWith('VITE_')) return;

    if (SECRET_NAME_PATTERN.test(name) || SECRET_VALUE_PATTERN.test(value)) {
      failures.push(`${file}:${index + 1} contient une variable Vite potentiellement sensible (${name}).`);
    }
  });
}

if (failures.length > 0) {
  console.error(failures.join('\n'));
  process.exit(1);
}

console.log('Variables Vite publiques validees.');
