<?php

namespace CarlBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * City
 *
 * @ORM\Table(name="cities")
 * @ORM\Entity(repositoryClass="CarlBundle\Repository\CityRepository")
 *
 */
class City
{
    public const MOSCOW_ID     = 1;
    public const PETERSBURG_ID = 2;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"brand_city", "partner_view","car_view","city_view"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     *
     * @Groups({"brand_city", "partner_view","car_view","city_view"})
     *
     */
    private $name;

    /**
     * @var float
     *
     * @ORM\Column(name="lat", type="decimal", precision=10, scale=6)
     *
     * @Groups({"city_view"})
     */
    private $latitude;

    /**
     * @var float
     *
     * @ORM\Column(name="lng", type="decimal", precision=10, scale=6)
     *
     * @Groups({"city_view"})
     */
    private $longitude;

    /**
     * @var string|null
     *
     * @ORM\Column(name="polyline", type="string")
     *
     * @Groups({"city_view"})
     *
     */
    private $polyline;

    /**
     *
     * @Groups({"brand_city"})
     *
     * @var int
     */
    private $state;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return City
     */
    public function setName($name): City
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set latitude
     *
     * @param float $latitude
     *
     * @return City
     */
    public function setLatitude($latitude): City
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get latitude
     *
     * @return float
     */
    public function getLatitude(): float
    {
        return round($this->latitude, 6);
    }

    /**
     * Set longitude
     *
     * @param float $longitude
     *
     * @return City
     */
    public function setLongitude($longitude): City
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get longitude
     *
     * @return float
     */
    public function getLongitude(): float
    {
        return round($this->longitude, 6);
    }

    /**
     * @param int $state
     * @return City
     */
    public function setState(int $state): City
    {
        $this->state = $state;

        return $this;
    }

    /**
     *
     * @return int
     */
    public function getState(): ?int
    {
        return $this->state;
    }

    /**
     * Get polyline
     *
     * @return string|null
     */
    public function getPolyline(): ?string
    {
        if($this->polyline){
            return $this->polyline;
        }

        return null;
    }

    /**
     * Set polyline
     *
     * @param string $polyline
     *
     * @return City
     */
    public function setPolyline($polyline): City
    {
        $this->polyline = $polyline;

        return $this;
    }
}
