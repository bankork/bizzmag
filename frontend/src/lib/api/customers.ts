"use client";

import { keepPreviousData, useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { apiFetch } from "./client";
import type { Customer, Pagination } from "./types";

export type CustomerFilters = { q?: string; page?: number };

export const customerKeys = {
  list: (filters: CustomerFilters) => ["customers", filters] as const,
  customer: (id: number) => ["customer", id] as const,
};

export function useCustomers(filters: CustomerFilters) {
  return useQuery({
    queryKey: customerKeys.list(filters),
    queryFn: async () => {
      const result = await apiFetch<Customer[]>("customers", { query: { "filter[q]": filters.q || undefined, page: filters.page } });

      return { customers: result.data, pagination: result.meta?.pagination as Pagination | undefined };
    },
    placeholderData: keepPreviousData,
  });
}

export function useCustomer(id: number | null) {
  return useQuery({
    queryKey: customerKeys.customer(id ?? 0),
    queryFn: async () => (await apiFetch<Customer>(`customers/${id}`)).data,
    enabled: id !== null,
  });
}

export function useCustomerMutation<TVariables>(request: (variables: TVariables) => Promise<Customer>) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: request,
    onSuccess: (customer) => {
      queryClient.setQueryData(customerKeys.customer(customer.id), customer);
      void queryClient.invalidateQueries({ queryKey: ["customers"] });
    },
  });
}

export type CustomerInput = { name?: string; phone?: string | null; email?: string | null; notes?: string | null; confirm_duplicate?: boolean };

export async function createCustomer(body: CustomerInput): Promise<Customer> {
  return (await apiFetch<Customer>("customers", { method: "POST", body })).data;
}

export async function updateCustomer(id: number, version: number, body: CustomerInput): Promise<Customer> {
  return (await apiFetch<Customer>(`customers/${id}`, { method: "PATCH", body: { version, ...body } })).data;
}

export async function anonymiseCustomer(id: number): Promise<Customer> {
  return (await apiFetch<Customer>(`customers/${id}/anonymise`, { method: "POST", body: { confirm: "REMOVE" } })).data;
}
