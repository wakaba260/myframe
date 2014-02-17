<?php
/*******************************************************************************
 * RouterConfigulation
 ******************************************************************************/
$routes = array (
   '/products/browse/:id'   => array('controller' => 'products', 'action' => 'browse'),
   '/:controller/:action/'  => array()
,  '/:controller/'          => array('action' => 'index')
,  '/'                      => array('controller' => 'products', 'action' => 'index')
);
 