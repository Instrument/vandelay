<?php
namespace Craft;

class VandelayVariable
{
  public function __construct()
  {
    $base = craft()->basePath;
    $contents = file_get_contents($base . '/../plugins/vandelay/resources/src/config/webpack-stats.json');

    $this->stats = json_decode($contents);
    // FOR REACT CRAFT
    // $path = craft()->request->getPath();
    // if (!$path) {
    //   $path = '/';
    // }
    // $url = craft()->config->get('nodeServer') . "/?path=" . urlencode($path);
    // $client = new \GuzzleHttp\Client();

    // $res = $client->get($url);
    // $this->data = json_decode($res->getBody());
    
  }

  public function markup()
  {
    return $this->data->markup;
  }

  public function payload()
  {
    return $this->data->payload;
  }

  public function stylesheets()
  {
    return $this->stats->css;
  }

  public function scripts()
  {
    return $this->stats->js;
  }
}

