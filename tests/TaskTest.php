<?php
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase {
    
    // Every test function MUST start with the word 'test'
    public function testBasicMathLogic() {
        // A simple test to prove Jenkins catches errors
        $expected = 4;
        $actual = 2 + 2;
        
        $this->assertEquals($expected, $actual, "Math is broken!");
    }

    public function testTaskArrayStructure() {
        // Arrange: Create a fake task array
        $task = [
            'id' => 1,
            'title' => 'Learn CI/CD',
            'status' => 'pending'
        ];

        // Assert: Verify the array has the keys we expect
        $this->assertArrayHasKey('title', $task);
        $this->assertEquals('pending', $task['status']);
    }
}