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
     * Access is PUBLIC, SIGNED_IN or a permission name. {user}, {role},
     * {category}, {item} and {customer} are filled in by the test.
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
    ];

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
