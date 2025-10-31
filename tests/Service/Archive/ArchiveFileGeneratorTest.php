<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Service\Archive;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Service\Archive\ArchiveFileGenerator;

/**
 * ArchiveFileGenerator 集成测试
 *
 * @internal
 */
#[CoversClass(ArchiveFileGenerator::class)]
#[RunTestsInSeparateProcesses]
final class ArchiveFileGeneratorTest extends AbstractIntegrationTestCase
{
    private ArchiveFileGenerator $fileGenerator;

    private string $tempDir;

    protected function onSetUp(): void
    {
        $this->fileGenerator = self::getService(ArchiveFileGenerator::class);
        $this->tempDir = sys_get_temp_dir() . '/archive_file_generator_test_' . uniqid();

        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0o755, true);
        }
    }

    public function testGenerateArchiveFileWithJsonFormat(): void
    {
        $userId = 'user-123';
        $courseId = 'course-456';
        $data = [
            'sessionSummary' => ['totalSessions' => 5],
            'behaviorSummary' => ['totalBehaviors' => 15],
            'anomalySummary' => ['totalAnomalies' => 2],
            'totalEffectiveTime' => 3600.0,
            'totalSessions' => 5,
        ];

        $filepath = $this->fileGenerator->generateArchiveFile($userId, $courseId, $data, 'json');

        $this->assertFileExists($filepath);
        $this->assertStringEndsWith('.json', $filepath);
        $this->assertStringContainsString($userId, basename($filepath));
        $this->assertStringContainsString($courseId, basename($filepath));

        $content = file_get_contents($filepath);
        $this->assertNotFalse($content);
        $decodedData = json_decode($content, true);

        $this->assertIsArray($decodedData);
        $this->assertEquals($data, $decodedData);
    }

    public function testGenerateArchiveFileWithXmlFormat(): void
    {
        $userId = 'user-123';
        $courseId = 'course-456';
        $data = [
            'sessionSummary' => ['totalSessions' => 5],
            'totalEffectiveTime' => 3600.0,
            'totalSessions' => 5,
        ];

        $filepath = $this->fileGenerator->generateArchiveFile($userId, $courseId, $data, 'xml');

        $this->assertFileExists($filepath);
        $this->assertStringEndsWith('.xml', $filepath);

        $content = file_get_contents($filepath);
        $this->assertNotFalse($content);
        $xml = simplexml_load_string($content);

        $this->assertNotFalse($xml);
        $this->assertEquals('5', (string) $xml->totalSessions);
        $this->assertEquals('3600', (string) $xml->totalEffectiveTime);
    }

    public function testGenerateArchiveFileWithPdfFormat(): void
    {
        $userId = 'user-123';
        $courseId = 'course-456';
        $data = [
            'sessionSummary' => ['totalSessions' => 5],
            'behaviorSummary' => ['totalBehaviors' => 15],
            'anomalySummary' => ['totalAnomalies' => 2],
        ];

        $filepath = $this->fileGenerator->generateArchiveFile($userId, $courseId, $data, 'pdf');

        $this->assertFileExists($filepath);
        $this->assertStringEndsWith('.pdf', $filepath);

        $content = file_get_contents($filepath);
        $this->assertNotFalse($content);
        $this->assertStringContainsString('学习档案报告', $content);
        $this->assertStringContainsString('会话汇总:', $content);
        $this->assertStringContainsString('行为汇总:', $content);
        $this->assertStringContainsString('异常汇总:', $content);
    }

    public function testGenerateArchiveFileWithCsvFormat(): void
    {
        $userId = 'user-123';
        $courseId = 'course-456';
        $data = [
            'sessionSummary' => ['totalSessions' => 5],
            'totalEffectiveTime' => 3600.0,
            'totalSessions' => 5,
        ];

        $filepath = $this->fileGenerator->generateArchiveFile($userId, $courseId, $data, 'csv');

        $this->assertFileExists($filepath);
        $this->assertStringEndsWith('.csv', $filepath);

        $content = file_get_contents($filepath);
        $this->assertNotFalse($content);
        $lines = explode("\n", trim($content));

        $this->assertCount(2, $lines); // header + data
        $this->assertStringContainsString('sessionSummary', $lines[0]);
        $this->assertStringContainsString('totalEffectiveTime', $lines[0]);
        $this->assertStringContainsString('totalSessions', $lines[0]);
    }

    public function testGenerateArchiveFileWithUnknownFormatDefaultsToJson(): void
    {
        $userId = 'user-123';
        $courseId = 'course-456';
        $data = ['test' => 'value'];

        $filepath = $this->fileGenerator->generateArchiveFile($userId, $courseId, $data, 'unknown');

        $this->assertFileExists($filepath);
        $this->assertStringEndsWith('.unknown', $filepath);

        $content = file_get_contents($filepath);
        $this->assertNotFalse($content);
        $decodedData = json_decode($content, true);

        $this->assertEquals($data, $decodedData);
    }

    public function testCalculateFileHashReturnsCorrectHash(): void
    {
        $testFile = $this->tempDir . '/test_hash.txt';
        $testContent = 'This is test content for hash calculation';
        file_put_contents($testFile, $testContent);

        $hash = $this->fileGenerator->calculateFileHash($testFile);

        $expectedHash = hash('sha256', $testContent);
        $this->assertEquals($expectedHash, $hash);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertNotNull($this->fileGenerator);
    }

    public function testParseXmlContentReturnsCorrectArray(): void
    {
        $xmlContent = '<?xml version="1.0"?>
<learnArchive>
    <totalSessions>5</totalSessions>
    <totalEffectiveTime>3600</totalEffectiveTime>
    <sessionSummary>
        <completionRate>80</completionRate>
    </sessionSummary>
</learnArchive>';

        $result = $this->fileGenerator->parseXmlContent($xmlContent);

        $this->assertIsArray($result);
        $this->assertEquals('5', $result['totalSessions']);
        $this->assertEquals('3600', $result['totalEffectiveTime']);
        $this->assertEquals(['completionRate' => '80'], $result['sessionSummary']);
    }

    public function testParseCsvContentReturnsCorrectArray(): void
    {
        $csvContent = "name,age,city\nJohn,25,New York\nJane,30,London";

        $result = $this->fileGenerator->parseCsvContent($csvContent);

        $this->assertCount(2, $result);
        $this->assertEquals(['name' => 'John', 'age' => '25', 'city' => 'New York'], $result[0]);
        $this->assertEquals(['name' => 'Jane', 'age' => '30', 'city' => 'London'], $result[1]);
    }

    public function testParseCsvContentWithEmptyContentReturnsEmptyArray(): void
    {
        $result = $this->fileGenerator->parseCsvContent('');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testParseCsvContentWithMismatchedColumnsSkipsRows(): void
    {
        $csvContent = "name,age,city\nJohn,25\nJane,30,London,Extra";

        $result = $this->fileGenerator->parseCsvContent($csvContent);

        $this->assertCount(0, $result); // Both rows should be skipped due to column count mismatch
    }

    public function testGenerateArchiveFileWithComplexNestedData(): void
    {
        $userId = 'user-complex';
        $courseId = 'course-complex';
        $data = [
            'sessionSummary' => [
                'totalSessions' => 10,
                'completionRate' => 85.5,
                'sessionDetails' => [
                    ['duration' => 1800, 'completed' => true],
                    ['duration' => 2400, 'completed' => false],
                ],
            ],
            'behaviorSummary' => [
                'totalBehaviors' => 45,
                'behaviorStats' => [
                    'play' => 25,
                    'pause' => 15,
                    'seek' => 5,
                ],
            ],
        ];

        $filepath = $this->fileGenerator->generateArchiveFile($userId, $courseId, $data, 'json');

        $content = file_get_contents($filepath);
        $this->assertNotFalse($content);
        $decodedData = json_decode($content, true);

        $this->assertEquals($data, $decodedData);
        $sessionSummary = is_array($decodedData) && isset($decodedData['sessionSummary']) ? $decodedData['sessionSummary'] : null;
        $this->assertIsArray($sessionSummary);
        $sessionDetails = $sessionSummary['sessionDetails'];
        $this->assertIsArray($sessionDetails);
        $this->assertCount(2, $sessionDetails);
    }

    public function testGenerateXmlContentWithNestedArrays(): void
    {
        $data = [
            'root' => [
                'child1' => 'value1',
                'child2' => [
                    'grandchild1' => 'value2',
                    'grandchild2' => 'value3',
                ],
            ],
            'simpleValue' => 'test',
        ];

        $filepath = $this->fileGenerator->generateArchiveFile('user', 'course', $data, 'xml');

        $content = file_get_contents($filepath);
        $this->assertNotFalse($content);
        $xml = simplexml_load_string($content);

        $this->assertNotFalse($xml);
        $this->assertEquals('value1', (string) $xml->root->child1);
        $this->assertEquals('value2', (string) $xml->root->child2->grandchild1);
        $this->assertEquals('value3', (string) $xml->root->child2->grandchild2);
        $this->assertEquals('test', (string) $xml->simpleValue);
    }
}
