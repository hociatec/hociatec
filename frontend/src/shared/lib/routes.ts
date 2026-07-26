export const isPathActive = (pathname: string, path: string) =>
  pathname === path || pathname.startsWith(`${path}/`);

export const isAnyPathActive = (pathname: string, paths: string[]) =>
  paths.some((path) => isPathActive(pathname, path));
