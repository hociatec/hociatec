export interface AdminDashboardSectionLink {
  to: string;
  title: string;
  icon?: React.ReactNode;
}

export interface AdminDashboardSection {
  id: string;
  title: string;
  subtitle: string;
  links: AdminDashboardSectionLink[];
}
