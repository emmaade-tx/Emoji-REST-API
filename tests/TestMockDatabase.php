<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;
use PDO;
use Demo\Emoji;
use Demo\Keyword;
use Demo\User;
use Demo\DatabaseSchema;

class TestMockDatabase
{
    /**
     * Create test emoji for test user.
     *
     * @param Demo\User $user
     *
     */
    private function createEmojiOwnedBy($user)
    {
        $emojiData = [
        'name'     => 'grinning face with smiling eyes',
        'chars'     => 'ut-1768b',
        'category' => 'Category D',
        'keywords' => ['smile', 'teeth opening'],
        ];

        $emoji = new Emoji();
        $emoji->name = $emojiData['name'];
        $emoji->chars = $emojiData['chars'];
        $emoji->category = $emojiData['category'];
        $user->emoji()->save($emoji);

        $createdKeyword = $this->createEmojiKeywords($emoji->id, $emojiData['keywords']);
    }
    /**
     * Create keywords.
     *
     * @param array $keywordsData
     *
     * @return array
     */
    private function createEmojiKeywords($emoji_id, array $keywords)
    {
        if ($keywords) {
            foreach ($keywords as $keyword) {
                $emojiKeyword = Keyword::create([
                        'emoji_id'     => $emoji_id,
                        'keyword_name' => $keyword,
                ]);
            }
        }

        return $emojiKeyword->id;
    }
    /**
     * mock test Database with tests values.
     *
     * @return Demo\User
     */
    public function mockData()
    {
        Capsule::beginTransaction();
            $user = User::firstOrCreate(['fullname' => 'John Test', 'username' => 'tester', 'password' => password_hash('test', PASSWORD_DEFAULT)]);
            $user2 = User::firstOrCreate(['fullname' => 'John Test2', 'username' => 'tester2', 'password' => password_hash('test', PASSWORD_DEFAULT)]);
            $this->createEmojiOwnedBy($user);
            $this->createEmojiOwnedBy($user2);
            Capsule::commit();
    
            return $user;
    }
}
