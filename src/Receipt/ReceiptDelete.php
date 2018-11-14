<?php

namespace FerencBalogh\Szamlazzhu\Receipt;
use Illuminate\Support\Facades\Log;
use FerencBalogh\Szamlazzhu\Traits\XmlHelper;

class ReceiptDelete
{
    use XmlHelper;

    private $receipt;

    public function __construct($receipt) {
        $this->receipt = $receipt;
    }

    public function deleteReceipt()
    {
        $szamla = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><xmlnyugtast xmlns="http://www.szamlazz.hu/xmlnyugtast" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.szamlazz.hu/xmlnyugtast xmlnyugtast.xsd"></xmlnyugtast>');

        $beallitasok = $szamla->addChild('beallitasok');
        $beallitasok->addChild('felhasznalo', env('SZAMLAZZ_USERNAME'));
        $beallitasok->addChild('jelszo', env('SZAMLAZZ_PASSWORD'));
        $beallitasok->addChild('pdfLetoltes', 'true');

        $fejlec = $szamla->addChild('fejlec');
        $fejlec->addChild('nyugtaszam', $nyugtaszam);

        $date = date('Ym');

        if (!file_exists(storage_path('data/nyugta'))) {
            mkdir(storage_path('data/nyugta'), 0755, true);
        }

        if (!file_exists(storage_path('data/nyugta/' . $date))) {
            mkdir(storage_path('data/nyugta/' . $date), 0755, true);
        }

        $file = fopen(storage_path('data/nyugta/' . $date . '/' . $this->receipt . '_storno.xml'), 'w+');
        fwrite($file, $xml);
        fclose($file);

        return $data = $this->sendXML(storage_path('data/nyugta/' . $date . '/' . $this->receipt . '_storno.xml'),
            $this->receipt, $date);

    }
}