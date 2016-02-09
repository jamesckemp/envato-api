<?php

include('class-envato-api.php');

$envato = new Envato_API('username', 'personal_token');
$item = $envato->item_details( '13523915' );
$prices = $envato->item_prices( '13523915' );

$envato->pretty_print( $item ); // prints all the juicy item details, including plugin/theme meta
$envato->pretty_print( $prices ); // prints regular and extended prices
