import { useState } from 'react';
import type { FormEvent } from 'react';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { useToast } from '@/shared/components/ui/toast';
import { sendContactMessage } from '../api/contactApi';

export const ContactPage = () => {
  useDocumentTitle('Contact');
  useMetaTags({
    title: 'Contact — hociatec',
    description: 'Contactez-nous pour vos projets, devis, rendez-vous et audits.',
    type: 'website',
  });
  const toast = useToast();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [subject, setSubject] = useState('');
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      await sendContactMessage({ name, email, subject, message });
      try {
        toast.show('Merci de nous avoir contactés. Votre demande sera traitée rapidement.', {
          variant: 'success',
        });
      } catch {}
      setName('');
      setEmail('');
      setSubject('');
      setMessage('');
    } catch (err) {
      const details = (err as Error & { details?: string[] }).details;
      try {
        toast.show(details?.[0] ?? (err as Error).message, { variant: 'error' });
      } catch {}
    } finally {
      setLoading(false);
    }
  };

  return (
    <SiteLayout headerVariant="light">
      <div className="container mx-auto max-w-2xl p-4">
        <h1 className="text-2xl font-semibold mb-4">Contact</h1>
        <form onSubmit={onSubmit} className="space-y-3">
          <div>
            <label className="block text-sm mb-1">Nom</label>
            <input
              className="w-full border rounded p-2"
              value={name}
              onChange={(e) => setName(e.target.value)}
              required
              maxLength={100}
            />
          </div>
          <div>
            <label className="block text-sm mb-1">Email</label>
            <input
              type="email"
              className="w-full border rounded p-2"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              maxLength={180}
            />
          </div>
          <div>
            <label className="block text-sm mb-1">Sujet</label>
            <input
              className="w-full border rounded p-2"
              value={subject}
              onChange={(e) => setSubject(e.target.value)}
              required
              maxLength={150}
            />
          </div>
          <div>
            <label className="block text-sm mb-1">Message</label>
            <textarea
              className="w-full border rounded p-2 h-40"
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              required
              maxLength={5000}
            />
          </div>
          <button
            disabled={loading}
            className="bg-blue-600 text-white px-4 py-2 rounded disabled:opacity-60"
          >
            {loading ? 'Envoi…' : 'Envoyer'}
          </button>
        </form>
      </div>
    </SiteLayout>
  );
};
