"use client";

import Link from "next/link";
import { useState } from "react";
import { Search } from "lucide-react";
import { AddStaffDialog } from "@/components/admin/add-staff-dialog";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { NativeSelect } from "@/components/ui/native-select";
import { Skeleton } from "@/components/ui/skeleton";
import { useUsers, type UserFilters } from "@/lib/api/admin";
import { formatDateTime, formatPhone } from "@/lib/format";

export function StaffList() {
  const [search, setSearch] = useState("");
  const [filters, setFilters] = useState<UserFilters>({ q: "", status: "", page: 1 });
  const users = useUsers(filters);
  const pagination = users.data?.pagination;

  return (
    <div className="space-y-4">
      <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <form
          role="search"
          className="flex flex-1 gap-2"
          onSubmit={(event) => {
            event.preventDefault();
            setFilters((current) => ({ ...current, q: search.trim(), page: 1 }));
          }}
        >
          <label htmlFor="staff-search" className="sr-only">
            Search staff
          </label>
          <Input id="staff-search" placeholder="Search by name, phone or email" value={search} onChange={(event) => setSearch(event.target.value)} className="sm:max-w-xs" />
          <Button type="submit" variant="outline" aria-label="Search">
            <Search aria-hidden />
          </Button>
          <label htmlFor="staff-status" className="sr-only">
            Status
          </label>
          <NativeSelect
            id="staff-status"
            className="w-auto"
            value={filters.status}
            onChange={(event) => setFilters((current) => ({ ...current, status: event.target.value as UserFilters["status"], page: 1 }))}
          >
            <option value="">Everyone</option>
            <option value="active">Active</option>
            <option value="disabled">Disabled</option>
          </NativeSelect>
        </form>
        <AddStaffDialog />
      </div>

      {users.isError ? <Alert variant="destructive">{users.error.message}</Alert> : null}

      <ul className="divide-y rounded-xl border bg-card" aria-busy={users.isFetching}>
        {users.isPending
          ? Array.from({ length: 4 }, (_, index) => (
              <li key={index} className="p-4">
                <Skeleton className="h-5 w-48" />
              </li>
            ))
          : null}
        {users.data?.users.length === 0 ? <li className="p-6 text-center text-sm text-muted-foreground">Nobody matches this search.</li> : null}
        {users.data?.users.map((user) => (
          <li key={user.id}>
            <Link href={`/admin/users/edit/?id=${user.id}`} className="flex flex-col gap-1 p-4 hover:bg-muted/50 sm:flex-row sm:items-center sm:justify-between">
              <span className="min-w-0">
                <span className="flex items-center gap-2 font-medium">
                  {user.name}
                  {!user.is_active ? <Badge variant="muted">Disabled</Badge> : null}
                  {user.is_active && user.must_change_password ? <Badge variant="warning">New password needed</Badge> : null}
                </span>
                <span className="block truncate text-sm text-muted-foreground">
                  {[formatPhone(user.phone), user.email].filter(Boolean).join(" · ")}
                </span>
              </span>
              <span className="flex flex-wrap items-center gap-1.5 sm:justify-end">
                {user.roles.map((role) => (
                  <Badge key={role.id} variant="secondary">
                    {role.name}
                  </Badge>
                ))}
                <span className="text-xs text-muted-foreground sm:ml-2">Last signed in: {formatDateTime(user.last_login_at)}</span>
              </span>
            </Link>
          </li>
        ))}
      </ul>

      {pagination && pagination.last_page > 1 ? (
        <div className="flex items-center justify-between text-sm">
          <span className="text-muted-foreground">
            Page {pagination.page} of {pagination.last_page} ({pagination.total} people)
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
