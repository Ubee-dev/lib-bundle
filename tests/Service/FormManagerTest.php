<?php

namespace UbeeDev\LibBundle\Tests\Service;

use UbeeDev\LibBundle\Service\FormManager;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class FormManagerTest extends AbstractWebTestCase
{
    private Request $request;
    private FormManager $formManager;

    public function setUp(): void
    {
        parent::setUp();
        $this->request = new Request();
        $this->formManager = $this->initManager();
    }

    public function testWasFilledByARobotWithJavascriptInput(): void
    {
        $this->assertTrue($this->formManager->wasFilledByARobot($this->request));

        //check if firstName and lastName are the same
        $this->request->request->set('Name_First', 'Goku');
        $this->request->request->set('Name_Last', 'Goku');
        $this->request->request->set('as_first', '');
        $this->assertTrue($this->formManager->wasFilledByARobot($this->request));
        $this->assertTrue($this->formManager->wasFilledByARobot($this->request));

        //check if firstName and lastName are the same with custom name fields
        $this->request->request->set('firstName', 'Goku');
        $this->request->request->set('lastName', 'Goku');
        $this->request->request->set('as_first', '');
        $this->assertTrue($this->formManager->wasFilledByARobot($this->request, 'firstName', 'lastName'));

        //check if hidden fields are filled if javascript is enabled
        $this->request->request->set('as_first', 'some data');
        $this->request->request->set('as_second', 'some data');
        $this->assertTrue($this->formManager->wasFilledByARobot($this->request));

        //check if javascript hidden field is filled
        $this->request->request->set('as_first', null);
        $this->request->request->set('as_second', 'some data');
        $this->assertTrue($this->formManager->wasFilledByARobot($this->request));

        //check if hidden field is filled if javascript is disabled
        $this->request->request->set('as_first', 'some data');
        $this->request->request->set('as_second', null);
        $this->assertTrue($this->formManager->wasFilledByARobot($this->request));

        //check if javascript hidden field is filled with a non-numeric value
        $this->request->request->set('as_first', null);
        $this->request->request->set('as_second', null);
        $this->request->request->set('execution_time', 'non-numeric');
        $this->assertTrue($this->formManager->wasFilledByARobot($this->request));

        //check if form is filled in equal than max execution time (very quickly)
        $this->request->request->set('as_first', null);
        $this->request->request->set('as_second', null);
        $this->request->request->set('execution_time', 2);
        $this->assertTrue($this->formManager->wasFilledByARobot($this->request));

        //check if form is filled in less than max execution time (very quickly)
        $this->request->request->set('as_first', null);
        $this->request->request->set('as_second', null);
        $this->request->request->set('execution_time', 1);
        $this->assertTrue($this->formManager->wasFilledByARobot($this->request));

        //check if form is have html
        $this->request->request->set('Name_First', 'Goku');
        $this->request->request->set('Name_Last', 'San');
        $this->request->request->set('as_first', null);
        $this->request->request->set('as_second', null);
        $this->request->request->set('execution_time', 3);
        $this->request->request->set('fieldWithHtml', '<b>I have html</b>');
        $this->assertTrue($this->formManager->wasFilledByARobot($this->request));

        //check if form doesn't have firstName
        $this->request->request->set('as_first', '');
        $this->request->request->set('as_second', '');
        $this->request->request->set('execution_time', 3);
        $this->request->request->set('fieldWithHtml', 'I don\'t have html');
        $this->request->request->set('Email', 'test@gmail.com');
        $this->request->request->set('Name_First', '');
        $this->request->request->set('Name_Last', 'San');
        $this->assertTrue($this->formManager->wasFilledByARobot($this->request));

        //check if form doesn't have lastName
        $this->request->request->set('Name_First', 'Goku');
        $this->request->request->set('Name_Last', '');
        $this->request->request->set('Email', 'test@gmail.com');
        $this->assertTrue($this->formManager->wasFilledByARobot($this->request));

        //check if form doesn't have email
        $this->request->request->set('Name_First', 'Goku');
        $this->request->request->set('Name_Last', 'San');
        $this->request->request->set('Email', '');
        $this->assertTrue($this->formManager->wasFilledByARobot($this->request));

        //check if form doesn't have email with custom field
        $this->request->request->set('Name_First', 'Goku');
        $this->request->request->set('Name_Last', 'San');
        $this->request->request->set('Email', 'test@gmail.com');
        $this->assertTrue($this->formManager->wasFilledByARobot($this->request, 'Name_First', 'Name_Last', 'otherEmail'));

        $this->request->request->set('Name_First', 'Goku');
        $this->request->request->set('Name_Last', 'San');
        $this->request->request->set('Email', 'test@gmail.com');
        $this->assertFalse($this->formManager->wasFilledByARobot($this->request));
    }

    public function testWasFilledByARobotWithoutJavascriptInput(): void
    {
        //check if hidden fields is filled
        $this->request->request->set('Name_First', 'Goku');
        $this->request->request->set('Name_Last', 'Goku');
        $this->request->request->set('Email', 'test@gmail.com');
        $this->request->request->set('as_first', 'some data');
        $this->assertTrue($this->formManager->wasFilledByARobot($this->request));

        $this->request->request->set('Name_First', 'Goku');
        $this->request->request->set('Name_Last', 'San');
        $this->request->request->set('as_first', null);
        $this->assertFalse($this->formManager->wasFilledByARobot($this->request));

        $this->request->request->set('as_first', '');
        $this->assertFalse($this->formManager->wasFilledByARobot($this->request));
    }

    public function testRemoveAntiSpamFields()
    {
        $data = [
            'as_first' => 'field1',
            'as_second' => 'field1',
            'execution_time' => 'field3',
            'other' => 'field'
        ];

        $this->assertEquals(['other' => 'field'], $this->formManager->removeAntiSpamFields($data));
        // test if anti spam fields doesn't exists
        $this->assertEquals(['other2' => 'field'], $this->formManager->removeAntiSpamFields(['other2' => 'field']));
    }

    public function testCheckIfDataFieldByARobot(): void
    {
        $this->assertFalse($this->formManager->checkIfDataFieldByARobot(['as_first' => null]));
        $this->assertTrue($this->formManager->checkIfDataFieldByARobot(['as_first' => 'some-value']));

        $this->assertTrue($this->formManager->checkIfDataFieldByARobot([
            'as_first' => null,
            'as_second' => null,
            'execution_time' => null
        ]));

        $this->assertFalse($this->formManager->checkIfDataFieldByARobot([
            'as_first' => null,
            'as_second' => null,
            'execution_time' => 3
        ]));

        $this->assertTrue($this->formManager->checkIfDataFieldByARobot([
            'as_first' => null,
            'as_second' => null,
            'execution_time' => 1
        ]));

        $this->assertTrue($this->formManager->checkIfDataFieldByARobot([
            'as_first' => null,
            'as_second' => null,
            'execution_time' => 'non-numeric-value'
        ]));
    }

    private function initManager(): FormManager
    {
        return new FormManager($this->createMock(CsrfTokenManagerInterface::class), true);
    }
}
