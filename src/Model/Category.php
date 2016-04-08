<?php 

namespace Demo;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
	 protected $fillable = ['category_name', 'created_at', 'updated_at'];

	/**
     * Get emoji category.
     */
    public function emoji()
    {
        return $this->belongsTo('Demo\Emoji');
    }
}	 