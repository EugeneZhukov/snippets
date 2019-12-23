<?php

namespace CarlBundle\Controller;
use CarlBundle\Entity\City;
use CarlBundle\Exception\InvalidValueException;
use CarlBundle\Service\CityService;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * Supported Queries:
 * – GET /city
 * – GET /city/:id
 * – POST /city
 * – PUT /city/:id
 * – DELETE /city/:id
 *
 */
class CityController extends RestController
{

    /**
     * Get a list of cities
     *
     * @Rest\Get("/city")
     * @Rest\View(serializerGroups={
     *     "city_view"
     * }, statusCode=200)
     *
     * @param CityService $CityService
     * @param Request $Request
     *
     * @return mixed
     * @throws \Exception
     */
    public function listAction(CityService $CityService, Request $Request)
    {
        $offset = $Request->get('offset', 0);
        $limit = $Request->get('limit', 10);

        return $CityService->getCities($offset, $limit);
    }

    /**
     * Get the city by its ID
     *
     * @Rest\Get("/city/{cityId}", requirements={"cityId": "\d+"})
     * @Rest\View(serializerGroups={
     *     "city_view"
     * }, statusCode=200)
     *
     * @param int $cityId
     * @param CityService $CityService
     *
     * @return City
     */
    public function showAction(CityService $CityService, int $cityId): City
    {
        /** @var City $City */
        $City = $CityService->get($cityId);

        return $City;
    }


    /**
     * Create city
     *
     * @Rest\Post("/city")
     * @Rest\View(serializerGroups={"city_view"}, statusCode=201)
     *
     * @IsGranted("ROLE_ADMIN_USER")
     *
     * @param CityService $CityService
     * @param Request $Request
     *
     * @return City
     * @throws \CarlBundle\Exception\InvalidValueException
     */
    public function createAction(CityService $CityService, Request $Request): City
    {
        $City = $CityService->create(City::class, $Request->getContent());

        return $City;
    }

    /**
     * Edit city
     *
     * @Rest\Post("/city/{cityId}")
     * @Rest\View(serializerGroups={
     *     "city_view"
     * }, statusCode=200)
     *
     * @IsGranted("ROLE_ADMIN_USER")
     *
     * @param CityService $CityService
     * @param Request $Request
     * @param int $cityId
     *
     * @return City
     * @throws InvalidValueException
     */
    public function updateAction(CityService $CityService, Request $Request, int $cityId): City
    {
        $City = $CityService->update(City::class, $cityId, $Request->getContent());

        return $City;
    }

    /**
     * Remove city
     *
     * @Rest\Delete("/city/{cityId}")
     * @Rest\View(serializerGroups={
     *     "city_view"
     * }, statusCode=200)
     *
     * @IsGranted("ROLE_ADMIN_USER")
     *
     * @param CityService $CityService
     * @param int $cityId
     *
     * @return City
     */
    public function deleteAction(CityService $CityService, int $cityId): City
    {
        $City = $CityService->delete($cityId);

        return $City;
    }

}
