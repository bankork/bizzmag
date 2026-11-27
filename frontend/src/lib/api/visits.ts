"use client";

import { keepPreviousData, useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { apiFetch } from "./client";
import type { AllocationMethod, Approval, CustomerVisit, Execution, MyWork, Order, OrderStatus, Pagination, StaffMember } from "./types";

/**
 * Visits, their items and recorded work (SRS §4.5 to §4.8). Every change
 * answers with the whole visit as the signed-in person may see it, and the
 * lists are refetched so everyone's screen catches up.
 */

/** How often open visits refresh by themselves while a screen shows them. */
export const LIVE_REFRESH_MS = 15_000;

export type OrderFilters = { status?: OrderStatus | "all"; q?: string; page?: number };

export const visitKeys = {
  all: ["visits"] as const,
  list: (filters: OrderFilters) => ["visits", "list", filters] as const,
  order: (id: number) => ["visits", "order", id] as const,
  approvals: ["visits", "approvals"] as const,
  mine: (page: number) => ["visits", "mine", page] as const,
  customer: (id: number) => ["visits", "customer", id] as const,
  staff: ["staff-directory"] as const,
};

export function useOrders(filters: OrderFilters, live = true) {
  return useQuery({
    queryKey: visitKeys.list(filters),
    queryFn: async () => {
      const result = await apiFetch<Order[]>("orders", { query: { "filter[status]": filters.status, "filter[q]": filters.q || undefined, page: filters.page } });

      return { orders: result.data, pagination: result.meta?.pagination as Pagination | undefined };
    },
    placeholderData: keepPreviousData,
    refetchInterval: live ? LIVE_REFRESH_MS : false,
  });
}

export function useOrder(id: number | null) {
  return useQuery({
    queryKey: visitKeys.order(id ?? 0),
    queryFn: async () => (await apiFetch<Order>(`orders/${id}`)).data,
    enabled: id !== null,
    refetchInterval: LIVE_REFRESH_MS,
  });
}

export function useStaffDirectory() {
  return useQuery({ queryKey: visitKeys.staff, queryFn: async () => (await apiFetch<StaffMember[]>("staff-directory")).data, staleTime: 60_000 });
}

export function useApprovals() {
  return useQuery({ queryKey: visitKeys.approvals, queryFn: async () => (await apiFetch<Approval[]>("approvals")).data, refetchInterval: LIVE_REFRESH_MS });
}

export function useMyWork(page: number) {
  return useQuery({
    queryKey: visitKeys.mine(page),
    queryFn: async () => {
      const result = await apiFetch<MyWork[]>("executions/mine", { query: { page } });

      return { work: result.data, pagination: result.meta?.pagination as Pagination | undefined };
    },
    placeholderData: keepPreviousData,
  });
}

export function useCustomerVisits(customerId: number | null, enabled: boolean) {
  return useQuery({
    queryKey: visitKeys.customer(customerId ?? 0),
    queryFn: async () => (await apiFetch<CustomerVisit[]>(`customers/${customerId}/orders`)).data,
    enabled: enabled && customerId !== null,
  });
}

/** Any change to a visit: store the returned visit and refresh the lists. */
export function useVisitMutation<TVariables, TResult>(request: (variables: TVariables) => Promise<TResult>) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: request,
    onSuccess: (result) => {
      if (isOrder(result)) {
        queryClient.setQueryData(visitKeys.order(result.id), result);
      }

      void queryClient.invalidateQueries({ queryKey: visitKeys.all });
    },
  });
}

function isOrder(value: unknown): value is Order {
  return typeof value === "object" && value !== null && "reference" in value && "items" in value;
}

export type NewVisitItem = { catalogue_item_id: number; quantity?: number; employee_ids?: number[] };

export type NewVisit = { customer_id?: number | null; guest_name?: string | null; guest_phone?: string | null; items: NewVisitItem[] };

async function post(path: string, body?: unknown): Promise<Order> {
  return (await apiFetch<Order>(path, { method: "POST", body })).data;
}

export const visitRequests = {
  create: (visit: NewVisit) => post("orders", visit),
  addItem: ({ orderId, item }: { orderId: number; item: NewVisitItem }) => post(`orders/${orderId}/items`, item),
  requestExtra: ({ orderId, itemId, note }: { orderId: number; itemId: number; note?: string | null }) => post(`orders/${orderId}/additional-requests`, { catalogue_item_id: itemId, note }),
  linkCustomer: ({ orderId, customerId }: { orderId: number; customerId: number }) => post(`orders/${orderId}/customer`, { customer_id: customerId }),
  cancelVisit: ({ orderId, reason }: { orderId: number; reason: string }) => post(`orders/${orderId}/cancel`, { reason }),
  assign: async ({ itemId, employeeIds }: { itemId: number; employeeIds: number[] }) =>
    (await apiFetch<Order>(`order-items/${itemId}/assignments`, { method: "PUT", body: { employee_ids: employeeIds } })).data,
  cancelItem: ({ itemId, reason }: { itemId: number; reason: string }) => post(`order-items/${itemId}/cancel`, { reason }),
  overridePrice: ({ itemId, priceKobo, reason }: { itemId: number; priceKobo: number; reason: string }) => post(`order-items/${itemId}/price-override`, { price_kobo: priceKobo, reason }),
  approve: ({ itemId }: { itemId: number }) => post(`order-items/${itemId}/approve`),
  reject: ({ itemId, reason }: { itemId: number; reason: string }) => post(`order-items/${itemId}/reject`, { reason }),
  withdraw: ({ itemId }: { itemId: number }) => post(`order-items/${itemId}/withdraw`),
};

export type SplitInput = { method: AllocationMethod; primary_share_bp?: number | null; shares?: { employee_id: number; share_bp: number }[]; reason: string };

export const workRequests = {
  /** One idempotency key per tap intent: a retry on a weak signal records once. */
  submit: async ({ itemId, colleagueIds, primaryId, key }: { itemId: number; colleagueIds: number[]; primaryId?: number | null; key: string }) =>
    (await apiFetch<Execution & { order_id: number }>(`order-items/${itemId}/executions`, {
      method: "POST",
      body: { contributor_employee_ids: colleagueIds, ...(primaryId ? { primary_employee_id: primaryId } : {}) },
      idempotencyKey: key,
    })).data,
  join: ({ executionId }: { executionId: number }) => post(`executions/${executionId}/join`),
  editPeople: async ({ executionId, employeeIds }: { executionId: number; employeeIds: number[] }) =>
    (await apiFetch<Order>(`executions/${executionId}/contributors`, { method: "PUT", body: { employee_ids: employeeIds } })).data,
  dispute: ({ executionId, reason }: { executionId: number; reason: string }) => post(`executions/${executionId}/dispute`, { reason }),
  dismissDispute: ({ executionId, reason }: { executionId: number; reason: string }) => post(`executions/${executionId}/dismiss-dispute`, { reason }),
  cancel: ({ executionId, reason }: { executionId: number; reason: string }) => post(`executions/${executionId}/cancel`, { reason }),
  overrideSplit: ({ executionId, split, primaryId }: { executionId: number; split: SplitInput; primaryId?: number }) =>
    post(`executions/${executionId}/allocation`, { ...split, ...(primaryId ? { primary_employee_id: primaryId } : {}) }),
  correct: ({ executionId, employeeIds, primaryId, reason }: { executionId: number; employeeIds: number[]; primaryId: number; reason: string }) =>
    post(`executions/${executionId}/correct`, { employee_ids: employeeIds, primary_employee_id: primaryId, reason }),
};
