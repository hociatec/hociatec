import type { TradeInInput } from '../types';
import { TradeInConsentSubmit } from './form/TradeInConsentSubmit';
import { TradeInContactFields } from './form/TradeInContactFields';
import { TradeInDeviceFields } from './form/TradeInDeviceFields';
import { TradeInPaymentFields } from './form/TradeInPaymentFields';

interface TradeInFormFieldsProps {
  categories: [string, string][];
  conditions: [string, string][];
  form: TradeInInput;
  isAuthenticated: boolean;
  saving: boolean;
  onChange: <K extends keyof TradeInInput>(key: K, value: TradeInInput[K]) => void;
}

export const TradeInFormFields = ({
  categories,
  conditions,
  form,
  isAuthenticated,
  saving,
  onChange,
}: TradeInFormFieldsProps) => (
  <>
    <TradeInContactFields form={form} isAuthenticated={isAuthenticated} onChange={onChange} />
    <TradeInDeviceFields categories={categories} conditions={conditions} form={form} onChange={onChange} />
    <TradeInPaymentFields onChange={onChange} />
    <TradeInConsentSubmit form={form} saving={saving} onChange={onChange} />
  </>
);
