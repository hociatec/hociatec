export interface BetaBugReportFormState {
  title: string;
  description: string;
  expectedBehavior: string;
  actualBehavior: string;
  severity: string;
  screenshots: File[];
}

export const emptyBetaBugReportForm = (): BetaBugReportFormState => ({
  title: '',
  description: '',
  expectedBehavior: '',
  actualBehavior: '',
  severity: 'normal',
  screenshots: [],
});
