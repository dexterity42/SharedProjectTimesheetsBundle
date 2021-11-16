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
use KimaiPlugin\SharedProjectTimesheetsBundle\Entity\SharedProjectTimesheet;
use KimaiPlugin\SharedProjectTimesheetsBundle\Model\RecordMergeMode;
use KimaiPlugin\SharedProjectTimesheetsBundle\Repository\SharedProjectTimesheetRepository;
use KimaiPlugin\SharedProjectTimesheetsBundle\Service\ManageService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \KimaiPlugin\SharedProjectTimesheetsBundle\Service\ManageService
 */
class ManageServiceTest extends TestCase
{
    /**
     * @var ManageService
     */
    private $service;

    protected function setUp(): void
    {
        $repository = $this->createMock(SharedProjectTimesheetRepository::class);
        $repository->method('save')
            ->willReturnArgument(0);

        $this->service = new ManageService($repository);
    }

    public function testCreateSuccess(): void
    {
        $sharedProjectTimesheet = (new SharedProjectTimesheet())
            ->setProject(new Project())
            ->setRecordMergeMode(RecordMergeMode::MODE_MERGE)
            ->setEntryUserVisible(true)
            ->setEntryRateVisible(true)
            ->setAnnualChartVisible(true)
            ->setMonthlyChartVisible(true);

        $saved = $this->service->create($sharedProjectTimesheet);

        self::assertNotNull($saved->getShareKey());
        self::assertNotNull($saved->getProject());
        self::assertNull($saved->getPassword());
        self::assertEquals(RecordMergeMode::MODE_MERGE, $saved->getRecordMergeMode());
        self::assertTrue($saved->isEntryUserVisible());
        self::assertTrue($saved->isEntryRateVisible());
        self::assertTrue($saved->isAnnualChartVisible());
        self::assertTrue($saved->isMonthlyChartVisible());
    }
    public function testDefaultValues(): void
    {
        $sharedProjectTimesheet = new SharedProjectTimesheet();
        self::assertNull($sharedProjectTimesheet->getShareKey());
        self::assertNull($sharedProjectTimesheet->getProject());
        self::assertNull($sharedProjectTimesheet->getPassword());
        self::assertEquals(RecordMergeMode::MODE_NONE, $sharedProjectTimesheet->getRecordMergeMode());
        self::assertFalse($sharedProjectTimesheet->isEntryUserVisible());
        self::assertFalse($sharedProjectTimesheet->isEntryRateVisible());
        self::assertFalse($sharedProjectTimesheet->isAnnualChartVisible());
        self::assertFalse($sharedProjectTimesheet->isMonthlyChartVisible());
    }

    public function testCreatePassword(): void
    {
        $sharedProjectTimesheet = (new SharedProjectTimesheet())
            ->setProject(new Project());

        $saved = $this->service->create($sharedProjectTimesheet, "password");

        self::assertNotNull($saved->getPassword());
    }

    public function testCreateInvalidProject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->create(new SharedProjectTimesheet());
    }

    public function testUpdateSuccess(): void
    {
        $sharedProjectTimesheet = (new SharedProjectTimesheet())
            ->setShareKey("sharekey")
            ->setProject(new Project())
            ->setPassword("password")
            ->setRecordMergeMode(RecordMergeMode::MODE_MERGE)
            ->setEntryUserVisible(true)
            ->setEntryRateVisible(true)
            ->setAnnualChartVisible(true)
            ->setMonthlyChartVisible(true);

        $saved = $this->service->update($sharedProjectTimesheet, "newPassword");

        self::assertEquals("sharekey", $saved->getShareKey());
        self::assertNotNull($saved->getProject());
        self::assertNotEquals("newPassword", $saved->getPassword());
        self::assertEquals(RecordMergeMode::MODE_MERGE, $saved->getRecordMergeMode());
        self::assertTrue($saved->isEntryUserVisible());
        self::assertTrue($saved->isEntryRateVisible());
        self::assertTrue($saved->isAnnualChartVisible());
        self::assertTrue($saved->isMonthlyChartVisible());
    }

    public function testUpdatePasswordDoesNotChange(): void
    {
        $sharedProjectTimesheet = (new SharedProjectTimesheet())
            ->setShareKey("sharekey")
            ->setProject(new Project())
            ->setPassword("password");

        $saved = $this->service->update($sharedProjectTimesheet, ManageService::PASSWORD_DO_NOT_CHANGE_VALUE);

        self::assertEquals("sharekey", $saved->getShareKey());
        self::assertNotNull($saved->getProject());
        self::assertEquals("password", $saved->getPassword());
    }

    public function testUpdatePasswordReset(): void
    {
        $sharedProjectTimesheet = (new SharedProjectTimesheet())
            ->setShareKey("sharekey")
            ->setProject(new Project())
            ->setPassword("password");

        $saved = $this->service->update($sharedProjectTimesheet, null);

        self::assertEquals("sharekey", $saved->getShareKey());
        self::assertNotNull($saved->getProject());
        self::assertNull($saved->getPassword());
    }

    public function testUpdateInvalidProject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->update(new SharedProjectTimesheet());
    }

}