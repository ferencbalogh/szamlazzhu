<?php

namespace FerencBalogh\Szamlazzhu;

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

        return $this->sendXML(storage_path('data/nyugta/' . $date . '/' . $this->rendelesszam . '.xml'),
            $this->rendelesszam, $date);
        //$this->sendReceiptInEmail($thnyugtaszam, $this->email, $this->targy, $this->uzenet);
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
        $content = curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        dd($content);
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

    private function sendReceiptInEmail($nyugtaszam, $email, $targy, $uzenet)
    {
        $szamla = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><xmlnyugtasend xmlns="http://www.szamlazz.hu/xmlnyugtasend" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.szamlazz.hu/xmlnyugtasend xmlnyugtasend.xsd"></xmlnyugtasend>');
        $beallitasok = $szamla->addChild('beallitasok');
        $beallitasok->addChild('felhasznalo', env('SZAMLAZZ_USERNAME'));
        $beallitasok->addChild('jelszo', env('SZAMLAZZ_PASSWORD'));
        $beallitasok->addChild('pdfLetoltes', 'true');

        $fejlec = $szamla->addChild('fejlec');
        $fejlec->addChild('nyugtaszam', $nyugtaszam);

        $email = $szamla->addChild('emailKuldes');
        $email->addChild('email', $email);
        $email->addChild('emailReplyto',config('szamlazz.email'));
        $email->addChild('emailTargy', $targy);
        $email->addChild('emailSzoveg', $uzenet);

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