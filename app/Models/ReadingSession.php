<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadingSession extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'user_book_id',
        'start_page',
        'end_page',
        'duration_minutes',
        'session_date',
    ];

    protected $casts = [
        'start_page' => 'integer',
        'end_page' => 'integer',
        'duration_minutes' => 'integer',
        'session_date' => 'date:Y-m-d',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userBook()
    {
        return $this->belongsTo(UserBook::class);
    }
}

