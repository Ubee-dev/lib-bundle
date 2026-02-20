<?php


namespace UbeeDev\LibBundle\Model;


use Symfony\Component\HttpFoundation\JsonResponse;

class ResourceResponse extends JsonResponse
{
    public function __construct(mixed $data, string $url)
    {
        parent::__construct($data, 200, [], false);
        $this->headers->set('resourceUri', $url);
    }
}
