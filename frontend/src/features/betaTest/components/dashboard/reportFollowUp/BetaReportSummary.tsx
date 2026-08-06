import { resolveBetaAttachmentUrl, type BugReport } from '../../../api/betaApi';

interface BetaReportSummaryProps {
  report: BugReport;
}

export const BetaReportSummary = ({ report }: BetaReportSummaryProps) => (
  <div className="max-h-56 overflow-y-auto border-b border-stone-200 bg-stone-50 p-5 text-sm text-stone-700">
    <div className="grid gap-4 md:grid-cols-2">
      <section className="md:col-span-2">
        <h3 className="font-semibold text-stone-900">Signalement initial</h3>
        <p className="mt-1 whitespace-pre-wrap leading-6">{report.description}</p>
      </section>
      {report.expectedBehavior && (
        <section>
          <h3 className="font-semibold text-stone-900">Résultat attendu</h3>
          <p className="mt-1 whitespace-pre-wrap leading-6">{report.expectedBehavior}</p>
        </section>
      )}
      {report.actualBehavior && (
        <section>
          <h3 className="font-semibold text-stone-900">Résultat constaté</h3>
          <p className="mt-1 whitespace-pre-wrap leading-6">{report.actualBehavior}</p>
        </section>
      )}
    </div>
    {(report.attachmentUrls ?? []).length > 0 && (
      <div className="mt-4">
        <strong>Captures :</strong>
        <ul className="mt-1 space-y-1">
          {(report.attachmentUrls ?? []).map((url, index) => {
            const attachmentUrl = resolveBetaAttachmentUrl(url);

            return attachmentUrl ? (
              <li key={url}>
                <a className="text-brand-700 underline" href={attachmentUrl} target="_blank" rel="noopener noreferrer">
                  Ouvrir la capture {index + 1}
                </a>
              </li>
            ) : null;
          })}
        </ul>
      </div>
    )}
  </div>
);
