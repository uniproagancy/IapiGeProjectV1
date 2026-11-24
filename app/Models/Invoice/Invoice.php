<?php

namespace App\Models\Invoice;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

/**
 * შეკვეთის გარეშე გაცემული ინვოისი.
 *
 * პოზიციები JSON-ად ინახება და არა კატალოგის ბმულად: ინვოისი იმ მომენტის
 * ფასს და სახელს უნდა ასახავდეს, როცა გაიცა — პროდუქტის შემდგომი ცვლილება
 * უკვე გაცემულ დოკუმენტს არ უნდა შეეხოს.
 */
class Invoice extends Model
{
    protected $table = 'db_invoices';

    protected $fillable = [
        'number',
        'customer_name',
        'customer_code',
        'customer_phone',
        'customer_email',
        'customer_address',
        'items',
        'delivery_amount',
        'total',
        'note',
        'created_by',
    ];

    protected $casts = [
        'items'           => 'array',
        'delivery_amount' => 'float',
        'total'           => 'float',
    ];

    protected static function booted(): void
    {
        // ✅ ნომერი id-დან — უნიკალურობა ბაზის მიერაა გარანტირებული, race condition-ის გარეშე
        static::created(function (Invoice $invoice) {
            if (empty($invoice->number)) {
                $invoice->updateQuietly([
                    'number' => sprintf('INV-%s-%05d', $invoice->created_at->format('Y'), $invoice->id),
                ]);
            }
        });
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
