<?php


namespace Khalil1608\LibBundle\Service;


use Symfony\Component\HttpFoundation\Request;

class UtmManager
{
    public function getUTMParamsFromRequest(Request $request): array
    {
        $getParams = $request->query->all();
        $utms = array_filter($getParams, function($key) {
            return !! preg_match('/^utm_.*/i', $key);
        }, ARRAY_FILTER_USE_KEY);

        if(!count($utms)) {
            $utms = $request->cookies->get('utm') ?? [];

            if($utms) {
                $utms = json_decode($request->cookies->get('utm'));
            }
        }
        return $utms;
    }

    public function utmParams($url, Request $request): string
    {
        $utms = $this->getUTMParamsFromRequest($request);
        $operator = '';
        $utmQuery = http_build_query($utms);

        if($utmQuery) {
            $operator = array_key_exists('query', parse_url($url)) ? '&' : '?';
        }

        return $url . $operator . $utmQuery;
    }
}