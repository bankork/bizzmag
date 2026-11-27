"use client";

import { useState } from "react";
import { Search } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useCustomers } from "@/lib/api/customers";
import type { Customer } from "@/lib/api/types";
import { useAuth } from "@/lib/auth/auth-provider";

/**
 * Find a registered customer by name or phone and pick them.
 */
export function CustomerSearch({ onPick }: { onPick: (customer: Customer) => void }) {
  const auth = useAuth();
  const [text, setText] = useState("");
  const [query, setQuery] = useState("");
  const customers = useCustomers({ q: query, page: 1 });
  const showContact = auth.can("customer.view_contact");

  return (
    <div className="space-y-2">
      <div className="flex gap-2">
        <label htmlFor="visit-customer-search" className="sr-only">
          Search customers
        </label>
        <Input
          id="visit-customer-search"
          placeholder={showContact ? "Name or phone number" : "Name"}
          value={text}
          onChange={(event) => setText(event.target.value)}
          onKeyDown={(event) => {
            if (event.key === "Enter") {
              event.preventDefault();
              setQuery(text.trim());
            }
          }}
        />
        <Button type="button" variant="outline" aria-label="Search customers" onClick={() => setQuery(text.trim())}>
          <Search aria-hidden />
        </Button>
      </div>
      {query !== "" ? (
        <ul className="max-h-56 divide-y overflow-y-auto rounded-lg border" aria-label="Matching customers">
          {customers.data?.customers.length === 0 ? <li className="p-3 text-sm text-muted-foreground">Nobody matches this search.</li> : null}
          {customers.data?.customers.map((customer) => (
            <li key={customer.id}>
              <button type="button" className="flex min-h-11 w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-muted/50" onClick={() => onPick(customer)}>
                <span className="font-medium">{customer.name}</span>
                <span className="text-muted-foreground tabular-nums">{showContact ? customer.phone_display || "No phone" : ""}</span>
              </button>
            </li>
          ))}
        </ul>
      ) : null}
    </div>
  );
}
