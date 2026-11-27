"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";
import { Trash2, UserRound, X } from "lucide-react";
import { AddCustomerDialog } from "@/components/customers/add-customer-dialog";
import { FormField } from "@/components/shared/form-field";
import { CustomerSearch } from "@/components/visits/customer-search";
import { ItemPicker } from "@/components/visits/item-picker";
import { StaffPicker } from "@/components/visits/staff-picker";
import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { ApiError } from "@/lib/api/errors";
import type { Customer } from "@/lib/api/types";
import { useVisitMutation, visitRequests } from "@/lib/api/visits";
import { useAuth } from "@/lib/auth/auth-provider";
import { formatKobo } from "@/lib/money";
import type { VisitChoice } from "@/lib/visits";

type Line = { key: number; choice: VisitChoice; quantity: number; employeeIds: number[]; assigning: boolean };

/**
 * Desk › New visit (FR-ORD-001 to 006, FR-CUS-003 to 005): a customer or a
 * guest, then services and products from the catalogue, optionally with
 * the staff expected to do each service. Prices come from the catalogue;
 * the server works out the totals.
 */
export function NewVisitForm() {
  const auth = useAuth();
  const router = useRouter();
  const [customer, setCustomer] = useState<Customer | null>(null);
  const [guest, setGuest] = useState({ name: "", phone: "" });
  const [lines, setLines] = useState<Line[]>([]);
  const [nextKey, setNextKey] = useState(1);
  const [failure, setFailure] = useState<string | null>(null);
  const create = useVisitMutation(visitRequests.create);
  const canAssign = auth.can("order.assign");

  function add(choice: VisitChoice) {
    setLines((current) => [...current, { key: nextKey, choice, quantity: 1, employeeIds: [], assigning: false }]);
    setNextKey((key) => key + 1);
  }

  function update(key: number, change: Partial<Line>) {
    setLines((current) => current.map((line) => (line.key === key ? { ...line, ...change } : line)));
  }

  async function open() {
    setFailure(null);

    if (lines.length === 0) {
      setFailure("Add at least one service or product.");

      return;
    }

    try {
      const visit = await create.mutateAsync({
        ...(customer ? { customer_id: customer.id } : { guest_name: guest.name.trim() || null, guest_phone: guest.phone.trim() || null }),
        items: lines.map((line) => ({
          catalogue_item_id: line.choice.id,
          ...(line.quantity > 1 ? { quantity: line.quantity } : {}),
          ...(line.employeeIds.length > 0 ? { employee_ids: line.employeeIds } : {}),
        })),
      });
      router.push(`/desk/visit/?id=${visit.id}`);
    } catch (error) {
      setFailure(error instanceof ApiError ? (Object.values(error.fields)[0]?.[0] ?? error.message) : "Something went wrong. Please try again.");
    }
  }

  return (
    <div className="grid gap-4 lg:grid-cols-2">
      <Card>
        <CardHeader>
          <CardTitle>Who is it for?</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {customer ? (
            <div className="flex items-center justify-between rounded-lg border bg-muted/40 p-3">
              <span className="flex items-center gap-2 font-medium">
                <UserRound className="size-4" aria-hidden />
                {customer.name}
              </span>
              <Button type="button" variant="ghost" size="sm" onClick={() => setCustomer(null)}>
                <X aria-hidden />
                Change
              </Button>
            </div>
          ) : (
            <>
              <CustomerSearch onPick={setCustomer} />
              {auth.can("customer.manage") ? <AddCustomerDialog basePath="/desk/customers/" onCreated={setCustomer} /> : null}
              <div className="space-y-3 border-t pt-4">
                <p className="text-sm text-muted-foreground">Or a guest. Name and phone are optional.</p>
                <FormField id="guest-name" label="Guest name">
                  <Input id="guest-name" value={guest.name} maxLength={100} onChange={(event) => setGuest({ ...guest, name: event.target.value })} />
                </FormField>
                {auth.can("customer.view_contact") ? (
                  <FormField id="guest-phone" label="Guest phone">
                    <Input id="guest-phone" inputMode="tel" value={guest.phone} onChange={(event) => setGuest({ ...guest, phone: event.target.value })} />
                  </FormField>
                ) : null}
              </div>
            </>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Services and products</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <ItemPicker onPick={add} />
          {lines.length > 0 ? (
            <ul className="space-y-2" aria-label="On this visit">
              {lines.map((line) => (
                <li key={line.key} className="space-y-3 rounded-lg border p-3">
                  <div className="flex items-start justify-between gap-2">
                    <div>
                      <p className="font-medium">{line.choice.name}</p>
                      {line.choice.priceKobo != null ? <p className="text-sm text-muted-foreground tabular-nums">{formatKobo(line.choice.priceKobo)} each</p> : null}
                    </div>
                    <Button type="button" variant="ghost" size="icon-sm" aria-label={`Remove ${line.choice.name}`} onClick={() => setLines((current) => current.filter((other) => other.key !== line.key))}>
                      <Trash2 aria-hidden />
                    </Button>
                  </div>
                  <div className="flex flex-wrap items-end gap-3">
                    {line.choice.quantityAllowed ? (
                      <FormField id={`quantity-${line.key}`} label="Quantity">
                        <Input
                          id={`quantity-${line.key}`}
                          type="number"
                          min={1}
                          max={99}
                          className="w-24"
                          value={line.quantity}
                          onChange={(event) => update(line.key, { quantity: Math.max(1, Math.min(99, Number(event.target.value) || 1)) })}
                        />
                      </FormField>
                    ) : null}
                    {line.choice.type === "service" && canAssign ? (
                      <Button type="button" variant="outline" size="sm" onClick={() => update(line.key, { assigning: !line.assigning })}>
                        {line.employeeIds.length > 0 ? `Assigned to ${line.employeeIds.length}` : "Assign staff"}
                      </Button>
                    ) : null}
                  </div>
                  {line.assigning ? <StaffPicker legend={`Who will do ${line.choice.name}?`} selected={line.employeeIds} onChange={(ids) => update(line.key, { employeeIds: ids })} /> : null}
                </li>
              ))}
            </ul>
          ) : (
            <p className="text-sm text-muted-foreground">Nothing added yet.</p>
          )}
          {failure ? <Alert variant="destructive">{failure}</Alert> : null}
          <Button type="button" size="lg" className="w-full" disabled={create.isPending} onClick={open}>
            {create.isPending ? "Opening..." : "Open visit"}
          </Button>
        </CardContent>
      </Card>
    </div>
  );
}
