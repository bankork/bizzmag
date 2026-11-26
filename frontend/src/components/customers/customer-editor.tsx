"use client";

import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { useState } from "react";
import { ArrowLeft } from "lucide-react";
import { ConfirmDialog } from "@/components/admin/confirm-dialog";
import { CustomerFormFields, customerFieldErrors, type CustomerErrors, type CustomerValues } from "@/components/customers/customer-form-fields";
import { DuplicatePrompt, duplicateMatches } from "@/components/customers/duplicate-prompt";
import { describedBy, FormField } from "@/components/shared/form-field";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import { anonymiseCustomer, updateCustomer, useCustomer, useCustomerMutation, type CustomerInput } from "@/lib/api/customers";
import { ApiError } from "@/lib/api/errors";
import type { Customer, DuplicateMatch } from "@/lib/api/types";
import { useAuth } from "@/lib/auth/auth-provider";
import { formatDateTime } from "@/lib/format";

export function CustomerEditor({ basePath }: { basePath: string }) {
  const params = useSearchParams();
  const id = Number(params.get("id"));
  const valid = Number.isInteger(id) && id > 0;
  const customer = useCustomer(valid ? id : null);

  if (!valid || customer.isError) {
    return (
      <Alert variant="destructive">
        This customer was not found.{" "}
        <Link href={basePath} className="underline">
          Back to customers
        </Link>
      </Alert>
    );
  }

  if (!customer.data) {
    return <Skeleton className="h-64 w-full" />;
  }

  return <Profile key={`${customer.data.id}-${customer.data.version}`} customer={customer.data} basePath={basePath} />;
}

function Profile({ customer, basePath }: { customer: Customer; basePath: string }) {
  const auth = useAuth();

  return (
    <div className="space-y-5">
      <Link href={basePath} className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
        <ArrowLeft className="size-4" aria-hidden />
        All customers
      </Link>
      <div className="flex flex-wrap items-center gap-2">
        <h2 className="text-xl font-semibold">{customer.name}</h2>
        {customer.is_removed ? <Badge variant="muted">Personal data removed {formatDateTime(customer.removed_at)}</Badge> : null}
      </div>
      <p className="text-sm text-muted-foreground">Customer since {formatDateTime(customer.created_at)}</p>

      {customer.is_removed ? (
        <Alert variant="info">This customer asked for their personal data to be removed. Their visits and payments stay on record without their name or contact details.</Alert>
      ) : auth.can("customer.manage") ? (
        <DetailsCard customer={customer} basePath={basePath} />
      ) : (
        <ReadOnlyDetails customer={customer} />
      )}

      <Card>
        <CardHeader>
          <CardTitle>Visits and payments</CardTitle>
          <CardDescription>Visits appear here from Phase 3, and bills and payments from Phases 4 and 5.</CardDescription>
        </CardHeader>
      </Card>

      {!customer.is_removed && auth.isOwner ? <RemoveCard customer={customer} /> : null}
    </div>
  );
}

function ReadOnlyDetails({ customer }: { customer: Customer }) {
  return (
    <Card>
      <CardContent className="space-y-1 pt-6 text-sm">
        {customer.phone_display !== undefined ? <p>Phone: {customer.phone_display || "None"}</p> : null}
        {customer.email !== undefined ? <p>Email: {customer.email || "None"}</p> : null}
        <p>Notes: {customer.notes || "None"}</p>
      </CardContent>
    </Card>
  );
}

function DetailsCard({ customer, basePath }: { customer: Customer; basePath: string }) {
  const auth = useAuth();
  const showContact = auth.can("customer.view_contact");
  const [values, setValues] = useState<CustomerValues>({ name: customer.name, phone: customer.phone_display ?? "", email: customer.email ?? "", notes: customer.notes ?? "" });
  const [errors, setErrors] = useState<CustomerErrors>({});
  const [failure, setFailure] = useState<string | null>(null);
  const [duplicate, setDuplicate] = useState<{ message: string; matches: DuplicateMatch[] } | null>(null);
  const [saved, setSaved] = useState(false);
  const save = useCustomerMutation((body: CustomerInput) => updateCustomer(customer.id, customer.version, body));

  async function submit(confirmDuplicate: boolean) {
    setFailure(null);
    setSaved(false);

    if (values.name.trim() === "") {
      setErrors({ name: "Enter the customer's name." });

      return;
    }

    setErrors({});

    try {
      await save.mutateAsync({
        name: values.name.trim(),
        notes: values.notes.trim() || null,
        ...(showContact ? { phone: values.phone.trim() || null, email: values.email.trim() || null } : {}),
        confirm_duplicate: confirmDuplicate,
      });
      setSaved(true);
    } catch (error) {
      const matches = duplicateMatches(error);

      if (matches !== null && error instanceof ApiError) {
        setDuplicate({ message: error.message, matches });
      } else if (error instanceof ApiError && error.code === "STALE") {
        setFailure("Someone else changed this customer while you were editing. Reload the page to see their change, then try again.");
      } else if (error instanceof ApiError && Object.keys(error.fields).length > 0) {
        setErrors(customerFieldErrors(error.fields));
      } else {
        setFailure(error instanceof ApiError ? error.message : "Something went wrong. Please try again.");
      }
    }
  }

  return (
    <Card>
      <form
        noValidate
        onSubmit={(event) => {
          event.preventDefault();
          void submit(false);
        }}
      >
        <CardHeader>
          <CardTitle>Details</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {failure ? <Alert variant="destructive">{failure}</Alert> : null}
          {saved ? <Alert variant="success">Saved.</Alert> : null}
          <CustomerFormFields
            values={values}
            errors={errors}
            showContact={showContact}
            onChange={(next) => {
              setValues(next);

              if (next.phone !== values.phone) {
                setDuplicate(null);
              }
            }}
          />
          {duplicate ? <DuplicatePrompt {...duplicate} basePath={basePath} confirmLabel="Save anyway" busy={save.isPending} onConfirm={() => void submit(true)} /> : null}
        </CardContent>
        <CardFooter>
          <Button type="submit" disabled={save.isPending || duplicate !== null}>
            {save.isPending ? "Saving..." : "Save changes"}
          </Button>
        </CardFooter>
      </form>
    </Card>
  );
}

function RemoveCard({ customer }: { customer: Customer }) {
  const [typed, setTyped] = useState("");
  const remove = useCustomerMutation(() => anonymiseCustomer(customer.id));

  return (
    <Card>
      <CardHeader>
        <CardTitle>Remove personal data</CardTitle>
        <CardDescription>
          Only when the customer asks. Their name, phone, email and notes are deleted for good. Visits and payments stay on record as &quot;Removed customer&quot;. This cannot be undone.
        </CardDescription>
      </CardHeader>
      <CardFooter>
        <ConfirmDialog
          trigger={<Button variant="destructive">Remove personal data</Button>}
          title={`Remove ${customer.name}'s personal data?`}
          description="This cannot be undone. Type REMOVE to confirm."
          confirmLabel="Remove for good"
          destructive
          onConfirm={async () => {
            if (typed !== "REMOVE") {
              throw new ApiError({ status: 422, code: "VALIDATION_FAILED", message: "Type REMOVE to confirm." });
            }

            await remove.mutateAsync(undefined);
          }}
        >
          <FormField id="remove-confirm" label="Type REMOVE">
            <Input id="remove-confirm" autoComplete="off" value={typed} onChange={(event) => setTyped(event.target.value)} aria-describedby={describedBy("remove-confirm")} />
          </FormField>
        </ConfirmDialog>
      </CardFooter>
    </Card>
  );
}
