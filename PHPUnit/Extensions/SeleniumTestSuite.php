<?php
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPUnit\Extensions;

use File_Iterator_Facade;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Util\Test as TestUtil;
use ReflectionClass;
use ReflectionMethod;

/**
 * TestSuite class for Selenium 1 tests
 */
class SeleniumTestSuite extends TestSuite
{
    /**
     * Overriding the default: Selenium suites are always built from a TestCase class.
     *
     * @var bool
     */
    protected $testCase = true;

    /**
     * Making the method public.
     *
     * @param ReflectionClass  $class
     * @param ReflectionMethod $method
     */
    public function addTestMethod(ReflectionClass $class, ReflectionMethod $method): void
    {
        parent::addTestMethod($class, $method);
    }

    /**
     * @param string $className extending PHPUnit_Extensions_SeleniumTestCase
     *
     * @return SeleniumTestSuite
     */
    public static function fromTestCaseClass($className)
    {
        $suite = new self();
        $suite->setName($className);

        $class            = new ReflectionClass($className);
        $classGroups      = TestUtil::getGroups($className);
        $staticProperties = $class->getStaticProperties();
        if (isset($staticProperties['browsers'])) {
            $browsers = $staticProperties['browsers'];
        } elseif (is_callable(sprintf('%s::browsers', $className))) {
            $browsers = $className::browsers();
        } else {
            $browsers = null;
        }

        //BC: renamed seleneseDirectory -> selenesePath
        if (! isset($staticProperties['selenesePath']) && isset($staticProperties['seleneseDirectory'])) {
            $staticProperties['selenesePath'] = $staticProperties['seleneseDirectory'];
        }

        // Create tests from Selenese/HTML files.
        if (isset($staticProperties['selenesePath']) &&
            (is_dir($staticProperties['selenesePath']) || is_file($staticProperties['selenesePath']))) {
            if (is_dir($staticProperties['selenesePath'])) {
                $files = array_merge(
                    self::getSeleneseFiles($staticProperties['selenesePath'], '.htm'),
                    self::getSeleneseFiles($staticProperties['selenesePath'], '.html')
                );
            } else {
                $files[] = realpath($staticProperties['selenesePath']);
            }

            // Create tests from Selenese/HTML files for multiple browsers.
            if ($browsers) {
                foreach ($browsers as $browser) {
                    $browserSuite = SeleniumBrowserSuite::fromClassAndBrowser($className, $browser);

                    foreach ($files as $file) {
                        self::addGeneratedTestTo(
                            $browserSuite,
                            new $className($file, [], '', $browser),
                            $classGroups
                        );
                    }

                    $suite->addTest($browserSuite);
                }
            } else {
                // Create tests from Selenese/HTML files for single browser.
                foreach ($files as $file) {
                    self::addGeneratedTestTo(
                        $suite,
                        new $className($file),
                        $classGroups
                    );
                }
            }
        }

        // Create tests from test methods for multiple browsers.
        if ($browsers) {
            foreach ($browsers as $browser) {
                $browserSuite = SeleniumBrowserSuite::fromClassAndBrowser($className, $browser);
                foreach ($class->getMethods() as $method) {
                    $browserSuite->addTestMethod($class, $method);
                }

                $browserSuite->setupSpecificBrowser($browser);

                $suite->addTest($browserSuite);
            }
        } else {
            // Create tests from test methods for single browser.
            foreach ($class->getMethods() as $method) {
                $suite->addTestMethod($class, $method);
            }
        }

        return $suite;
    }

    private static function addGeneratedTestTo(TestSuite $suite, \PHPUnit\Framework\TestCase $test, $classGroups)
    {
        [$methodName, ] = explode(' ', $test->getName());
        $test->setDependencies(
            TestUtil::getDependencies(get_class($test), $methodName)
        );
        $suite->addTest($test, $classGroups);
    }

    /**
     * @param  string $directory
     * @param  string $suffix
     *
     * @return array
     */
    private static function getSeleneseFiles($directory, $suffix)
    {
        $facade = new File_Iterator_Facade();

        return $facade->getFilesAsArray($directory, $suffix);
    }
}
