<?php

namespace App\Form;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class RemoteForm.
 */
class RemoteForm
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
     */
    public function setKeys(array $keys)
    {
        $this->keys = $keys;
    }
}
