"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";
import { UserPlus } from "lucide-react";
import { CustomerFormFields, customerFieldErrors, type CustomerErrors, type CustomerValues } from "@/components/customers/customer-form-fields";
import { DuplicatePrompt, duplicateMatches } from "@/components/customers/duplicate-prompt";
import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { createCustomer, useCustomerMutation, type CustomerInput } from "@/lib/api/customers";
import { ApiError } from "@/lib/api/errors";
import type { DuplicateMatch } from "@/lib/api/types";
import { useAuth } from "@/lib/auth/auth-provider";

const BLANK: CustomerValues = { name: "", phone: "", email: "", notes: "" };

export function AddCustomerDialog({ basePath }: { basePath: string }) {
  const auth = useAuth();
  const router = useRouter();
  const [open, setOpen] = useState(false);
  const [values, setValues] = useState<CustomerValues>(BLANK);
  const [errors, setErrors] = useState<CustomerErrors>({});
  const [failure, setFailure] = useState<string | null>(null);
  const [duplicate, setDuplicate] = useState<{ message: string; matches: DuplicateMatch[] } | null>(null);
  const create = useCustomerMutation(createCustomer);
  const showContact = auth.can("customer.view_contact");

  async function save(confirmDuplicate: boolean) {
    setFailure(null);

    if (values.name.trim() === "") {
      setErrors({ name: "Enter the customer's name." });

      return;
    }

    setErrors({});
    const body: CustomerInput = {
      name: values.name.trim(),
      notes: values.notes.trim() || null,
      ...(showContact ? { phone: values.phone.trim() || null, email: values.email.trim() || null } : {}),
      confirm_duplicate: confirmDuplicate,
    };

    try {
      const customer = await create.mutateAsync(body);
      setOpen(false);
      router.push(`${basePath}edit/?id=${customer.id}`);
    } catch (error) {
      const matches = duplicateMatches(error);

      if (matches !== null && error instanceof ApiError) {
        setDuplicate({ message: error.message, matches });
      } else if (error instanceof ApiError && Object.keys(error.fields).length > 0) {
        setErrors(customerFieldErrors(error.fields));
      } else {
        setFailure(error instanceof ApiError ? error.message : "Something went wrong. Please try again.");
      }
    }
  }

  return (
    <Dialog
      open={open}
      onOpenChange={(next) => {
        setOpen(next);

        if (next) {
          setValues(BLANK);
          setErrors({});
          setFailure(null);
          setDuplicate(null);
        }
      }}
    >
      <DialogTrigger render={<Button />}>
        <UserPlus aria-hidden />
        Add customer
      </DialogTrigger>
      <DialogContent>
        <DialogTitle>Add customer</DialogTitle>
        <DialogDescription>Only the name is needed. A phone number lets you find them quickly next time.</DialogDescription>
        <form
          noValidate
          className="mt-4 space-y-4"
          onSubmit={(event) => {
            event.preventDefault();
            void save(false);
          }}
        >
          {failure ? <Alert variant="destructive">{failure}</Alert> : null}
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
          {duplicate ? <DuplicatePrompt {...duplicate} basePath={basePath} confirmLabel="Add anyway" busy={create.isPending} onConfirm={() => void save(true)} /> : null}
          <DialogFooter>
            <DialogClose render={<Button type="button" variant="outline" />}>Cancel</DialogClose>
            <Button type="submit" disabled={create.isPending || duplicate !== null}>
              {create.isPending ? "Adding..." : "Add customer"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
