<?php


namespace UbeeDev\LibBundle\Model;

use Symfony\Component\HttpFoundation\JsonResponse;

class EditableItemCollectionResponse extends JsonResponse
{
    public function __construct(iterable $existingItems, iterable $availableItems, ?string $dtoClass = null, array $parameters = [])
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
