<?php

namespace Demo;

use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
    protected $fillable = ['emoji_id', 'keyword_name', 'created_at', 'updated_at'];

    /**
     * Get emoji keywords.
     */
    public function emoji()
    {
        return $this->belongsTo('Demo\Emoji');
    }
}
