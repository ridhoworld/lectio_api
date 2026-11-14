<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoteAndHighlight extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'notes_and_highlights';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_book_id',
        'page_number',
        'quote_text',
        'personal_note',
    ];

    protected $casts = [
        'page_number' => 'integer',
    ];

    public function userBook()
    {
        return $this->belongsTo(UserBook::class);
    }
}

