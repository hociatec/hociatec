import { useNavigate } from 'react-router-dom';
import { PageContainer } from '@/shared/components/PageContainer';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { BetaBugReportDialog } from '../components/BetaBugReportDialog';

export const BetaBugReportPage = () => {
  const navigate = useNavigate();

  const handleClose = () => {
    navigate('/beta');
  };

  return (
    <SiteLayout headerVariant="light">
      <PageContainer title="Nouveau signalement">
        <p className="text-stone-600 mb-6">Chargement du formulaire de signalement...</p>
        <BetaBugReportDialog open={true} onClose={handleClose} />
      </PageContainer>
    </SiteLayout>
  );
};
