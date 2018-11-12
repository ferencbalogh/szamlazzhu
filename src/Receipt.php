<?php

namespace FerencBalogh\Szamlazzhu;

class Receipt
{
    private $elotag;
    private $fizmod;
    private $rendelesszam;
    private $netto;
    private $download;

    public function __construct(
        $elotag,
        $fizmod,
        $rendelesszam,
        $netto,
        $download = true
    ) {
        $this->elotag = $elotag;
        $this->fizmod = $fizmod;
        $this->rendelesszam = $rendelesszam;
        $this->netto = $netto;
        $this->download = $download;
    }

    public function createReceipt()
    {
        $szamla = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><xmlnyugtacreate xmlns="http://www.szamlazz.hu/xmlnyugtacreate" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.szamlazz.hu/xmlnyugtacreate xmlnyugtacreate.xsd"></xmlszamla>');

        $beallitasok = $szamla->addChild('beallitasok');
        $beallitasok->addChild('felhasznalo', env('SZAMLAZZ_USERNAME'));
        $beallitasok->addChild('jelszo', env('SZAMLAZZ_PASSWORD'));
        if ($this->download) {
            $beallitasok->addChild('szamlaLetoltes', 'true');
        }

        $fejlec = $szamla->addChild('fejlec');
        $fejlec->addChild('elotag', $this->elotag);
        $fejlec->addChild('fizmod', 'bankártya');
        $fejlec->addChild('penznem', 'HUF');

        $tetelek = $szamla->addChild('tetelek');
        $tetel = $tetelek->addChild('tetel');
        $tetel->addChild('megnevezes', 'Digitális szolgáltatás');
        $tetel->addChild('mennyiseg', 1);
        $tetel->addChild('mennyisegiEgyseg', 'db');
        $tetel->addChild('nettoEgysegar', $this->netto);
        $tetel->addChild('afakulcs', '27');
        $tetel->addChild('nettoErtek', $this->netto);
        $tetel->addChild('afaErtek', (($this->netto * 1.27) - $this->netto));
        $tetel->addChild('bruttoErtek', ($this->netto * 1.27));
        $tetel->addChild('megjegyzes', '');
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
    }

    private function sendXML($xmlfile = 'nyugta.xml', $rendelesszam, $date)
    {
        if (!file_exists(storage_path('data/nyugta/' . $date . '/pdf'))) {
            mkdir(storage_path('data/nyugta/' . $date . '/pdf', 0755, true));
        }

        $ch = curl_init("https://www.szamlazz.hu/szamla/");
        $pdf = storage_path('data/nyugta/' . $date . '/pdf/' . $rendelesszam . '.pdf');
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
        curl_setopt($ch, CURLOPT_POSTFIELDS, array('action-xmlagentxmlfile' => '@' . $xmlfile));
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