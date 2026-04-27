<?php

namespace App\Services;

class WorkspacePermissionService
{
    /**
     * Flatten grouped permissions into a plain list of slugs.
     *
     * Supports both:
     * - keyed by module: { "contracts": { "permissions": [{ "workspace_application_permission_slug": "erp.contracts.view" }] } }
     * - "without_module" key at root level
     *
     * @param  array<string, mixed>  $groupedPermissions
     * @return array<string>
     */
    public function flattenPermissions(array $groupedPermissions): array
    {
        $slugs = [];

        foreach ($groupedPermissions as $module) {
            if (! is_array($module) || ! isset($module['permissions'])) {
                continue;
            }

            foreach ($module['permissions'] as $permission) {
                $slug = $permission['workspace_application_permission_slug']
                    ?? $permission['slug']
                    ?? null;

                if ($slug !== null) {
                    $slugs[] = $slug;
                }
            }
        }

        return array_unique($slugs);
    }

    /**
     * @param  array<string, mixed>  $groupedPermissions
     */
    public function hasPermission(array $groupedPermissions, string $permissionSlug): bool
    {
        return in_array($permissionSlug, $this->flattenPermissions($groupedPermissions), true);
    }

    /**
     * @param  array<string, mixed>  $groupedPermissions
     * @param  array<string>  $permissionSlugs
     */
    public function hasAnyPermission(array $groupedPermissions, array $permissionSlugs): bool
    {
        $flat = $this->flattenPermissions($groupedPermissions);

        foreach ($permissionSlugs as $slug) {
            if (in_array($slug, $flat, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $groupedPermissions
     * @param  array<string>  $permissionSlugs
     */
    public function hasAllPermissions(array $groupedPermissions, array $permissionSlugs): bool
    {
        $flat = $this->flattenPermissions($groupedPermissions);

        foreach ($permissionSlugs as $slug) {
            if (! in_array($slug, $flat, true)) {
                return false;
            }
        }

        return true;
    }
}
