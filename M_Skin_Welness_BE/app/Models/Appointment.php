<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Appointment extends Model
{
    /** @use HasFactory<\Database\Factories\AppointmentFactory> */
    use HasFactory;

    public const SOURCES = ['staff', 'online_client'];

    protected $fillable = [
        'center_id',
        'treatment_id',
        'room_id',
        'client_id',
        'worker_id',
        'machine_id',
        'starts_at',
        'ends_at',
        'actual_duration_minutes',
        'booking_source',
        'status_id',
        'reserved_price',
        'cancelled_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'actual_duration_minutes' => 'integer',
            'reserved_price' => 'decimal:2',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(SessionStatus::class, 'status_id');
    }

    public function assistants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'appointment_assistants', 'appointment_id', 'user_id')
            ->withPivot(['center_id', 'notes']);
    }

    public function scopeForCenter(Builder $query, int $centerId): Builder
    {
        return $query->where('center_id', $centerId);
    }

    public function scopeOverlapping(Builder $query, \DateTimeInterface $startsAt, \DateTimeInterface $endsAt): Builder
    {
        return $query->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt);
    }

    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->whereHas(
            'status',
            function (Builder $q) {
                return $q->whereNotIn('name', ['cancelada', 'no_presentada']);
            }
        );
    }
}
