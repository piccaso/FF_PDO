<?php

require_once 'FF_PDO.class.php';

class FF_PDO_TESTS extends PHPUnit_Framework_TestCase {
    /**
     * @covers FF_PDO:q
     * @covers FF_PDO:qt
     */
    public function test_sqlite(){
        $db = new FF_PDO('sqlite::memory:',null,null,array(
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ));
        $this->assertTrue($db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION));
        $this->assertEquals(PDO::FETCH_ASSOC, $db->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));

        $this->assertTrue(0 === $db->exec("PRAGMA busy_timeout = 5000;"));
        $this->assertTrue(0 === $db->exec("PRAGMA synchronous = OFF"));
        $this->assertTrue(0 === $db->exec("PRAGMA journal_mode = MEMORY"));

        $this->assertEquals('its_working',$db->q("SELECT 'its_working'"));
        $this->once();

        try {
            $db->exec("syntax error");
        }catch (PDOException $e){
            $this->once();
        }

        $this->assertTrue(0 === $db->exec("CREATE TABLE test (a PRIMARY KEY,b,c,bin,binmd5)"));
        $this->assertTrue($db->beginTransaction());
        $iterations=100;
        for($i=1;$i<$iterations;++$i){
            $bin = openssl_random_pseudo_bytes(1024);
            $binmd5=md5($bin);

            $db->q("INSERT INTO test (a,bin) VALUES (:a,:bin)",array(':a'=>$i,'bin'=>$bin));
            $db->q("UPDATE test set b=?,c=? WHERE a=?",md5($i),sha1($i),$i);
            $db->q("UPDATE test set binmd5=? WHERE a=?",$binmd5,$i);
            $this->exactly($iterations);
        }
        $this->assertTrue($db->commit());
        foreach($db->qt("select * from test") as $row){
            $this->atLeastOnce();
            $this->assertTrue(md5($row['a']) === $row['b']);
            $this->assertTrue(sha1($row['a']) === $row['c']);
            $this->assertTrue(md5($row['bin']) === $row['binmd5']);
        }

        $this->assertEquals(3266,strlen(implode(',',$db->qv("SELECT b FROM test"))));
        $this->assertTrue($db->exec("DELETE FROM test") >= 1);
        $this->assertTrue($db->replace('test',array('a'=>1,'b'=>'b')));
        $this->assertTrue($db->replace('test',array('a'=>1,'c'=>'c')));
        $this->assertEquals(1,$db->q("SELECT count(*) FROM test WHERE a='1'"));
        $a1 = $db->q("SELECT * FROM test WHERE a=?",1);
        $a1['c']='c';
        $this->assertTrue($db->replace('test',$a1));
        $this->assertEquals(1,$db->q("SELECT a FROM test WHERE c='c'"));
    }

    function test_mysql(){
        $db = new FF_PDO('mysql:host=localhost;dbname=travis','travis','',array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ));
        $this->assertEquals(PDO::FETCH_ASSOC, $db->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $db->getAttribute(PDO::ATTR_ERRMODE));
        $db->exec("DROP TABLE IF EXISTS test");
        $db->exec("CREATE TABLE test (id int NOT NULL AUTO_INCREMENT, data BLOB NOT NULL, PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        $db->replace('test',array('data'=>1));
        $db->replace('test',array('data'=>2));
        $db->replace('test',array('data'=>3));
        $db->replace('test',array('data'=>4,'id'=>1));
        $this->assertEquals(array('4','2','3'),$db->qv('SELECT data FROM test ORDER BY id'));
        $before = $db->qt("select * from test");
        $db->beginTransaction();
        for($i=10;$i<20;$i++){
            $db->replace('test',array('data'=>2));
            $this->assertTrue($db->inTransaction());
        }
        $db->rollBack();
        if($db->inTransaction()) $db->commit();
        $this->assertFalse($db->inTransaction());
        $after = $db->qt("select * from test");
        $this->assertEquals($before,$after);
        $testvalues = array(
            '"',';',"'",'`','"\';`´','´',openssl_random_pseudo_bytes(1024)
        );
        $db->exec('TRUNCATE TABLE test');
        $i=0;
        foreach($testvalues as $testvalue){
            $i++;
            $db->replace('test',array('id'=>$i,'data'=>$testvalue));
            $this->assertEquals($i, $db->q("select id from test where data=?",$testvalue));
            $this->assertEquals($i, $db->q("select id from test where data=:data",array(':data'=>$testvalue)));
            $this->assertEquals($testvalue, $db->q('select `data` from test where id=?',$i));
            $this->assertEquals($testvalue, $db->q('select `data` from test where id=:id',array(':id'=>$i)));
        }

  }

} 