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

export type ItemType = "service" | "product";

export type SplitMethod = "equal" | "primary_assistant";

export type CatalogueCategory = {
  id: number;
  type: ItemType;
  name: string;
  sort_order: number;
  is_active: boolean;
  version: number;
  items_count: number | null;
};

/**
 * A service, product or variant. `price_kobo` is absent, not null, for
 * people who may not see prices (BR-041). A service with variants has no
 * price of its own.
 */
export type CatalogueItem = {
  id: number;
  category_id: number;
  parent_id: number | null;
  type: ItemType;
  name: string;
  description: string | null;
  has_variants: boolean;
  price_kobo?: number | null;
  taxable: boolean;
  quantity_allowed: boolean;
  allocation_method: SplitMethod | null;
  primary_share_bp: number | null;
  sort_order: number;
  is_active: boolean;
  orderable: boolean;
  version: number;
  variants?: CatalogueItem[];
};

export type CatalogueHistoryEntry = HistoryEntry & {
  item: { id: number; name: string | null; is_variant: boolean };
};

/** Phone and email are absent for people without customer.view_contact. */
export type Customer = {
  id: number;
  name: string;
  has_phone: boolean;
  has_email: boolean;
  phone?: string | null;
  phone_display?: string | null;
  email?: string | null;
  notes: string | null;
  is_removed: boolean;
  removed_at: string | null;
  version: number;
  created_at: string | null;
};

export type DuplicateMatch = { id: number; name: string };

export type OrderStatus = "open" | "finalised" | "cancelled";

export type ItemStatus = "requested" | "pending_approval" | "approved" | "rejected" | "cancelled";

export type ExecutionStatus = "confirmed" | "disputed" | "superseded" | "cancelled";

export type AllocationMethod = "equal" | "primary_assistant" | "custom";

export type Contributor = { employee_id: number; name: string; is_primary: boolean; share_bp: number };

/** A recorded service. Shares are percentages of credit, never money. */
export type Execution = {
  id: number;
  order_item_id: number;
  status: ExecutionStatus;
  late: boolean;
  submitted_by: { id: number; name: string };
  submitted_at: string;
  allocation_method: AllocationMethod;
  primary_share_bp: number | null;
  supersedes_id: number | null;
  override_reason: string | null;
  dispute_reason: string | null;
  disputed_by: string | null;
  resolution_reason: string | null;
  includes_me: boolean;
  contributors: Contributor[];
};

/**
 * One line on a visit. The price fields are present only for people who may
 * see prices (order.view_all); employees never receive them (BR-041).
 */
export type OrderItem = {
  id: number;
  type: ItemType;
  name: string;
  quantity: number;
  status: ItemStatus;
  origin: "order" | "desk_add" | "additional_request";
  request_note: string | null;
  decision_reason: string | null;
  cancel_reason: string | null;
  requested_by: number;
  assigned: { employee_id: number; name: string }[];
  assigned_to_me: boolean;
  work: Execution | null;
  history: Execution[];
  unit_price_kobo?: number;
  override_price_kobo?: number | null;
  override_reason?: string | null;
  line_value_kobo?: number;
  taxable?: boolean;
};

export type Order = {
  id: number;
  reference: string;
  status: OrderStatus;
  customer_name: string;
  is_guest: boolean;
  opened_at: string;
  opened_business_day: string;
  opened_by: string;
  cancelled_at: string | null;
  cancel_reason: string | null;
  progress: { services: number; recorded: number; disputed: number; waiting: number; pending_approval: number };
  items: OrderItem[];
  customer_id?: number | null;
  subtotal_kobo?: number;
  phone?: string | null;
  phone_display?: string | null;
};

export type StaffMember = { id: number; name: string; job_title: string | null; is_me: boolean };

export type Approval = {
  type: "additional_service";
  order_item_id: number;
  name: string;
  quantity: number;
  note: string | null;
  requested_by: { id: number; name: string | null };
  requested_at: string | null;
  order: { id: number; reference: string; customer_name: string };
  unit_price_kobo?: number;
  line_value_kobo?: number;
};

export type MyWork = Execution & {
  item_name: string;
  order: { id: number; reference: string; customer_name: string; status: OrderStatus };
  my_share_bp: number | null;
};

export type CustomerVisit = { id: number; reference: string; status: OrderStatus; opened_at: string; items: string[]; subtotal_kobo: number };

export type AppNotification = {
  id: number;
  type: string;
  title: string;
  body: string | null;
  data: { order_id?: number; order_item_id?: number; execution_id?: number; reference?: string; approved?: boolean };
  read: boolean;
  created_at: string;
};
