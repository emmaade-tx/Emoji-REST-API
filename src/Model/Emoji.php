<?php

namespace Demo;

use Illuminate\Database\Eloquent\Model;

class Emoji extends Model
{
    protected $fillable = ['name', 'chars', 'category', 'created_at', 'created_by', 'updated_at'];

    /**
     * Get emoji creator.
     */
    public function created_by()
    {
        return $this->hasOne('Demo\User', 'id', 'created_by')->select('id');
    }

    /**
     * Get emoji category.
     */
    public function category()
    {
        return $this->hasOne('Demo\Category', 'id', 'category')->select('id', 'category_name');
    }

    /**
     * Get emoji keywords.
     */
    public function keywords()
    {
        return $this->hasMany('Demo\Keyword', 'emoji_id', 'Id')->select(['emoji_id', 'keyword_name']);
    }
}
