import { lazy, type ComponentType } from 'react';

type LazyPageModule<Props extends object, ExportName extends string> = Record<
  ExportName,
  ComponentType<Props>
>;

export const lazyPage = <
  Props extends object = Record<string, never>,
  ExportName extends string = string,
>(
  load: () => Promise<LazyPageModule<Props, ExportName>>,
  exportName: ExportName,
) =>
  lazy(async () => ({
    default: (await load())[exportName],
  }));
