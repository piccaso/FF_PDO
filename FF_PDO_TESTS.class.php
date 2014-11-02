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
        $iterations=1000;
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

        $this->assertEquals(32966,strlen(implode(',',$db->qv("SELECT b FROM test"))));
        $this->assertTrue($db->exec("DELETE FROM test") >= 1);
        $this->assertTrue($db->replace('test',array('a'=>1,'b'=>'b')));
        $this->assertTrue($db->replace('test',array('a'=>1,'c'=>'c')));
        $this->assertEquals(1,$db->q("SELECT count(*) FROM test WHERE a='1'"));
        $a1 = $db->q("SELECT * FROM test WHERE a=?",1);
        $a1['c']='c';
        $this->assertTrue($db->replace('test',$a1));
        $this->assertEquals(1,$db->q("SELECT a FROM test WHERE c='c'"));
    }

} 