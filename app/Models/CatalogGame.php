<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogGame extends Model
{
    protected $fillable = [
        'hltb_id', 'title', 'normalized_title', 'cover_url',
        'main_story_minutes', 'completionist_minutes',
    ];
}
