<?php

namespace Khalil1608\LibBundle\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Testwork\Tester\Result\TestResult;
use Khalil1608\LibBundle\Entity\Date;
use Khalil1608\LibBundle\Entity\DateTime;
use Khalil1608\LibBundle\Service\OpsAlertManager;
use Khalil1608\LibBundle\Tests\Helper\CleanerInterface;
use Khalil1608\LibBundle\Tests\Helper\ContextStateInterface;
use Khalil1608\LibBundle\Tests\Helper\DateMock;
use Khalil1608\LibBundle\Tests\Helper\DateTimeMock;
use Khalil1608\LibBundle\Tests\Helper\FactoryInterface;
use Khalil1608\LibBundle\Tests\Helper\FakeEmailProvider;
use Khalil1608\LibBundle\Traits\DateTimeTrait;
use Khalil1608\LibBundle\Traits\MoneyTrait;
use Khalil1608\LibBundle\Traits\ProcessTrait;
use Exception;
use PHPUnit\Framework\Assert;
use SlopeIt\ClockMock\ClockMock;
use Swift_Message;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use WebDriver\Exception\NoAlertOpenError;
use WebDriver\Session;

class CommonContext extends MinkContext implements Context
{
    use DateTimeTrait;
    use MoneyTrait;
    use ProcessTrait;

    private array $currentEmailData = [];
    protected Filesystem $fileSystem;
    protected string $screenshotDir;
    protected string $nsysPath;

    /**
     * BootstrapContext constructor.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     *
     */
    public function __construct(
        protected readonly FactoryInterface      $factory,
        protected readonly CleanerInterface      $cleaner,
        protected readonly KernelInterface       $kernel,
        protected readonly ParameterBagInterface $parameterBag,
        protected readonly RouterInterface       $router,
        protected readonly ContextStateInterface $contextState,
        protected readonly OpsAlertManager       $opsAlertManager,
        protected readonly string                $testToken,
        protected readonly string                $isCI,
        protected readonly string                $slackNotificationTs,
    )
    {
        $this->screenshotDir = '/var/www/behat/' . basename($this->parameterBag->get('kernel.project_dir'));
        $this->fileSystem = new Filesystem();
        $this->nsysPath = '/var/www/business-center-nsys';
    }

    /**
     * We need to purge the spool between each scenario
     *
     * @BeforeScenario @resizeWindowToMobile
     */
    public function resizeToMobile()
    {
        $resolution = $this->getResolutionByDevice('iphone 6');
        $this->resizeWindow($resolution['width'], $resolution['height']);
    }

    /**
     * We need to purge the spool between each scenario
     *
     * @AfterScenario @resizeWindowToMobile
     */
    public function resetToDesktop()
    {
        $resolution = $this->getResolutionByDevice("default");
        $this->resizeWindow($resolution['width'], $resolution['height']);
    }

    /**
     * @BeforeScenario
     */
    public function unlinkMockTime()
    {
        if ($this->fileSystem->exists($this->getMockTimeFilePath())) {
            unlink($this->getMockTimeFilePath());
        }

        try {
            // in recette mock time not needed
            if (extension_loaded('uopz')) {
                ClockMock::reset();
                uopz_unset_mock(DateTime::class);
                uopz_unset_mock(Date::class);
            }

        } catch (\Exception) {
        }

    }

    /**
     * @BeforeScenario
     */
    public function sessionClear()
    {
        $session = $this->getSession();

        if ($session->isStarted()) {
            $session->restart();
        }
    }

    /**
     * We need to purge the spool between each scenario
     *
     * @BeforeScenario @clearNsysEmails
     */
    public function clearNsysEmail()
    {
        $this->purgeFakeEmails('nsys');
    }

    /**
     * We need to purge the spool between each scenario
     *
     * @BeforeScenario @clearSignatureEmails
     */
    public function clearSignatureEmails()
    {
        $this->purgeFakeEmails('signature-platform');
    }

    /**
     * We need to purge the spool between each scenario
     *
     * @BeforeScenario @clearEmails
     */
    public function clearEmail()
    {
        $this->purgeFakeEmails();
    }

    /**
     * Take screen-shot when step fails
     * Works only with Selenium2Driver.
     *
     * @AfterStep
     */
    public function takeScreenShotAfterFailedStep(AfterStepScope $scope)
    {
        if (TestResult::FAILED === $scope->getTestResult()->getResultCode()) {
            $this->takeScreenshot(errorMessage: $scope->getTestResult()->getException()->getMessage());
        }
    }

    /**
     * Allow debugging of behat tests by PAUSING on failure.
     * Add DEBUG env define above to use it.
     * @AfterStep
     *
     * @param AfterStepScope $scope
     */
    public function wait_to_debug_in_browser_on_step_error(AfterStepScope $scope)
    {
        if (getenv('DEBUG')) {
            if ($scope->getTestResult()->getResultCode() == TestResult::FAILED) {
                // \x07 = BEL character, so the TTY makes a sound to let you know a failure happened.
                fwrite(STDOUT, PHP_EOL . "\x07PAUSING ON FAILURE - Press any key to continue" . PHP_EOL);
                fflush(STDOUT);
                $anything = fread(STDIN, 1);
            }
        }
    }

    /**
     * Step for debugging
     * Dump the first lines of the document.
     *
     * Example: And I dump the first 5 lines
     * Example: And dump first 5 lines
     *
     * @Then /^(?:|I )dump (?:|the )first (?P<count>[0-9]+) lines$/
     */
    public function dumpFirstLines($count)
    {
        $lines = explode("\n", $this->getSession()->getPage()->getContent());
        print(implode("\n", array_slice($lines, 0, $count)));
    }

    /**
     * Step for debugging
     * Dump the first lines of the body element (skip the head element).
     *
     * Example: And I dump the first 5 body lines
     * Example: And dump first 5 body lines
     * Example: And dump 5 body lines
     *
     * @Then /^(?:|I )dump (?:|the )(?:first )?(?P<count>[0-9]+) body lines$/
     */
    public function dumpFirstBodyLines($count)
    {
        $content = $this->getSession()->getPage()->getContent();
        $body_start = stripos($content, '<body>');
        $body_start = $body_start ? $body_start + 6 : 0;
        $body_end = stripos($content, '</body>');
        $body_end = $body_end ? $body_end : strlen($content);
        $body = substr($content, $body_start, $body_end);
        $lines = explode("\n", $body);
        print(implode("\n", array_slice($lines, 0, $count)));
    }

    /**
     * Step for debugging
     * Dump the current URL, HTTP status code and HTTP response headers.
     *
     * Example: And I dump the http headers
     * Example: And dump http headers
     *
     * @Then /^(?:|I )dump (?:|the )http headers$/
     */
    public function dumpHeaders()
    {
        $dump[] = 'Url: ' . $this->getSession()->getCurrentUrl();
        $dump[] = 'Status Code: ' . $this->getSession()->getStatusCode();
        foreach ($this->getSession()->getResponseHeaders() as $name => $values) {
            $dump[] = $name . ': ' . implode("\n", $values);
        }
        print(implode("\n", $dump));
    }

    /**
     * Example: And I drop the session
     *
     * @Then I drop the session
     */
    public function iDropTheSession()
    {
        $this->getMink()->restartSessions();
    }

    /**
     * @Given I clear the cookie :name
     */
    public function iClearTheCookie($name)
    {
        $this->getMink()->getSession()->setCookie($name, null);
    }

    /**
     * @Then I should not have the cookie :cookieName
     */
    public function iShouldNotHaveTheCookie($cookieName)
    {
        Assert::assertNull($this->getMink()->getSession()->getCookie($cookieName));
    }

    /**
     * @Then I should have the cookie :cookieName
     */
    public function iShouldHaveTheCookie($cookieName)
    {
        Assert::assertNotNull($this->getMink()->getSession()->getCookie($cookieName));
    }

    /**
     * @Then I should have the cookie :cookieName with :cookieValue
     */
    public function iShouldHaveTheCookieWith($cookieName, $cookieValue)
    {
        Assert::assertEquals($cookieValue, $this->getMink()->getSession()->getCookie($cookieName));
    }

    /**
     * @Then the absolute url should match :url
     * @Then the absolute url should :exactly match :url
     */
    public function theAbsoluteUrlShouldMatch(string $exactly = null, string $url = null)
    {
        $currentUrl = $this->getSession()->getCurrentUrl();

        if ($exactly) {
            Assert::assertEquals(trim($url, '/'), trim($currentUrl, '/'));
        } else {
            try {
                Assert::assertStringContainsString($url, $this->getSession()->getCurrentUrl(),
                    'url not match. Expected: ' . $url . ' actual: ' . $this->getSession()->getCurrentUrl()
                );
            } catch (\Exception|\Error $exception) {
                Assert::assertTrue(
                    (bool)preg_match('|' . $url . '|', $this->getSession()->getCurrentUrl()),
                    'url not match. Expected: ' . $url . ' actual: ' . $this->getSession()->getCurrentUrl()
                );
            }

        }
    }

    /**
     * Example: I switch to the opened tab
     *
     * @Then I switch to the opened tab
     */
    public function iSwitchToTheOpenedTab()
    {
        $session = $this->getSession();
        $driver = $this->getSession()->getDriver();
        if ($driver instanceof Selenium2Driver) {
            $windowNames = $session->getWindowNames();
            $nbWindows = count($windowNames);
            $this->spin(function () use ($nbWindows) {
                return $nbWindows > 1;
            });
            $session->switchToWindow($windowNames[$nbWindows - 1]);
        }
    }

    /**
     *
     * @Then I switch to the main tab
     */
    public function iSwitchToTheMainTab()
    {
        $session = $this->getSession();
        $windowNames = $this->getSession()->getWindowNames();
        $driver = $this->getSession()->getDriver();
        if ($driver instanceof Selenium2Driver) {
            $session->switchToWindow($windowNames[0]);
        }
    }

    /**
     * Needed for the animation to load
     *
     * @When I click on tab :textLink
     */
    public function iClickOnAdminTab(string $textLink)
    {
        $selectorsHandler = $this->getSession()->getSelectorsHandler();
        $page = $this->getPage();
        $linkEl = $page->find(
            'named',
            array(
                'link',
                $selectorsHandler->selectorToXpath('xpath', $textLink)
            )
        );
        $linkEl->click();
        $id = $linkEl->getAttribute('href');
        $this->spin(function ($session) use ($id) {
            return $session->getPage()->find('css', $id . ' .box-body')->isVisible();
        });
    }

    /**
     * @When I switch to the in page tab :tabTitle
     */
    public function iSwitchToTheInPageTab($tabTitle)
    {
        /** @var NodeElement[] $nodes */
        $nodes = $this->getPage()->findAll('css', '[data-toggle="tab"]');
        foreach ($nodes as $node) {
            if (false !== strpos($node->getText(), $tabTitle)) {
                $node->click();

                // Wait for animation.
                $this->spin(function () use ($node) {
                    $tabId = substr($node->getAttribute('href'), 1);
                    return $this->getPage()->findById($tabId)->isVisible();
                }, 5);

                return;
            }
        }
        throw new \Exception("Failed to find in page tab " . $tabTitle . ".");
    }

    /**
     * @When I take screenshot
     */
    public function iTakeScreenshot()
    {
        $this->takeScreenshot();
    }

    /**
     * @When I close the modal
     */
    public function iCloseTheModal()
    {
        $page = $this->getPage();
        $modal = $page->find('css', '.modal_open');
        $closeBtn = $modal->find('css', 'button[aria-label="Fermer"]');
        $this->spin(function () use ($closeBtn) {
            return $closeBtn->isVisible();
        }, 5);
        $closeBtn->click();
        $this->spin(function () use ($page) {
            return !($page->find('css', '.modal_open'));
        }, 5);
    }

    /**
     * @Then I should see a :extension document
     */
    public function iShouldSeeADocument($extension)
    {
        $url = $this->getSession()->getCurrentUrl();
        $path = parse_url($url, PHP_URL_PATH);
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        Assert::assertEquals($extension, $ext);
    }

    /**
     * @param string $shouldSee
     * @param string $message
     *
     * Example: I should not see a error message
     * Example: I should see a error message with "Done."
     *
     * @Then /^I should (?<shouldSee>|not )see a error message(| with "(?<message>[^"]+)")$/
     */
    public function iShouldNotSeeAErrorMessage($shouldSee = '', $message = null)
    {
        $node = $this->getPage()->find('css', '.alert-danger');
        if (empty($shouldSee)) {
            $this->assertIsNode($node);
            if ($message) {
                Assert::assertStringContainsString($message, $node->getText());
            }
        } else {
            Assert::assertNull($node);
        }
    }

    /**
     * @param string $shouldSee
     * @param string $message
     * Example: I should see a success message
     * Example: I should not see a success message
     * Example: I should see a success message with "Done."
     *
     * @Then /^I should (?<shouldSee>|not )see a success message(| with "(?<message>[^"]+)")$/
     */
    public function iShouldSeeASuccessMessage($shouldSee = '', $message = null)
    {
        $node = $this->getPage()->find('css', '.alert-success');
        if (empty($shouldSee)) {
            $this->assertIsNode($node);
            if ($message) {
                Assert::assertStringContainsString($message, $node->getText());
            }
        } else {
            Assert::assertNull($node);
        }
    }

    /**
     * @When I wait for :delay seconds
     * @When I wait for :delay second
     */
    public function iWaitForXSeconds($delay)
    {
        sleep((int)$delay);
    }

    /**
     * @When I click on :label
     */
    public function iClickOn($label)
    {
        $this->spin(function () use ($label) {
            try {
                $this->getPage()->clickLink($label);
            } catch (\Exception) {
                try {
                    $this->getPage()->pressButton($label);
                } catch (\Exception) {
                    try {
                        $button = $this->getPage()->find('css', 'button[aria-label="' . $label . '"]') ?? throw new \Exception('Button not found');

                        $button->click();
                    } catch (\Exception) {
                        $this->iClickOnText($label);
                    }
                }
            }

            return true;
        }, 5, 'element with text ' . $label . ' not found');
    }

    /**
     * @Then I should approx. see :text
     */
    public function iShouldSeeWhileIgnoringWhitespace($text)
    {
        Assert::assertStringContainsString($this->collapseConsecutiveWhitespace($text), $this->collapseConsecutiveWhitespace($this->getPage()->getText()));
    }

    /**
     * @Then I scroll to the page top
     */
    public function iScrollToThePageTop()
    {
        $this->getSession()->executeScript("window.scrollTo(0, 0)");
    }

    /**
     * @Then I scroll to the page bottom
     */
    public function iScrollToThePageBottom()
    {
        $this->getSession()->executeScript("window.scrollTo(0, document.body.scrollHeight);");
    }

    /**
     * @Given /^I set browser window size to "([^"]*)" x "([^"]*)"$/
     */
    public function iSetBrowserWindowSizeToX($width, $height)
    {
        $this->getSession()->resizeWindow((int)$width, (int)$height, 'current');
    }

    /**
     *
     * @Then /^The "(?<label>[^"]+)" button should (?<shouldBe>|not )be disabled$/
     */
    public function buttonShouldBeOrNotBeDisabled($label, $shouldBe = '')
    {
        $button = $this->getPage()->findButton($label);
        $disabled = $button->getAttribute('disabled') ? true : false;
        Assert::assertEquals($disabled, empty($shouldBe));
    }

    /**
     * @Given the current time is :currentTime
     */
    public function theCurrentTimeIs($currentTime)
    {
        if ($this->fileSystem->exists($this->getMockTimeFilePath())) {
            unlink($this->getMockTimeFilePath());
        }
        ClockMock::freeze(new DateTime($currentTime));
        uopz_set_mock(DateTime::class, DateTimeMock::class);
        uopz_set_mock(Date::class, DateMock::class);
        $this->fileSystem->appendToFile($this->getMockTimeFilePath(), $currentTime);
    }

    /**
     * @Given /^The text "(?<text>.*?)" should (?<shouldNotSee>|not )be displayed$/
     * @param $text
     * @param $shouldNotSee
     * @throws Exception
     */
    public function theTextShouldBeDisplayed($text, $shouldNotSee)
    {
        $shouldNotSee = !($shouldNotSee === '');
        $this->spin(function () use ($text, $shouldNotSee) {
            $text = $this->formatStringDateInString($text);

            $textInPage = $this->getPage()->getText();
            // remove special chars
            $textInPage = str_replace(' ', ' ', $textInPage);
            // str to lower
            $textInPage = mb_strtolower($textInPage);
            $pos = strpos($textInPage, strtolower($text));
            return ($shouldNotSee ? $pos === false : $pos !== false) || ($this->getPage()->hasContent($text) !== $shouldNotSee);
        }, 10, $this->getPage()->getText());
    }

    /**
     * @Then I should see following data displayed in this order:
     * @param TableNode $table
     * @throws Exception
     */
    public function iShouldSeeFollowingDataDisplayedInThisOrder(TableNode $table)
    {
        $this->assertFollowingDataDisplayed($table);
    }

    /**
     * @Then I should see following data displayed:
     * @param TableNode $table
     * @throws Exception
     */
    public function iShouldSeeFollowingDataDisplayed(TableNode $table)
    {
        $this->assertFollowingDataDisplayed($table, false);
    }

    /**
     * @Then the following :selectName options should be displayed:
     * @param $selectName
     * @param TableNode $tableNodes
     * @throws Exception
     */
    public function theFollowingOptionsShouldBeDisplayed($selectName, TableNode $tableNodes)
    {
        $this->spin(function () use ($selectName) {
            return $this->getPage()->find('css', 'select[name="' . $selectName . '"]');
        }, 30, "Select " . $selectName . " not found");

        $select = $this->getPage()->find('css', 'select[name="' . $selectName . '"]');

        $this->spin(function () use ($tableNodes, $select) {
            $options = $select->findAll('css', 'option');

            return count($options) > 1 || (count($options) === 1 && ($options[0]->getValue()));
        }, 30, "Data for select " . $selectName . " not loaded.");

        $options = $select->findAll('css', 'option');

        if (!$options[0]->getValue()) {
            unset($options[0]);
            $options = array_values($options);
        }

        foreach ($tableNodes as $key => $optionNode) {

            $label = $optionNode['label'];

            if (isset($optionNode['selected']) && $optionNode['selected'] === "true") {
                Assert::assertTrue(strpos($options[$key]->getOuterHtml(), 'selected') !== false);
            }

            $optionText = $options[$key]->getText();
            // remove special chars
            $optionText = str_replace(' ', ' ', $optionText);

            Assert::assertEquals($label, $optionText);
        }
    }

    /**
     * @Then the following :selectName select2 options should be displayed:
     * @param $selectName
     * @param TableNode $tableNodes
     * @throws Exception
     */
    public function theFollowingSelect2OptionsShouldBeDisplayed($selectName, TableNode $tableNodes)
    {
        $this->spin(function () use ($selectName) {
            return $this->getSelect2($selectName);
        }, 30, "Select " . $selectName . " not found");

        $selectWrapper = $this->getSelect2($selectName);

        $this->spin(function () use ($tableNodes, $selectWrapper) {
            $selectWrapper->click();
            $options = $selectWrapper->findAll('css', '.form__select2__option');

            return count($options) > 1 || (count($options) === 1 && ($options[0]->getText()));
        }, 30, "Data for select " . $selectName . " not loaded.");

        $options = $selectWrapper->findAll('css', '.form__select2__option');

        if (!$options[0]->getText()) {
            unset($options[0]);
            $options = array_values($options);
        }

        foreach ($tableNodes as $key => $optionNode) {
            $label = $optionNode['label'];

            if (isset($optionNode['selected']) && $optionNode['selected'] === "true") {
                Assert::assertTrue($options[$key]->hasClass('form__select2__option--is-selected'));
            }

            $optionText = $options[$key]->getText();
            // remove special chars
            $optionText = str_replace(' ', ' ', $optionText);

            Assert::assertEquals($label, $optionText);
        }

        //close select2
        $selectWrapper->click();
    }

    /**
     * @Then I search and select :option from :selectName
     * @throws Exception
     * @throws Exception
     */
    public function iSelectFromSelect(string $option, string $selectName)
    {
        $this->spin(function () use ($selectName, $option) {

            try {
                $selectWrapper = $this->getSelect2($selectName);

                if (!$selectWrapper) {

                    return false;
                }
                $isMenuOpen = count($selectWrapper->findAll('css', '.form__select2__option'));

                if (!$isMenuOpen) {
                    $selectWrapper->click();
                }

                $optionNode = $selectWrapper->find('xpath', '//div[contains(translate(text(),"ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"),"' . strtolower($option) . '")]');

                if (!$optionNode) {
                    return false;
                }

                $optionNode->click();

                return !is_null($selectWrapper->find('css', 'input[name="' . $selectName . '"]')->getValue());
            } catch (\Exception) {
                return false;
            }

        }, 30, "Cannot click on option " . $option . " on select " . $selectName);
    }

    protected function getSelect2($selectName): ?NodeElement
    {
        return $this->getPage()->find('css', '.form__select2#' . $selectName);
    }

    /**
     * @Then the fields should have following values:
     * @throws Exception
     */
    public function theFieldsShouldHaveFollowingValues(TableNode $table): void
    {
        foreach ($table as $item) {

            $label = $this->getPage()->find('xpath', sprintf('//label[text()="%s"]', $item['label']));

            if (null === $label) {
                throw new \Exception(sprintf('No label was found with the text: %s', $item['label']));
            }

            $labelFor = $label->getAttribute('for');
            if (null === $labelFor) {
                throw new \Exception(sprintf('No associated input found for label with text: %s', $item['label']));
            }

            $field = $this->getPage()->findById($labelFor);
            if (null === $field) {
                throw new \Exception(sprintf('No input field found with id: %s', $labelFor));
            }

            $actualType = $field->getTagName();
            $fieldType = ('input' === $actualType) ? $field->getAttribute('type') : $actualType;

            switch ($fieldType) {
                case 'textarea':
                    Assert::assertEquals($item['value'], $field->getText());
                    break;
                case 'checkbox':
                    Assert::assertEquals($item['value'] === "true", $field->isChecked());
                    break;
                default:
                    Assert::assertEquals($item['value'], $field->getAttribute('value'));
            }

        }
    }

    /**
     * @When I click on text :text
     * @throws Exception
     */
    public function iClickOnTextStep($text, $parent = null)
    {
        $this->iClickOnText($text, $parent);
    }

    /**
     * @When I hover on text :text
     * @param $text
     * @param null $parent
     * @throws Exception
     */
    public function iHoverOnTextStep($text, $parent = null)
    {
        $this->iHoverOnText($text, $parent);
    }

    /**
     * @Given the following nsys fixtures exist:
     * @throws Exception
     */
    public function loadNsysDataExists(TableNode $tableNode): void
    {
        $fixturesFilePath = $this->nsysPath . '/var/fixtures/fixtures'.$this->testToken.'.json';
        if ($this->fileSystem->exists($fixturesFilePath)) {
            $this->fileSystem->remove($fixturesFilePath);
        }
        $this->fileSystem->appendToFile($fixturesFilePath, json_encode(['data' => $tableNode->getRows()]));
        $this->executeCommand('TEST_TOKEN='.$this->testToken.' php ' . $this->nsysPath . '/bin/console nsys:behat:create_data --env=' . $this->currentEnv);
    }

    /**
     * @Then I should receive an :projectName email on :email with subject :subject
     * @Then I should receive an email on :email with subject :subject
     * @throws Exception
     */
    public function iShouldReceiveAnEmailWithSubjectOn($projectName = null, $email = null, $subject = null): void
    {
        $emailData = null;
        $this->spin(function (CommonContext $context) use ($projectName, $email, $subject, &$emailData) {
            return ($emailData = $this->getEmailData($email, $subject, $projectName)) !== null;
        }, 5);

        Assert::assertNotNull($emailData, 'Could not find fake email for email ' . $email . ' on project ' . $projectName);
        $this->currentEmailData = $emailData;
    }

    /**
     * @Then the email subject should be :subject
     *
     * @throws Exception
     */
    public function theEmailSubjectShouldBe(string $subject)
    {
        Assert::assertEquals($subject, $this->currentEmailData['subject']);
    }

    /**
     * @Then the email text should contains :text
     *
     * @throws Exception
     */
    public function theEmailTextShouldContains(string $expectedText)
    {
        Assert::assertStringContainsString($expectedText, $this->currentEmailData['body']);
    }

    /**
     * @Then the email sender should be :sender
     *
     * @throws Exception
     */
    public function theEmailSenderShouldBe(string $sender): void
    {
        Assert::assertEquals($sender, $this->currentEmailData['from']);
    }

    /**
     * @Then the text :text should not be part of the HTML
     *
     * @throws Exception
     */
    public function theTextShouldNotBePartOfTheHTML(string $text): void
    {
        Assert::assertFalse(str_contains($this->getPage()->getHtml(), $text), "Text \"$text\" was found in the page HTML");
    }

    /**
     * @Then the text :text should be part of the HTML
     *
     * @throws Exception
     */
    public function theTextShouldBePartOfTheHTML(string $text): void
    {
        Assert::assertTrue(str_contains($this->getPage()->getHtml(), $text), "Text \"$text\" was not found in the page HTML");
    }

    /**
     * @When I click on the link :linkText in the email
     * @param $linkText
     * @param $projectName
     * @throws Exception
     */
    public function iClickOnTheLinkInTheEmail(string $linkText)
    {
        $body = $this->currentEmailData['body'];

        $crawler = new Crawler($body);

        $description = $crawler->filterXPath('//a[text()="' . $linkText . '"]');
        Assert::assertEquals($linkText, $description->text());

        $link = $description->attr('href');
        Assert::assertEquals($linkText, $description->text());

        if ($this->getSession()->getDriver() instanceof Selenium2Driver) {
            $this->getSession()->visit($link);
        } else {
            $this->visitPath($link);
        }
    }

    /**
     * @When I give a rating of ":mark" with the comment ":comment"
     */
    public function iGiveARatingOfWithTheComment($mark, $comment)
    {
        $page = $this->getPage();
        $stars = $page->findAll('css', '.stars__star');

        /**
         * @var int $key
         * @var NodeElement $star
         */
        foreach ($stars as $key => $star) {

            if ($key + 1 === (int)$mark) {
                $star->press();
                break;
            }
        }

        $this->spin(function (CommonContext $context) {
            return $context->getPage()->find('css', '#comment') !== null && $context->getPage()->find('css', '#comment')->isVisible() === true;
        });

        $page->fillField('comment', $comment);
    }

    /**
     * Fills in form field with specified id|name|label|value
     * Example: When I fill in "username" with: "bwayne"
     * Example: And I fill in "bwayne" for "username"
     *
     * @When /^(?:|I )fill in "(?P<field>(?:[^"]|\\")*)" date with "(?P<value>(?:[^"]|\\")*)"$/
     * @When /^(?:|I )fill in "(?P<field>(?:[^"]|\\")*)" date with:$/
     * @When /^(?:|I )fill in "(?P<value>(?:[^"]|\\")*)" for date "(?P<field>(?:[^"]|\\")*)"$/
     * @throws ElementNotFoundException
     */
    public function fillDateField($field, $value)
    {
        $date = $this->convertMatchedDateToFormattedDate($value);
        $this->getSession()->getPage()->fillField($field, ($this->dateTime($date))->format('d-m-Y'));

        // Selenium bug: sometimes fill field fails
        // re fill field
        try {
            $this->dateTime($this->getSession()->getPage()->findField($field)->getValue());
        } catch (\Exception) {
            $this->getSession()->getPage()->fillField($field, ($this->dateTime($date))->format('d-m-Y'));
        }
    }

    /**
     * Fills in form field with specified id|name|label|value
     *
     * @When /^(?:|I )fill in "(?P<field>(?:[^"]|\\")*)" datetime with "(?P<value>(?:[^"]|\\")*)"$/
     * @When /^(?:|I )fill in "(?P<field>(?:[^"]|\\")*)" datetime with:$/
     * @When /^(?:|I )fill in "(?P<value>(?:[^"]|\\")*)" for datetime "(?P<field>(?:[^"]|\\")*)"$/
     * @throws Exception
     */
    public function fillDateTimeField($field, $value)
    {
        $date = $this->convertMatchedDateToFormattedDate($value);
        $this->getSession()->getPage()->fillField($field, ($this->dateTime($date))->format("d-m-Y H:i"));
    }

    /**
     * @Then I fill in :field timepicker with :value
     */
    public function iFillInTimepickerWith($field, $value)
    {
        $field = $this->getPage()->find('named', ['field', $field]);
        $container = $field->getParent();
        $field = $container->find('css', '.js-timepicker-handler');

        $this->getPage()->fillField($field->getAttribute('id'), $value);
        $this->getPage()->fillField($field->getAttribute('id'), $value);
    }

    /**
     * @Then the Google Analytics script should be called anonymously with :UAId
     */
    public function theGoogleAnalyticsScriptShouldBeCalledAnonymouslyWith($UAId)
    {
        Assert::assertNotNull($this->getPage()->find('css', '#ua-anon-' . $UAId));
    }

    /**
     * @Then the Google Tag Manager script should be called with :GTMId
     */
    public function theGoogleTagManagerScriptShouldBeCalledWith($GTMId)
    {
        Assert::assertNotNull($this->getPage()->find('css', '#gtm-' . $GTMId));
    }

    /**
     * @Then there should be a chat :script
     */
    public function thereShouldBeAChat($script)
    {
        /** @var NodeElement[] $allScriptsDOM */
        $allScriptsDOM = $this->getPage()->findAll('css', 'script');
        foreach ($allScriptsDOM as $scriptDOM) {
            if ($scriptDOM->getOuterHtml() == $script) {
                return;
            }
        }
        throw new \Exception("Script '" . $script . "' not found in page.");
    }

    /**
     * @Given I switch to the iframe
     */
    public function iSwitchToTheIframe(): void
    {
        $this->getSession()->getDriver()->getWebDriverSession()->frame(array('id' => 0));;
    }

    /**
     * Maps application route name to path
     */
    public function pathFromRouteName(string $route, array $args = []): string
    {
        $path = $this->router->generate($route, $args);

        // Hack because selenium includes host in returned path
        $driver = $this->getSession()->getDriver();
        if ($driver instanceof Selenium2Driver) {
            if (preg_match('/^\/\/[^\/]+(.*)/', $path, $match)) {
                $path = $match[1];
            }
        }

        return $path;
    }


    /**
     * @throws Exception
     */
    protected function assertFollowingDataDisplayed(TableNode $table, bool $ordered = true)
    {
        $rows = $table->getRows();

        $result = call_user_func_array('array_merge', $rows);
        $values = array_filter(array_values($result), 'strlen');

        $formattedValues = [];

        foreach ($values as $value) {

            $value = $this->formatStringDateInString($value);
            $value = mb_strtolower(trim($value));

            if (!$this->stringHasMultipleValues($value) && !empty(trim($value))) {
                $value = mb_strtolower(trim($value));
                $formattedValues[] = trim($value);
            } else {
                $formattedValues = $this->extractMultipleValuesFromString($formattedValues, $value);
            }
        }

        if ($ordered) {
            $this->checkValuesAreDisplayedWithRightOrder($formattedValues);
        } else {
            $this->checkValuesAreDisplayed($formattedValues);
        }
    }

    /**
     * Save a screenshot of the current window to the file system. Works only with Selenium2Driver.
     *
     * @param string|null $filename
     * @throws DriverException
     * @throws UnsupportedDriverActionException
     * @throws TransportExceptionInterface
     */
    public function takeScreenshot(string $filename = null, ?string $errorMessage = null): void
    {
        $driver = $this->getSession()->getDriver();
        if (!($driver instanceof Selenium2Driver)) {
            print "Failed to take screenshot, requires selenium driver...\n";
            return;
        }

        $fileSystem = new Filesystem();

        $screenshotDir = $this->getScreenshotDir();
        if (!$fileSystem->exists($screenshotDir)) {
            $fileSystem->mkdir($screenshotDir);
            print "Folder: " . $screenshotDir . " created \n";
        }

        $filename = $filename ?: sprintf('screenshot_%s.%s', date('d-m-Y_His'), 'jpg');
        $path = $screenshotDir . '/' . $filename;
        print "Screenshot saved to: " . $path . "\n";
        file_put_contents($path, $this->getSession()->getDriver()->getScreenshot());

        if ($this->isCI === 'true') {
            $this->opsAlertManager->sendSlackNotification([
                'file' => $path,
                'filetype' => 'jpg',
                'threadTs' => $this->slackNotificationTs,
                'encodeParameters' => false,
                'initialComment' => $errorMessage,
                'channel' => 'ci'
            ]);
        }
    }

    /**
     * Helper for parsing Gherkin 'active' variable
     * Returns true if 'active' was written.
     * Returns false if 'inactive' was written.
     * Returns null if neither 'active' nor 'inactive' was written.
     *
     * @param string $activeArg
     * @return bool|null
     */
    public function parseIsActive($activeArg)
    {
        $active = null;
        if ($activeArg == 'active ') {
            $active = true;
        } elseif ($activeArg == 'inactive ') {
            $active = false;
        }
        return $active;
    }

    public function parseBool($stringArg)
    {
        $active = null;
        if ($stringArg == 'true') {
            $active = true;
        } elseif ($stringArg == 'false') {
            $active = false;
        }
        return $active;
    }

    /**
     * Assert HTTP 200 response if supported by mink driver
     */
    public function assertHttpOk()
    {
        $driver = $this->getSession()->getDriver();
        if ($driver instanceof Selenium2Driver) {
            return;
        }
        Assert::assertEquals(200, $this->getSession()->getStatusCode());
    }

    /**
     * Assert HTTP 404 response if supported by mink driver
     */
    public function assertHttpNotFound()
    {
        $driver = $this->getSession()->getDriver();
        if ($driver instanceof Selenium2Driver) {
            return;
        }
        Assert::assertEquals(404, $this->getSession()->getStatusCode());
    }

    public function assertIsNode($node, $message = '')
    {
        Assert::assertNotNull($node);
        Assert::assertInstanceOf('\Behat\Mink\Element\NodeElement', $node, $message);
    }

    /**
     * Spin function
     * Repeatedly executes a callback until it returns true or timeout is attained.
     *
     * @param callable $lambda
     * @param int $wait
     * @param string|null $message
     * @return bool
     * @throws Exception
     */
    public function spin(callable $lambda, int $wait = 60, string $message = null): bool
    {
        $wait = $wait * 2;
        for ($i = 0; $i < $wait; $i++) {
            try {
                if ($lambda($this)) {
                    return true;
                }
            } catch (Exception $e) {
                // do nothing
            }

            usleep(0.5 * 1000000);
        }

        $backtrace = debug_backtrace();
        throw new Exception($message ?: "Timeout thrown by " . $backtrace[1]['class'] . "::" . $backtrace[1]['function'] . "()\n");
    }

    public function visitPath($path, $sessionName = null)
    {
        parent::visitPath($path, $sessionName);
        try {
            // If alert popup is opened -> closed

            if ($this->getSession()->getDriver() instanceof Selenium2Driver) {
                /** @var Session $webDriverSession */
                $webDriverSession = $this->getSession()->getDriver()->getWebDriverSession();
                $webDriverSession->accept_alert();
            }

        } catch (NoAlertOpenError $e) {
        }
    }

    public function assertSame($arg1, $arg2, $arg3)
    {
        Assert::assertSame($arg1, $arg2, $arg3);
    }

    /**
     * @param string $baseName
     * @param bool|null $useAssetsInWebContainer
     * @return string Full file name
     */
    public function getUploadFile(string $baseName, ?bool $useAssetsInWebContainer = null): string
    {
        if (!$this->getSession()->getDriver() instanceof Selenium2Driver || $useAssetsInWebContainer) {

            return $this->getMinkParameter('files_path') . '/' . $baseName;
        } else {
            return '/home/seluser/features/assets/' . $baseName;
        }
    }

    public function collapseConsecutiveWhitespace($text): array|string|null
    {
        return preg_replace('/\s+/', ' ', $text);
    }

    public function cacheEntity($cacheKey, $entityKey, $entity)
    {
        if (null === $entities = $this->getState($cacheKey)) {
            $this->setState($cacheKey, []);
        }
        $entities[$entityKey] = $entity;
        $this->setState($cacheKey, $entities);
    }

    #[ArrayShape(['width' => "int", 'height' => "int"])]
    public function getResolutionByDevice($device): array
    {
        return match ($device) {
            'iphone 6' => ['width' => 375, 'height' => 667],
            default => ['width' => 1920, 'height' => 1080],
        };
    }

    public function resizeWindow($width, $height)
    {
        if ($this->getSession()->getDriver() instanceof Selenium2Driver) {
            $this->getSession()->resizeWindow((int)$width, (int)$height, 'current');
        }
    }

    public function getAsset($asset): string
    {
        return $this->parameterBag->get('kernel.project_dir') . '/tests/assets/' . $asset;
    }

    /**
     * @param string $path
     * @param null $parent
     * @return NodeElement|mixed|null
     */
    protected function getLinkByPath($path, $parent = null): mixed
    {
        $parent = $parent ?: $this->getPage();
        return $parent->find('css', 'a[href*="' . $path . '"]');
    }

    /**
     * @throws Exception
     */
    protected function waitLinkToBeVisible(string $resource, $linkParams = [], $parent = null)
    {
        $this->spin(function () use ($resource, $linkParams, $parent) {
            return $this->getLinkByPath($this->pathFromPageName($resource, $linkParams), $parent) && $this->getLinkByPath($this->pathFromPageName($resource, $linkParams), $parent)->isVisible();
        }, 30);

        return $this->getLinkByPath($this->pathFromPageName($resource, $linkParams), $parent);
    }

    protected function waitForElements($elements)
    {
        $test = $this->spin(function () use ($elements) {
            return count($this->getPage()->findAll('css', $elements));
        }, 30);

        return $this->getPage()->findAll('css', $elements);
    }

    protected function waitForElement($element)
    {
        return $this->waitForElements($element)[0];
    }

    /**
     * Returns the closest parent element having a specific class attribute.
     *
     * @param NodeElement $el
     * @param String $class
     * @return NodeElement|null
     */
    protected function findParentByClass(NodeElement $el, $class)
    {
        $container = $el->getParent();
        while ($container && $container->getTagName() != 'body') {
            if ($container->isVisible() && in_array($class, explode(' ', $container->getAttribute('class')))) {
                return $container;
            }
            $container = $container->getParent();
        }
        return null;
    }

    /**
     * @param $className
     * @param $index
     * @param $parent
     * @return NodeElement|mixed|null
     */
    protected function getChildAtIndex($className, $index, $parent)
    {
        $finder = $parent ?: $this->getPage();
        return $finder->find('css', $className . ':nth-child(' . $index . ')');
    }

    /**
     * @param $htmlElement
     * @param $text
     * @param null $parent
     * @return NodeElement|mixed|null
     */
    protected function getElementByText($htmlElement, $text, $parent = null)
    {
        $finder = $parent ? $parent : $this->getPage();
        return $finder->find('xpath', '//' . $htmlElement . '[contains(translate(text(),"ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"),"' . strtolower($text) . '")]');
    }

    /**
     * @throws Exception
     */
    public function iClickOnText($text, $parent = null): void
    {
        $element = null;

        $this->spin(function () use (&$element, $text, $parent) {
            $parent = $parent ?: $this->getPage();
            return $element = $parent->find('xpath', '//*[text()[contains(.,"' . $text . '")]]');
        }, 30, "Text " . $text . " not found : " . $this->getPage()->getText());

        try {
            $element->click();
        } catch (Exception) {
            $element->getParent()->click();
        }
    }

    /**
     * @throws Exception
     */
    public function iHoverOnText($text, $parent = null)
    {
        $element = null;

        $this->spin(function () use (&$element, $text, $parent) {
            $parent = $parent ? $parent : $this->getPage();
            $element = $parent->find('xpath', '//*[text()[contains(.,"' . $text . '")]]');
            return $element;
        }, 30);

        try {
            $element->mouseOver();
        } catch (Exception $exception) {
            $element->getParent()->mouseOver();
        }
    }

    /**
     * @param $string
     * @return string|null
     */
    private function extractJsonFromStringWithDate($string): ?string
    {
        preg_match('/\{.*"format":.*\}/', $string, $jsonMatch);
        return count($jsonMatch) > 0 ? $jsonMatch[0] : null;
    }

    /**
     * @param $string
     * @return string|string[]
     * @throws Exception
     */
    protected function formatStringDateInString($string)
    {
        $formattedString = $string;

        if ($jsonToMatch = $this->extractJsonFromStringWithDate($string)) {
            $formattedString = str_replace($jsonToMatch, $this->convertJsonDateTimeToFormattedDate($jsonToMatch), $string);
        } elseif ($extractedStringDate = $this->extractStringDateFromString($string)) {
            $formattedDate = $this->convertMatchedDateToFormattedDate($extractedStringDate);
            $formattedString = str_replace($extractedStringDate, $this->convertDateTimeToString($formattedDate), $string);
        }

        return $formattedString;
    }

    /**
     * @param string $string
     * @return bool
     */
    private function stringHasMultipleValues(string $string): bool
    {
        return strpos($string, ';') !== false;
    }

    /**
     * @param array $formattedValues
     * @param string $string
     * @return array
     */
    private function extractMultipleValuesFromString(array $formattedValues, string $string)
    {
        $explodedValues = explode(';', $string);

        foreach ($explodedValues as $value) {
            $value = trim($value);
            $formattedValues[] = $value;
        }

        return $formattedValues;
    }

    /**
     * @param $values
     * @throws Exception
     */
    private function checkValuesAreDisplayedWithRightOrder($values)
    {
        $regexValues = array_map(function ($value) {
            return '(' . preg_quote($value, '/') . ')';
        }, $values);

        $regexValues = implode('.*', $regexValues);

        try {
            $this->spin(function () use ($regexValues) {
                $text = $this->getPage()->getText();
                // remove special chars
                $text = str_replace(' ', ' ', $text);
                // remove all double spaces
                $text = preg_replace('/\s+/', ' ', $text);
                // str to lower
                $text = mb_strtolower($text);
                return preg_match('/' . $regexValues . '/', $text, $match);
            }, 30);
        } catch (Exception) {
            $html = mb_strtolower($this->getPage()->getText());
            $notFound = array_filter(array_map(function ($value) use ($html) {
                if (strpos($html, $value) === false) {
                    return $value;
                }
            }, $values));

            if (!count($notFound)) {
                $html = mb_strtolower($this->getPage()->getText());
                foreach ($values as $value) {
                    $position = strpos($html, $value);

                    if ($position === false) {
                        throw new Exception($value . ' not in the right place');
                    } else {
                        $html = substr($html, $position + strlen($value));
                    }
                }
            }

            throw new Exception('Following values are not found: "' . join(',', $notFound) . '"');
        }
    }

    /**
     * @param $values
     * @throws Exception
     */
    private function checkValuesAreDisplayed(array $values)
    {
        $regexValues = array_map(function ($value) {
            return '(' . preg_quote($value, '/') . ')';
        }, $values);

        $notFound = [];
        foreach ($regexValues as $key => $regexValue) {

            try {
                $this->spin(function () use ($regexValue) {
                    $textInPage = $this->getPage()->getText();
                    // remove special chars
                    $textInPage = str_replace(' ', ' ', $textInPage);
                    // str to lower
                    $textInPage = mb_strtolower($textInPage);

                    return (preg_match('/' . $regexValue . '/', $textInPage)) || (preg_match('/' . $regexValue . '/', $textInPage));
                }, 30);
            } catch (Exception $exception) {
                $notFound[] = $values[$key];
            }
        }

        if (count($notFound)) {
            throw new Exception('Following values are not found: "' . join(',', $notFound) . '"');
        }
    }

    /**
     * @param null $projectName
     * @return Swift_Message|null
     */
    protected function getEmailData(string $recipientEmail, string $subject = null, ?string $projectName = null): ?array
    {
        $fakeEmailFilePath = $this->getFakeEmailsDir($projectName) . FakeEmailProvider::getFileNameForEmailAndSubject($recipientEmail, $subject);
        if ($this->fileSystem->exists($fakeEmailFilePath)) {
            $fakeEmailData = unserialize(file_get_contents($fakeEmailFilePath));
        } else {
            $fakeEmailData = null;
        }

        return $fakeEmailData;
    }


    /**
     * @param string|null $projectName
     */
    public function purgeFakeEmails($projectName = null)
    {
        $filesystem = new Filesystem();
        $folder = $this->getFakeEmailsDir($projectName);

        if ($filesystem->exists($folder)) {
            $filesystem->remove($folder);
        }

    }


    /**
     * @param null $projectName
     * @return Finder
     */
    protected function getSpooledEmails($projectName = null)
    {
        $finder = new Finder();
        $spoolDir = $this->getSpoolDir(trim($projectName));
        $finder->files()->in($spoolDir);

        return $finder;
    }

    /**
     * @param $file
     *
     * @return string
     */
    protected function getEmailContent($file)
    {
        return unserialize(file_get_contents($file));
    }

    /**
     * @return string
     */
    protected function getFakeEmailsDir(?string $projectName = null)
    {
        return $projectName ? '/var/www/' . trim($projectName) . '/var/fake-emails/' . $this->testToken . '/' : $this->parameterBag->get('kernel.project_dir') . '/var/fake-emails/' . $this->testToken . '/';
    }


    public function getKernel(): KernelInterface
    {
        return $this->kernel;
    }

    public function getPage(): DocumentElement
    {
        return $this->getSession()->getPage();
    }

    public function getFactory(): FactoryInterface
    {
        return $this->factory;
    }

    public function getCleaner(): CleanerInterface
    {
        return $this->cleaner;
    }

    /**
     * Project root directory
     */
    public function getRootDir(): string
    {
        return $this->parameterBag->get('kernel.project_dir');
    }

    /**
     * @return string Directory in which screenshots are to be saved
     */
    public function getScreenshotDir(): string
    {
        return $this->screenshotDir;
    }

    /**
     * Get state information shared between contexts
     */
    public function getState(string $key): mixed
    {
        return $this->contextState->getState($key);
    }

    /**
     * Set state information shared context between
     */
    public function setState(string $key, mixed $value)
    {
        $this->contextState->setState($key, $value);
    }


    /**
     * @When I drop :fileName on the drop zone
     * @param $fileName
     */
    public function iDropOnTheDropZone($fileName)
    {
        $inputDOM = $this->getPage()->find('css', 'input[type="file"]');
        $file = $this->getUploadFile($fileName);
        $inputDOM->attachFile($file);
    }

    private function getMockTimeFilePath(): string
    {
        return $this->getAsset('mockTime' . $this->testToken . '.txt');
    }
}
