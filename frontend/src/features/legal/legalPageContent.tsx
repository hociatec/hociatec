import type { ReactNode } from 'react';

export type LegalSectionContent = {
  title: string;
  body: ReactNode;
};

export const renderLegalSections = (sections: LegalSectionContent[]) =>
  sections.map((section) => (
    <section key={section.title}>
      <h2>{section.title}</h2>
      {section.body}
    </section>
  ));
