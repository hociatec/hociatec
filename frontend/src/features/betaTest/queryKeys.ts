export const betaQueryKeys = {
  profile: () => ['beta', 'profile'] as const,
  profileForm: () => ['beta', 'profile-form'] as const,
  profileChoices: () => ['beta', 'profile-choices'] as const,
  campaigns: () => ['beta', 'campaigns'] as const,
  reports: () => ['beta', 'reports'] as const,
  reportsPage: (page: number) => [...betaQueryKeys.reports(), { page }] as const,
  report: (id: number | null) => ['beta', 'report', id] as const,
  reportComments: (id: number | null) => ['beta', 'report-comments', id] as const,
  reportCommentsPage: (id: number | null, page: number) =>
    [...betaQueryKeys.reportComments(id), { page }] as const,
};

export const adminBetaQueryKeys = {
  campaigns: () => ['admin', 'beta', 'campaigns'] as const,
  testers: (search: string, status: string) => ['admin', 'beta', 'testers', { search, status }] as const,
  profileChoices: () => ['admin', 'beta', 'profile-choices'] as const,
  bugReports: () => ['admin', 'beta', 'bug-reports'] as const,
  bugReportsList: (filters: Record<string, unknown>) =>
    [...adminBetaQueryKeys.bugReports(), filters] as const,
  bugReport: (id: number | null) => ['admin', 'beta', 'bug-report', id] as const,
  bugReportDashboard: () => ['admin', 'beta', 'bug-report-dashboard'] as const,
  bugReportActivity: (id: number | null) => ['admin', 'beta', 'bug-report-activity', id] as const,
  bugReportComments: (id: number | null) => ['admin', 'beta', 'bug-report-comments', id] as const,
  bugReportCommentsPage: (id: number | null, page: number) =>
    [...adminBetaQueryKeys.bugReportComments(id), { page }] as const,
};
