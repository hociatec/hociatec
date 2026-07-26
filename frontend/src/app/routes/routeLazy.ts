import { lazy, type ComponentType } from 'react';

type RoutePageModule = Record<string, unknown>;

export const lazyPage = <Props extends object = Record<string, never>>(
  load: () => Promise<unknown>,
  exportName: string,
) => lazy(async () => ({
  default: (await load() as RoutePageModule)[exportName] as ComponentType<Props>,
}));
