<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasFactory, Notifiable;


    const ROLE_ADMIN = 'admin';
    const ROLE_DHEAD = 'dhead';
    const ROLE_EMPLOYEE = 'employee';

    const ROLES = [
        self::ROLE_ADMIN => 'admin',
        self::ROLE_DHEAD => 'dhead',
        self::ROLE_EMPLOYEE => 'employee',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
        ];
    }


    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === self::ROLE_ADMIN || $this->role === self::ROLE_DHEAD || $this->role === self::ROLE_EMPLOYEE;
    }



    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isDhead()
    {

        return $this->role === self::ROLE_DHEAD;
    }

    public function isEmployee()
    {

        return $this->role === self::ROLE_EMPLOYEE;
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function salary()
    {
        return $this->hasOne(SalarySlip::class);
    }



    public function getFilamentAvatarUrl(): ?string
    {

        return     asset("storage/" . $this->avatar);
    }
}
