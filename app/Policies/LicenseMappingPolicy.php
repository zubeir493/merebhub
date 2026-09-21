<?php

namespace App\Policies;

use App\Models\LicenseMapping;
use Lunar\Core\Models\Staff as CoreStaff;

class LicenseMappingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(CoreStaff $staff): bool
    {
        return $staff->admin;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(CoreStaff $staff, LicenseMapping $licenseMapping): bool
    {
        return $staff->admin;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(CoreStaff $staff): bool
    {
        return $staff->admin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(CoreStaff $staff, LicenseMapping $licenseMapping): bool
    {
        return $staff->admin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(CoreStaff $staff, LicenseMapping $licenseMapping): bool
    {
        return $staff->admin;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(CoreStaff $staff, LicenseMapping $licenseMapping): bool
    {
        return $staff->admin;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(CoreStaff $staff, LicenseMapping $licenseMapping): bool
    {
        return $staff->admin;
    }
}
