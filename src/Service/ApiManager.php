<?php

namespace Khalil1608\LibBundle\Service;

use Khalil1608\LibBundle\Config\ParameterType;
use Khalil1608\LibBundle\Entity\DateTime;
use Khalil1608\LibBundle\Model\JsonSerializable;
use Khalil1608\LibBundle\Traits\DateTimeTrait;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Money\Money;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiManager
{
    use DateTimeTrait;

    public function __construct(
        private readonly Paginator       $paginator,
        private readonly OptionsResolver $optionsResolver,
        private readonly ?string         $appToken = null
    )
    {
    }

    /**
     * @throws Exception
     */
    public function sanitizeParameters(
        array $data,
        array $sanitizingExpectations,
        array $allowedValues = [],
        array $defaultValues = [],
        bool  $strictMode = true
    ): array
    {
        return $this->sanitize(
            $data,
            $sanitizingExpectations,
            $allowedValues,
            $defaultValues,
            $strictMode
        );
    }

    /**
     * @throws Exception
     */
    public function sanitizePostParameters(
        Request $request,
        array   $sanitizingExpectations,
        array   $allowedValues = [],
        array   $defaultValues = [],
        bool    $strictMode = true
    ): array
    {
        return $this->sanitize(
            $request->request->all(),
            $sanitizingExpectations,
            $allowedValues,
            $defaultValues,
            $strictMode
        );
    }

    /**
     * @throws Exception
     */
    public function sanitizeQueryParameters(
        Request $request,
        array   $sanitizingExpectations,
        array   $allowedValues = [],
        array   $defaultValues = [],
        bool    $strictMode = true
    ): array
    {
        return $this->sanitize(
            $request->query->all(),
            $sanitizingExpectations,
            $allowedValues,
            $defaultValues,
            $strictMode
        );
    }

    /**
     * @throws Exception
     */
    public function sanitizeFileParameters(
        Request $request,
        array   $sanitizingExpectations,
        array   $allowedValues = [],
        array   $defaultValues = [],
        bool    $strictMode = true
    ): array
    {
        return $this->sanitize(
            $request->files->all(),
            $sanitizingExpectations,
            $allowedValues,
            $defaultValues,
            $strictMode
        );
    }

    /**
     * @throws Exception
     */
    public function sanitizeHeaderParameters(
        Request $request,
        array   $sanitizingExpectations,
        array   $allowedValues = [],
        array   $defaultValues = [],
        bool    $strictMode = true
    ): array
    {
        $params = [];
        foreach ($request->headers->all() as $headerName => $param) {
            $params[$headerName] = $param[0] ?? $param;
        }
        return $this->sanitize($params, $sanitizingExpectations, $allowedValues, $defaultValues, $strictMode);
    }

    /**
     * @param array $output
     * @param array $requiredParameters
     * @return array
     */
    public function formatOutput(array $output, array $requiredParameters): array
    {
        foreach ($requiredParameters as $key => $type) {
            $serializedValue = $output[$key];

            if ($serializedValue) {
                // Handle both enum and string types
                $typeValue = $type instanceof ParameterType ? $type->value : $type;

                if ($typeValue === 'date') {
                    /** @var DateTime $serializedValue */
                    $serializedValue = $serializedValue->format('Y-m-d');
                } elseif ($typeValue === 'datetime') {
                    /** @var DateTime $serializedValue */
                    $serializedValue = $serializedValue->format('c');
                } elseif ($typeValue === 'money') {
                    /** @var Money $serializedValue */
                    $serializedValue = (int)$serializedValue->getAmount();
                } elseif ($typeValue === 'int') {
                    $serializedValue = (int)$serializedValue;
                } elseif ($typeValue === 'entity') {
                    /** @var object $serializedValue */
                    $serializedValue = $serializedValue->getId();
                }
            }

            $output[$key] = $serializedValue;
        }

        return $output;
    }

    /**
     * @throws Exception
     */
    public function sanitize(
        array $params,
        array $sanitizingExpectations,
        array $allowedValues = [],
        array $defaultValues = [],
        bool  $strictMode = true
    ): array
    {
        return $this->optionsResolver
            ->setParameters($params)
            ->setSanitizingExpectations($sanitizingExpectations)
            ->setAllowedValues($allowedValues)
            ->setDefaultValues($defaultValues)
            ->setStrictMode($strictMode)
            ->resolve();
    }

    /**
     * @throws Exception
     */
    public function paginatedApiResponse(
        QueryBuilder $queryBuilder,
        Request      $request,
        ?string      $dtoClass = null,
        array        $params = [],
        ?string      $timezone = null,
        array        $orderBy = []
    ): JsonResponse
    {
        if ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                $queryBuilder->addOrderBy($field, $direction);
            }
        }

        $paginatorResult = $this->paginator->getPaginatedQueryResult(
            $queryBuilder,
            $request,
            $dtoClass,
            $params
        );

        $results = $paginatorResult->getCurrentPageResults();

        if ($timezone) {
            $response = $this->jsonWithTimezone($results, $timezone);
        } else {
            $response = new JsonResponse($results);
        }

        $response->headers->set('nbTotalResults', $paginatorResult->getNbTotalResults());
        $response->headers->set('nbDisplayedResults', $paginatorResult->getNbCumulativeResults());
        $response->headers->set('pageSize', $paginatorResult->getPageSize());

        return $response;
    }

    /**
     * @throws Exception
     */
    public function convertLocalDateTimesToDefaultTimezone(array $params, array $times, string $timeZone): array
    {
        foreach ($params as $paramName => $date) {
            $dateTime = new DateTime($date);
            $params[$paramName] = $this->convertLocalDateTimeToDefaultTimezone($dateTime, $timeZone, $times[$paramName] ?? $dateTime->format('H:i:s'));
        }

        return $params;
    }

    /**
     * @param $data
     * @param array $params
     * @return array
     */
    public function jsonSerializeData($data, array $params = []): array
    {
        $result = [];
        foreach ($data as $entity) {
            if ($entity instanceof JsonSerializable) {
                $result[] = $entity->jsonSerialize($params);
            }
        }

        return $result;
    }

    /**
     * @param array|\JsonSerializable $data
     * @param string $timezone
     * @return JsonResponse
     */
    public function jsonWithTimezone($data, string $timezone): JsonResponse
    {
        if ($data instanceof \JsonSerializable) {
            return new JsonResponse($this->convertDataWithTimezone($data->jsonSerialize(), $timezone));
        }

        return new JsonResponse($this->convertDataWithTimezone($data, $timezone));
    }

    public function checkHeadersParameters(?string $token): void
    {
        if ($token !== $this->appToken) {
            throw new HttpException(401, 'Wrong Token');
        }
    }

    private function convertDataWithTimezone(array $data, string $timezone): array
    {
        $convertedData = [];
        foreach ($data as $key => $dataToConvertWithTimezone) {
            if ($dataToConvertWithTimezone instanceof DateTime) {
                $convertedData[$key] = $dataToConvertWithTimezone->setTimezone(new \DateTimeZone($timezone));
            } elseif ($dataToConvertWithTimezone instanceof \JsonSerializable) {
                $value = $dataToConvertWithTimezone->jsonSerialize();
                $convertedData[$key] = is_array($value) ? $this->convertDataWithTimezone($value, $timezone) : $value;
            } elseif (is_array($dataToConvertWithTimezone)) {
                $convertedData[$key] = $this->convertDataWithTimezone($dataToConvertWithTimezone, $timezone);
            } else {
                $convertedData[$key] = $dataToConvertWithTimezone;
            }
        }

        return $convertedData;
    }
}
