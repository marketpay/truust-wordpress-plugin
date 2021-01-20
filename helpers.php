<?php

defined('ABSPATH') || exit;

use Truust\Truust;

if (!function_exists('truust')) {
	function truust($abstract = null, array $parameters = [])
	{
		if (is_null($abstract)) {
			return Truust::getInstance();
		}

		return (Truust::getInstance())->make($abstract, $parameters);
	}
}

if (!function_exists('config')) {
	function config($key = null, $default = null)
	{
		if (is_null($key)) {
			return truust('config');
		}

		if (is_array($key)) {
			return truust('config')->set($key);
		}

		return truust('config')->get($key, $default);
	}
}

if (!function_exists('add_flash_notice')) {
	function add_flash_notice($notice = '', $type = 'warning', $dismissible = true)
	{
		$notices = get_option('truust_flash_notices', []);
		$dismissible_text = ($dismissible) ? 'is-dismissible' : '';

		array_push($notices, [
			'notice' => $notice,
			'type' => $type,
			'dismissible' => $dismissible_text
		]);

		update_option('truust_flash_notices', $notices);
	}
}

if (!function_exists('display_flash_notices')) {
	function display_flash_notices()
	{
		$notices = get_option('truust_flash_notices', []);

		foreach ($notices as $notice) {
			printf(
				'<div class="notice notice-%1$s %2$s"><p>%3$s</p></div>',
				$notice['type'],
				$notice['dismissible'],
				$notice['notice']
			);
		}

		if (!empty($notices)) {
			delete_option('truust_flash_notices', []);
		}
	}
}

if (!function_exists('remove_utf8_bom')) {
	function remove_utf8_bom($text)
	{
		$bom = pack('H*', 'EFBBBF');

		return preg_replace("/^$bom/", '', $text);
	}
}

if (!function_exists('determine_env')) {
	function determine_env($key)
	{
		return preg_match('/(sk_stage_)/', $key) ? 'sandbox' : 'production';
	}
}

if (!function_exists('api_base_url')) {
	function api_base_url($key)
	{
		return preg_match('/(sk_stage_)/', $key) ? config('api.sandbox') : config('api.production');
	}
}

if (!function_exists('dd')) {
	function dd($data)
	{
		array_map(function ($x) {
			dump($x);
		}, func_get_args());
		die;
	}
}

if (!function_exists('countries')) {
	function countries()
	{
		return [
			"AF" => "Afghanistan",
			"AL" => "Albania",
			"DZ" => "Algeria",
			"AS" => "American Samoa",
			"AD" => "Andorra",
			"AO" => "Angola",
			"AI" => "Anguilla",
			"AQ" => "Antarctica",
			"AG" => "Antigua and Barbuda",
			"AR" => "Argentina",
			"AM" => "Armenia",
			"AW" => "Aruba",
			"AU" => "Australia",
			"AT" => "Austria",
			"AZ" => "Azerbaijan",
			"BS" => "Bahamas",
			"BH" => "Bahrain",
			"BD" => "Bangladesh",
			"BB" => "Barbados",
			"BY" => "Belarus",
			"BE" => "Belgium",
			"BZ" => "Belize",
			"BJ" => "Benin",
			"BM" => "Bermuda",
			"BT" => "Bhutan",
			"BO" => "Bolivia",
			"BA" => "Bosnia and Herzegovina",
			"BW" => "Botswana",
			"BV" => "Bouvet Island",
			"BR" => "Brazil",
			"IO" => "British Indian Ocean Territory",
			"BN" => "Brunei Darussalam",
			"BG" => "Bulgaria",
			"BF" => "Burkina Faso",
			"BI" => "Burundi",
			"KH" => "Cambodia",
			"CM" => "Cameroon",
			"CA" => "Canada",
			"CV" => "Cape Verde",
			"KY" => "Cayman Islands",
			"CF" => "Central African Republic",
			"TD" => "Chad",
			"CL" => "Chile",
			"CN" => "China",
			"CX" => "Christmas Island",
			"CC" => "Cocos (Keeling) Islands",
			"CO" => "Colombia",
			"KM" => "Comoros",
			"CG" => "Congo",
			"CD" => "Congo, the Democratic Republic of the",
			"CK" => "Cook Islands",
			"CR" => "Costa Rica",
			"CI" => "Cote D'Ivoire",
			"HR" => "Croatia",
			"CU" => "Cuba",
			"CY" => "Cyprus",
			"CZ" => "Czech Republic",
			"DK" => "Denmark",
			"DJ" => "Djibouti",
			"DM" => "Dominica",
			"DO" => "Dominican Republic",
			"EC" => "Ecuador",
			"EG" => "Egypt",
			"SV" => "El Salvador",
			"GQ" => "Equatorial Guinea",
			"ER" => "Eritrea",
			"EE" => "Estonia",
			"ET" => "Ethiopia",
			"FK" => "Falkland Islands (Malvinas)",
			"FO" => "Faroe Islands",
			"FJ" => "Fiji",
			"FI" => "Finland",
			"FR" => "France",
			"GF" => "French Guiana",
			"PF" => "French Polynesia",
			"TF" => "French Southern Territories",
			"GA" => "Gabon",
			"GM" => "Gambia",
			"GE" => "Georgia",
			"DE" => "Germany",
			"GH" => "Ghana",
			"GI" => "Gibraltar",
			"GR" => "Greece",
			"GL" => "Greenland",
			"GD" => "Grenada",
			"GP" => "Guadeloupe",
			"GU" => "Guam",
			"GT" => "Guatemala",
			"GN" => "Guinea",
			"GW" => "Guinea-Bissau",
			"GY" => "Guyana",
			"HT" => "Haiti",
			"HM" => "Heard Island and Mcdonald Islands",
			"VA" => "Holy See (Vatican City State)",
			"HN" => "Honduras",
			"HK" => "Hong Kong",
			"HU" => "Hungary",
			"IS" => "Iceland",
			"IN" => "India",
			"ID" => "Indonesia",
			"IR" => "Iran, Islamic Republic of",
			"IQ" => "Iraq",
			"IE" => "Ireland",
			"IL" => "Israel",
			"IT" => "Italy",
			"JM" => "Jamaica",
			"JP" => "Japan",
			"JO" => "Jordan",
			"KZ" => "Kazakhstan",
			"KE" => "Kenya",
			"KI" => "Kiribati",
			"KP" => "Korea, Democratic People's Republic of",
			"KR" => "Korea, Republic of",
			"KW" => "Kuwait",
			"KG" => "Kyrgyzstan",
			"LA" => "Lao People's Democratic Republic",
			"LV" => "Latvia",
			"LB" => "Lebanon",
			"LS" => "Lesotho",
			"LR" => "Liberia",
			"LY" => "Libyan Arab Jamahiriya",
			"LI" => "Liechtenstein",
			"LT" => "Lithuania",
			"LU" => "Luxembourg",
			"MO" => "Macao",
			"MK" => "Macedonia, the Former Yugoslav Republic of",
			"MG" => "Madagascar",
			"MW" => "Malawi",
			"MY" => "Malaysia",
			"MV" => "Maldives",
			"ML" => "Mali",
			"MT" => "Malta",
			"MH" => "Marshall Islands",
			"MQ" => "Martinique",
			"MR" => "Mauritania",
			"MU" => "Mauritius",
			"YT" => "Mayotte",
			"MX" => "Mexico",
			"FM" => "Micronesia, Federated States of",
			"MD" => "Moldova, Republic of",
			"MC" => "Monaco",
			"MN" => "Mongolia",
			"MS" => "Montserrat",
			"MA" => "Morocco",
			"MZ" => "Mozambique",
			"MM" => "Myanmar",
			"NA" => "Namibia",
			"NR" => "Nauru",
			"NP" => "Nepal",
			"NL" => "Netherlands",
			"AN" => "Netherlands Antilles",
			"NC" => "New Caledonia",
			"NZ" => "New Zealand",
			"NI" => "Nicaragua",
			"NE" => "Niger",
			"NG" => "Nigeria",
			"NU" => "Niue",
			"NF" => "Norfolk Island",
			"MP" => "Northern Mariana Islands",
			"NO" => "Norway",
			"OM" => "Oman",
			"PK" => "Pakistan",
			"PW" => "Palau",
			"PS" => "Palestinian Territory, Occupied",
			"PA" => "Panama",
			"PG" => "Papua New Guinea",
			"PY" => "Paraguay",
			"PE" => "Peru",
			"PH" => "Philippines",
			"PN" => "Pitcairn",
			"PL" => "Poland",
			"PT" => "Portugal",
			"PR" => "Puerto Rico",
			"QA" => "Qatar",
			"RE" => "Reunion",
			"RO" => "Romania",
			"RU" => "Russian Federation",
			"RW" => "Rwanda",
			"SH" => "Saint Helena",
			"KN" => "Saint Kitts and Nevis",
			"LC" => "Saint Lucia",
			"PM" => "Saint Pierre and Miquelon",
			"VC" => "Saint Vincent and the Grenadines",
			"WS" => "Samoa",
			"SM" => "San Marino",
			"ST" => "Sao Tome and Principe",
			"SA" => "Saudi Arabia",
			"SN" => "Senegal",
			"CS" => "Serbia and Montenegro",
			"SC" => "Seychelles",
			"SL" => "Sierra Leone",
			"SG" => "Singapore",
			"SK" => "Slovakia",
			"SI" => "Slovenia",
			"SB" => "Solomon Islands",
			"SO" => "Somalia",
			"ZA" => "South Africa",
			"GS" => "South Georgia and the South Sandwich Islands",
			"ES" => "Spain",
			"LK" => "Sri Lanka",
			"SD" => "Sudan",
			"SR" => "Suriname",
			"SJ" => "Svalbard and Jan Mayen",
			"SZ" => "Swaziland",
			"SE" => "Sweden",
			"CH" => "Switzerland",
			"SY" => "Syrian Arab Republic",
			"TW" => "Taiwan, Province of China",
			"TJ" => "Tajikistan",
			"TZ" => "Tanzania, United Republic of",
			"TH" => "Thailand",
			"TL" => "Timor-Leste",
			"TG" => "Togo",
			"TK" => "Tokelau",
			"TO" => "Tonga",
			"TT" => "Trinidad and Tobago",
			"TN" => "Tunisia",
			"TR" => "Turkey",
			"TM" => "Turkmenistan",
			"TC" => "Turks and Caicos Islands",
			"TV" => "Tuvalu",
			"UG" => "Uganda",
			"UA" => "Ukraine",
			"AE" => "United Arab Emirates",
			"GB" => "United Kingdom",
			"US" => "United States",
			"UM" => "United States Minor Outlying Islands",
			"UY" => "Uruguay",
			"UZ" => "Uzbekistan",
			"VU" => "Vanuatu",
			"VE" => "Venezuela",
			"VN" => "Viet Nam",
			"VG" => "Virgin Islands, British",
			"VI" => "Virgin Islands, U.s.",
			"WF" => "Wallis and Futuna",
			"EH" => "Western Sahara",
			"YE" => "Yemen",
			"ZM" => "Zambia",
			"ZW" => "Zimbabwe",
		];
	}
}

if (!function_exists('prefix')) {
	function prefix($code)
	{
		$prefix_list = [
			'CA' => '1',
			'US' => '1',

			'BS' => '1 242',
			'BB' => '1 246',
			'AI' => '1 264',
			'AG' => '1 268',
			'VG' => '1 284',

			'VI' => '1 340',
			'KY' => '1 345',

			'BM' => '1 441',
			'GD' => '1 473',

			'TC' => '1 649',
			'MS' => '1 664',
			'MP' => '1 670',
			'GU' => '1 671',
			'AS' => '1 684',

			'SX' => '1 721',
			'LC' => '1 758',
			'DM' => '1 767',
			'VC' => '1 784',
			'PR' => '1 787',

			'DO' => '1 809',
			'TT' => '1 868',
			'KN' => '1 869',
			'JM' => '1 876',

			'EG' => '20',
			'SS' => '211',
			'MA' => '212',
			'EH' => '212',
			'DZ' => '213',
			'TN' => '216',
			'LY' => '218',

			'GM' => '220',
			'SN' => '221',
			'MR' => '222',
			'ML' => '223',
			'GN' => '224',
			'CI' => '225',
			'BF' => '226',
			'NE' => '227',
			'TG' => '228',
			'BJ' => '229',

			'MU' => '230',
			'LR' => '231',
			'SL' => '232',
			'GH' => '233',
			'NG' => '234',
			'TD' => '235',
			'CF' => '236',
			'CM' => '237',
			'CV' => '238',
			'ST' => '239',

			'GQ' => '240',
			'GA' => '241',
			'CG' => '242',
			'CD' => '243',
			'AO' => '244',
			'GW' => '245',
			'IO' => '246',
			'AC' => '247',
			'SC' => '248',
			'SD' => '249',

			'RW' => '250',
			'ET' => '251',
			'SO' => '252',
			'DJ' => '253',
			'KE' => '254',
			'TZ' => '255',
			'UG' => '256',
			'BI' => '257',
			'MZ' => '258',

			'ZM' => '260',
			'MG' => '261',
			'RE' => '262',
			'YT' => '262',
			'TF' => '262',
			'ZW' => '263',
			'NA' => '264',
			'MW' => '265',
			'LS' => '266',
			'BW' => '267',
			'SZ' => '268',
			'KM' => '269',

			'ZA' => '27',
			'SH' => '290',
			'TA' => '290',
			'ER' => '291',
			'AW' => '297',
			'FO' => '298',
			'GL' => '299',

			'GR' => '30',
			'NL' => '31',
			'BE' => '32',
			'FR' => '33',
			'ES' => '34',
			'GI' => '350',
			'PT' => '351',
			'LU' => '352',
			'IE' => '353',
			'IS' => '354',
			'AL' => '355',
			'MT' => '356',
			'CY' => '357',
			'FI' => '358',
			'AX' => '358',
			'BG' => '359',

			'HU' => '36',
			'LT' => '370',
			'LV' => '371',
			'EE' => '372',
			'MD' => '373',
			'AM' => '374',
			'QN' => '374',
			'BY' => '375',
			'AD' => '376',
			'MC' => '377',
			'SM' => '378',
			'VA' => '379',

			'UA' => '380',
			'RS' => '381',
			'ME' => '382',
			'XK' => '383',
			'HR' => '385',
			'SI' => '386',
			'BA' => '387',
			'EU' => '388',
			'MK' => '389',

			'IT' => '39',
			'RO' => '40',
			'CH' => '41',
			'CZ' => '420',
			'SK' => '421',
			'LI' => '423',

			'AT' => '43',
			'GB' => '44',
			'UK' => '44',
			'GG' => '44',
			'IM' => '44',
			'JE' => '44',
			'DK' => '45',
			'SE' => '46',
			'NO' => '47',
			'SJ' => '47',
			'BV' => '47',
			'PL' => '48',
			'DE' => '49',

			'FK' => '500',
			'GS' => '500',

			'BZ' => '501',
			'GT' => '502',
			'SV' => '503',
			'HN' => '504',
			'NI' => '505',
			'CR' => '506',
			'PA' => '507',
			'PM' => '508',
			'HT' => '509',

			'PE' => '51',
			'MX' => '52',
			'CU' => '53',
			'AR' => '54',
			'BR' => '55',
			'CL' => '56',
			'CO' => '57',
			'VE' => '58',
			'GP' => '590',
			'BL' => '590',
			'MF' => '590',
			'BO' => '591',
			'GY' => '592',
			'EC' => '593',
			'GF' => '594',
			'PY' => '595',
			'MQ' => '596',
			'SR' => '597',
			'UY' => '598',
			'BQ' => '599',
			'CW' => '599',

			'MY' => '60',
			'AU' => '61',
			'CX' => '61',
			'CC' => '61',
			'ID' => '62',
			'PH' => '63',
			'NZ' => '64',
			'PN' => '64',
			'SG' => '65',
			'TH' => '66',
			'TL' => '670',
			'NF' => '672',
			'AQ' => '672',
			'HM' => '672',
			'BN' => '673',
			'NR' => '674',
			'PG' => '675',
			'TO' => '676',
			'SB' => '677',
			'VU' => '678',
			'FJ' => '679',

			'PW' => '680',
			'WF' => '681',
			'CK' => '682',
			'NU' => '683',
			'WS' => '685',
			'KI' => '686',
			'NC' => '687',
			'TV' => '688',
			'PF' => '689',

			'TK' => '690',
			'FM' => '691',
			'MH' => '692',

			'RU' => '7',
			'KZ' => '7',

			'XT' => '800',
			'XS' => '808',

			'JP' => '81',
			'KR' => '82',
			'VN' => '84',
			'KP' => '850',
			'HK' => '852',
			'MO' => '853',
			'KH' => '855',
			'LA' => '856',

			'CN' => '86',
			'XN' => '870',
			'XP' => '878',

			'BD' => '880',
			'XG' => '881',
			'XV' => '882',
			'TW' => '886',
			'XD' => '888',

			'TR' => '90',
			'CT' => '90',
			'IN' => '91',
			'PK' => '92',
			'AF' => '93',
			'LK' => '94',
			'MM' => '95',
			'MV' => '960',
			'LB' => '961',
			'JO' => '962',
			'SY' => '963',
			'IQ' => '964',
			'KW' => '965',
			'SA' => '966',
			'YE' => '967',
			'OM' => '968',

			'PS' => '970',
			'AE' => '971',
			'IL' => '972',
			'BH' => '973',
			'QA' => '974',
			'BT' => '975',
			'MN' => '976',
			'NP' => '977',
			'XR' => '979',

			'IR' => '98',
			'XC' => '991',
			'TJ' => '992',
			'TM' => '993',
			'AZ' => '994',
			'GE' => '995',
			'KG' => '996',
			'UZ' => '998'
		];

		return (!$prefix_list[$code]) ? $code : $prefix_list[$code];
	}

	if (!function_exists('phone_prefix')) {
	    function phone_prefix($code)
        {
            if (empty($code)) return null;
            return '+'.prefix(strtoupper($code));
        }
    }
}
