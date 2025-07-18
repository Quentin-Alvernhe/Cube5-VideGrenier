<?php

use PHPUnit\Framework\TestCase;

class FirstTest extends TestCase
{
    /**
     * Test que PHPUnit fonctionne
     */
    public function testPHPUnitWorks()
    {
        $this->assertTrue(true);
        echo "✓ PHPUnit fonctionne correctement\n";
    }

    /**
     * Test que nos classes se chargent
     */
    public function testClassesLoad()
    {
        $this->assertTrue(class_exists('App\Utility\Hash'));
        echo "✓ La classe Hash se charge\n";
    }

    /**
     * Test basique de la fonction Hash
     */
    public function testHashFunction()
    {
        $hash1 = \App\Utility\Hash::generate('test', 'salt');
        $hash2 = \App\Utility\Hash::generate('test', 'salt');
        
        $this->assertEquals($hash1, $hash2);
        $this->assertNotEmpty($hash1);
        echo "✓ La fonction de hashage fonctionne\n";
    }
}