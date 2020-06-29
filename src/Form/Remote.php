<?php

namespace App\Form;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Remote.
 *
 * @package App\Form
 */
class Remote
{
    /**
     * @var array
     *
     * @Assert\NotBlank()
     */
    protected $keys;

    /**
     * Get the selected keys.
     *
     * @return array
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * Set the selected keys.
     *
     * @param array $keys
     */
    public function setKeys(array $keys)
    {
        $this->keys = $keys;
    }
}