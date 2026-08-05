import * as Sentry from '@sentry/react';
import { onCLS, onINP, onLCP, onTTFB, type Metric } from 'web-vitals';

import { BUILD_INFO, OBSERVABILITY_CONFIG } from '@/shared/config/appConfig';
import { logger } from './logger';

const SENSITIVE_KEY_PATTERN =
  /(authorization|cookie|csrf|password|secret|token|jwt|card|iban|bic|email|phone|address|message|content)/i;

type ObservabilityContext = Record<string, unknown>;

let initialized = false;
let webVitalsTracked = false;

const isRecord = (value: unknown): value is Record<string, unknown> =>
  Boolean(value) && typeof value === 'object' && !Array.isArray(value);

const sanitizeValue = (key: string, value: unknown): unknown => {
  if (SENSITIVE_KEY_PATTERN.test(key)) {
    return '[redacted]';
  }

  if (value instanceof Error) {
    return {
      message: value.message,
      name: value.name,
    };
  }

  if (Array.isArray(value)) {
    return value.slice(0, 10).map((entry, index) => sanitizeValue(`${key}.${index}`, entry));
  }

  if (isRecord(value)) {
    return sanitizeObservabilityContext(value);
  }

  if (typeof value === 'string') {
    return value.length > 240 ? `${value.slice(0, 240)}...` : value;
  }

  return value;
};

export const sanitizeObservabilityContext = (context: ObservabilityContext = {}) =>
  Object.fromEntries(
    Object.entries(context).map(([key, value]) => [key, sanitizeValue(key, value)]),
  );

const scrubSentryEvent: NonNullable<Parameters<typeof Sentry.init>[0]['beforeSend']> = (event) => {
  if (event.request) {
    delete event.request.cookies;
    delete event.request.data;
    delete event.request.headers;
    delete event.request.query_string;
  }

  if (event.user) {
    delete event.user.email;
    delete event.user.ip_address;
    delete event.user.username;
  }

  event.extra = sanitizeObservabilityContext(event.extra ?? {});

  return event;
};

export const initObservability = () => {
  if (initialized) return;
  initialized = true;

  if (!OBSERVABILITY_CONFIG.enabled || !OBSERVABILITY_CONFIG.sentryDsn) {
    logger.info('Observability disabled or Sentry DSN missing.', {
      environment: BUILD_INFO.environment,
    });
    return;
  }

  Sentry.init({
    beforeSend: scrubSentryEvent,
    dsn: OBSERVABILITY_CONFIG.sentryDsn,
    environment: BUILD_INFO.environment,
    integrations: [Sentry.browserTracingIntegration()],
    release: `${BUILD_INFO.frontendVersion}@${BUILD_INFO.commitSha}`,
    sendDefaultPii: false,
    tracesSampleRate: OBSERVABILITY_CONFIG.tracesSampleRate,
  });

  Sentry.setTag('frontend.version', BUILD_INFO.frontendVersion);
  Sentry.setTag('frontend.commit', BUILD_INFO.commitSha);
};

export const reportError = (error: unknown, context: ObservabilityContext = {}) => {
  const sanitized = sanitizeObservabilityContext(context);
  const message = typeof sanitized.message === 'string' ? sanitized.message : 'Frontend error.';

  logger.error(message, sanitized);

  if (!OBSERVABILITY_CONFIG.enabled || !OBSERVABILITY_CONFIG.sentryDsn) {
    return;
  }

  Sentry.captureException(error, {
    extra: sanitized,
    tags: {
      category: typeof sanitized.category === 'string' ? sanitized.category : 'frontend',
      environment: BUILD_INFO.environment,
    },
  });
};

export const toWebVitalPayload = (metric: Metric) => ({
  buildDate: BUILD_INFO.buildDate,
  commitSha: BUILD_INFO.commitSha,
  delta: metric.delta,
  environment: BUILD_INFO.environment,
  id: metric.id,
  name: metric.name,
  rating: metric.rating,
  route: window.location.pathname,
  value: metric.value,
  version: BUILD_INFO.frontendVersion,
});

const sendWebVital = (metric: Metric) => {
  const payload = toWebVitalPayload(metric);

  logger.info('Web vital measured.', payload);

  if (OBSERVABILITY_CONFIG.enabled && OBSERVABILITY_CONFIG.sentryDsn) {
    Sentry.captureMessage(`Web Vital ${metric.name}`, {
      extra: payload,
      level: metric.rating === 'poor' ? 'warning' : 'info',
      tags: {
        metric: metric.name,
        rating: metric.rating,
      },
    });
  }

  if (!OBSERVABILITY_CONFIG.webVitalsEndpoint) {
    return;
  }

  const body = JSON.stringify(payload);

  if (navigator.sendBeacon) {
    navigator.sendBeacon(OBSERVABILITY_CONFIG.webVitalsEndpoint, body);
    return;
  }

  void fetch(OBSERVABILITY_CONFIG.webVitalsEndpoint, {
    body,
    headers: { 'Content-Type': 'application/json' },
    keepalive: true,
    method: 'POST',
  }).catch((error: unknown) => {
    logger.warn('Unable to send web vitals.', { error });
  });
};

export const trackWebVitals = () => {
  if (webVitalsTracked) return;
  webVitalsTracked = true;

  onCLS(sendWebVital);
  onINP(sendWebVital);
  onLCP(sendWebVital);
  onTTFB(sendWebVital);
};
