<?php namespace Zulfajuniadi\WatchLess;

use Illuminate\Support\ServiceProvider;
use \Event;
use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;
use \lessc;

class WatchLessServiceProvider extends ServiceProvider {
	protected $defer = false;

	public function register()
	{
		Event::listen('watcher:check', function($options){
      $timestamp = strtotime($options->timestamp);
      $reload = false;
      if(isset($options->less_process) && is_array($options->less_process) && $timestamp) {
        $lessCompiler = new \Less_Parser();
        if(isset($options->less_importdirs) && is_array($options->less_importdirs)) {
          $importDirs = array();
          foreach ($options->less_importdirs as $importdir) {
            $importdir = realpath(base_path($importdir));
            if(is_dir($importdir)) {
              $importDirs[] = $importdir;
              foreach (new RecursiveIteratorIterator (new RecursiveDirectoryIterator ($importdir)) as $x) {
                if(!$x->isDir() && $x->getCTime() > $timestamp)
                  $reload = true;
              }
            }
          }
          $lessCompiler->SetImportDirs($importDirs);
        }
        foreach ($options->less_process as $source => $output) {
          $source = realpath(base_path($source));
          $output = realpath(base_path($output));
          if(!$output) {
            $output = base_path($output);
          }
          if(is_file($source)) {
            if($reload) {
              touch($source);
            }
            if(filemtime($source) > $timestamp || $reload) {
              try {
                $lessCompiler->parseFile( $source , '/' );
                $css = $lessCompiler->getCss();
                if($css) {
                  file_put_contents($output, $css);
                }
              } catch (Exception $e) {}
            }
          }
        }
      }
    });
	}
}