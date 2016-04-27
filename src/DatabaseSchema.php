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
                $table->increments('id')->unsigned;
                $table->string('fullname');
                $table->string('username')->unique();
                $table->string('password');
                $table->timestamps();
            });
        }
    }

    /**
     * Create emojis table
     */
    public function createEmojisTable()
    {
        if (!Capsule::schema()->hasTable('emojis')) {
            Capsule::schema()->create('emojis', function (Blueprint $table) {
                $table->increments('id')->unsigned;
                $table->string('name');
                $table->string('chars');
                $table->string('category');
                $table->string('created_by');
                $table->timestamps();

                $table->foreign('created_by')
                    ->references('username')
                    ->on('users')
                    ->delete('cascade');
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
                $table->increments('id')->unsigned();
                $table->integer('emoji_id')->unsigned();
                $table->string('name');
                $table->timestamps();

                $table->foreign('emoji_id')
                    ->references('id')
                    ->on('emojis')
                    ->delete('cascade');
            });
        }
    }

    /**
     * This method dropdown the table
     */
    public function down()
    {
        Capsule::schema()->drop('keywords');
        Capsule::schema()->drop('emojis');
        Capsule::schema()->drop('users');
    }
}