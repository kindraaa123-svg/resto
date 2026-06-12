<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\RecordsDeletion;
use App\Traits\TracksDeletionImpact;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable, RecordsDeletion, SoftDeletes, TracksDeletionImpact;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'google_id',
        'password',
        'phonenumber',
        'roleid',
        'branchid',
        'deleted_by',
        'face_descriptor',
    ];

    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'userid', 'id');
    }

    protected function getImpactRelations(): array
    {
        return [
            'payrolls' => 'Payroll History',
        ];
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'face_descriptor' => 'array',
        ];
    }

    protected static function booted()
    {
        static::saved(function ($user) {
            if ($user->isDirty('roleid') && $user->roleid) {
                $role = Role::find($user->roleid);
                if ($role) {
                    $user->syncRoles([$role->name]);
                }
            }
        });

        static::created(function ($user) {
            if ($user->roleid) {
                $role = Role::find($user->roleid);
                if ($role) {
                    $user->syncRoles([$role->name]);
                }
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'roleid');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branchid', 'branchid');
    }
}
