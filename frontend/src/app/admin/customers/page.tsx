import type { Metadata } from "next";
import { CustomerList } from "@/components/customers/customer-list";
import { AreaShell } from "@/components/shared/area-shell";

export const metadata: Metadata = { title: "Customers" };

export default function CustomersPage() {
  return (
    <AreaShell area="admin" title="Customers" permissions={["customer.view"]}>
      <CustomerList basePath="/admin/customers/" />
    </AreaShell>
  );
}
