type LogLevel = 'debug' | 'info' | 'warn' | 'error';

const isProduction = import.meta.env.PROD;

const sanitize = (value: unknown): unknown => {
  if (value instanceof Error) {
    return { name: value.name, message: value.message };
  }

  if (!value || typeof value !== 'object') {
    return value;
  }

  return '[redacted]';
};

const write = (level: LogLevel, message: string, context?: Record<string, unknown>) => {
  if (isProduction && (level === 'debug' || level === 'info')) {
    return;
  }

  const payload = context
    ? Object.fromEntries(Object.entries(context).map(([key, value]) => [key, sanitize(value)]))
    : undefined;

  console[level](message, payload);
};

export const logger = {
  debug: (message: string, context?: Record<string, unknown>) => write('debug', message, context),
  info: (message: string, context?: Record<string, unknown>) => write('info', message, context),
  warn: (message: string, context?: Record<string, unknown>) => write('warn', message, context),
  error: (message: string, context?: Record<string, unknown>) => write('error', message, context),
};
