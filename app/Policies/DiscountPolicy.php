<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Discount;
use Illuminate\Auth\Access\HandlesAuthorization;

class DiscountPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_discount');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Discount $discount): bool
    {
        return $user->can('view_discount');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_discount');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Discount $discount): bool
    {
        return $user->can('update_discount');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Discount $discount): bool
    {
        return $user->can('delete_discount');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_discount');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Discount $discount): bool
    {
        return $user->can('force_delete_discount');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_discount');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Discount $discount): bool
    {
        return $user->can('restore_discount');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_discount');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Discount $discount): bool
    {
        return $user->can('replicate_discount');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_discount');
    }
}
