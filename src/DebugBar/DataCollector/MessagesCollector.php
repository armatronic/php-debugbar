<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\DataCollector;

use Psr\Log\AbstractLogger;

/**
 * Provides a way to log messages
 */
class MessagesCollector extends AbstractLogger implements DataCollectorInterface, Renderable
{
    protected $messages = array();

    /**
     * {@inheritDoc}
     */
    public function log($level, $message, array $context = array())
    {
        $this->addMessage($message, $level);
    }

    /**
     * Adds a message
     *
     * A message can be anything from an object to a string
     * 
     * @param mixed $message
     * @param string $label
     */
    public function addMessage($message, $label = 'info')
    {
        $this->messages[] = array(
            'message' => print_r($message, true),
            'is_string' => is_string($message),
            'label' => $label,
            'time' => microtime(true),
            'memory_usage' => memory_get_usage()
        );
    }

    /**
     * Returns all messages
     * 
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * {@inheritDoc}
     */
    public function collect()
    {
        return array(
            'count' => count($this->messages),
            'messages' => $this->messages
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'messages';
    }

    /**
     * {@inheritDoc}
     */
    public function getWidgets()
    {
        return array(
            "messages" => array(
                "widget" => "PhpDebugBar.Widgets.MessagesWidget", 
                "map" => "messages.messages", 
                "default" => "[]"
            ),
            "messages:badge" => array(
                "map" => "messages.count",
                "default" => "null"
            )
        );
    }
}
