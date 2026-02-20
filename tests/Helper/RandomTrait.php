<?php

namespace UbeeDev\LibBundle\Tests\Helper;

trait RandomTrait
{
    protected function isCharacterClass($string): bool
    {
        return $string[0] == '[' && $string[strlen($string) - 1] == ']';
    }

    protected function getCharacterClass($characterClassName): ?string
    {
        $characterClasses = [
            '[a-z]' => 'abcdefghijklmnopqrstuvwxyz',
            '[A-Z]' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            '[0-9]' => '0123456789'
        ];
        $characterClasses['[a-zA-Z]'] = $characterClasses['[a-z]'] . $characterClasses['[A-Z]'];
        $characterClasses['[a-z0-9]'] = $characterClasses['[a-z]'] . $characterClasses['[0-9]'];
        $characterClasses['[A-Z0-9]'] = $characterClasses['[A-Z]'] . $characterClasses['[0-9]'];
        $characterClasses['[a-zA-Z0-9]'] = $characterClasses['[a-z]'] . $characterClasses['[A-Z]'] . $characterClasses['[0-9]'];

        return array_key_exists($characterClassName, $characterClasses) ? $characterClasses[$characterClassName] : null;
    }

    protected function capitalizeWords($string): string
    {
        $parts = explode(' ', strtolower($string));
        for ($i = 0, $len = count($parts); $i < $len; $i++) {
            $parts[$i] = ucfirst($parts[$i]);
        }
        return implode(' ', $parts);
    }

    public function randomBool(): bool
    {
        return $this->faker->boolean(50);
    }

    public function randomPhoneNumber(): string
    {
        return $this->faker->phoneNumber();
    }

    public function randomFacebookEventLink(): string
    {
        return 'https://www.facebook.com/events/' . $this->randomFacebookId();
    }

    public function randomFacebookLink(): string
    {
        return 'https://www.facebook.com/' . $this->randomFacebookId();
    }

    public function randomFacebookId(): string
    {
        return $this->faker->numerify('############');
    }

    public function randomYoutubeLink(): string
    {
        return 'https://www.youtube.com/watch?v=' . $this->faker->word();
    }

    public function randomYoutubeEmbedLink(): string
    {
        return 'https://www.youtube.com/embed/' . $this->faker->word();
    }

    public function randomToken(): string
    {
        return $this->faker->slug;
    }

    public function randomName($unique = false): string
    {
        return ($unique) ? $this->faker->unique()->name() : $this->faker->name();
    }

    public function randomEmail(): string
    {
        return $this->faker->email();
    }

    public function randomDomain(): string
    {
        return $this->faker->unique()->domainName();
    }
    public function randomGtmId(): string
    {
        $number = $this->faker->randomNumber(2);
        return 'GTM-' . strtoupper($this->faker->word()) . $number;
    }

    public function randomAddressLine(): string
    {
        return $this->faker->streetAddress();
    }

    public function randomPostCode(): string
    {
        return str_replace(' ','',$this->faker->postcode());
    }

    public function randomTitle($wordCount = 2): string
    {
        return $this->faker->unique()->words($wordCount, true);
    }

    public function randomSentence($wordCount = 10): string
    {
        return $this->faker->sentence($wordCount);
    }
    public function randomSentences($sentenceCount = 4): string
    {
        return implode(' ', $this->faker->sentences($sentenceCount));
    }

    public function randomPrice(): int
    {
        return $this->faker->randomNumber();
    }

    public function randomCity(): string
    {
        return $this->faker->city();
    }

    public function randomUsername(): string
    {
        return $this->faker->userName();
    }

    public function randomFirstName(): string
    {
        return $this->faker->firstName();
    }

    public function randomLastName(): string
    {
        return $this->faker->lastName();
    }

    public function randomId(): int
    {
        return $this->faker->unique()->randomDigit();
    }

    public function getRandomId(): int
    {
        return $this->faker->unique()->randomDigitNotNull();
    }

    public function getRandomIpv4(): string
    {
        return $this->faker->ipv4();
    }

    public function randomParagraph($nbSentences = 5): string
    {
        return $this->faker->paragraph($nbSentences);
    }

    public function randomFunction(): string
    {
        return $this->faker->jobTitle();
    }

    public function randomUrl(): string
    {
        return $this->faker->url();
    }

    public function generateImage($dir): string
    {
        return $this->faker->image($dir);
    }

    public function randomPassword(): string
    {
        return $this->faker->password();
    }

    public function randomStreetNumber(): string
    {
        return $this->faker->buildingNumber;
    }

    public function randomStreetName(): string
    {
        return $this->faker->streetName();
    }

    public function randomIban(): string
    {
        return $this->faker->iban('FR');
    }


}