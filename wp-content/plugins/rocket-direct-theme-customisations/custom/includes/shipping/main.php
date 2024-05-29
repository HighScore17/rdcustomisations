<?php
require_once __DIR__ . '/admin.php';
require_once __DIR__ . '/providers/shipping-provider.php';
require_once __DIR__ . '/providers/shipping-ups.php';
require_once __DIR__ . '/providers/shipping-estes-express.php';
require_once __DIR__ . '/shipping-calculator.php';
require_once __DIR__ . '/graphql-shipping-calculator.php';
require_once __DIR__ . '/wc-shipping-calculator.php';



require_once __DIR__ . '/carriers/shipping-carrier.php';
require_once __DIR__ . '/carriers/shipstation-carrier.php';
require_once __DIR__ . '/carriers/estes-express-carrier.php';
require_once __DIR__ . '/carriers/flt-carrier.php';

require_once __DIR__ . '/shipping-packages.php';
require_once __DIR__ . '/shipping-calculator-addresses.php';
require_once __DIR__ . '/shipping-calculator-v2.php';
require_once __DIR__ . '/shipping-calculator-v2-graphql.php';
require_once __DIR__ . '/shipping-insurance-graphql.php';
require_once __DIR__ . '/wc-calculator-graphql.php';
require_once __DIR__ . '/pallets-calculator.php';
require_once __DIR__ . '/wc-shipping-rates-graphql.php';