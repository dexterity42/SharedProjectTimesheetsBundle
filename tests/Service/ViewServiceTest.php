<?php
/**
 * This file is part of the SharedProjectTimesheetsBundle for Kimai 2.
 * All rights reserved by Fabian Vetter (https://vettersolutions.de).
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\SharedProjectTimesheetsBundle\tests\Service;


use App\Entity\Project;
use App\Repository\TimesheetRepository;
use KimaiPlugin\SharedProjectTimesheetsBundle\Entity\SharedProjectTimesheet;
use KimaiPlugin\SharedProjectTimesheetsBundle\Service\ViewService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class ViewServiceTest extends TestCase
{

    /**
     * @var ViewService
     */
    private $service;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var PasswordEncoderInterface
     */
    private $encoder;

    /**
     * @var string
     */
    private $sessionKey;

    protected function setUp(): void
    {
        $timesheetRepository = $this->createMock(TimesheetRepository::class);
        $this->session = $this->createPartialMock(SessionInterface::class, []);
        $this->encoder = $this->createMock(PasswordEncoderInterface::class);

        $this->service = new ViewService($timesheetRepository, $this->session, $this->encoder);
    }

    private function createSharedProjectTimesheet()
    {
        $project = $this->createMock(Project::class);
        $project->method('getId')
            ->willReturn(1);

        return (new SharedProjectTimesheet())
            ->setProject($project)
            ->setShareKey("sharekey");
    }

    public function testNoPassword()
    {
        $sharedProjectTimesheet = $this->createSharedProjectTimesheet();
        $hasAccess = $this->service->hasAccess($sharedProjectTimesheet, null);
        self::assertTrue($hasAccess);
    }

    public function testValidPassword()
    {
        $this->encoder->method('isPasswordValid')
            ->willReturnCallback(function($hashedPassword, $givenPassword) {
                return $hashedPassword === $givenPassword;
            });

        $sharedProjectTimesheet = ($this->createSharedProjectTimesheet())
            ->setPassword("password");

        $hasAccess = $this->service->hasAccess($sharedProjectTimesheet, "password");
        self::assertTrue($hasAccess);
    }

    public function testInvalidPassword()
    {
        $this->encoder->method('isPasswordValid')
            ->willReturnCallback(function($hashedPassword, $givenPassword) {
                return $hashedPassword === $givenPassword;
            });

        $sharedProjectTimesheet = ($this->createSharedProjectTimesheet())
            ->setPassword("password");

        $hasAccess = $this->service->hasAccess($sharedProjectTimesheet, "wrong");
        self::assertFalse($hasAccess);
    }

    public function testPasswordRemember()
    {
        // Mock session behaviour
        $this->session->expects($this->exactly(1))
            ->method('set')
            ->willReturnCallback(function($key) {
                $this->sessionKey = $key;
            });

        $this->session->expects($this->exactly(2))
            ->method('has')
            ->willReturnCallback(function($key) {
                return $key === $this->sessionKey;
            });

        // Expect the encoder->isPasswordValid method is called only once
        $this->encoder->expects($this->exactly(1))
            ->method('isPasswordValid')
            ->willReturnCallback(function($hashedPassword, $givenPassword) {
                return $hashedPassword === $givenPassword;
            });

        $sharedProjectTimesheet = ($this->createSharedProjectTimesheet())
            ->setPassword("test");

        $this->service->hasAccess($sharedProjectTimesheet, "test");
        $this->service->hasAccess($sharedProjectTimesheet, "test");
    }

    public function testPasswordChange()
    {
        // Mock session behaviour
        $this->session->expects($this->exactly(1))
            ->method('set')
            ->willReturnCallback(function($key) {
                $this->sessionKey = $key;
            });

        $this->session->expects($this->exactly(2))
            ->method('has')
            ->willReturnCallback(function($key) {
                return $key === $this->sessionKey;
            });

        // Expect the encoder->isPasswordValid method is called only once
        $this->encoder->expects($this->exactly(2))
            ->method('isPasswordValid')
            ->willReturnCallback(function($hashedPassword, $givenPassword) {
                return $hashedPassword === $givenPassword;
            });

        $sharedProjectTimesheet = ($this->createSharedProjectTimesheet())
            ->setPassword("test");

        $hasAccess = $this->service->hasAccess($sharedProjectTimesheet, "test");
        self::assertTrue($hasAccess);

        $sharedProjectTimesheet = ($this->createSharedProjectTimesheet())
            ->setPassword("changed");

        $hasAccess = $this->service->hasAccess($sharedProjectTimesheet, "test");
        self::assertFalse($hasAccess);
    }

}
