<?php

/**
 * This file is part of the pd-admin pd-widget package.
 *
 * @package     pd-widget
 * @license     LICENSE
 * @author      Ramazan APAYDIN <apaydin541@gmail.com>
 * @link        https://github.com/appaydin/pd-widget
 */

namespace Pd\WidgetBundle\Render;

use Pd\WidgetBundle\Builder\ItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

/**
 * Widget Twig Render.
 *
 * @author Ramazan APAYDIN <apaydin541@gmail.com>
 */
class TwigRender implements RenderInterface
{
    public function __construct(
        private Environment $engine,
        private CacheItemPoolInterface $cache,
        private TokenStorageInterface $tokenStorage,
        private string $baseTemplate)
    {
    }

    /**
     * Render Widgets.
     *
     * @param $widgets ItemInterface[]
     * @param bool $base
     *
     * @return string
     */
    public function render($widgets, bool $base = true): string
    {
        if (!$widgets) {
            return false;
        }

        // Output Storage
        $output = '';

        // Get User ID
        $userId = $this->tokenStorage->getToken()->getUser()->getId();

        foreach ($widgets as $widget) {
            if ($widget->isActive()) {
                $output .= $this->getOutput($widget, $userId);
            }
        }

        // Render Base
        if ($base) {
            try {
                $output = @$this->engine->render($this->baseTemplate, ['widgets' => $output]);
            } catch (\Exception $e) {
            }
        }

        return $output;
    }

    /**
     * Get Widget Output for Cache.
     */
    public function getOutput(ItemInterface $item, $userId): string
    {
        if ($item->getCacheTime()) {
            // Get Cache Item
            $cache = $this->cache->getItem($item->getId() . $userId);

            // Set Cache Expires
            $cache->expiresAfter($item->getCacheTime());

            // Save
            if (false === $cache->isHit()) {
                $cache->set($item->getTemplate() ? $this->engine->render($item->getTemplate(), ['widget' => $item]) : $item->getContent());
                $this->cache->save($cache);
            }

            return $cache->get();
        }

        return $item->getTemplate() ? $this->engine->render($item->getTemplate(), ['widget' => $item]) : $item->getContent();
    }
}
