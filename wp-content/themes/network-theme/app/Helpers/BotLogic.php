<?php

namespace App\Helpers;

use Roots\Acorn\Application;

class BotLogic
{
    /**
     * Check if the current visitor is a VIP bot (Google, Bing, OpenAI, etc).
     * Used for Payload Diet optimization.
     *
     * @return bool
     */
    public static function is_vip_ai_bot()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Extended list of bots we want to serve a simplified payload to
        $bots = [
            'Googlebot',
            'Bingbot',
            'Slurp',
            'DuckDuckBot',
            'Baiduspider',
            'YandexBot',
            'Sogou',
            'Exabot',
            'facebot',
            'ia_archiver',
            'GPTBot',
            'ChatGPT-User',
            'Google-Extended',
            'AnthropicAI',
            'ClaudeBot',
            'Omgilibot',
            'FacebookBot'
        ];

        foreach ($bots as $bot) {
            if (stripos($userAgent, $bot) !== false) {
                return true;
            }
        }

        return false;
    }
}
