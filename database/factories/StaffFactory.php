<?php

namespace Database\Factories;

use App\Models\Staff;
use Lunar\Core\Database\Factories\StaffFactory as LunarStaffFactory;

/**
 * @extends LunarStaffFactory<Staff>
 */
class StaffFactory extends LunarStaffFactory
{
    protected $model = Staff::class;
}
