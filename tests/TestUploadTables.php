<?php

/**
 * @author: Raimi Ademola <ademola.raimi@andela.com>
 * @copyright: 2016 Andela
 */
namespace Tests;

use Carbon\Carbon;
use Demo\User;
use Demo\Emoji;
use Demo\Keywords;

class TestUploadTables
{
    public function __construct()
    {
        $this->createUser();
        $this->createCategory();
        $this->createEmoji();
    }

    public function createUser()
    { 
        $user = User::create([
            'fullname'  => 'Tester',
            'username'  => 'tester',
            'password'   => 'test',
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
        ]);
    }

    public function createEmoji()
    {
        $emojiKeyword = 'happy, face, smile';
        $userId = 1;
        $created_at = Carbon::now()->toDateTimeString();
        $emoji = Emoji::create([
            'name'       => 'GRINNING FACE',
            'chars'       => '\u{1F606}',
            'created_at' => $created_at,
            'category'   => 'Category A',
            'created_by' => $userId,
        ]);
        if ($emoji->id) {
            $createdKeyword = $this->createEmojiKeywords($emoji->id, $emojiKeyword);
        }
    }

    public function createEmojiKeywords($emoji_id, $keywords)
    {
        if ($keywords) {
            $splittedKeywords = explode(',', $keywords);
            $created_at = Carbon::now()->toDateTimeString();
            foreach ($splittedKeywords as $keyword) {
                $emojiKeyword = Keyword::create([
                    'emoji_id'     => $emoji_id,
                    'keyword_name' => $keyword,
                    'created_at'   => $created_at,
                ]);
            }
        }
        return $emojiKeyword->id;
    }
}
