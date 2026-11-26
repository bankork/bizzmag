/**
 * Shapes returned by the Phase 1 API (see docs/api/openapi.json and the
 * backend presenters). Money is integer kobo; rates are basis points.
 */

export type AreaKey = "admin" | "desk" | "staff";

export type EmployeeProfile = {
  id: number;
  display_name: string;
  job_title: string | null;
  is_active: boolean;
};

export type BusinessSummary = {
  id: number;
  name: string;
  currency: string;
  timezone: string;
  logo_url: string | null;
};

export type Me = {
  user: {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    must_change_password: boolean;
    employee_profile: EmployeeProfile | null;
  };
  business: BusinessSummary;
  roles: { id: number; name: string; system_key: string | null }[];
  permissions: string[];
  discount_cap: { percent_bp: number | null; per_invoice_kobo: number | null };
  session: { kind: "personal" | "privileged"; idle_timeout_seconds: number };
  home: AreaKey;
};

export type StaffUser = {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  is_active: boolean;
  must_change_password: boolean;
  last_login_at: string | null;
  disabled_at: string | null;
  roles: { id: number; name: string }[];
  employee_profile: EmployeeProfile | null;
  created_at: string | null;
};

export type Role = {
  id: number;
  name: string;
  description: string | null;
  system_key: string | null;
  is_system: boolean;
  is_owner: boolean;
  discount_cap_bp: number | null;
  discount_cap_kobo: number | null;
  permissions: string[];
  users_count: number;
};

export type PermissionDefinition = { name: string; group: string; label: string };

export type RangedSetting = { value: number; default: number; range: { min: number; max: number } | null };

export type NumberingSetting = { format: string; default: string; example: string };

export type BusinessSettings = {
  version: number;
  profile: {
    name: string;
    phone: string | null;
    email: string | null;
    address: string | null;
    receipt_footer: string | null;
    invoice_footer: string | null;
    timezone: string;
    currency: string;
    logo_url: string | null;
  };
  numbering: Record<"order" | "invoice" | "receipt", NumberingSetting>;
  windows: Record<"late_submission_hours" | "transfer_alert_days" | "share_link_days", RangedSetting>;
  sessions: Record<"idle_minutes_privileged" | "idle_days_low_risk", RangedSetting>;
};

export type TaxSetting = {
  version: number;
  enabled: boolean;
  name: string;
  rate_bp: number;
  mode: "inclusive" | "exclusive";
  updated_at: string | null;
};

export type HistoryEntry = {
  id: number;
  action: string;
  actor: { id: number; name: string } | null;
  before: Record<string, unknown> | null;
  after: Record<string, unknown> | null;
  at: string | null;
};

export type Pagination = { page: number; per_page: number; total: number; last_page: number };
