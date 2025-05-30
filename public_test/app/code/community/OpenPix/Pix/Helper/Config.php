<?php

class OpenPix_Pix_Helper_Config extends Mage_Core_Helper_Abstract
{
  public function getOpenPixApiUrl()
  {
      // production
      return "https://api.openpix.com.br";
  }

  public function getOpenPixPlatformUrl()
  {
      // production
      return "https://app.openpix.com.br";
  }

  public function getOpenPixPluginUrlScript()
  {
      return "https://plugin.openpix.com.br/v1/openpix.js";
  }

  public function getOpenPixKey() {
    return 'LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUlHZk1BMEdDU3FHU0liM0RRRUJBUVVBQTRHTkFEQ0JpUUtCZ1FDLytOdElranpldnZxRCtJM01NdjNiTFhEdApwdnhCalk0QnNSclNkY2EzcnRBd01jUllZdnhTbmQ3amFnVkxwY3RNaU94UU84aWVVQ0tMU1dIcHNNQWpPL3paCldNS2Jxb0c4TU5waS91M2ZwNnp6MG1jSENPU3FZc1BVVUcxOWJ1VzhiaXM1WloySVpnQk9iV1NwVHZKMGNuajYKSEtCQUE4MkpsbitsR3dTMU13SURBUUFCCi0tLS0tRU5EIFBVQkxJQyBLRVktLS0tLQo=';
  }
}