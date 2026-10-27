<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\ServiceCustomer;
use Illuminate\Validation\ValidationException;

class TicketCustomerContact
{
    public static function summary(ServiceCustomer $customer): array
    {
        $missing = [];
        $phone = trim((string) $customer->phone);
        if ($phone === '' || preg_match('/^-+$/', $phone)) {
            $missing[] = 'phone';
        }
        if (trim((string) $customer->email) === '') {
            $missing[] = 'email';
        }

        return [
            'can_create' => $missing === [],
            'missing_fields' => $missing,
            'edit_customer_url' => route('aftersales.pages.customers.edit', ['customer' => $customer->id], false),
            'message' => $missing ? 'Customer data is incomplete. Phone and Email are required because surveys and notifications are sent by email and phone.' : null,
        ];
    }

    public static function assertComplete(ServiceCustomer $customer): void
    {
        $summary = self::summary($customer);
        if ($summary['can_create']) {
            return;
        }
        $errors = [];
        foreach ($summary['missing_fields'] as $field) {
            $errors['customer.'.$field] = 'Customer '.ucfirst($field).' is required before creating a new ticket.';
        }
        $exception = ValidationException::withMessages($errors);
        $exception->response = response()->json([
            'message' => $summary['message'],
            'code' => 'customer_contact_incomplete',
            'customer_id' => $customer->id,
            'errors' => $exception->errors(),
            'ticket_creation' => $summary,
        ], 422);

        throw $exception;
    }
}
