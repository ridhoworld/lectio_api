<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBook extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'book_id',
        'status',
        'format',
        'current_page',
        'rating',
        'review',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'current_page' => 'integer',
        'rating' => 'float',
        'started_at' => 'date',
        'finished_at' => 'date',
    ];

    protected $appends = ['progress_percentage'];

    public function getProgressPercentageAttribute(): float
    {
        if (!$this->relationLoaded('book') || !$this->book || !$this->book->total_pages || $this->book->total_pages <= 0) {
            return 0.0;
        }

        $pct = ($this->current_page / $this->book->total_pages) * 100;
        return round(min($pct, 100.0), 1);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function readingSessions()
    {
        return $this->hasMany(ReadingSession::class);
    }

    public function notes()
    {
        return $this->hasMany(NoteAndHighlight::class);
    }

    public function collectionItems()
    {
        return $this->hasMany(CollectionItem::class);
    }
}

