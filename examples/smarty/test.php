<?php
error_reporting(E_ALL | E_STRICT);
set_include_path(dirname(__FILE__) . PATH_SEPARATOR . dirname(__FILE__) . '/../../lib/' . PATH_SEPARATOR . get_include_path());

// You need to have simpletest in your include_path
if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}
require_once '../../lib/konstrukt/virtualbrowser.inc.php';
require_once 'index.php';

class TestOfExampleSmarty extends WebTestCase {
  function setUp() {
    $_SESSION = array();
    $this->directory = dirname(__FILE__) . '/templates_c/';
    if (!is_dir($this->directory)) {
        mkdir($this->directory);
    }
  }
  function tearDown() {
    unset($_SESSION);
    $this->rmdir($this->directory);
  }
  function rmdir($dir) {
    if (is_dir($dir)) {
      $objects = scandir($dir);
      foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
          if (filetype($dir."/".$object) == "dir") {
            $this->rmdir($dir."/".$object);
          } else {
            unlink($dir."/".$object);
          }
        }
      }
      reset($objects);
      rmdir($dir);
    }
  }
  function createInvoker() {
    return new SimpleInvoker($this);
  }
  function createBrowser() {
    return new k_VirtualSimpleBrowser('HelloComponent');
  }
  function test_root() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->assertText("Fred Irving Johnathan Bradley Peppergill");
  }
}
