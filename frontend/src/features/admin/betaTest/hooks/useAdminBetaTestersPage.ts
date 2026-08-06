import { useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import {
  deleteAdminBetaTester,
  fetchAdminBetaTesters,
  updateAdminBetaTester,
  type AdminBetaTesterDto,
  type PaginationMeta,
} from '../api';
import { fetchBetaProfileChoices, formatBetaList, type BetaProfileChoices } from '@/features/betaTest/publicApi';
import { useConfirm } from '@/shared/components/ui/confirm';
import { adminBetaQueryKeys } from '@/features/betaTest/queryKeys';
import { omitUndefinedProperties } from '@/shared/lib/object';

export const useAdminBetaTestersPage = () => {
  const confirm = useConfirm();
  const queryClient = useQueryClient();
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);
  const [selectedTester, setSelectedTester] = useState<AdminBetaTesterDto | null>(null);

  const testersQuery = useQuery<{ items: AdminBetaTesterDto[]; meta: PaginationMeta }, Error>({
    queryKey: [...adminBetaQueryKeys.testers(search, status), { page }],
    queryFn: () =>
      fetchAdminBetaTesters(omitUndefinedProperties({
        page,
        perPage: 10,
        search: search || undefined,
        status: status || undefined,
      })),
  });
  const choicesQuery = useQuery<BetaProfileChoices, Error>({
    queryKey: adminBetaQueryKeys.profileChoices(),
    queryFn: fetchBetaProfileChoices,
  });

  const invalidateTesters = () =>
    queryClient.invalidateQueries({ queryKey: adminBetaQueryKeys.testers(search, status) });

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
  }, [search, status]);
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
