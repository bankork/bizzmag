<?php

namespace App\Domain\Identity\Permissions;

/**
 * Every permission in SRS §3.3 with its plain-English label, its group on
 * the Roles screen and the default roles that hold it.
 *
 * Authorisation is always decided by permission name, never by role name
 * (SRS §3.1). Later phases add permissions here and the seeder picks them up.
 */
final class PermissionCatalogue
{
    private const O = SystemRole::Owner;

    private const S = SystemRole::Supervisor;

    private const C = SystemRole::Cashier;

    private const E = SystemRole::Employee;

    /**
     * @var array<string, array{group: string, label: string, roles: list<SystemRole>}>
     */
    private const PERMISSIONS = [
        'user.manage' => ['group' => 'Staff and access', 'label' => 'Create, edit and disable staff accounts', 'roles' => [self::O]],
        'role.manage' => ['group' => 'Staff and access', 'label' => 'Manage roles, permissions and discount limits', 'roles' => [self::O]],
        'settings.manage' => ['group' => 'Settings', 'label' => 'Change business settings, tax, numbering and time limits', 'roles' => [self::O]],

        'customer.view' => ['group' => 'Customers', 'label' => 'Search and view customers', 'roles' => [self::O, self::S, self::C]],
        'customer.view_contact' => ['group' => 'Customers', 'label' => 'See customer phone numbers and emails', 'roles' => [self::O, self::S, self::C]],
        'customer.manage' => ['group' => 'Customers', 'label' => 'Add and edit customers', 'roles' => [self::O, self::S, self::C]],
        'customer.associate' => ['group' => 'Customers', 'label' => 'Link a walk-in visit to a customer', 'roles' => [self::O, self::S, self::C]],

        'catalogue.manage' => ['group' => 'Services and products', 'label' => 'Manage services, products, prices and credit splits', 'roles' => [self::O]],

        'order.create' => ['group' => 'Visits', 'label' => 'Start a visit', 'roles' => [self::O, self::S, self::C]],
        'order.view_open' => ['group' => 'Visits', 'label' => 'See today\'s open visits (no prices or contact details)', 'roles' => [self::O, self::S, self::C, self::E]],
        'order.view_all' => ['group' => 'Visits', 'label' => 'See all visits with prices', 'roles' => [self::O, self::S, self::C]],
        'order.item.add' => ['group' => 'Visits', 'label' => 'Add services or products to a visit', 'roles' => [self::O, self::S, self::C]],
        'order.item.cancel_unexecuted' => ['group' => 'Visits', 'label' => 'Remove a service nobody has started', 'roles' => [self::O, self::S, self::C]],
        'order.item.price_override' => ['group' => 'Visits', 'label' => 'Change a price on a visit, with a reason', 'roles' => [self::O, self::S]],
        'order.assign' => ['group' => 'Visits', 'label' => 'Assign staff to services', 'roles' => [self::O, self::S, self::C]],
        'order.cancel' => ['group' => 'Visits', 'label' => 'Cancel a whole visit', 'roles' => [self::O, self::S]],

        'execution.submit' => ['group' => 'Work done', 'label' => 'Record work done', 'roles' => [self::O, self::S, self::E]],
        'execution.join' => ['group' => 'Work done', 'label' => 'Join work someone else recorded', 'roles' => [self::E]],
        'execution.dispute' => ['group' => 'Work done', 'label' => 'Dispute work they are named on', 'roles' => [self::O, self::S, self::E]],
        'execution.correct' => ['group' => 'Work done', 'label' => 'Correct or cancel recorded work and resolve disputes', 'roles' => [self::O, self::S]],
        'execution.submit_late' => ['group' => 'Work done', 'label' => 'Record work after the late window or after the bill is final', 'roles' => [self::O, self::S]],
        'allocation.override' => ['group' => 'Work done', 'label' => 'Change how credit for shared work is split', 'roles' => [self::O, self::S]],

        'additional.request' => ['group' => 'Approvals', 'label' => 'Ask to add an extra service', 'roles' => [self::E]],
        'additional.decide' => ['group' => 'Approvals', 'label' => 'Approve or reject extra service requests', 'roles' => [self::O, self::S]],

        'reconciliation.view' => ['group' => 'Bills', 'label' => 'See mismatches between work done and the bill', 'roles' => [self::O, self::S, self::C]],
        'invoice.view' => ['group' => 'Bills', 'label' => 'View bills', 'roles' => [self::O, self::S, self::C]],
        'invoice.finalise' => ['group' => 'Bills', 'label' => 'Make a bill final', 'roles' => [self::O, self::S, self::C]],
        'invoice.finalise_with_exception' => ['group' => 'Bills', 'label' => 'Make a bill final even when checks fail', 'roles' => [self::O, self::S]],
        'invoice.amend' => ['group' => 'Bills', 'label' => 'Correct a final bill with a new revision', 'roles' => [self::O, self::S]],
        'invoice.void' => ['group' => 'Bills', 'label' => 'Void a bill with nothing paid on it', 'roles' => [self::O]],

        'discount.apply' => ['group' => 'Discounts', 'label' => 'Give discounts up to their limit', 'roles' => [self::O, self::S, self::C]],
        'discount.approve' => ['group' => 'Discounts', 'label' => 'Approve discounts above someone\'s limit', 'roles' => [self::O, self::S]],

        'payment.record' => ['group' => 'Payments', 'label' => 'Record payments', 'roles' => [self::O, self::S, self::C]],
        'payment.reverse' => ['group' => 'Payments', 'label' => 'Reverse a payment', 'roles' => [self::O, self::S]],
        'refund.issue' => ['group' => 'Payments', 'label' => 'Give a refund', 'roles' => [self::O, self::S]],
        'transfer.verify' => ['group' => 'Payments', 'label' => 'Confirm or reject bank transfers', 'roles' => [self::O]],

        'document.share' => ['group' => 'Receipts', 'label' => 'Print, download and share bills and receipts', 'roles' => [self::O, self::S, self::C]],

        'closing.submit' => ['group' => 'End of day', 'label' => 'Submit their own end-of-day closing', 'roles' => [self::O, self::S, self::C]],
        'closing.approve' => ['group' => 'End of day', 'label' => 'Approve or return closings', 'roles' => [self::O, self::S]],
        'closing.reopen' => ['group' => 'End of day', 'label' => 'Reopen an approved closing', 'roles' => [self::O]],

        'report.own' => ['group' => 'Reports', 'label' => 'See their own performance', 'roles' => [self::O, self::S, self::C, self::E]],
        'report.team' => ['group' => 'Reports', 'label' => 'See every staff member\'s performance', 'roles' => [self::O, self::S]],
        'report.financial' => ['group' => 'Reports', 'label' => 'See revenue, collections and amounts owed', 'roles' => [self::O, self::S]],
        'dashboard.view' => ['group' => 'Reports', 'label' => 'See the owner dashboard', 'roles' => [self::O, self::S]],

        'bonus.configure' => ['group' => 'Bonuses', 'label' => 'Set up bonus rules', 'roles' => [self::O]],
        'bonus.calculate' => ['group' => 'Bonuses', 'label' => 'Calculate bonuses', 'roles' => [self::O, self::S]],
        'bonus.approve' => ['group' => 'Bonuses', 'label' => 'Approve or void bonuses', 'roles' => [self::O]],
        'bonus.pay' => ['group' => 'Bonuses', 'label' => 'Record bonus payments', 'roles' => [self::O]],

        'audit.view' => ['group' => 'Records', 'label' => 'Search the record of every change', 'roles' => [self::O]],
        'import.run' => ['group' => 'Records', 'label' => 'Import data', 'roles' => [self::O]],
    ];

    /**
     * Permissions that alone allow a long session on a personal device
     * (SEC-018). They are the Employee defaults. A user with anything more
     * gets the short idle limit.
     *
     * @var list<string>
     */
    public const LOW_RISK = [
        'order.view_open',
        'execution.submit',
        'execution.join',
        'execution.dispute',
        'additional.request',
        'report.own',
    ];

    /**
     * Permissions that must stay with at least one active user, so the
     * salon can never be locked out of its own staff management.
     *
     * @var list<string>
     */
    public const ADMINISTRATION = ['user.manage', 'role.manage'];

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::PERMISSIONS);
    }

    public static function exists(string $permission): bool
    {
        return isset(self::PERMISSIONS[$permission]);
    }

    /**
     * @return list<string>
     */
    public static function defaultsFor(SystemRole $role): array
    {
        $names = [];

        foreach (self::PERMISSIONS as $name => $definition) {
            if (in_array($role, $definition['roles'], true)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * The catalogue for the Roles screen, in display order.
     *
     * @return list<array{name: string, group: string, label: string}>
     */
    public static function describe(): array
    {
        $rows = [];

        foreach (self::PERMISSIONS as $name => $definition) {
            $rows[] = ['name' => $name, 'group' => $definition['group'], 'label' => $definition['label']];
        }

        return $rows;
    }
}
