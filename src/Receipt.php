<?php

namespace FerencBalogh\Szamlazzhu;
use Log;

class Receipt
{
    private $elotag;
    private $fizmod;
    private $rendelesszam;
    private $brutto;
    private $email;
    private $targy;
    private $uzenet;

    public function __construct(
        $elotag,
        $fizmod,
        $rendelesszam,
        $brutto,
        $email,
        $targy,
        $uzenet
    ) {
        $this->elotag = $elotag;
        $this->fizmod = $fizmod;
        $this->rendelesszam = $rendelesszam;
        $this->brutto = $brutto;
        $this->email = $email;
        $this->targy = $targy;
        $this->uzenet = $uzenet;
    }

    public function createReceipt()
    {
        $szamla = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><xmlnyugtacreate xmlns="http://www.szamlazz.hu/xmlnyugtacreate" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.szamlazz.hu/xmlnyugtacreate xmlnyugtacreate.xsd"></xmlnyugtacreate>');

        $beallitasok = $szamla->addChild('beallitasok');
        $beallitasok->addChild('felhasznalo', env('SZAMLAZZ_USERNAME'));
        $beallitasok->addChild('jelszo', env('SZAMLAZZ_PASSWORD'));
        $beallitasok->addChild('pdfLetoltes', 'true');

        $netto = ($this->brutto / 1.27);
        $brutto = $this->brutto;
        $afa = $brutto - $netto;
        $afa = (float)number_format(($brutto - $netto), 2, '.', '');
        $netto = (float)number_format($netto, 2, '.', '');
        $brutto = (float)number_format($brutto, 2, '.', '');

        $fejlec = $szamla->addChild('fejlec');
        $fejlec->addChild('elotag', $this->elotag);
        $fejlec->addChild('fizmod', 'bankártya');
        $fejlec->addChild('penznem', 'HUF');
        $fejlec->addChild('hivasAzonosito', $this->rendelesszam);
        $fejlec->addChild('megjegyzes', 'Rendelés azonosító: '.$this->rendelesszam);

        $tetelek = $szamla->addChild('tetelek');
        $tetel = $tetelek->addChild('tetel');
        $tetel->addChild('megnevezes', 'Digitális szolgáltatás');
        $tetel->addChild('mennyiseg', 1);
        $tetel->addChild('mennyisegiEgyseg', 'db');
        $tetel->addChild('nettoEgysegar', $netto);
        $tetel->addChild('netto', $netto);
        $tetel->addChild('afakulcs', 27);
        $tetel->addChild('afa', $afa);
        $tetel->addChild('brutto', $brutto);
        $xml = $szamla->asXML();

        $date = date('Ym');

        if (!file_exists(storage_path('data/nyugta'))) {
            mkdir(storage_path('data/nyugta'), 0755, true);
        }

        if (!file_exists(storage_path('data/nyugta/' . $date))) {
            mkdir(storage_path('data/nyugta/' . $date), 0755, true);
        }

        $file = fopen(storage_path('data/nyugta/' . $date . '/' . $this->rendelesszam . '.xml'), 'w+');
        fwrite($file, $xml);
        fclose($file);

        $data = $this->sendXML(storage_path('data/nyugta/' . $date . '/' . $this->rendelesszam . '.xml'),
            $this->rendelesszam, $date);


        if($data['body']['xmlnyugtavalasz']['sikeres'] == 'true')
        {
            $nyugtaszam = $data['body']['xmlnyugtavalasz']['nyugta']['alap']['nyugtaszam'];
            $this->sendReceiptInEmail($nyugtaszam, $this->email, $this->targy, $this->uzenet);
            return $data;
        }

        Log::debug($data);
    }

    private function sendXML($xmlfile = 'nyugta.xml', $rendelesszam, $date)
    {
        if (!file_exists(storage_path('data/nyugta/' . $date . '/pdf'))) {
            mkdir(storage_path('data/nyugta/' . $date . '/pdf', 0755, true));
        }

        $ch = curl_init("https://www.szamlazz.hu/szamla/");
        $pdf = storage_path('data/nyugta/' . $date . '/pdf/' . $rendelesszam . '.xml');
        $cookie_file = storage_path('data/nyugta/nyugta_cookie.txt');
        if (!file_exists($cookie_file)) {
            $cookie = fopen($cookie_file, 'w');
            fwrite($cookie, '');
            fclose($cookie);
        }
        $fp = fopen($pdf, "w");
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            array('action-szamla_agent_nyugta_create' => new \CURLFile(realpath($xmlfile))));
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        if (file_exists($cookie_file) && filesize($cookie_file) > 0) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        }
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        if (mime_content_type($pdf) == 'text/plain') {
            $result = false;
        } else {
            $result = true;
        }

        $xmlNode = simplexml_load_file($pdf);
        $arrayData = $this->xmlToArray($xmlNode);
        Log::debug($arrayData);
        $response = array(
            'result' => $result,
            'body'   => $arrayData
        );
        return $response;
    }
    public function myEach(&$arr) {
        $key = key($arr);
        $result = ($key === null) ? false : [$key, current($arr), 'key' => $key, 'value' => current($arr)];
        next($arr);
        return $result;
    }

    public function xmlToArray($xml, $options = array()) {
        $defaults = array(
            'namespaceSeparator' => ':',//you may want this to be something other than a colon
            'attributePrefix' => '@',   //to distinguish between attributes and nodes with the same name
            'alwaysArray' => array(),   //array of xml tag names which should always become arrays
            'autoArray' => true,        //only create arrays for tags which appear more than once
            'textContent' => '$',       //key used for the text content of elements
            'autoText' => true,         //skip textContent key if node has no attributes or child nodes
            'keySearch' => false,       //optional search and replace on tag and attribute names
            'keyReplace' => false       //replace values for above search values (as passed to str_replace())
        );
        $options = array_merge($defaults, $options);
        $namespaces = $xml->getDocNamespaces();
        $namespaces[''] = null; //add base (empty) namespace

        //get attributes from all namespaces
        $attributesArray = array();
        foreach ($namespaces as $prefix => $namespace) {
            foreach ($xml->attributes($namespace) as $attributeName => $attribute) {
                //replace characters in attribute name
                if ($options['keySearch']) $attributeName =
                    str_replace($options['keySearch'], $options['keyReplace'], $attributeName);
                $attributeKey = $options['attributePrefix']
                    . ($prefix ? $prefix . $options['namespaceSeparator'] : '')
                    . $attributeName;
                $attributesArray[$attributeKey] = (string)$attribute;
            }
        }

        //get child nodes from all namespaces
        $tagsArray = array();
        foreach ($namespaces as $prefix => $namespace) {
            foreach ($xml->children($namespace) as $childXml) {
                //recurse into child nodes
                $childArray = $this->xmlToArray($childXml, $options);
                list($childTagName, $childProperties) = $this->myEach($childArray);
         
                //replace characters in tag name
                if ($options['keySearch']) $childTagName =
                    str_replace($options['keySearch'], $options['keyReplace'], $childTagName);
                //add namespace prefix, if any
                if ($prefix) $childTagName = $prefix . $options['namespaceSeparator'] . $childTagName;

                if (!isset($tagsArray[$childTagName])) {
                    //only entry with this key
                    //test if tags of this type should always be arrays, no matter the element count
                    $tagsArray[$childTagName] =
                        in_array($childTagName, $options['alwaysArray']) || !$options['autoArray']
                            ? array($childProperties) : $childProperties;
                } elseif (
                    is_array($tagsArray[$childTagName]) && array_keys($tagsArray[$childTagName])
                    === range(0, count($tagsArray[$childTagName]) - 1)
                ) {
                    //key already exists and is integer indexed array
                    $tagsArray[$childTagName][] = $childProperties;
                } else {
                    //key exists so convert to integer indexed array with previous value in position 0
                    $tagsArray[$childTagName] = array($tagsArray[$childTagName], $childProperties);
                }
            }
        }

        //get text content of node
        $textContentArray = array();
        $plainText = trim((string)$xml);
        if ($plainText !== '') $textContentArray[$options['textContent']] = $plainText;

        //stick it all together
        $propertiesArray = !$options['autoText'] || $attributesArray || $tagsArray || ($plainText === '')
            ? array_merge($attributesArray, $tagsArray, $textContentArray) : $plainText;

        //return node as array
        return array(
            $xml->getName() => $propertiesArray
        );
    }
    private function sendReceiptInEmail($nyugtaszam, $email, $targy, $uzenet)
    {
        $szamla = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><xmlnyugtasend xmlns="http://www.szamlazz.hu/xmlnyugtasend" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.szamlazz.hu/xmlnyugtasend xmlnyugtasend.xsd"></xmlnyugtasend>');
        $beallitasok = $szamla->addChild('beallitasok');
        $beallitasok->addChild('felhasznalo', env('SZAMLAZZ_USERNAME'));
        $beallitasok->addChild('jelszo', env('SZAMLAZZ_PASSWORD'));

        $fejlec = $szamla->addChild('fejlec');
        $fejlec->addChild('nyugtaszam', $nyugtaszam);

        $emailKuldes = $szamla->addChild('emailKuldes');
        $emailKuldes->addChild('email', $email);
        $emailKuldes->addChild('emailReplyto',config('szamlazz.email'));
        $emailKuldes->addChild('emailTargy', $targy);
        $emailKuldes->addChild('emailSzoveg', $uzenet);

        $xml = $szamla->asXML();

        $date = date('Ym');

        if (!file_exists(storage_path('data/nyugta'))) {
            mkdir(storage_path('data/nyugta'), 0755, true);
        }

        if (!file_exists(storage_path('data/nyugta/' . $date))) {
            mkdir(storage_path('data/nyugta/' . $date), 0755, true);
        }

        $file = fopen(storage_path('data/nyugta/' . $date . '/' . $this->rendelesszam . '_email.xml'), 'w+');
        fwrite($file, $xml);
        fclose($file);
        return $this->sendEmail(storage_path('data/nyugta/' . $date . '/' . $this->rendelesszam . '_email.xml'),
            $this->rendelesszam, $date);
    }

    private function sendEmail($xmlfile = 'nyugta.xml', $rendelesszam, $date)
    {
        if (!file_exists(storage_path('data/nyugta/' . $date . '/pdf'))) {
            mkdir(storage_path('data/nyugta/' . $date . '/pdf', 0755, true));
        }

        $ch = curl_init("https://www.szamlazz.hu/szamla/");
        $pdf = storage_path('data/nyugta/' . $date . '/pdf/' . $rendelesszam . '_email.pdf');
        $cookie_file = storage_path('data/nyugta/nyugta_email_cookie.txt');
        if (!file_exists($cookie_file)) {
            $cookie = fopen($cookie_file, 'w');
            fwrite($cookie, '');
            fclose($cookie);
        }
        $fp = fopen($pdf, "w");
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            array('action-szamla_agent_nyugta_send' => new \CURLFile(realpath($xmlfile))));
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        if (file_exists($cookie_file) && filesize($cookie_file) > 0) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        }
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        if (mime_content_type($pdf) == 'text/plain') {
            $result = false;
        } else {
            $result = true;
        }
        $response = array(
            'result' => $result,
            'body'   => $pdf
        );

        return $response;
    }
}