<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'isbn',
        'title',
        'author',
        'publisher',
        'total_pages',
        'genre',
        'cover_url',
        'synopsis',
    ];

    public function userBooks()
    {
        return $this->hasMany(UserBook::class);
    }
}

