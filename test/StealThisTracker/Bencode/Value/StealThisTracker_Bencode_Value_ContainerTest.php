<?php

/**
 * Test class for StealThisTracker_Bencode_Value_Container.
 */
class StealThisTracker_Bencode_Value_ContainerTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var StealThisTracker_Bencode_Value_Container
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        // Don't call constructor.
        $this->object = $this->getMockForAbstractClass( 'StealThisTracker_Bencode_Value_Container', array(), '', false );
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {

    }

    /**
     * Running testcase testRepresent().
     */
    public function testConstructAssociative()
    {
        $test_array = array(
            'key1' => new StealThisTracker_Bencode_Value_Integer( 1 ),
            'key2' => new StealThisTracker_Bencode_Value_Integer( 2 ),
        );

        $this->object->expects( $this->at( 0 ) )
            ->method( 'contain' )
            ->with(
                $this->equalTo( $test_array['key1'] ),
                $this->isInstanceOf( 'StealThisTracker_Bencode_Value_String' )
            );
        $this->object->expects( $this->at( 1 ) )
            ->method( 'contain' )
            ->with(
                $this->equalTo( $test_array['key2'] ),
                $this->isInstanceOf( 'StealThisTracker_Bencode_Value_String' )
            );

        $this->object->__construct( $test_array );
    }

    /**
     * Running testcase testRepresent().
     */
    public function testConstructList()
    {
        $test_array = array(
            new StealThisTracker_Bencode_Value_Integer( 3 ),
            new StealThisTracker_Bencode_Value_Integer( 4 ),
        );

        $this->object->expects( $this->at( 0 ) )
            ->method( 'contain' )
            ->with(
                $this->equalTo( $test_array[0] )
            );
        $this->object->expects( $this->at( 1 ) )
            ->method( 'contain' )
            ->with(
                $this->equalTo( $test_array[1] )
             );

        $this->object->__construct( $test_array );
    }

}
