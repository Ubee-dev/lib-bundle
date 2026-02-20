<?php


namespace UbeeDev\LibBundle\Model;

use Symfony\Component\HttpFoundation\JsonResponse;

class DuplicateResponse extends JsonResponse
{

    public function __construct(Itemizable|array|null $data, string $matchType, ?string $primaryLabel = null, ?string $secondaryLabel = null)
    {
        $match = [];
        if($data) {

            if($matchType === 'suspects') {
                /** @var Itemizable $duplicate */
                foreach ($data as $duplicate) {
                    $match[] = $duplicate->getItem();
                }

                $match[] = ['resourceId' => null, 'primaryLabel' => $primaryLabel, 'secondaryLabel' => $secondaryLabel];
            } else {
                $match = [$data->getItem()];
            }

            $data = ['matchType' => $matchType, 'match' => $match];

        } else {
            $data = [];
        }
        parent::__construct($data, 200, [], false);
    }
}
