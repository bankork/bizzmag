"use client";

import Link from "next/link";
import { useState } from "react";
import { Search } from "lucide-react";
import { AddCustomerDialog } from "@/components/customers/add-customer-dialog";
import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import { useCustomers, type CustomerFilters } from "@/lib/api/customers";
import { useAuth } from "@/lib/auth/auth-provider";

/**
 * Customer search (FR-CUS-008). `basePath` keeps the person in their own
 * area: /desk/customers/ or /admin/customers/.
 */
export function CustomerList({ basePath }: { basePath: string }) {
  const auth = useAuth();
  const [search, setSearch] = useState("");
  const [filters, setFilters] = useState<CustomerFilters>({ q: "", page: 1 });
  const customers = useCustomers(filters);
  const pagination = customers.data?.pagination;
  const showContact = auth.can("customer.view_contact");

  return (
    <div className="space-y-4">
      <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <form
          role="search"
          className="flex flex-1 gap-2"
          onSubmit={(event) => {
            event.preventDefault();
            setFilters({ q: search.trim(), page: 1 });
          }}
        >
          <label htmlFor="customer-search" className="sr-only">
            Search customers
          </label>
          <Input id="customer-search" placeholder={showContact ? "Name or phone number" : "Name"} value={search} onChange={(event) => setSearch(event.target.value)} className="sm:max-w-xs" />
          <Button type="submit" variant="outline" aria-label="Search">
            <Search aria-hidden />
          </Button>
        </form>
        {auth.can("customer.manage") ? <AddCustomerDialog basePath={basePath} /> : null}
      </div>

      {customers.isError ? <Alert variant="destructive">{customers.error.message}</Alert> : null}

      <ul className="divide-y rounded-xl border bg-card" aria-busy={customers.isFetching}>
        {customers.isPending
          ? Array.from({ length: 4 }, (_, index) => (
              <li key={index} className="p-4">
                <Skeleton className="h-5 w-48" />
              </li>
            ))
          : null}
        {customers.data?.customers.length === 0 ? (
          <li className="p-6 text-center text-sm text-muted-foreground">{filters.q ? "Nobody matches this search." : "No customers yet."}</li>
        ) : null}
        {customers.data?.customers.map((customer) => (
          <li key={customer.id}>
            <Link href={`${basePath}edit/?id=${customer.id}`} className="flex flex-col gap-0.5 p-4 hover:bg-muted/50 sm:flex-row sm:items-center sm:justify-between">
              <span className="font-medium">{customer.name}</span>
              <span className="text-sm text-muted-foreground tabular-nums">
                {showContact ? customer.phone_display || "No phone" : customer.has_phone ? "Phone on file" : "No phone"}
              </span>
            </Link>
          </li>
        ))}
      </ul>

      {pagination && pagination.last_page > 1 ? (
        <div className="flex items-center justify-between text-sm">
          <span className="text-muted-foreground">
            Page {pagination.page} of {pagination.last_page} ({pagination.total} customers)
          </span>
          <div className="flex gap-2">
            <Button variant="outline" size="sm" disabled={pagination.page <= 1} onClick={() => setFilters((current) => ({ ...current, page: (current.page ?? 1) - 1 }))}>
              Previous
            </Button>
            <Button variant="outline" size="sm" disabled={pagination.page >= pagination.last_page} onClick={() => setFilters((current) => ({ ...current, page: (current.page ?? 1) + 1 }))}>
              Next
            </Button>
          </div>
        </div>
      ) : null}
    </div>
  );
}
