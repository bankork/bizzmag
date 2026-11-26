import type { Metadata } from "next";
import { AccountPage } from "@/components/auth/account-page";

export const metadata: Metadata = { title: "My account" };

export default function Account() {
  return <AccountPage />;
}
