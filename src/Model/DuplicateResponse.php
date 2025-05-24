<?php


namespace Khalil1608\LibBundle\Model;

use Symfony\Component\HttpFoundation\JsonResponse;

class DuplicateResponse extends JsonResponse
{

    /**
     * DuplicateResponse constructor.
     * @param Itemizable|array $data
     * @param $matchType
     * @param null $primaryLabel
     * @param null $secondaryLabel
     */
    public function __construct($data, $matchType, $primaryLabel = null, $secondaryLabel = null)
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
