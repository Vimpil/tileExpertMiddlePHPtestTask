<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class PriceRequest
{
    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^[a-zA-Z0-9-_]+$/")
     */
    public string $factory;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^[a-zA-Z0-9-_]+$/")
     */
    public string $collection;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^[a-zA-Z0-9-_]+$/")
     */
    public string $article;
}