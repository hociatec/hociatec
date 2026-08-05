import { Component, type ErrorInfo, type PropsWithChildren, type ReactNode } from 'react';
import { Link, useLocation } from 'react-router';

import { ErrorState } from '@/shared/components/ui/page-state';
import { BUILD_INFO } from '@/shared/config/appConfig';
import { logger } from '@/shared/lib/logger';

interface ErrorBoundaryProps extends PropsWithChildren {
  resetKey: string;
}

interface ErrorBoundaryState {
  error: Error | null;
  errorId: string | null;
}

const createErrorId = () =>
  `front_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 8)}`;

const isAdminPath = (path: string) => path.startsWith('/admin');

const ErrorFallback = ({
  errorId,
  path,
  onRetry,
}: {
  errorId: string;
  path: string;
  onRetry: () => void;
}) => {
  const homePath = isAdminPath(path) ? '/admin' : '/';

  return (
    <div className="site-layout">
      <main className="site-layout__content" tabIndex={-1}>
        <ErrorState>
          <div className="mx-auto max-w-xl space-y-4">
            <h1 className="text-2xl font-semibold text-red-800">
              Une erreur inattendue est survenue.
            </h1>
            <p>
              La page n&apos;a pas pu être affichée correctement. Réessayez ou revenez à une page
              stable.
            </p>
            <p className="text-sm">Référence : {errorId}</p>
            <p className="text-xs text-red-700/80">
              Version : {BUILD_INFO.frontendVersion} ({BUILD_INFO.commitSha})
            </p>
            <div className="flex flex-wrap justify-center gap-3">
              <button className="button" type="button" onClick={onRetry}>
                Réessayer
              </button>
              <Link className="button button-muted" to={homePath}>
                {isAdminPath(path) ? 'Retour admin' : 'Retour accueil'}
              </Link>
            </div>
          </div>
        </ErrorState>
      </main>
    </div>
  );
};

export class ErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
  state: ErrorBoundaryState = {
    error: null,
    errorId: null,
  };

  static getDerivedStateFromError(error: Error): ErrorBoundaryState {
    return {
      error,
      errorId: createErrorId(),
    };
  }

  componentDidCatch(error: Error, info: ErrorInfo) {
    logger.error('React render tree failed.', {
      error,
      componentStack: info.componentStack,
      errorId: this.state.errorId,
    });
  }

  componentDidUpdate(previousProps: ErrorBoundaryProps) {
    if (this.state.error && previousProps.resetKey !== this.props.resetKey) {
      this.setState({ error: null, errorId: null });
    }
  }

  handleRetry = () => {
    this.setState({ error: null, errorId: null });
  };

  render(): ReactNode {
    if (this.state.error && this.state.errorId) {
      return (
        <ErrorFallback
          errorId={this.state.errorId}
          path={this.props.resetKey}
          onRetry={this.handleRetry}
        />
      );
    }

    return this.props.children;
  }
}

export const RouteErrorBoundary = ({ children }: PropsWithChildren) => {
  const location = useLocation();

  return <ErrorBoundary resetKey={location.pathname}>{children}</ErrorBoundary>;
};
