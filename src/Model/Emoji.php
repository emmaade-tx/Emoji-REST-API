<?php

/**
 * @author: Raimi Ademola <ademola.raimi@andela.com>
 * @copyright: 2016 Andela
 */
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
     * Get emoji keywords.
     */
    public function keywords()
    {
        return $this->hasMany('Demo\Keyword', 'emoji_id', 'id')->select(['emoji_id', 'keyword_name']);
    }
}
