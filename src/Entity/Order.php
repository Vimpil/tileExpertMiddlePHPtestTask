<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\OrderRepository")
 * @ORM\Table(name="orders")
 */
class Order
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createDate;

    public function getId(): ?int { return $this->id; }
    public function getCreateDate(): ?\DateTimeInterface { return $this->createDate; }
    public function setCreateDate(\DateTimeInterface $createDate): self { $this->createDate = $createDate; return $this; }
}
?>