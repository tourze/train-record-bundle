<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Procedure;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\TrainClassroomBundle\Entity\Registration;
use Tourze\TrainClassroomBundle\Repository\RegistrationRepository;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Procedure\GetJobTrainingLearnSessionDetail;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * GetJobTrainingLearnSessionDetail 测试
 */
class GetJobTrainingLearnSessionDetailTest extends TestCase
{
    private Security&MockObject $security;
    private RegistrationRepository&MockObject $registrationRepository;
    private LearnSessionRepository&MockObject $learnSessionRepository;
    private GetJobTrainingLearnSessionDetail $procedure;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->registrationRepository = $this->createMock(RegistrationRepository::class);
        $this->learnSessionRepository = $this->createMock(LearnSessionRepository::class);
        
        $this->procedure = new GetJobTrainingLearnSessionDetail(
            $this->security,
            $this->registrationRepository,
            $this->learnSessionRepository
        );
    }

    public function test_execute_returns_registration_with_course_and_sessions(): void
    {
        $user = $this->createMock(UserInterface::class);
        $registration = $this->createMock(Registration::class);
        $course = $this->createMock(Course::class);
        $session = $this->createMock(LearnSession::class);
        
        $registrationId = '12345';
        $this->procedure->registrationId = $registrationId;
        
        $registrationData = [
            'id' => $registrationId,
            'status' => 'active'
        ];
        
        $courseData = [
            'id' => 'course-1',
            'title' => '测试课程'
        ];
        
        $sessionData = [
            'id' => 'session-1',
            'duration' => 3600
        ];
        
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
            
        $this->registrationRepository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'student' => $user,
                'id' => $registrationId
            ])
            ->willReturn($registration);
            
        $registration->expects($this->once())
            ->method('retrieveApiArray')
            ->willReturn($registrationData);
            
        $registration->expects($this->once())
            ->method('getCourse')
            ->willReturn($course);
            
        $course->expects($this->once())
            ->method('retrieveApiArray')
            ->willReturn($courseData);
            
        $this->learnSessionRepository->expects($this->once())
            ->method('findBy')
            ->with(['registration' => $registration])
            ->willReturn([$session]);
            
        $session->expects($this->once())
            ->method('retrieveApiArray')
            ->willReturn($sessionData);

        $result = $this->procedure->execute();

        $this->assertEquals($registrationId, $result['id']);
        $this->assertEquals('active', $result['status']);
        $this->assertArrayHasKey('course', $result);
        $this->assertEquals($courseData, $result['course']);
        $this->assertArrayHasKey('sessions', $result);
        $this->assertCount(1, $result['sessions']);
        $this->assertEquals($sessionData, $result['sessions'][0]);
    }

    public function test_execute_returns_empty_sessions_when_no_sessions_found(): void
    {
        $user = $this->createMock(UserInterface::class);
        $registration = $this->createMock(Registration::class);
        $course = $this->createMock(Course::class);
        
        $registrationId = '12345';
        $this->procedure->registrationId = $registrationId;
        
        $registrationData = ['id' => $registrationId];
        $courseData = ['id' => 'course-1'];
        
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
            
        $this->registrationRepository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'student' => $user,
                'id' => $registrationId
            ])
            ->willReturn($registration);
            
        $registration->expects($this->once())
            ->method('retrieveApiArray')
            ->willReturn($registrationData);
            
        $registration->expects($this->once())
            ->method('getCourse')
            ->willReturn($course);
            
        $course->expects($this->once())
            ->method('retrieveApiArray')
            ->willReturn($courseData);
            
        $this->learnSessionRepository->expects($this->once())
            ->method('findBy')
            ->with(['registration' => $registration])
            ->willReturn([]);

        $result = $this->procedure->execute();

        $this->assertArrayHasKey('sessions', $result);
        $this->assertEmpty($result['sessions']);
    }

    public function test_execute_handles_multiple_sessions(): void
    {
        $user = $this->createMock(UserInterface::class);
        $registration = $this->createMock(Registration::class);
        $course = $this->createMock(Course::class);
        $session1 = $this->createMock(LearnSession::class);
        $session2 = $this->createMock(LearnSession::class);
        
        $registrationId = '12345';
        $this->procedure->registrationId = $registrationId;
        
        $registrationData = ['id' => $registrationId];
        $courseData = ['id' => 'course-1'];
        $sessionData1 = ['id' => 'session-1'];
        $sessionData2 = ['id' => 'session-2'];
        
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
            
        $this->registrationRepository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'student' => $user,
                'id' => $registrationId
            ])
            ->willReturn($registration);
            
        $registration->expects($this->once())
            ->method('retrieveApiArray')
            ->willReturn($registrationData);
            
        $registration->expects($this->once())
            ->method('getCourse')
            ->willReturn($course);
            
        $course->expects($this->once())
            ->method('retrieveApiArray')
            ->willReturn($courseData);
            
        $this->learnSessionRepository->expects($this->once())
            ->method('findBy')
            ->with(['registration' => $registration])
            ->willReturn([$session1, $session2]);
            
        $session1->expects($this->once())
            ->method('retrieveApiArray')
            ->willReturn($sessionData1);
            
        $session2->expects($this->once())
            ->method('retrieveApiArray')
            ->willReturn($sessionData2);

        $result = $this->procedure->execute();

        $this->assertArrayHasKey('sessions', $result);
        $this->assertCount(2, $result['sessions']);
        $this->assertEquals($sessionData1, $result['sessions'][0]);
        $this->assertEquals($sessionData2, $result['sessions'][1]);
    }
}