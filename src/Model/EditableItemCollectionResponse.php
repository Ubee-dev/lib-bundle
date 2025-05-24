<?php


namespace Khalil1608\LibBundle\Model;

use Symfony\Component\HttpFoundation\JsonResponse;

class EditableItemCollectionResponse extends JsonResponse
{
    /**
     * EditableItemCollectionResponse constructor.
     * @param $existingItems
     * @param $availableItems
     * @param null $dtoClass
     * @param array $parameters
     */
    public function __construct($existingItems, $availableItems, $dtoClass = null, $parameters = [])
    {
        $data = ['existingItems' => [], 'availableItems' => []];

        foreach ($existingItems as $item) {
            $data['existingItems'][] = $dtoClass ? (new $dtoClass($item, ...$parameters))->getItem(true) : $item->getItem(true);
        }

        foreach ($availableItems as $item) {
            $data['availableItems'][] = $dtoClass ? (new $dtoClass($item, ...$parameters))->getItem() : $item->getItem();
        }

        parent::__construct($data);
    }
}
