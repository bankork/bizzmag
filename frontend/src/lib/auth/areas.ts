import type { AreaKey } from "@/lib/api/types";

/**
 * The three work areas and the permissions that open them. Navigation and
 * page guards use the same definitions, so a link is never shown to a page
 * that would refuse the person.
 */
export type NavItem = { href: string; label: string; permissions: readonly string[] };

export type AreaDefinition = {
  key: AreaKey;
  href: string;
  title: string;
  description: string;
  permissions: readonly string[];
  nav: readonly NavItem[];
};

export const AREAS: Record<AreaKey, AreaDefinition> = {
  staff: {
    key: "staff",
    href: "/staff/",
    title: "Staff",
    description: "Record the services you perform and see your performance.",
    permissions: ["order.view_open", "execution.submit", "report.own"],
    nav: [
      { href: "/staff/", label: "Today", permissions: ["order.view_open"] },
      { href: "/staff/work/", label: "My work", permissions: ["execution.submit", "execution.join"] },
    ],
  },
  desk: {
    key: "desk",
    href: "/desk/",
    title: "Front desk",
    description: "Open visits, take payments and issue receipts.",
    permissions: ["order.create", "payment.record"],
    nav: [
      { href: "/desk/", label: "Visits", permissions: ["order.create"] },
      { href: "/desk/visits/new/", label: "New visit", permissions: ["order.create"] },
      { href: "/desk/customers/", label: "Customers", permissions: ["customer.view"] },
    ],
  },
  admin: {
    key: "admin",
    href: "/admin/",
    title: "Owner and admin",
    description: "Dashboard, approvals, staff, settings and reports.",
    permissions: ["user.manage", "role.manage", "settings.manage", "dashboard.view", "catalogue.manage", "additional.decide"],
    nav: [
      { href: "/admin/", label: "Overview", permissions: ["user.manage", "role.manage", "settings.manage", "dashboard.view", "catalogue.manage"] },
      { href: "/admin/approvals/", label: "Approvals", permissions: ["additional.decide"] },
      { href: "/admin/catalogue/", label: "Catalogue", permissions: ["catalogue.manage"] },
      { href: "/admin/customers/", label: "Customers", permissions: ["customer.view"] },
      { href: "/admin/users/", label: "Staff", permissions: ["user.manage"] },
      { href: "/admin/roles/", label: "Roles", permissions: ["role.manage"] },
      { href: "/admin/settings/", label: "Settings", permissions: ["settings.manage"] },
    ],
  },
};

export function areasFor(permissions: readonly string[]): AreaDefinition[] {
  return Object.values(AREAS).filter((area) => area.permissions.some((permission) => permissions.includes(permission)));
}

export function navFor(area: AreaKey, permissions: readonly string[]): NavItem[] {
  return AREAS[area].nav.filter((item) => item.permissions.some((permission) => permissions.includes(permission)));
}
