"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { apiFetch } from "./client";
import type { CatalogueCategory, CatalogueHistoryEntry, CatalogueItem, ItemType } from "./types";

/**
 * Queries and mutations for the catalogue screens. Every write returns the
 * saved record, and the lists are refetched so the order stays the server's.
 */

export const catalogueKeys = {
  categories: ["catalogue", "categories"] as const,
  items: ["catalogue", "items"] as const,
  item: (id: number) => ["catalogue", "item", id] as const,
  history: (id: number) => ["catalogue", "history", id] as const,
};

export function useCategories() {
  return useQuery({ queryKey: catalogueKeys.categories, queryFn: async () => (await apiFetch<CatalogueCategory[]>("catalogue/categories")).data });
}

export function useCatalogueItems() {
  return useQuery({ queryKey: catalogueKeys.items, queryFn: async () => (await apiFetch<CatalogueItem[]>("catalogue/items")).data });
}

export function useCatalogueItem(id: number | null) {
  return useQuery({
    queryKey: catalogueKeys.item(id ?? 0),
    queryFn: async () => (await apiFetch<CatalogueItem>(`catalogue/items/${id}`)).data,
    enabled: id !== null,
  });
}

export function useCatalogueHistory(id: number | null) {
  return useQuery({
    queryKey: catalogueKeys.history(id ?? 0),
    queryFn: async () => (await apiFetch<CatalogueHistoryEntry[]>(`catalogue/items/${id}/history`)).data,
    enabled: id !== null,
  });
}

/** Any catalogue write: refresh every catalogue query. */
export function useCatalogueMutation<TVariables, TResult>(request: (variables: TVariables) => Promise<TResult>) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: request,
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ["catalogue"] });
    },
  });
}

export type ItemInput = {
  category_id?: number;
  parent_id?: number;
  name?: string;
  description?: string | null;
  has_variants?: boolean;
  price_kobo?: number | null;
  taxable?: boolean;
  quantity_allowed?: boolean;
  allocation_method?: string | null;
  primary_share_bp?: number | null;
  sort_order?: number;
};

export async function createItem(body: ItemInput): Promise<CatalogueItem> {
  return (await apiFetch<CatalogueItem>("catalogue/items", { method: "POST", body })).data;
}

export async function updateItem(id: number, version: number, body: ItemInput): Promise<CatalogueItem> {
  return (await apiFetch<CatalogueItem>(`catalogue/items/${id}`, { method: "PATCH", body: { version, ...body } })).data;
}

export async function setItemActive(id: number, active: boolean): Promise<CatalogueItem> {
  return (await apiFetch<CatalogueItem>(`catalogue/items/${id}/${active ? "activate" : "deactivate"}`, { method: "POST" })).data;
}

export async function createCategory(type: ItemType, name: string): Promise<CatalogueCategory> {
  return (await apiFetch<CatalogueCategory>("catalogue/categories", { method: "POST", body: { type, name } })).data;
}

export async function renameCategory(category: CatalogueCategory, name: string): Promise<CatalogueCategory> {
  return (await apiFetch<CatalogueCategory>(`catalogue/categories/${category.id}`, { method: "PATCH", body: { version: category.version, name } })).data;
}

export async function setCategoryActive(id: number, active: boolean): Promise<CatalogueCategory> {
  return (await apiFetch<CatalogueCategory>(`catalogue/categories/${id}/${active ? "activate" : "deactivate"}`, { method: "POST" })).data;
}

export async function reorderCategories(type: ItemType, ids: number[]): Promise<CatalogueCategory[]> {
  return (await apiFetch<CatalogueCategory[]>("catalogue/categories/reorder", { method: "POST", body: { type, ids } })).data;
}
