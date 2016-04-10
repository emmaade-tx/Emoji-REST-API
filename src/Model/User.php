<?php

/**
 * @author: Raimi Ademola <ademola.raimi@andela.com>
 * @copyright: 2016 Andela
 */

namespace Demo;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = ['fullname', 'username', 'password', 'created_at', 'updated_at'];

    /**
     * Get creator of an emoji.
     */
    public function emoji()
    {
        return $this->hasMany('Demo\Emoji', 'created_by', 'id');
    }
}
