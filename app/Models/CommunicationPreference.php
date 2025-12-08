<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CommunicationPreference extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'people_id',
        'email_opt_in',
        'phone_opt_in',
        'sms_opt_in',
        'postal_opt_in',
        'preferred_channel',
        'preferred_time',
        'do_not_contact',
    ];

    /**
     * @return BelongsTo<People, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(People::class, 'people_id');
    }

    public function canContact(string $channel): bool
    {
        if ($this->do_not_contact) {
            return false;
        }

        return match (strtolower($channel)) {
            'email' => (bool) $this->email_opt_in,
            'phone' => (bool) $this->phone_opt_in,
            'sms' => (bool) $this->sms_opt_in,
            'postal' => (bool) $this->postal_opt_in,
            default => true,
        };
    }
}
