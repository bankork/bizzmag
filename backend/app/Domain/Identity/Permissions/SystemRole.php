<?php

namespace App\Domain\Identity\Permissions;

/**
 * The four default roles (SRS §3.1). Each business gets its own copy, which
 * the owner may rename or re-permission, except the Owner role, whose
 * permissions are fixed.
 */
enum SystemRole: string
{
    case Owner = 'owner';
    case Supervisor = 'supervisor';
    case Cashier = 'cashier';
    case Employee = 'employee';

    public function defaultName(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Supervisor => 'Supervisor',
            self::Cashier => 'Front desk / Cashier',
            self::Employee => 'Employee',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Owner => 'Full control of the salon, its staff and its settings.',
            self::Supervisor => 'Approvals, corrections, refunds, finalising bills and approving closings.',
            self::Cashier => 'Customers, orders, bills, payments, receipts and their own closing.',
            self::Employee => 'Record their own work, join shared work and see their own performance.',
        };
    }

    /**
     * Default discount cap as basis points of the line or invoice. Null means unlimited.
     */
    public function defaultCapBasisPoints(): ?int
    {
        return match ($this) {
            self::Owner => null,
            self::Supervisor => 3_000,
            self::Cashier => 1_000,
            self::Employee => 0,
        };
    }

    /**
     * Default discount cap per invoice in kobo. Null means no naira limit.
     */
    public function defaultCapKobo(): ?int
    {
        return match ($this) {
            self::Cashier => 500_000,
            self::Employee => 0,
            default => null,
        };
    }

    /**
     * Where this role starts in the web app.
     */
    public function homeArea(): string
    {
        return match ($this) {
            self::Owner, self::Supervisor => 'admin',
            self::Cashier => 'desk',
            self::Employee => 'staff',
        };
    }
}
