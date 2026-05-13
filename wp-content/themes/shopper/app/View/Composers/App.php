<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use App\Helpers\BotLogic;

class App extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        '*',
    ];

    /**
     * Data to be passed to view before rendering.
     *
     * @return array
     */
    public function with()
    {
        return [
            'siteName' => $this->siteName(),
            'storeOptions' => $this->storeOptions(),
            'getLogoURL' => $this->getLogoURL(),
            'isBot' => BotLogic::is_vip_ai_bot(),
        ];
    }

    /**
     * Returns the site name.
     *
     * @return string
     */
    public function siteName()
    {
        return get_bloginfo('name', 'display');
    }

    public function storeOptions()
    {
        return get_option('store_settings') ?: [];
    }

    public function getLogoURL()
    {
        $logo = wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full');

        if ($logo) {
            return $logo;
        }

        // Fallback to Network Main Site (ID 1)
        if (function_exists('switch_to_blog')) {
            switch_to_blog(1);
            $logo = wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full');
            restore_current_blog();
        }

        return $logo ?? '';
    }
}
