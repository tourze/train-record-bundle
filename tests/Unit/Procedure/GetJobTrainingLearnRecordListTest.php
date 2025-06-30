<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Procedure;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\TrainClassroomBundle\Entity\Registration;
use Tourze\TrainClassroomBundle\Repository\RegistrationRepository;
use Tourze\TrainRecordBundle\Procedure\GetJobTrainingLearnRecordList;

/**
 * GetJobTrainingLearnRecordList 测试
 */
class GetJobTrainingLearnRecordListTest extends TestCase
{
    private Security&MockObject $security;
    private RegistrationRepository&MockObject $registrationRepository;
    private GetJobTrainingLearnRecordList $procedure;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->registrationRepository = $this->createMock(RegistrationRepository::class);
        
        $this->procedure = new GetJobTrainingLearnRecordList(
            $this->security,
            $this->registrationRepository
        );
    }

    public function test_execute_returns_empty_list_when_no_registrations(): void
    {
        $user = $this->createMock(UserInterface::class);
        
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
            
        $this->registrationRepository->expects($this->once())
            ->method('findBy')
            ->with(['student' => $user])
            ->willReturn([]);

        $result = $this->procedure->execute();

        $this->assertArrayHasKey('list', $result);
        $this->assertEmpty($result['list']);
    }

    public function test_execute_returns_registration_list(): void
    {
        $user = $this->createMock(UserInterface::class);
        $registration = $this->createMock(Registration::class);
        
        $registrationData = [
            'id' => 1,
            'courseName' => '测试课程',
            'status' => 'active'
        ];
        
        $registration->expects($this->once())
            ->method('retrieveApiArray')
            ->willReturn($registrationData);
        
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
            
        $this->registrationRepository->expects($this->once())
            ->method('findBy')
            ->with(['student' => $user])
            ->willReturn([$registration]);

        $result = $this->procedure->execute();

        $this->assertArrayHasKey('list', $result);
        $this->assertCount(1, $result['list']);
        $this->assertEquals($registrationData, $result['list'][0]);
    }

    public function test_execute_handles_multiple_registrations(): void
    {
        $user = $this->createMock(UserInterface::class);
        $registration1 = $this->createMock(Registration::class);
        $registration2 = $this->createMock(Registration::class);
        
        $registrationData1 = ['id' => 1, 'courseName' => '课程1'];
        $registrationData2 = ['id' => 2, 'courseName' => '课程2'];
        
        $registration1->expects($this->once())
            ->method('retrieveApiArray')
            ->willReturn($registrationData1);
            
        $registration2->expects($this->once())
            ->method('retrieveApiArray')
            ->willReturn($registrationData2);
        
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
            
        $this->registrationRepository->expects($this->once())
            ->method('findBy')
            ->with(['student' => $user])
            ->willReturn([$registration1, $registration2]);

        $result = $this->procedure->execute();

        $this->assertArrayHasKey('list', $result);
        $this->assertCount(2, $result['list']);
        $this->assertEquals($registrationData1, $result['list'][0]);
        $this->assertEquals($registrationData2, $result['list'][1]);
    }
}