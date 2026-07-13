import type { HTMLAttributes } from 'react';

import { cn } from '@/shared/lib/cn';

export const Card = ({ className, ...props }: HTMLAttributes<HTMLDivElement>) => (
  <div
    className={cn(
      'group rounded-2xl border border-slate-800/60 bg-slate-900/60 p-6 text-slate-100 shadow-lg transition-colors duration-200',
      'backdrop-blur-sm hover:border-slate-700 hover:bg-slate-900/75',
      className,
    )}
    {...props}
  />
);

export const CardHeader = ({ className, ...props }: HTMLAttributes<HTMLDivElement>) => (
  <div className={cn('mb-4 flex flex-col gap-2', className)} {...props} />
);

export const CardTitle = ({ className, ...props }: HTMLAttributes<HTMLHeadingElement>) => (
  <h3 className={cn('text-xl font-semibold text-white', className)} {...props} />
);

export const CardDescription = ({ className, ...props }: HTMLAttributes<HTMLParagraphElement>) => (
  <p className={cn('text-sm text-slate-300', className)} {...props} />
);

export const CardContent = ({ className, ...props }: HTMLAttributes<HTMLDivElement>) => (
  <div className={cn('mt-auto text-sm text-slate-200', className)} {...props} />
);
