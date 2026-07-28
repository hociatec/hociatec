import type { EditableProfile } from '../lib/betaProfileForm';
import { normalizeCheckboxSelection } from '../lib/betaProfileForm';

interface BetaProfileCheckboxGroupProps {
  form: EditableProfile;
  label: string;
  name: keyof EditableProfile;
  options: readonly { value: string; label: string }[];
  required?: boolean;
  setForm: React.Dispatch<React.SetStateAction<EditableProfile | null>>;
}

export const BetaProfileCheckboxGroup = ({
  form,
  label,
  name,
  options,
  required = false,
  setForm,
}: BetaProfileCheckboxGroupProps) => {
  const current = Array.isArray(form[name]) ? (form[name] as string[]) : [];

  return (
    <section className="rounded-lg border border-stone-200 bg-stone-50 p-4">
      <h2 className="text-sm font-semibold text-stone-700">
        {label} {required ? '*' : ''}
      </h2>
      <div className="mt-3 grid gap-2 sm:grid-cols-2">
        {options.map(({ value, label: text }) => (
          <label
            key={value}
            className="grid cursor-pointer select-none grid-cols-[1rem_1fr] items-center gap-3 rounded-lg bg-white px-3 py-2 text-sm text-stone-700 hover:text-stone-950"
          >
            <input
              type="checkbox"
              name={String(name)}
              value={value}
              checked={current.includes(value)}
              onChange={(event) =>
                setForm((previous) => {
                  if (!previous) return null;
                  return {
                    ...previous,
                    [name]: normalizeCheckboxSelection(
                      name,
                      value,
                      event.target.checked,
                      current,
                    ),
                  };
                })
              }
              aria-label={text}
              className="h-4 w-4 shrink-0 rounded border-stone-300 text-brand-700 focus:ring-brand-500"
            />
            <span aria-hidden="true" className="leading-5">{text}</span>
          </label>
        ))}
      </div>
    </section>
  );
};
