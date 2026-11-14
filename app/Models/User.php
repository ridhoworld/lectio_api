<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'avatar_url',
        'timezone',
        'reminder_time',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function userBooks()
    {
        return $this->hasMany(UserBook::class);
    }

    public function readingSessions()
    {
        return $this->hasMany(ReadingSession::class);
    }

    public function readingGoals()
    {
        return $this->hasMany(ReadingGoal::class);
    }

    public function streak()
    {
        return $this->hasOne(UserStreak::class);
    }

    public function userBadges()
    {
        return $this->hasMany(UserBadge::class);
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'user_badges')->withPivot('earned_at');
    }

    public function collections()
    {
        return $this->hasMany(Collection::class);
    }
}
