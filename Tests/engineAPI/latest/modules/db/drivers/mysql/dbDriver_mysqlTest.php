<?php

require_once __DIR__.'/../../mockPDO.php';

class dbDriver_mysqlTest extends PHPUnit_Extensions_Database_TestCase{
    /**
     * @var PDO
     */
    static $pdo;
    /**
     * @var dbDriver
     */
    static $db;

    private function createPDO(){
        return new PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
    }

    static function setUpBeforeClass(){
        $options  = array(
            'dsn'    => $GLOBALS['DB_DSN'],
            'user'   => $GLOBALS['DB_USER'],
            'pass'   => $GLOBALS['DB_PASSWD'],
            'dbname' => $GLOBALS['DB_DBNAME'],
        );
        self::$db = db::create('mysql', $options);
    }

    public function getConnection(){
        self::$pdo = $this->createPDO();

        return $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
    }

    public function getDataSet(){
        self::$pdo->exec(file_get_contents(__DIR__.'/../../testData/drivers/mysql/dbObjectTesting.sql'));

        $dataSet = new PHPUnit_Extensions_Database_DataSet_QueryDataSet($this->getConnection());
        $dataSet->addTable('dbObjectTesting');

        return $dataSet;
    }

    // =================================================================================================================

    # Tests for __construct()
    #########################################
    function testItUsesAPassedPDOObject(){
        $pdo = $this->createPDO();
        $db  = db::create('mysql', $pdo);
        $this->assertEquals($pdo, $db->getPDO());
    }

    # Tests for query()
    #########################################
    function test_queryAutoExecutesWithNoParams(){
        $stmt = self::$db->query('SELECT 1');
        $this->assertInstanceOf('dbStatement', $stmt, 'dbDriver::query() returns a dbStatement object');
        $this->assertTrue($stmt->isExecuted(), 'dbDriver::query() auto executes when given no params');
    }
    function test_queryDosentExecuteWhenPassedFALSE(){
        $stmt = self::$db->query('SELECT 1', FALSE);
        $this->assertInstanceOf('dbStatement', $stmt, 'dbDriver::query() returns a dbStatement object');
        $this->assertFalse($stmt->isExecuted(), "dbDriver::query() doesn't execute when given FALSE as params");
    }
    function test_queryTakesArrayOfParams(){
        $stmt = self::$db->query('SELECT 1 WHERE 1=?', array(1));
        $this->assertInstanceOf('dbStatement', $stmt, 'dbDriver::query() returns a dbStatement object');
        $this->assertTrue($stmt->isExecuted(), "dbDriver::query() executes when given an array of params");
    }

    # Tests for escape()
    #########################################
    function test_escape(){
        $this->assertEquals("'\'foo\''", self::$db->escape("'foo'")); // single-quotes
        $this->assertEquals("'\\\"foo\\\"'", self::$db->escape('"foo"')); // double-quotes
        $this->assertEquals("'\\n'", self::$db->escape("\n")); // new-line
        $this->assertEquals("'\\r'", self::$db->escape("\r")); // carriage-return
        $this->assertEquals("'\\0'", self::$db->escape("\x00")); // null-character
        $this->assertEquals("'\\Z'", self::$db->escape("\x1a")); // substitute-character
    }

    # Tests for inTransaction()
    #########################################
    private function getTransLevel(dbDriver $db){
        if(preg_match('/In transaction: Yes \(depth: (\d+)\)/', (string)$db, $m)){
            return (int)$m[1];
        }else{
            return 0;
        }
    }
    function test_objectDoesNotStartInTransactionMode(){
        $db = db::create('mysql', $this->createPDO());
        $this->assertFalse($db->inTransaction());
    }
    function test_inTransactionReturnsTrueWhenConnectionIsInTransactionState(){
        $db = db::create('mysql', $this->createPDO());
        $db->beginTransaction();
        $this->assertTrue($db->inTransaction());
        $db->commit();
        $this->assertFalse($db->inTransaction());
    }

    # Tests for beginTransaction()
    #########################################
    function test_dbObjectStartsWithTransLevelOfZero(){
        $db  = db::create('mysql', $this->createPDO());
        $this->assertEquals(0, $this->getTransLevel($db));
        $db = NULL;
    }
    function test_beginTransactionIncrementsTansLevel(){
        $db = db::create('mysql', $this->createPDO());
        $n  = $this->getTransLevel($db);

        $db->beginTransaction();
        $this->assertEquals(++$n, $this->getTransLevel($db));

        $db->beginTransaction();
        $this->assertEquals(++$n, $this->getTransLevel($db));

        $db->beginTransaction();
        $this->assertEquals(++$n, $this->getTransLevel($db));

        $db = NULL;
    }
    function test_beginTransactionCallsPdoBeginTransactionOnlyWhenTransLevelGoesFromZeroToOne(){
        // Only allow beginTransaction() to be called once on the underlying PDO
        $pdo = $this->getMock('mockPDO');
        $pdo->expects($this->once())->method('beginTransaction');

        // Run test
        $db = db::create('mysql', $pdo);
        $db->beginTransaction(); // Call beginTransaction() on PDO
        $db->beginTransaction(); // Do not call beginTransaction() on PDO
        $db->beginTransaction(); // Do not call beginTransaction() on PDO

        // Cleanup
        $pdo = NULL;
        $db  = NULL;
    }

    # Tests for commit()
    #########################################
    function test_commitDecrementsTansLevel(){
        $db = db::create('mysql', $this->createPDO());
        $db->beginTransaction();
        $db->beginTransaction();
        $db->beginTransaction();
        $n  = $this->getTransLevel($db);

        $db->commit();
        $this->assertEquals(--$n, $this->getTransLevel($db));

        $db->commit();
        $this->assertEquals(--$n, $this->getTransLevel($db));

        $db->commit();
        $this->assertEquals(--$n, $this->getTransLevel($db));

        $db = NULL;
    }
    function test_commitDecrementsTansLevelAndWontGoNegative(){
        $db = db::create('mysql', $this->createPDO());
        $this->assertEquals(0, $this->getTransLevel($db));
        $db->commit();
        $this->assertEquals(0, $this->getTransLevel($db));
        $db = NULL;
    }
    function test_commitCallsCommitOnlyWhenTransLevelGoesFromOneToZero(){
        // Setup the mock
        $pdo = $this->getMock('mockPDO');
        $pdo->expects($this->any())->method('inTransaction')->will($this->onConsecutiveCalls(TRUE, TRUE, TRUE, TRUE, FALSE));
        $pdo->expects($this->any())->method('beginTransaction')->will($this->returnValue(TRUE));
        $pdo->expects($this->once())->method('commit');

        // Test logic
        $db = db::create('mysql', $pdo);
        $db->beginTransaction(); // transLevel 0->1
        $db->beginTransaction(); // transLevel 1->2
        $db->commit();           // transLevel 2->1 (Do not call commit() on PDO)
        $db->commit();           // transLevel 1->0 (Call commit() on PDO)

        // Cleanup
        $pdo = NULL;
        $db  = NULL;
    }

    # Tests for rollback()
    #########################################
    function test_rollbackDecrementsTansLevel(){
        $db = db::create('mysql', $this->createPDO());
        $db->beginTransaction();
        $db->beginTransaction();
        $db->beginTransaction();
        $n  = $this->getTransLevel($db);

        $db->rollback();
        $this->assertEquals(--$n, $this->getTransLevel($db));

        $db->rollback();
        $this->assertEquals(--$n, $this->getTransLevel($db));

        $db->rollback();
        $this->assertEquals(--$n, $this->getTransLevel($db));

        $db = NULL;
    }
    function test_rollbackDecrementsTansLevelAndWontGoNegative(){
        $db = db::create('mysql', $this->createPDO());
        $this->assertEquals(0, $this->getTransLevel($db));
        $db->rollback();
        $this->assertEquals(0, $this->getTransLevel($db));
        $db = NULL;
    }
    function test_rollbackCallsCommitOnlyWhenTransLevelGoesFromOneToZero(){
        // Setup the mock
        $pdo = $this->getMock('mockPDO');
        $pdo->expects($this->any())->method('inTransaction')->will($this->onConsecutiveCalls(TRUE, TRUE, TRUE, TRUE, FALSE));
        $pdo->expects($this->any())->method('beginTransaction')->will($this->returnValue(TRUE));
        $pdo->expects($this->once())->method('rollback');

        // Test logic
        $db = db::create('mysql', $pdo);
        $db->beginTransaction(); // transLevel 0->1
        $db->beginTransaction(); // transLevel 1->2
        $db->rollback();         // transLevel 2->1 (Do not call rollback() on PDO)
        $db->rollback();         // transLevel 1->0 (Call rollback() on PDO)

        // Cleanup
        $pdo = NULL;
        $db  = NULL;
    }

    # Test Rollback-only mode
    #########################################
    function test_commitOnlyCallsRollbackWhenInRollbackOnlyMode(){
        // Setup the mock
        $pdo = $this->getMock('mockPDO');
        $pdo->expects($this->any())->method('inTransaction')->will($this->onConsecutiveCalls(TRUE, TRUE, TRUE, TRUE, FALSE));
        $pdo->expects($this->any())->method('beginTransaction')->will($this->returnValue(TRUE));
        $pdo->expects($this->never())->method('commit');
        $pdo->expects($this->once())->method('rollback');

        // Test logic
        $db = db::create('mysql', $pdo);
        $db->beginTransaction(); // transLevel 0->1
        $db->beginTransaction(); // transLevel 1->2
        $db->rollback();         // transLevel 2->1 (Place into rollback-only mode)
        $db->commit();           // transLevel 1->0 (Call rollback() on PDO)

        // Cleanup
        $pdo = NULL;
        $db  = NULL;
    }

    # Tests for readOnly()
    #########################################
    function test_objectDoesNotStartInReadOnlyMode(){
        $db = db::create('mysql', $this->createPDO());
        $this->assertRegExp('/Read-only Mode: No/', (string)$db);
        $db = NULL;
    }
    function test_objectCanBePlacedIntoReadOnlyMode(){
        $db = db::create('mysql', $this->createPDO());
        $db->readOnly();
        $this->assertRegExp('/Read-only Mode: Yes/', (string)$db);
        $db = NULL;
    }
    function test_objectCanBePlacedBackIntoReadWriteMode(){
        $db = db::create('mysql', $this->createPDO());
        $db->readOnly();
        $this->assertRegExp('/Read-only Mode: Yes/', (string)$db);
        $db->readOnly(FALSE);
        $this->assertRegExp('/Read-only Mode: No/', (string)$db);
        $db = NULL;
    }
    function test_queryDoesNotChecksSqlWhenInReadOnlyMode(){
        $db = db::create('mysql', $this->createPDO());
        $this->assertTrue(FALSE !== $db->query('DROP `thisDoesNotExistAndShouldNeverExistInTheDatabase`'));
    }
    function test_queryChecksSqlWhenInReadOnlyMode(){
        $db = db::create('mysql', $this->createPDO());
        $db->readOnly();
        $this->assertTrue(FALSE === $db->query('DROP `thisDoesNotExistAndShouldNeverExistInTheDatabase`'));

    }
}