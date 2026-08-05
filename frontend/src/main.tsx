import React from 'react';
import ReactDOM from 'react-dom/client';

import { App } from './app/App';
import { initObservability, trackWebVitals } from '@/shared/lib/observability';
import './app/styles/global.css';
import './app/styles/site-chrome.css';
import './app/styles/app-surfaces.css';
import './app/styles/responsive.css';

initObservability();
trackWebVitals();

ReactDOM.createRoot(document.getElementById('root') as HTMLElement).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>,
);
