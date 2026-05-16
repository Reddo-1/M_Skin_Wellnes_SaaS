<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->center_id !== $invoice->center_id) {
            return false;
        }

        if (! $user->can('invoices.view')) {
            return false;
        }

        if ($user->hasRole('cliente')) {
            return $invoice->client_id === $user->id;
        }

        return true;
    }
}
