import { useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useSearchParams } from 'react-router';

import {
  deleteAdminBetaTester,
  fetchAdminBetaTesters,
  updateAdminBetaTester,
  type AdminBetaTesterDto,
  type PaginationMeta,
} from '../api';
import { fetchBetaProfileChoices, formatBetaList, type BetaProfileChoices } from '@/features/betaTest/publicApi';
import { useConfirm } from '@/shared/components/ui/confirm';
import { adminBetaQueryKeys } from '@/features/betaTest/publicApi';
import { omitUndefinedProperties } from '@/shared/lib/object';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

export const useAdminBetaTestersPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const confirm = useConfirm();
  const queryClient = useQueryClient();
  const [search, setSearch] = useState(searchParams.get('q') ?? '');
  const [status, setStatus] = useState(searchParams.get('status') ?? '');
  const [page, setPage] = useState(parseNullablePositiveInteger(searchParams.get('page')) ?? 1);
  const [selectedTester, setSelectedTester] = useState<AdminBetaTesterDto | null>(null);
  const debouncedSearch = useDebounce(search.trim(), 250);

  const testersQuery = useQuery<{ items: AdminBetaTesterDto[]; meta: PaginationMeta }, Error>({
    queryKey: [...adminBetaQueryKeys.testers(debouncedSearch, status), { page }],
    queryFn: () =>
      fetchAdminBetaTesters(omitUndefinedProperties({
        page,
        perPage: 10,
        q: debouncedSearch || undefined,
        status: status || undefined,
      })),
  });
  const choicesQuery = useQuery<BetaProfileChoices, Error>({
    queryKey: adminBetaQueryKeys.profileChoices(),
    queryFn: fetchBetaProfileChoices,
  });

  const invalidateTesters = () =>
    queryClient.invalidateQueries({ queryKey: adminBetaQueryKeys.testers(debouncedSearch, status) });

  const updateMutation = useMutation({
    mutationFn: ({ id, nextStatus }: { id: number; nextStatus: string }) =>
      updateAdminBetaTester(id, nextStatus),
    onSuccess: invalidateTesters,
  });
  const deleteMutation = useMutation({
    mutationFn: deleteAdminBetaTester,
    onSuccess: invalidateTesters,
  });

  const choices = choicesQuery.data ?? {};
  const testers = testersQuery.data?.items ?? [];
  const testersPagination = testersQuery.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 };
  useEffect(() => {
    setPage(1);
  }, [debouncedSearch, status]);
  useEffect(() => {
    const next = new URLSearchParams();
    if (search.trim()) {
      next.set('q', search.trim());
    }
    if (status) {
      next.set('status', status);
    }
    if (page > 1) {
      next.set('page', String(page));
    }
    setSearchParams(next, { replace: true });
  }, [page, search, setSearchParams, status]);
  const formatChoiceList = (group: string, values: string[]) => {
    const labels = new Map((choices[group] ?? []).map((choice) => [choice.value, choice.label]));
    const readableValues = values.map((value) => labels.get(value) ?? value);

    return formatBetaList(readableValues);
  };

  const deleteTester = async (tester: AdminBetaTesterDto) => {
    if (await confirm({
      title: 'Supprimer le profil bêta',
      description: 'Supprimer définitivement ce profil bêta et ses données ?',
      confirmLabel: 'Supprimer',
      cancelLabel: 'Annuler',
    })) {
      deleteMutation.mutate(tester.id);
    }
  };

  return {
    deleteMutation,
    formatChoiceList,
    search,
    selectedTester,
    setSearch,
    setSelectedTester,
    setStatus,
    status,
    testers,
    testersPagination,
    setPage,
    testersQuery,
    updateMutation,
    deleteTester,
  };
};
