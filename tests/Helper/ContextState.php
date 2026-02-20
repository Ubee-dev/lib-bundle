<?php

namespace UbeeDev\LibBundle\Tests\Helper;

/**
 * ContextState
 * Helper for Behat contexts.
 * Use this to cache state information or to exchange state information between Behat contexts.
 * Warning: This is essentially a global object, so use with caution!
 */
class ContextState implements ContextStateInterface
{
    /**
     * @var array State information for context
     */
    private array $state = [];

    /**
     * Get state information for context
     *
     * @param string $key
     * @return mixed|null
     */
    public function getState($key) {
        return array_key_exists($key, $this->state) ? $this->state[$key] : null;
    }

    /**
     * Set state information for context
     *
     * @param string $key
     * @param mixed|null $value
     */
    public function setState($key, $value) {
        $this->state[$key] = $value;
    }
}