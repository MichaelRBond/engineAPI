<?php
require_once 'mockPDO.php';

class dbTest extends PHPUnit_Framework_TestCase {
    private $pdoStub;

    function setUp(){

    }

    function testItReturnsItselfViaGetInstance(){
        $db = db::getInstance();
        $this->assertInstanceOf('db', $db);
    }


    # Tests for __get()
    ###########################################################################################
    function testItUsesMagicGetToAllowEasyAccessToCreatedConnections(){
        $dbAlias = md5(__METHOD__);
        $mockPDO = $this->getMock('mockPDO');
        $db      = db::getInstance();

        @$this->assertNull($db->$dbAlias);
        $driver = $db->create('mysql', $mockPDO, $dbAlias);
        $this->assertNotNull($db->$dbAlias);
        $this->assertEquals($driver, $db->$dbAlias);
    }

    # Tests for uegisterAs()
    ###########################################################################################
    function testRegisterAs(){
        $dbAlias = md5(__METHOD__);
        $mockPDO = $this->getMock('mockPDO');
        $db      = db::getInstance();
        $driver  = $db->create('mysql', $mockPDO);

        @$this->assertNull($db->$dbAlias);
        $db->registerAs($driver, $dbAlias);
        $this->assertNotNull($db->$dbAlias);
        $this->assertEquals($driver, $db->$dbAlias);
    }
    function testRegisterAsWithNameConflict(){
        $dbAlias = md5(__METHOD__);
        $mockPDO = $this->getMock('mockPDO');
        $db      = db::getInstance();
        $driver  = $db->create('mysql', $mockPDO, $dbAlias);

        $this->assertNotNull($db->$dbAlias);
        $this->assertEquals($driver, $db->$dbAlias);
        $this->assertFalse($db->registerAs($driver, $dbAlias));
        $this->assertNotNull($db->$dbAlias);
        $this->assertEquals($driver, $db->$dbAlias);
    }

    # Tests for unregisterAlias()
    ###########################################################################################
    function testUnregisterAlias(){
        $dbAlias = md5(__METHOD__);
        $mockPDO = $this->getMock('mockPDO');
        $db      = db::getInstance();

        @$this->assertNull($db->$dbAlias);
        $driver = $db->create('mysql', $mockPDO, $dbAlias);
        $this->assertNotNull($db->$dbAlias);
        $this->assertEquals($driver, $db->$dbAlias);
        $db->unregisterAlias($dbAlias);
        @$this->assertNull($db->$dbAlias);
    }
    function testUnregisterAliasUndefinedAlias(){
        $dbAlias = md5(__METHOD__);
        $this->assertFalse(db::unregisterAlias($dbAlias));
    }

    # Tests for unregisterObject()
    ###########################################################################################
    function testUnregisterObject(){
        $dbAlias = md5(__METHOD__);
        $mockPDO = $this->getMock('mockPDO');
        $db      = db::getInstance();

        @$this->assertNull($db->$dbAlias);
        $driver = $db->create('mysql', $mockPDO, $dbAlias);
        $this->assertNotNull($db->$dbAlias);
        $this->assertEquals($driver, $db->$dbAlias);
        $db->unregisterObject($driver);
        @$this->assertNull($db->$dbAlias);
    }
    function testUnregisterObjectUsingUnregisteredObjecet(){
        $this->markTestIncomplete('Having issues resetting the db object');
        errorHandle::errorReporting(errorHandle::E_ALL);
        $dbAlias = md5(__METHOD__);
        $db      = db::getInstance();
        $mockPDO = $this->getMock('mockPDO');

        db::reset();
        $this->assertEquals(0, sizeof($db));
        @$this->assertNull($db->$dbAlias);
        $driver = $db->create('mysql', $mockPDO);
        @$this->assertNull($db->$dbAlias);
        $this->assertFalse($db->unregisterObject($driver));
        @$this->assertNull($db->$dbAlias);

        /*
         * $driver is being remembered from previous test runs...  need a way to reset it
         */
    }

    # Tests for listDrivers()
    ###########################################################################################
    function testListDrivers(){
        $this->markTestIncomplete('TODO');
        $ds = DIRECTORY_SEPARATOR;
        db::$driverDir = __DIR__.$ds.'testData'.$ds.'drivers';
        $drivers = db::listDrivers();
        $this->assertTrue(is_array($drivers));
        $this->assertTrue(1, sizeof($drivers));
        $this->assertContains('drivera', $drivers);
    }

}