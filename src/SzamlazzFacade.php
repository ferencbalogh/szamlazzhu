<?php 
namespace FerencBalogh\Szamlazz;

use Illuminate\Support\Facades\Facade;

class SzamlazzFacade extends Facade
{
    public static function getFacadeAccessor()
    {
        return Szamlazz::class;
    }
}
