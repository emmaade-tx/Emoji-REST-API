<?php

namespace Demo;


use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class DatabaseSchema
{
    /**
     * Create users table
     */
    public function createUsersTable()
    {
        if (!Capsule::schema()->hasTable('users')) {
            Capsule::schema()->create('users', function (Blueprint $table) {
                $table->increments('id');
                $table->string('fullname');
                $table->string('username');
                $table->string('password');
                $table->timestamps();
            });
        }
    }

    /**
     * Create emojies table
     */
    public function createEmojisTable()
    {
        if (!Capsule::schema()->hasTable('emojis')) {
            Capsule::schema()->create('emojis', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->string('chars');
                $table->string('category');
                $table->integer('created_by');
                $table->timestamps();
            });
        }
    }

    /**
     * Create keywords table
     */
    public function createKeywordsTable()
    {
        if (!Capsule::schema()->hasTable('keywords')) {
            Capsule::schema()->create('keywords', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('emoji_id');
                $table->string('keyword_name');
                $table->timestamps();
            });
        }
    }
}