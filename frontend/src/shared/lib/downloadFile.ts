export const downloadBlob = (blob: Blob, filename: string) => {
  const url = window.URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = filename;
  document.body.appendChild(anchor);
  anchor.click();
  anchor.remove();
  window.URL.revokeObjectURL(url);
};

const CSV_INJECTION_PREFIX = /^[=+\-@]/u;

export const sanitizeCsvCell = (value: string) =>
  CSV_INJECTION_PREFIX.test(value.trimStart()) ? `'${value}` : value;

export const sanitizeCsvText = (csv: string) =>
  csv
    .split(/\r?\n/u)
    .map((line) =>
      line
        .split(',')
        .map((cell) => sanitizeCsvCell(cell))
        .join(','),
    )
    .join('\n');

export const downloadCsvBlob = async (blob: Blob, filename: string) => {
  const csv = await blob.text();
  downloadBlob(new Blob([sanitizeCsvText(csv)], { type: 'text/csv;charset=utf-8' }), filename);
};
