export const isDocumentHidden = () => {
  if (typeof document === 'undefined') {
    return true;
  }

  return document.hidden;
};

export const shouldRefetchWhenVisible = (hasError?: boolean) => !isDocumentHidden() && !hasError;
