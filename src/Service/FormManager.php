<?php


namespace UbeeDev\LibBundle\Service;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class FormManager
{
    const MAX_EXECUTION_TIME = 2;

    public function __construct(
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly bool $blockBots = true,
    )
    {
    }

    public function wasFilledByARobot(
        Request $request,
        string $firstNameField = 'Name_First',
        string $lastNameField = 'Name_Last',
        string $emailField = 'Email',
        ?string $dataPath = null,
        bool $compareFirstAndLastName = true
    ): bool
    {
        if($this->blockBots === false) {
            return false;
        }

        $data = $dataPath ? $request->request->get($dataPath) : $request->request->all();

        if(!array_key_exists('as_first', $data)) {
            return true;
        }
        
        $firstName = $data[$firstNameField] ?? null;
        $lastName = $data[$lastNameField] ?? null;
        $email = $data[$emailField] ?? null;

        if(!$firstName or !$lastName or !$email) {
            return true;
        }

        if($compareFirstAndLastName && trim($firstName) === trim($lastName)) {
            return true;
        }
        
        return $this->checkIfDataFieldByARobot($data);
    }

    /**
     * @param array $data
     * @return array
     */
    public function removeAntiSpamFields(array $data): array
    {
        unset($data['as_first']);
        unset($data['as_second']);
        unset($data['execution_time']);
        return $data;
    }

    public function checkIfDataFieldByARobot(array $data): bool
    {
        if($this->dataContainsHtml($data)) {
            return true;
        }

        $javascriptIsEnabled = array_key_exists('as_second', $data);

        if($javascriptIsEnabled) {
            return ($data['as_second'] or $data['as_first']) or (!is_numeric($data['execution_time'])) or ($data['execution_time'] <= self::MAX_EXECUTION_TIME);
        }

        return (bool)$data['as_first'];
    }
    
    public function csrfTokenIsValid(?string $token, string $tokenId): bool
    {
        if (empty($token)) {
            return false;
        }

        try {
            return $this->csrfTokenManager->isTokenValid(
                new \Symfony\Component\Security\Csrf\CsrfToken($tokenId, $token)
            );
        } catch (TokenNotFoundException $e) {
            return false;
        }
    }

    /**
     * @param array $data
     * @return bool
     */
    private function dataContainsHtml(array $data): bool
    {
        foreach ($data as $value) {
            if($value !== null && $value != strip_tags($value)) {
                return true;
            }
        }
        
        return false;
    }
}
