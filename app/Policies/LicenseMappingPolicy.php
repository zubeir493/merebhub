<?php

namespace App\Policies;

use App\Models\LicenseMapping;
use App\Models\Staff;

class LicenseMappingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Staff $staff): bool
    {
        return $staff->admin;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Staff $staff, LicenseMapping $licenseMapping): bool
    {
        return $staff->admin;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Staff $staff): bool
    {
        return $staff->admin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Staff $staff, LicenseMapping $licenseMapping): bool
    {
        return $staff->admin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Staff $staff, LicenseMapping $licenseMapping): bool
    {
        return $staff->admin;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Staff $staff, LicenseMapping $licenseMapping): bool
    {
        return $staff->admin;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Staff $staff, LicenseMapping $licenseMapping): bool
    {
        return $staff->admin;
    }
}
