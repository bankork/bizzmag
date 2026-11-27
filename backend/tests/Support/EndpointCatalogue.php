<?php

namespace Tests\Support;

/**
 * Every /api/v1 endpoint and who may call it. The permission matrix test
 * checks each one against every default role, and the route access test
 * fails if a route exists that is not listed here.
 *
 * Later phases add their endpoints here.
 */
final class EndpointCatalogue
{
    public const PUBLIC = 'public';

    public const SIGNED_IN = 'signed-in';

    /**
     * Route name (without the api.v1. prefix) => [method, path, access].
     * Access is PUBLIC, SIGNED_IN or a permission name; "a|b" means either
     * one is enough. {user}, {role}, {category}, {item}, {customer},
     * {order}, {orderItem} and {execution} are filled in by the test.
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    public const ENDPOINTS = [
        'system.meta' => ['GET', 'meta', self::PUBLIC],
        'auth.login' => ['POST', 'auth/login', self::PUBLIC],
        'business.logo' => ['GET', 'businesses/{business}/logo', self::PUBLIC],

        'auth.logout' => ['POST', 'auth/logout', self::SIGNED_IN],
        'me' => ['GET', 'me', self::SIGNED_IN],
        'me.password' => ['PUT', 'me/password', self::SIGNED_IN],

        'users.index' => ['GET', 'users', 'user.manage'],
        'users.store' => ['POST', 'users', 'user.manage'],
        'users.show' => ['GET', 'users/{user}', 'user.manage'],
        'users.update' => ['PATCH', 'users/{user}', 'user.manage'],
        'users.disable' => ['POST', 'users/{user}/disable', 'user.manage'],
        'users.enable' => ['POST', 'users/{user}/enable', 'user.manage'],
        'users.reset-password' => ['POST', 'users/{user}/reset-password', 'user.manage'],

        'users.roles' => ['PUT', 'users/{user}/roles', 'role.manage'],
        'permissions.index' => ['GET', 'permissions', 'role.manage'],
        'roles.index' => ['GET', 'roles', 'role.manage'],
        'roles.store' => ['POST', 'roles', 'role.manage'],
        'roles.update' => ['PATCH', 'roles/{role}', 'role.manage'],
        'roles.destroy' => ['DELETE', 'roles/{role}', 'role.manage'],

        'settings.show' => ['GET', 'settings', 'settings.manage'],
        'settings.update' => ['PATCH', 'settings', 'settings.manage'],
        'settings.logo' => ['POST', 'settings/logo', 'settings.manage'],
        'settings.tax.show' => ['GET', 'settings/tax', 'settings.manage'],
        'settings.tax.update' => ['PATCH', 'settings/tax', 'settings.manage'],
        'settings.history' => ['GET', 'settings/history', 'settings.manage'],

        'catalogue.categories.index' => ['GET', 'catalogue/categories', self::SIGNED_IN],
        'catalogue.items.index' => ['GET', 'catalogue/items', self::SIGNED_IN],
        'catalogue.items.show' => ['GET', 'catalogue/items/{item}', self::SIGNED_IN],
        'catalogue.categories.store' => ['POST', 'catalogue/categories', 'catalogue.manage'],
        'catalogue.categories.reorder' => ['POST', 'catalogue/categories/reorder', 'catalogue.manage'],
        'catalogue.categories.update' => ['PATCH', 'catalogue/categories/{category}', 'catalogue.manage'],
        'catalogue.categories.deactivate' => ['POST', 'catalogue/categories/{category}/deactivate', 'catalogue.manage'],
        'catalogue.categories.activate' => ['POST', 'catalogue/categories/{category}/activate', 'catalogue.manage'],
        'catalogue.items.store' => ['POST', 'catalogue/items', 'catalogue.manage'],
        'catalogue.items.update' => ['PATCH', 'catalogue/items/{item}', 'catalogue.manage'],
        'catalogue.items.deactivate' => ['POST', 'catalogue/items/{item}/deactivate', 'catalogue.manage'],
        'catalogue.items.activate' => ['POST', 'catalogue/items/{item}/activate', 'catalogue.manage'],
        'catalogue.items.history' => ['GET', 'catalogue/items/{item}/history', 'catalogue.manage'],

        'customers.index' => ['GET', 'customers', 'customer.view'],
        'customers.show' => ['GET', 'customers/{customer}', 'customer.view'],
        'customers.store' => ['POST', 'customers', 'customer.manage'],
        'customers.update' => ['PATCH', 'customers/{customer}', 'customer.manage'],
        'customers.anonymise' => ['POST', 'customers/{customer}/anonymise', 'customer.manage'],

        'orders.index' => ['GET', 'orders', 'order.view_open|order.view_all'],
        'orders.show' => ['GET', 'orders/{order}', 'order.view_open|order.view_all'],
        'staff-directory' => ['GET', 'staff-directory', 'order.view_open|order.view_all'],
        'orders.store' => ['POST', 'orders', 'order.create'],
        'orders.items.store' => ['POST', 'orders/{order}/items', 'order.item.add'],
        'orders.additional-requests.store' => ['POST', 'orders/{order}/additional-requests', 'additional.request'],
        'orders.customer' => ['POST', 'orders/{order}/customer', 'customer.associate'],
        'orders.cancel' => ['POST', 'orders/{order}/cancel', 'order.cancel'],
        'order-items.assignments' => ['PUT', 'order-items/{orderItem}/assignments', 'order.assign'],
        'order-items.cancel' => ['POST', 'order-items/{orderItem}/cancel', 'order.item.cancel_unexecuted|execution.correct'],
        'order-items.price-override' => ['POST', 'order-items/{orderItem}/price-override', 'order.item.price_override'],
        'order-items.approve' => ['POST', 'order-items/{orderItem}/approve', 'additional.decide'],
        'order-items.reject' => ['POST', 'order-items/{orderItem}/reject', 'additional.decide'],
        'order-items.withdraw' => ['POST', 'order-items/{orderItem}/withdraw', 'additional.request|order.item.cancel_unexecuted'],
        'approvals.index' => ['GET', 'approvals', 'additional.decide'],
        'customers.orders' => ['GET', 'customers/{customer}/orders', 'order.view_all'],

        'executions.store' => ['POST', 'order-items/{orderItem}/executions', 'execution.submit'],
        'executions.mine' => ['GET', 'executions/mine', 'execution.submit|execution.join'],
        'executions.join' => ['POST', 'executions/{execution}/join', 'execution.join'],
        'executions.contributors' => ['PUT', 'executions/{execution}/contributors', 'execution.submit'],
        'executions.allocation' => ['POST', 'executions/{execution}/allocation', 'allocation.override'],
        'executions.dispute' => ['POST', 'executions/{execution}/dispute', 'execution.dispute|execution.correct'],
        'executions.dismiss-dispute' => ['POST', 'executions/{execution}/dismiss-dispute', 'execution.correct'],
        'executions.correct' => ['POST', 'executions/{execution}/correct', 'execution.correct'],
        'executions.cancel' => ['POST', 'executions/{execution}/cancel', 'execution.correct'],

        'notifications.index' => ['GET', 'notifications', self::SIGNED_IN],
        'notifications.read' => ['POST', 'notifications/read', self::SIGNED_IN],
    ];

    /**
     * Endpoints whose action makes a further check on top of the route
     * permission, such as "only the person who asked". A caller who passes
     * the route may still be refused by the action; that refusal names a
     * different permission or marker, which the matrix test accepts.
     *
     * @var list<string>
     */
    public const FURTHER_CHECKS = ['order-items.withdraw', 'order-items.cancel', 'executions.contributors', 'executions.dispute'];

    /**
     * Endpoints that also require the Owner role (`owner` middleware), on
     * top of their permission.
     *
     * @var list<string>
     */
    public const OWNER_ONLY = ['customers.anonymise'];

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function protected(): array
    {
        return array_filter(self::ENDPOINTS, fn (array $endpoint) => $endpoint[2] !== self::PUBLIC && $endpoint[2] !== self::SIGNED_IN);
    }
}
