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
     * @var string
     *
     * @Assert\NotBlank()
     */
    protected $key;

    /**
     * Get the selected key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the selected key.
     *
     * @param $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }
}