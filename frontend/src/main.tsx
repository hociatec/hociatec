import React from 'react';
import ReactDOM from 'react-dom/client';

import { App } from './app/App';
import './app/styles/global.css';
import './app/styles/site-chrome.css';
import './app/styles/home.css';
import './app/styles/app-surfaces.css';
import './app/styles/admin.css';
import './app/styles/responsive.css';
import './app/styles/feature-overrides.css';

ReactDOM.createRoot(document.getElementById('root') as HTMLElement).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>,
);
