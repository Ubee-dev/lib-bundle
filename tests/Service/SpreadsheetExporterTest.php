<?php

namespace Khalil1608\LibBundle\Tests\Service;

use Khalil1608\LibBundle\Service\SpreadsheetExporter;
use Khalil1608\LibBundle\Tests\AbstractWebTestCase;
use Khalil1608\LibBundle\Traits\PhoneNumberTrait;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class SpreadsheetExporterTest extends AbstractWebTestCase
{
    use PhoneNumberTrait;

    private SpreadsheetExporter $spreadsheetExporter;

    public function setUp(): void
    {
        parent::setUp();
        $this->initExporter();
    }

    private function initExporter()
    {
        $this->spreadsheetExporter = new SpreadsheetExporter($this);
    }

    /**
     * @throws Exception
     */
    public function testExportSpreadSheet()
    {
        $country1 = new Country("France");
        $country2 = new Country("Angleterre");
        $city1 = new City("Paris", $country1);
        $city2 = new City("London", $country2);
        $address1 = new Address("103 Boulevard Saint-Michel", $city1);
        $address2 = new Address("1105 Wellington Rd", $city2);
        $cat1 = new Cat("Harry", $address2);
        $person1 = new Person("Goku", "San", 33, "0142021148", [$address1, $address2]);
        $person2 = new Person("Piccolo", "Namek", null, null, [], $cat1);

        $spreadsheet = $this->spreadsheetExporter->exportSpreadSheet("Some title", [$person1, $person2], [
            ['Person firstName', 'firstName', 'formatFirstName'],
            ['Person lastName', 'lastName'],
            ['Person phone number', ['countryCallingCode', 'phoneNumber'], 'getFormattedPhoneNumber'],
            ['Address street', ['getAddressForCity.street'], null, [$city1]],
            ['Address city name', ['getAddressForCity.city.name'], null, [$city1]],
            ['FullName', null, 'getFormattedFullName'],
            ['Cat address name', ['cat.name']],
            ['Cat address street', ['cat.address.street']],
            ['Cat address street', ['cat.address.street']],
        ]);

        $this->assertEquals("Some title", $spreadsheet->getProperties()->getTitle());
        $this->assertEquals("Khalil1608", $spreadsheet->getProperties()->getCreator());
        $this->assertCellValue('A1', 'Person firstName', $spreadsheet);
        $this->assertCellValue('B1', 'Person lastName', $spreadsheet);
        $this->assertCellValue('C1', 'Person phone number', $spreadsheet);
        $this->assertCellValue('D1', 'Address street', $spreadsheet);
        $this->assertCellValue('E1', 'Address city name', $spreadsheet);
        $this->assertCellValue('F1', 'FullName', $spreadsheet);
        $this->assertCellValue('G1', 'Cat address name', $spreadsheet);
        $this->assertCellValue('H1', 'Cat address street', $spreadsheet);

        $this->assertCellValue('A2', 'FirstName : Goku', $spreadsheet);
        $this->assertCellValue('B2', 'San', $spreadsheet);
        $this->assertCellValue('C2', '+33 1 42 02 11 48', $spreadsheet);
        $this->assertCellValue('D2', '103 Boulevard Saint-Michel', $spreadsheet);
        $this->assertCellValue('E2', 'Paris', $spreadsheet);
        $this->assertCellValue('F2', 'Goku San', $spreadsheet);
        $this->assertCellValue('G2', null, $spreadsheet);
        $this->assertCellValue('H2', null, $spreadsheet);

        $this->assertCellValue('A3', 'FirstName : Piccolo', $spreadsheet);
        $this->assertCellValue('B3', 'Namek', $spreadsheet);
        $this->assertCellValue('C3', null, $spreadsheet);
        $this->assertCellValue('D3', null, $spreadsheet);
        $this->assertCellValue('E3', null, $spreadsheet);
        $this->assertCellValue('F3', 'Piccolo Namek', $spreadsheet);
        $this->assertCellValue('G3', 'Harry', $spreadsheet);
        $this->assertCellValue('H3', '1105 Wellington Rd', $spreadsheet);
    }

    /**
     * @throws Exception
     */
    public function testExportSpreadSheetWithDataUpToZ(): void
    {
        $country = new Country("France");
        $dataToExport = [];

        for ($i = 0; $i <= 26; $i++) {
            $dataToExport[] = ['Country name'.$i, 'name'];
        }
        $spreadsheet = $this->spreadsheetExporter->exportSpreadSheet("Some title", [$country], $dataToExport);

        $this->assertCellValue('AA1', "Country name26", $spreadsheet);
        $this->assertCellValue('AA2', "France", $spreadsheet);
    }

    public function formatFirstName(string $firstName): string
    {
        return 'FirstName : '.$firstName;
    }

    public function getFormattedFullName(Person $person): string
    {
        return $person->getFirstName().' '.$person->getLastName();
    }

    public function getFormattedFullNamesWithObjectsAsParameters(Person $person1, Person $person2): string
    {
        return $this->getFormattedFullName($person1).' & '.$this->getFormattedFullName($person2);
    }

    /**
     * @param $value
     * @param mixed $cell
     * @param Spreadsheet $spreadsheet
     * @throws Exception
     */
    private function assertCellValue(string $cell, $value, Spreadsheet $spreadsheet) : void
    {
        $this->assertEquals($value, $spreadsheet->getSheet(0)->getCell($cell)->getValue());
    }
}

class Person {

    private string $firstName;
    private string $lastName;
    private ?int $countryCallingCode;
    private ?string $phoneNumber;
    private array $addresses;
    private ?Cat $cat;

    public function __construct(string $firstName, string $lastName, int $countryCallingCode = null, string $phoneNumber = null, array $addresses = [], ?Cat $cat = null)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->countryCallingCode = $countryCallingCode;
        $this->phoneNumber = $phoneNumber;
        $this->addresses = $addresses;
        $this->cat = $cat;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getAddresses(): array
    {
        return $this->addresses;
    }

    public function getAddressForCity(City $city): ?Address
    {
        return array_filter($this->addresses, function (Address $address) use ($city) {
            return $address->getCity() === $city;
        })[0] ?? null;
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @return int|null
     */
    public function getCountryCallingCode(): ?int
    {
        return $this->countryCallingCode;
    }

    public function getCat() :?Cat
    {
        return $this->cat;
    }
}

class Address {

    private string $street;
    private City $city;

    public function __construct(string $street, City $city)
    {
        $this->street = $street;
        $this->city = $city;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getCity(): City
    {
        return $this->city;
    }
}

class City {

    private string $name;
    private Country $country;

    public function __construct(string $name, Country $country)
    {
        $this->name = $name;
        $this->country = $country;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCountry(): Country
    {
        return $this->country;
    }
}

class Country {

    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

class Cat {

    private string $name;
    private Address $address;

    public function __construct(string $name, Address $address)
    {
        $this->name = $name;
        $this->address = $address;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }
}